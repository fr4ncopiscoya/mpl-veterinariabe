<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class VeterinariaController extends Controller
{

    private static $conexion = 'sqlsrv';
    private static $conexionpsql = 'pgsql';

    // public function loginUser(Request $request)
    // {
    //     $p_loging = $request['p_loging'];
    //     $p_passwd = $request['p_passwd'];

    //     $results = DB::connection('pgsql')->select('SELECT * FROM postgres.logue_usuario_dashboard(?,?)', [
    //         $p_loging,
    //         $p_passwd
    //     ]);

    //     return response()->json($results);
    // }
    public function loginUser(Request $request)
    {
        $p_loging = $request->input('p_loging');
        $p_passwd = $request->input('p_passwd');

        $results = DB::connection('pgsql')->select(
            'SELECT * FROM public.sp_user_login(?, ?)',
            [$p_loging, $p_passwd]
        );

        if (empty($results)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $results[0]
        ]);
    }

    public function getFechasDisponibles()
    {
        // $results = DB::connection('sqlsrv')->select('EXEC sp_fechas_disponibles_sel');
        $results = DB::connection('pgsql')->select('SELECT * FROM public.sp_fechas_disponibles_sel()');

        return response()->json($results);
    }
    public function getHorariosDisponibles(Request $request)
    {
        $p_fecha = $request['p_fecha'];

        $results = DB::connection('pgsql')->select(
            'SELECT * FROM public.sp_horariosxfecha_sel(?::date)',
            [$p_fecha]
        );

        return response()->json($results);
    }
    public function getServiciosDisponibles(Request $request)
    {
        $p_fecha = $request['p_fecha'];

        $results = DB::connection('pgsql')->select(
            'SELECT * FROM public.sp_serviciosxfecha_sel (?::date)',
            [$p_fecha]
        );

        return response()->json($results);
    }
    public function getRazas(Request $request)
    {
        $p_especieid = $request['p_especieid'];

        $results = DB::connection('pgsql')->select(
            'SELECT * FROM public.sp_razasxespecie_sel (?)',
            [$p_especieid]
        );

        return response()->json($results);
    }
    public function updReservaEstado(Request $request)
    {
        $reserva_id = $request['reserva_id'];
        $estado_id = $request['estado_id'];

        $results = DB::connection('pgsql')->select(
            'SELECT * FROM public.sp_reservaestado_upd (?,?)',
            [$reserva_id, $estado_id]
        );

        return response()->json($results);
    }
    public function updLiquidacionPago(Request $request)
    {
        $numero_liquidacion = $request['numero_liquidacion'];

        $results = DB::connection('sqlsrv')->select(
            'EXEC sp_liquidacionpago_upd ?,?',
            [$numero_liquidacion]
        );

        return response()->json($results);
    }
    public function getReservaCita(Request $request)
    {
        $FechaInicio  = $request->input('FechaInicio') ?: null;
        $FechaFin     = $request->input('FechaFin') ?: null;
        $FechaExacta  = $request->input('FechaExacta') ?: null;

        $ServicioId   = $request->input('ServicioId');
        $ServicioId   = ($ServicioId == 0) ? null : $ServicioId;

        $EstadoId     = $request->input('EstadoId');
        $EstadoId     = ($EstadoId == 0) ? null : $EstadoId;

        $results = DB::connection('pgsql')->select(
            'SELECT * FROM public.sp_reservacita_sel(?,?,?,?,?)',
            [
                $FechaInicio,
                $FechaFin,
                $FechaExacta,
                $ServicioId,
                $EstadoId
            ]
        );

        return response()->json($results);
    }

    public function insReservaCita(Request $request)
    {
        $tipdoc_id = $request['tipdoc_id'];
        $persona_numdoc = $request['persona_numdoc'];
        $persona_nombre = $request['persona_nombre'];
        $persona_apepaterno = $request['persona_apepaterno'];
        $persona_apematerno = $request['persona_apematerno'];
        $cliente_direccion = $request['cliente_direccion'];
        $cliente_correo = $request['cliente_correo'];
        $cliente_telefono = $request['cliente_telefono'];
        $mascota_nombre = $request['mascota_nombre'];
        $especie_id = $request['especie_id'];
        $raza_id = $request['raza_id'];
        $servicio_id = $request['servicio_id'];
        $fecha_cita = $request['fecha_cita'];
        $hora_cita = $request['hora_cita'];
        $estado_id = $request['estado_id'];
        $observaciones = $request['observaciones'];

        $results = DB::connection('pgsql')->select(
            'SELECT * FROM public.sp_reservacita_ins(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $tipdoc_id,
                $persona_numdoc,
                $persona_nombre,
                $persona_apepaterno,
                $persona_apematerno,
                $cliente_direccion,
                $cliente_correo,
                $cliente_telefono,
                $mascota_nombre,
                $especie_id,
                $raza_id,
                $servicio_id,
                $fecha_cita,
                $hora_cita,
                $estado_id,
                $observaciones
            ]
        );

        return response()->json($results);
    }

    public function getServicios()
    {
        $results = DB::connection('pgsql')->select('SELECT * FROM public.sp_servicios_sel()');

        return response()->json($results);
    }

    public function getReniec(Request $request)
    {
        $response = Http::get(env('URL_RENIEC') . '/Consultar', [
            'nuDniConsulta' => $request->input('nuDniConsulta'),
            'nuDniUsuario' => env('RENIEC_NUDNI'),
            'nuRucUsuario' => env('RENIEC_RUC'),
            'password' => env('RENIEC_PASS'),
            'out' => 'json'
        ]);

        return response()->json($response->json(), $response->status());
    }

    public function getCarnetExtranjeria(Request $request)
    {
        $response = Http::get(env('URL_MIGRACIONES'), [
            'username' => env('MIGRACIONES_USERNAME'),
            'password' => env('MIGRACIONES_PASSWORD'),
            'ip' => env('MIGRACIONES_IP'),
            'nivelacceso' => env('MIGRACIONES_NACCESO'),
            'docconsulta' => $request->input('docconsulta'),
            'out' => 'json'
        ]);

        return response()->json($response->json(), $response->status());
    }
}
