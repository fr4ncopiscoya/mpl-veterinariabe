<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservaController extends Controller
{
    public function actualizarEstadoPago($reserva_id, $paymentResponse = null)
    {
        try {
            $reservaIdNumerico = $reserva_id;

            // Usando esquema explicitamente
            $affectedRows = DB::connection('pgsql')->table('public.reserva_cita')
                ->where('reserva_id', $reservaIdNumerico)
                ->update([
                    'estado_pago' => 1,
                    'payment_response' => $paymentResponse, // si tienes info de la pasarela
                    'updated_at' => now() // para mantener timestamps correctos
                ]);

            if ($affectedRows > 0) {
                return response()->json([
                    'message' => 'Estado de pago actualizado correctamente.',
                    'reserva_id' => $reservaIdNumerico
                ]);
            } else {
                return response()->json(['message' => 'Reserva no encontrada.'], 404);
            }
        } catch (\Exception $e) {
            Log::error('Error al actualizar pago: ' . $e->getMessage());
            return response()->json(['message' => 'Ocurrió un error en el servidor.'], 500);
        }
    }
}
