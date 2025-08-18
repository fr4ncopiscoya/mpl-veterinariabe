<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservaController extends Controller{
    public function actualizarEstadoPago($reserva_id){
        try {
            // 2. Ya no se valida un request body, se usa el argumento directamente
            $reservaIdNumerico = $reserva_id;

            // El resto de la lógica es la misma
            $affectedRows = DB::connection('sqlsrv')->table('reserva_cita')
                ->where('numero_liquidacion', $reservaIdNumerico)
                ->update(['estado_pago' => 1]);

            if ($affectedRows > 0) {
                return redirect('http://localhost:4200/veterinaria/success-payment/'.$reservaIdNumerico)->with('status', '¡Estado de pago '.$reservaIdNumerico.' actualizado correctamente.!');
                
                // return response()->json([
                //     'message' => 'Estado de pago actualizado correctamente.',
                //     'reserva_id' => $reservaIdNumerico
                // ]);
            } else {
                return response()->json(['message' => 'Reserva no encontrada.'], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error al actualizar pago desde URL: ' . $e->getMessage());
            return response()->json(['message' => 'Ocurrió un error en el servidor.'], 500);
        }
    }
}
