<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class NiubizController extends Controller{
    // private $paymentEnvironment = 'niubiz_dev';
    private $paymentEnvironment = 'niubiz_prd';
    
    public function createSessionToken(Request $request){
        // El monto debe venir del frontend o ser calculado aquí
        $amount = $request->input('amount');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $ipAddress = $request->input('ipAddress');
        
        // 1. Obtener Access Token de Niubiz
        $securityUrl = config('services.'.$this->paymentEnvironment.'.api_url_security').'/api.security/v1/security';
        $credentials = base64_encode(config('services.'.$this->paymentEnvironment.'.api_user').':'.config('services.'.$this->paymentEnvironment.'.api_password'));

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$credentials,
        ])->get($securityUrl);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to get Niubiz access token'], 500);
        }

        $accessToken = $response->body();

        // 2. Crear el Token de Sesión
        $sessionUrl = config('services.'.$this->paymentEnvironment.'.api_url_transaction').'/api.ecommerce/v2/ecommerce/token/session/'.config('services.'.$this->paymentEnvironment.'.merchant_id');
        
        $sessionResponse = Http::withHeaders([
            'Authorization' => $accessToken,
            'Content-Type'  => 'application/json',
        ])->post($sessionUrl, [
            'channel'   => 'web',
            'amount'    => $amount,
            'antifraud' => [
                'clientIp' => $ipAddress,
                'merchantDefineData' => [
                    'MDD4'  => $email,
                    'MDD32' => $email,
                    'MDD75' => 'Registrado',
                    'MDD77' => 30
                ],
                'dataMap' => [
                    'cardholderCity' => 'Lima',
                    'cardholderCountry' => 'PE',
                    'cardholderAddress' => empty($address) ? 'Av Antonio Jose de Sucre 556' : $address,
                    'cardholderPostalCode' => '27001',
                    'cardholderState' => 'LIM',
                    'cardholderPhoneNumber' => $phone
                ]
            ]
        ]);

        if ($sessionResponse->failed()) {
            return response()->json(['error' => 'Failed to create session token'], 500);
        }

        return response()->json([
            'sessionToken' => $sessionResponse->json()['sessionKey']
        ]);
    }

    // /**
    //  * Procesa el pago final usando el token de transacción del frontend.
    //  */

    public function processPayment($reserva_id, $pay_amount, $url_address, Request $request){
        $transactionToken = $request->input('transactionToken');
        $amount = $pay_amount;
        $purchaseNumber = $reserva_id;
        $urlAddress = $url_address;

        // 1. Obtener Access Token de nuevo (es de corta duración)
        $securityUrl = config('services.'.$this->paymentEnvironment.'.api_url_security').'/api.security/v1/security';
        $credentials = base64_encode(config('services.'.$this->paymentEnvironment.'.api_user').':'.config('services.'.$this->paymentEnvironment.'.api_password'));
        $accessToken = Http::withHeaders(['Authorization' => 'Basic '.$credentials])->get($securityUrl)->body();

        if(!$accessToken){
            return response()->json(['error' => 'Failed to get Niubiz access token for payment'], 500);
        }
        
        // 2. Realizar la Autorización (cobro)
        $authUrl = config('services.'.$this->paymentEnvironment.'.api_url_transaction').'/api.authorization/v3/authorization/ecommerce/'.config('services.'.$this->paymentEnvironment.'.merchant_id');

        $paymentData = [
            'channel'       => 'web',
            'captureType'   => 'manual',
            'countable'     => true,
            'order' => [
                'tokenId'           => $transactionToken,
                'purchaseNumber'    => $purchaseNumber,
                'amount'            => $amount,
                'currency'          => 'PEN',
                'dataMap'           => [
                    'urlAddress'                            => $urlAddress,
                    'serviceLocationCityName'               => 'Lima',
                    'serviceLocationCountrySubdivisionCode' => 'LIM',
                    'serviceLocationCountryCode'            => 'PE',
                    'serviceLocationPostalCode'             => '17001'
                ]
            ],
        ];

        $paymentResponse = Http::withHeaders([
            'Authorization' => $accessToken,
            'Content-Type' => 'application/json',
        ])->post($authUrl, $paymentData);

        if($paymentResponse->failed()){
            // return response()->json(['success' => false, 'enviado' => $paymentData, 'data' => $paymentResponse->json()], 400);
            return redirect('http://localhost:4200/veterinaria/error-payment/'.$purchaseNumber);
        }

        $jsonGuardable = json_encode($paymentResponse->json());

        if($jsonGuardable['dataMap']['STATUS'] == 'Authorized'){
            $update = DB::connection('sqlsrv')->table('reserva_cita')
            ->where('numero_liquidacion', $purchaseNumber)
            ->update([
                'payment_response' => $jsonGuardable,
                'estado_pago' => 1
            ]);

            // return response()->json(['success' => true, 'data' => $paymentResponse->json()]);
            return redirect('http://localhost:4200/veterinaria/success-payment/'.$purchaseNumber);
        }else{
            return redirect('http://localhost:4200/veterinaria/error-payment/'.$purchaseNumber);
        }
    }
}