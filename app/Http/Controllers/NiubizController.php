<?php

namespace App\Http\Controllers;

use App\Mail\PagoExtraConfirmado;
use App\Mail\ReservaConfirmada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NiubizController extends Controller
{
    // private $paymentEnvironment = 'niubiz_dev';
    private $paymentEnvironment = 'niubiz_prd';

    public function createSessionToken(Request $request)
    {
        // El monto debe venir del frontend o ser calculado aquí
        $amount = $request->input('amount');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $ipAddress = $request->input('ipAddress');

        // 1. Obtener Access Token de Niubiz
        $securityUrl = config('services.' . $this->paymentEnvironment . '.api_url_security') . '/api.security/v1/security';
        $credentials = base64_encode(config('services.' . $this->paymentEnvironment . '.api_user') . ':' . config('services.' . $this->paymentEnvironment . '.api_password'));

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
        ])->get($securityUrl);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to get Niubiz access token'], 500);
        }

        $accessToken = $response->body();

        // 2. Crear el Token de Sesión
        $sessionUrl = config('services.' . $this->paymentEnvironment . '.api_url_transaction') . '/api.ecommerce/v2/ecommerce/token/session/' . config('services.' . $this->paymentEnvironment . '.merchant_id');

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
                    'MDD75' => 'Invitado',
                    'MDD77' => 1
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

    public function processPayment($reserva_id, $pay_amount, $url_address, Request $request)
    {
        $transactionToken = $request->input('transactionToken');
        $amount = $pay_amount;
        $purchaseNumber = $reserva_id;

        $urlAddress = base64_decode($url_address);

        // 1. Obtener Access Token de nuevo (es de corta duración)
        $securityUrl = config('services.' . $this->paymentEnvironment . '.api_url_security') . '/api.security/v1/security';
        $credentials = base64_encode(config('services.' . $this->paymentEnvironment . '.api_user') . ':' . config('services.' . $this->paymentEnvironment . '.api_password'));
        $accessToken = Http::withHeaders(['Authorization' => 'Basic ' . $credentials])->get($securityUrl)->body();

        if (!$accessToken) {
            return response()->json(['error' => 'Failed to get Niubiz access token for payment'], 500);
        }

        // 2. Realizar la Autorización (cobro)
        $authUrl = config('services.' . $this->paymentEnvironment . '.api_url_transaction') . '/api.authorization/v3/authorization/ecommerce/' . config('services.' . $this->paymentEnvironment . '.merchant_id');

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

        $data = $paymentResponse->json();
        $encoded = urlencode(base64_encode(json_encode($data)));

        if ($paymentResponse->failed()) {
            // return response()->json(['success' => false, 'enviado' => $paymentData, 'data' => $paymentResponse->json()], 400);
            // return redirect('https://apps.muniplibre.gob.pe/veterinaria/error-payment/' . $purchaseNumber . '?purchaseNumber=' . $purchaseNumber . '&data=' . $encoded);
            return redirect('http://localhost:4200/error-payment/' . $purchaseNumber . '?purchaseNumber=' . $purchaseNumber . '&data=' . $encoded);
        }

        // $jsonGuardable = json_encode($paymentResponse->json());

        if (isset($data['dataMap']['STATUS']) && $data['dataMap']['STATUS'] === 'Authorized') {
            $update = DB::connection('pgsql')->table('public.reserva_cita')
                ->where('numero_liquidacion', $purchaseNumber)
                ->update([
                    'payment_response' => json_encode($data), // aquí sí lo guardas como string
                    'estado_pago'      => 1
                ]);


            $reserva = DB::connection('pgsql')->table('public.reserva_cita as r')
                ->join('public.mascota as m', 'r.mascota_id', '=', 'm.mascota_id')
                ->join('public.cliente as c', 'm.cliente', '=', 'c.cliente_id')
                ->join('public.persona as p', 'c.persona_id', '=', 'p.persona_id')
                ->join('public.servicio as s', 'r.servicio_id', '=', 's.servicio_id')
                ->select(
                    'r.numero_liquidacion',
                    'r.fecha_cita',
                    'r.hora_cita',
                    'r.estado_pago',
                    'm.mascota_nombre',
                    's.servicio_descri',
                    's.servicio_precio',
                    'p.persona_nombre',
                    'p.persona_apepaterno',
                    'p.persona_apematerno',
                    'c.cliente_correo',
                    'c.cliente_telefono'
                )
                ->where('r.numero_liquidacion', $purchaseNumber)
                ->first();

            // Preparar datos para el correo
            $reservaData = [
                'cliente_email' => $reserva->cliente_correo,
                'cliente_nombre' => $reserva->persona_nombre . ' ' . $reserva->persona_apepaterno,
                'mascota' => $reserva->mascota_nombre,
                'servicio' => $reserva->servicio_descri,
                'purchaseNumber' => $reserva->numero_liquidacion,
                'fecha' => $reserva->fecha_cita,
                'hora' => $reserva->hora_cita,
                'monto' => $reserva->servicio_precio,
                'currency' => 'PEN',
                'tarjeta' => $data['dataMap']['CARD'],
                'marca' => $data['dataMap']['BRAND']
            ];

            // Enviar correo
            Mail::to($reservaData['cliente_email'])->send(new ReservaConfirmada($reservaData));

            return redirect('http://localhost:4200/success-payment/' . $purchaseNumber . '?data=' . $encoded);
            // return redirect('http://apps.muniplibre.gob.pe/veterinaria/success-payment/' . $purchaseNumber . '?data=' . $encoded);
        } else {
            // return redirect('https://apps.muniplibre.gob.pe/veterinaria/error-payment/' . $purchaseNumber . '?purchaseNumber=' . $purchaseNumber . '&data=' . $encoded);
            return redirect('https://localhost:4200/error-payment/' . $purchaseNumber . '?purchaseNumber=' . $purchaseNumber . '&data=' . $encoded);
        }
    }


    /**
     * Procesa el pago final solo del pago extra del servicio
     */

    public function processPaymentExtra($pagoex_id, $pay_amount, $url_address, Request $request)
    {
        $transactionToken = $request->input('transactionToken');
        $amount = $pay_amount;
        $purchaseNumber = $pagoex_id; // ahora usamos el id del pago extra o su numero_liquidacion

        $urlAddress = base64_decode($url_address);

        // 1. Obtener Access Token
        $securityUrl = config('services.' . $this->paymentEnvironment . '.api_url_security') . '/api.security/v1/security';
        $credentials = base64_encode(config('services.' . $this->paymentEnvironment . '.api_user') . ':' . config('services.' . $this->paymentEnvironment . '.api_password'));
        $accessToken = Http::withHeaders(['Authorization' => 'Basic ' . $credentials])->get($securityUrl)->body();

        if (!$accessToken) {
            return response()->json(['error' => 'Failed to get Niubiz access token for payment'], 500);
        }

        // 2. Realizar la Autorización
        $authUrl = config('services.' . $this->paymentEnvironment . '.api_url_transaction') . '/api.authorization/v3/authorization/ecommerce/' . config('services.' . $this->paymentEnvironment . '.merchant_id');

        $paymentData = [
            'channel'       => 'web',
            'captureType'   => 'manual',
            'countable'     => true,
            'order' => [
                'tokenId'        => $transactionToken,
                'purchaseNumber' => $purchaseNumber,
                'amount'         => $amount,
                'currency'       => 'PEN',
                'dataMap'        => [
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

        $data = $paymentResponse->json();
        $encoded = urlencode(base64_encode(json_encode($data)));

        if ($paymentResponse->failed()) {
            return redirect('http://localhost:4200/error-payment/' . $purchaseNumber . '?purchaseNumber=' . $purchaseNumber . '&data=' . $encoded);
        }

        // 3. Si está autorizado → actualizar pagos_extra
        if (isset($data['dataMap']['STATUS']) && $data['dataMap']['STATUS'] === 'Authorized') {
            $update = DB::connection('pgsql')->table('public.pagos_extra')
                ->where('numero_liquidacion', $purchaseNumber)
                ->update([
                    'payment_response' => json_encode($data),
                    'estado_pago'      => 1
                ]);

            // Traer datos para enviar correo
            $pago = DB::connection('pgsql')->table('public.pagos_extra as px')
                ->join('public.reserva_cita as r', 'px.reserva_id', '=', 'r.reserva_id')
                ->join('public.mascota as m', 'r.mascota_id', '=', 'm.mascota_id')
                ->join('public.cliente as c', 'm.cliente', '=', 'c.cliente_id')
                ->join('public.persona as p', 'c.persona_id', '=', 'p.persona_id')
                ->join('public.servicio as s', 'px.servicio_id', '=', 's.servicio_id')
                ->select(
                    'px.numero_liquidacion',
                    's.servicio_descri',
                    's.servicio_precio',
                    'm.mascota_nombre',
                    'p.persona_nombre',
                    'p.persona_apepaterno',
                    'c.cliente_correo',
                    'c.cliente_telefono'
                )
                ->where('px.numero_liquidacion', $purchaseNumber)
                ->first();

            $pagoData = [
                'cliente_email' => $pago->cliente_correo,
                'cliente_nombre' => $pago->persona_nombre . ' ' . $pago->persona_apepaterno,
                'mascota' => $pago->mascota_nombre,
                'servicio' => $pago->servicio_descri,
                'purchaseNumber' => $pago->numero_liquidacion,
                'monto' => $pago->servicio_precio,
                'currency' => 'PEN',
                'tarjeta' => $data['dataMap']['CARD'],
                'marca' => $data['dataMap']['BRAND']
            ];

            Mail::to($pagoData['cliente_email'])->send(new PagoExtraConfirmado($pagoData));

            return redirect('http://localhost:4200/success-payment/' . $purchaseNumber . '?data=' . $encoded);
        } else {
            return redirect('http://localhost:4200/error-payment/' . $purchaseNumber . '?purchaseNumber=' . $purchaseNumber . '&data=' . $encoded);
        }
    }
}
