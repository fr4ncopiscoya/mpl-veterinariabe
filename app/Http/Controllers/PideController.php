<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class PideController extends Controller
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

    public function updatePasswordUser(Request $request)
    {
        try {
            $p_password = $request->input('password');
            $p_userid = $request->input('iduser');

            $results = DB::connection('mysql')->select('CALL sp_passworduser_upd(?, ?)', [
                $p_password,
                $p_userid
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente',
                'data' => $results
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $fullMessage = $e->getMessage();

            if (preg_match('/1644\s(.+?)\s*\(Connection:/', $fullMessage, $matches)) {
                $cleanMessage = trim($matches[1]);
            } else {
                $cleanMessage = 'Ocurrió un error desconocido.';
            }

            return response()->json([
                'success' => false,
                'message' => $cleanMessage
            ]);
        }
    }

    public function getMenus()
    {
        $results = DB::connection('mysql')->select('CALL sp_menus_sel()', []);

        // $results = DB::connection('mysql')->select('CALL sp_menu_sel ()');

        return response()->json($results);
    }
    public function getMenusByUser(Request $request)
    {
        $p_user_id = $request['p_user_id'];

        $results = DB::connection('mysql')->select('CALL sp_menuByUser_sel(?)', [$p_user_id]);

        return response()->json($results);
    }
    public function insertPermissions(Request $request)
    {
        $usuario = $request->input('p_usu_id');
        $permisos = $request->input('p_permisos_menu'); // array [{menu: x, estatus: y}, ...]

        foreach ($permisos as $permiso) {
            $menu = $permiso['menu'];
            $estado = $permiso['estado'];

            // Ejecutamos el SP ya existente
            DB::statement('CALL sp_permisos_ins(?, ?, ?, @message)', [$usuario, $menu, $estado]);
            // $results = DB::connection('mysql')->select('CALL sp_permisos_ins(?,?,?)', [$usuario, $menu, $estado]);
        }

        return response()->json(['message' => 'Permisos sincronizados correctamente']);

        // return response()->json($results);
    }
    public function insertUsers(Request $request)
    {
        $usu_id = $request->input('p_usu_id');
        $usu_nombres = $request->input('p_usu_nombres');
        $usu_apellidos = $request->input('p_usu_apellidos');
        $usu_usuario = $request->input('p_usu_usuario');
        $usu_password = $request->input('p_usu_password');
        $usu_correo = $request->input('p_usu_correo');
        $usu_dni = $request->input('p_usu_dni');
        $usu_area = $request->input('p_usu_area');
        $usu_estatus = $request->input('p_usu_estatus');

        DB::statement('CALL sp_users_ins(?, ?, ?, ?, ?, ?, ?, ?, ?, @message)', [
            $usu_id,
            $usu_nombres,
            $usu_apellidos,
            $usu_usuario,
            $usu_password,
            $usu_correo,
            $usu_dni,
            $usu_area,
            $usu_estatus
        ]);

        $message = DB::select('SELECT @message AS message')[0]->message;

        return response()->json([
            'message' => $message
        ]);
    }
    public function updateReniecCredentials(Request $request)
    {
        $response = Http::get(env('URL_RENIEC') . '/Actualizar', [
            'credencialAnterior' => $request->input('oldCredential'),
            'credencialNueva' => $request->input('newCredential'),
            'nuDni' => $request->input('nuDni'),
            'nuRuc' => env('RENIEC_RUC'),
            'out' => 'json'
        ]);

        return response()->json($response->json(), $response->status());
    }

    public function getAreas()
    {
        $results = DB::connection('mysql')->select('CALL sp_areas_sel()', []);

        // $results = DB::connection('mysql')->select('CALL sp_menu_sel ()');

        return response()->json($results);
    }

    public function getAllUsers()
    {
        $results = DB::connection('mysql')->select('CALL sp_users_sel ()');

        return response()->json($results);
    }

    //IDENTIFICACIÓN

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

    //PROPIEDADES

    public function getPartida(Request $request)
    {
        $response = Http::get(env('URL_SUNARP') . '/LASIRSARP', [
            'usuario' => env('SUNARP_USUARIO'),
            'clave' => env('SUNARP_CLAVE'),
            'zona' => env('SUNARP_ZONA'),
            'oficina' => env('SUNARP_OFICINA'),
            'partida' => $request->input('partida'),
            'registro' => env('SUNARP_REGISTRO'),
            'out' => 'json'
        ]);

        return response()->json($response->json(), $response->status());
    }
    public function getPartidaImg(Request $request)
    {
        $response = Http::get(env('URL_SUNARP') . '/VASIRSARP', [
            'usuario' => env('SUNARP_USUARIO'),
            'clave' => env('SUNARP_CLAVE'),
            'transaccion' => $request->input('transaccion'),
            'idImg' => $request->input('idImg'),
            'tipo' => $request->input('tipo'),
            'nroTotalPag' => $request->input('nroTotalPag'),
            'nroPagRef' => $request->input('nroPagRef'),
            'pagina' => $request->input('pagina'),
            'out' => 'json'
        ]);

        return response()->json($response->json(), $response->status());
    }
    public function getRegistroVehicular(Request $request)
    {

        $response = Http::withHeaders([
            'Content-Type' => 'application/json; charset=utf-8'
        ])->get(env('URL_SUNARP') . '/VDRPVExtra', [
            'usuario' => env('SUNARP_USUARIO'),
            'clave' => env('SUNARP_CLAVE'),
            'zona' => env('SUNARP_ZONA'),
            'oficina' => env('SUNARP_OFICINA'),
            'placa' => $request->input('placa'),
            'out' => 'json'
        ]);

        return response()->json($response->json(), $response->status(), [
            'Content-Type' => 'application/json; charset=utf-8'
        ]);

        // $response = Http::get(env('URL_SUNARP') . '/VDRPVExtra', [
        //     'usuario' => env('SUNARP_USUARIO'),
        //     'clave' => env('SUNARP_CLAVE'),
        //     'zona' => env('SUNARP_ZONA'),
        //     'oficina' => env('SUNARP_OFICINA'),
        //     'placa' => $request->input('placa'),
        //     'out' => 'json'
        // ], 200, ['Content-Type' => 'application/json; charset=utf-8']);

        // return response()->json($response->json(), $response->status());
    }
    public function getBienesPerNatural(Request $request)
    {
        $response = Http::get(env('URL_SUNARP') . '/TSIRSARP', [
            'usuario' => env('SUNARP_USUARIO'),
            'clave' => env('SUNARP_CLAVE'),
            'tipoParticipante' => $request->input('tipoParticipante'),
            'apellidoPaterno' => $request->input('apellidoPaterno'),
            'apellidoMaterno' => $request->input('apellidoMaterno'),
            'nombres' => $request->input('nombres'),
            'razonSocial' => $request->input('razonSocial', ''),
            'out' => 'json',
        ]);

        return response()->json($response->json(), $response->status());
    }
    public function getBienesPerJuridica(Request $request)
    {
        $response = Http::get(env('URL_SUNARP') . '/BPJRSocial', [
            'usuario' => env('SUNARP_USUARIO'),
            'clave' => env('SUNARP_CLAVE'),
            'razonSocial' => $request->input('razonSocial'),
            'out' => 'json',
        ]);

        return response()->json($response->json(), $response->status());
    }
    public function getSunedu(Request $request)
    {
        $response = Http::get(env('URL_SUNEDU') . '/Grado', [
            'usuario' => env('SUNEDU_USUARIO'),
            'clave' => env('SUNEDU_CLAVE'),
            'idEntidad' => env('SUNEDU_IDENTIDIDAD'),
            // 'fecha' => $request->input('', ''),
            // 'hora' => $request->input('', ''),
            'mac_wsServer' => env('SUNEDU_MACSERVER'),
            'ip_wsServer' => env('SUNEDU_IPSERVER'),
            'ip_wsUser' => env('SUNEDU_IPUSER'),
            'nroDocIdentidad' => $request->input('nroDocIdentidad'),
            'out' => 'json',
        ]);

        return response()->json($response->json(), $response->status());
    }

    // ANTECEDENTES
    public function getAPolicialNumDoc(Request $request)
    {
        $response = Http::get(env('URL_ANT_POLICIALES') . '/APolicialPerNumDoc', [
            'clienteUsuario' => env('ANTPP_USUARIO'),
            'clienteClave' => env('ANTPP_CLAVE'),
            'servicioCodigo' => env('ANTPP_CODIGO'),
            'clienteSistema' => env('ANTPP_SISTEMA'),
            'clienteIp' => env('ANTPP_IP'),
            'clienteMac' => env('ANTPP_MAC'),
            'tipoDocUserClieFin' => env('ANTPP_TIPDOC'),
            'nroDocUserClieFin' => env('ANTPP_NRODOC'),
            'nroDoc' => $request->input('nroDoc'),
            'out' => 'json',
        ]);

        return response()->json($response->json(), $response->status());
    }
    public function getAPolicialNomPatMat(Request $request)
    {
        $response = Http::get(env('URL_ANT_POLICIALES') . '/APolicialPerNomPatMat', [
            'clienteUsuario' => env('ANTPP_USUARIO'),
            'clienteClave' => env('ANTPP_CLAVE'),
            'servicioCodigo' => env('ANTPP_CODIGO'),
            'clienteSistema' => env('ANTPP_SISTEMA'),
            'clienteIp' => env('ANTPP_IP'),
            'clienteMac' => env('ANTPP_MAC'),
            'tipoDocUserClieFin' => env('ANTPP_TIPDOC'),
            'nroDocUserClieFin' => env('ANTPP_NRODOC'),
            'nombre' => $request->input('nombre'),
            'paterno' => $request->input('paterno'),
            'materno' => $request->input('materno'),
            'out' => 'json',
        ]);

        return response()->json($response->json(), $response->status());
    }
    public function getAPolicialNomPat(Request $request)
    {
        $response = Http::get(env('URL_ANT_POLICIALES') . '/APolicialPerNomPat', [
            'clienteUsuario' => env('ANTPP_USUARIO'),
            'clienteClave' => env('ANTPP_CLAVE'),
            'servicioCodigo' => env('ANTPP_CODIGO'),
            'clienteSistema' => env('ANTPP_SISTEMA'),
            'clienteIp' => env('ANTPP_IP'),
            'clienteMac' => env('ANTPP_MAC'),
            'tipoDocUserClieFin' => env('ANTPP_TIPDOC'),
            'nroDocUserClieFin' => env('ANTPP_NRODOC'),
            'nombre' => $request->input('nombre'),
            'paterno' => $request->input('paterno'),
            'out' => 'json',
        ]);

        return response()->json($response->json(), $response->status());
    }
    public function getAPolicialCodPer(Request $request)
    {
        $response = Http::get(env('URL_ANT_POLICIALES') . '/APolicialAntCodPer', [
            'clienteUsuario' => env('ANTPP_USUARIO'),
            'clienteClave' => env('ANTPP_CLAVE'),
            'servicioCodigo' => env('ANTPP_CODIGO'),
            'clienteSistema' => env('ANTPP_SISTEMA'),
            'clienteIp' => env('ANTPP_IP'),
            'clienteMac' => env('ANTPP_MAC'),
            'tipoDocUserClieFin' => env('ANTPP_TIPDOC'),
            'nroDocUserClieFin' => env('ANTPP_NRODOC'),
            'codigoPersona' => $request->input('codigoPersona'),
            'out' => 'json',
        ]);

        return response()->json($response->json(), $response->status());
    }
    public function getAJudiciales(Request $request)
    {
        $response = Http::get(env('URL_ANT_JUDICIALES') . '/AJudicial', [
            'institucionRuc' => env('ANTPP_USUARIO'),
            'institucionIp' => env('ANTPP_CLAVE'),
            'usuarioDNI' => env('ANTPP_CODIGO'),
            'primerApellido' => $request->input('primerApellido'),
            'segundoApellido' => $request->input('segundoApellido'),
            'nombres' => $request->input('nombres'),
            'out' => 'json',
        ]);

        return response()->json($response->json(), $response->status());
    }
    public function getAPenales(Request $request)
    {
        $response = Http::get(env('URL_ANT_JUDICIALES') . '/AJudicial', [
            'xApellidoPaterno' => $request->input('apePaterno'),
            'xApellidoMaterno' => $request->input('apeMaterno'),
            'xNombre1' => $request->input('nombre1'),
            'xNombre2' => $request->input('nombre2'),
            'xNombre3' => $request->input('nombre3'),
            'xDni' => $request->input('nuDni'),
            'xMotivoConsulta' => env('nombres'),
            'xProcesoEntidadConsultante' => env('nombres'),
            'xRucEntidadConsultante' => env('nombres'),
            'xIpPublica' => env('nombres'),
            'xDniPersonaConsultante' => $request->input('segundoApellido'),
            'xAudNombrePC' => gethostname(),
            // 'xAudNombrePC' => $request->input('segundoApellido'),
            'xAudIP' => $request->input('segundoApellido'),
            'xAudNombreUsuario' => env('nombres'),
            'xAudDireccionMAC' => env('nombres'),
            'out' => 'json',
        ]);

        return response()->json($response->json(), $response->status());
    }
}
