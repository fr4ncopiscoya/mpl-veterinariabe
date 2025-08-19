<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NiubizController extends Controller{
    
    public function createSessionToken(Request $request){
        // El monto debe venir del frontend o ser calculado aquí
        $amount = $request->input('amount');
        $email = $request->input('email');
        $phone = $request->input('phone');
        
        // 1. Obtener Access Token de Niubiz
        $securityUrl = config('services.niubiz_prd.api_url_security').'/api.security/v1/security';
        $credentials = base64_encode(config('services.niubiz_prd.api_user').':'.config('services.niubiz_prd.api_password'));

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$credentials,
        ])->get($securityUrl);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to get Niubiz access token'], 500);
        }

        $accessToken = $response->body();

        // 2. Crear el Token de Sesión
        $sessionUrl = config('services.niubiz_prd.api_url_transaction').'/api.ecommerce/v2/ecommerce/token/session/'.config('services.niubiz_prd.merchant_id');
        
        $sessionResponse = Http::withHeaders([
            'Authorization' => $accessToken,
            'Content-Type'  => 'application/json',
        ])->post($sessionUrl, [
            'channel'   => 'web',
            'amount'    => $amount,
            'antifraud' => [
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

    public function processPayment(Request $request){
        $request->validate([
            'transactionToken'  => 'required|string',
            'amount'            => 'required|numeric',
            'purchaseNumber'    => 'required|string'
        ]);

        // 1. Obtener Access Token de nuevo (es de corta duración)
        $securityUrl = config('services.niubiz_prd.api_url_security').'/api.security/v1/security';
        $credentials = base64_encode(config('services.niubiz_prd.api_user').':'.config('services.niubiz_prd.api_password'));
        $accessToken = Http::withHeaders(['Authorization' => 'Basic '.$credentials])->get($securityUrl)->body();

        if(!$accessToken){
            return response()->json(['error' => 'Failed to get Niubiz access token for payment'], 500);
        }
        
        // 2. Realizar la Autorización (cobro)
        $authUrl = config('services.niubiz_prd.api_url_transaction').'/api.authorization/v3/authorization/ecommerce/'.config('services.niubiz_prd.merchant_id');

        $paymentData = [
            'channel'       => 'web',
            'captureType'   => 'manual',
            'countable'     => true,
            'order' => [
                'tokenId'           => $request->input('transactionToken'),
                'purchaseNumber'    => $request->input('purchaseNumber'),
                'amount'            => $request->input('amount'),
                'currency'          => 'PEN',
                'dataMap'           => [
                    'urlAddress'                            => 'http://localhost:4200',
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

        if ($paymentResponse->failed()) {
            return response()->json(['success' => false, 'data' => $paymentResponse->json()], 400);
        }

        // Aquí guardas el resultado en tu BD
        // Ejemplo:
        // $cita = Cita::where('purchaseNumber', $request->input('purchaseNumber'))->first();
        // $cita->estado_pago = 'pagado';
        // $cita->save();

        return response()->json(['success' => true, 'data' => $paymentResponse->json()]);
    }
}