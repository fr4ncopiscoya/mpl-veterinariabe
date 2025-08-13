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
    public function loginAuth(Request $request)
    {
        $p_loging = $request['p_loging'];
        $p_passwd = $request['p_passwd'];

        // Llamada al login
        DB::statement('CALL sp_login_auth(?, ?, @codigo, @mensaje, @user_id, @user_name)', [
            $p_loging,
            $p_passwd
        ]);

        // Recoger los resultados
        $resultado = DB::select('SELECT @codigo AS codigo, @mensaje AS mensaje, @user_id AS user_id, @user_name AS user_name')[0];

        // echo($resultado->user_id);
        // die();
        // Si login fue exitoso, obtenemos los menús
        if ($resultado->codigo === 0) {
            $menus = DB::connection('mysql')->select('CALL sp_menuByUser_sel(?)', [$resultado->user_id]);

            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Inicio de sesión correcta',
                'id_usuario' => $resultado->user_id,
                'username' => $resultado->user_name,
                'menus' => $menus
            ]);
        }

        return response()->json([
            'codigo' => $resultado->codigo,
            'mensaje' => $resultado->mensaje
        ]);
    }

    public function getFechasDisponibles()
    {
        $results = DB::connection('sqlsrv')->select('EXEC sp_fechas_disponibles_sel');

        return response()->json($results);
    }
    public function getHorariosDisponibles(Request $request)
    {
        $p_fecha = $request['p_fecha'];

        $results = DB::connection('sqlsrv')->select(
            'EXEC sp_horariosxfecha_sel ?',
            [$p_fecha]
        );

        return response()->json($results);
    }
    public function getServiciosDisponibles(Request $request)
    {
        $p_fecha = $request['p_fecha'];

        $results = DB::connection('sqlsrv')->select(
            'EXEC sp_serviciosxfecha_sel ?',
            [$p_fecha]
        );

        return response()->json($results);
    }
    public function getRazas(Request $request)
    {
        $p_especieid = $request['p_especieid'];

        $results = DB::connection('sqlsrv')->select(
            'EXEC sp_razasxespecie_sel ?',
            [$p_especieid]
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

        $results = DB::connection('sqlsrv')->select(
            'EXEC sp_reservacita_ins ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?',
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
            $observaciones]
        );

        return response()->json($results);
    }
    // public function getServicios()
    // {
    //     $results = DB::connection('sqlsrv')->select('EXEC sp_servicios_sel');

    //     return response()->json($results);
    // }


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
