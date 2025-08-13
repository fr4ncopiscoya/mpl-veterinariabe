<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReporteController extends Controller
{

    private static $conexion = 'sqlsrv';
    private static $conexionpsql = 'pgsql';
    
    public function loginUser(Request $request)
    {
        $p_loging = $request['p_loging'];
        $p_passwd = $request['p_passwd'];
        
        $results = DB::connection('pgsql')->select('SELECT * FROM postgres.logue_usuario_dashboard(?,?)', [
            $p_loging,
            $p_passwd
        ]);

        return response()->json($results);
    }
    public function selAnio(Request $request)
    {
        $p_anio = $request['p_anio'];
        $p_tipo = $request['p_tipo'];
        
        // $results = DB::connection(self::$conexion)->select('EXEC siget.usp_ingresoany_sel ?', [
        //     $p_anio,
            
        // ]);
        $results = DB::connection('pgsql')->select('SELECT * FROM postgres.uf_ingresosany_sel(?)', [
            $p_anio
        ]);

        return response()->json($results);
    }

    // public function selAnio(Request $request)
    // {
    //     $p_anio = $request['p_anio'];
    //     $p_tipo = $request['p_tipo'];

    //     $results = DB::connection(self::$conexion)->select('EXEC siget.usp_ingresoany_sel ?,?', [
    //         $p_anio,
    //         $p_tipo
    //     ]);

    //     return response()->json($results);
    // }

    public function selMes(Request $request)
    {
        $p_anio = $request['p_anio'];
        $p_mes = $request['p_mes'];

        // $results = DB::connection(self::$conexion)->select('EXEC siget.usp_ingresodia_sel ?, ?', [
        //     $p_anio,
        //     $p_mes
        // ]);
        
        $results = DB::connection('pgsql')->select('SELECT * FROM postgres.uf_ingresosany_sel_dia(?, ?)', [
            $p_anio,
            $p_mes
        ]);
        return response()->json($results);
    }

    public function selMultaAnio(Request $request)
    {
        $p_anio = $request['p_anypro'];
        
        $results = DB::connection('pgsql')->select('SELECT * FROM postgres.uf_ingresosany_sel_multas(?)', [
            $p_anio,
        ]);
        return response()->json($results);
    }

    public function selDia(Request $request)
    {
        $p_dia = $request['p_dia'];
        $p_dia_fin = $request['p_dia_fin'];
        $p_tipo = $request['p_tipo'];
        // $results = DB::connection(self::$conexion)->select('EXEC siget.usp_ingresocondsh_sel ?,?', [
        $results = DB::connection('pgsql')->select('SELECT * FROM postgres.uf_ingresosdia_sel(?, ?)', [
            $p_dia,
            $p_dia_fin
        ]);
        return response()->json($results);
    }
    
    public function selContribuyente(Request $request)
    {
        $p_dia = $request['p_dia'];
        $p_dia_fin = $request['p_dia_fin'];
        
        $results = DB::connection('pgsql')->select('SELECT * FROM postgres.uf_topcontribuyentes_sel(?, ?)', [
            $p_dia,
            $p_dia_fin
        ]);
        // $results = DB::connection(self::$conexion)->select('EXEC siget.usp_ingresocontri_sel ?,?', [
        //     $p_dia,
        //     $p_dia_fin
        // ]);

        return response()->json($results);
    }

    public function selConcepto(Request $request)
    {
        $p_anio = $request['p_anio'];

        // $results = DB::connection(self::$conexion)->select('EXEC siget.usp_ingresorubany_sel ?', [
        //     $p_anio,
        // ]);
        $results = DB::connection('pgsql')->select('SELECT * FROM postgres.uf_ingresosany_rubro_sel(?)', [
            $p_anio,
        ]);
        
        return response()->json($results);
    }
    
    public function selConceptoMontos(Request $request)
    {
        $p_dia = $request['p_dia'];
        $p_dia_fin = $request['p_dia_fin'];
        
        // $results = DB::connection(self::$conexion)->select('EXEC siget.usp_ingresorubran_sel ?,?', [
        //     $p_dia,
        //     $p_dia_fin,
        // ]);
        $results = DB::connection('pgsql')->select('SELECT * FROM postgres.uf_ingresosany_rubro_sel_dia(?,?)', [
            $p_dia,
            $p_dia_fin
        ]);

        return response()->json($results);
    }


    public function reporteTupa(Request $request)
    {

        $p_anypro = ($request['p_anypro']) ? $request['p_anypro'] : 0;
        $p_mespro = ($request['p_mespro']) ? $request['p_mespro'] : 0;

        $results = DB::connection(self::$conexion)->select('exec siget.usp_reptuptus_tot ?,?', [
            $p_anypro,
            $p_mespro

        ]);

        return response()->json($results);
    }

    public function reporteTupaArea(Request $request)
    {
        -

            $p_anypro = ($request['p_anypro']) ? $request['p_anypro'] : 0;
        $p_mespro = ($request['p_mespro']) ? $request['p_mespro'] : 0;
        $p_arenid = ($request['p_arenid']) ? $request['p_arenid'] : 0;

        $results = DB::connection(self::$conexion)->select('exec siget.usp_reptuptus_uno ?,?,?', [
            $p_anypro,
            $p_mespro,
            $p_arenid

        ]);

        return response()->json($results);
    }

    public function reporteTupaAreaCalendar(Request $request)
    {
        $p_anypro = ($request['p_anypro']) ? $request['p_anypro'] : 0;
        $p_mespro = ($request['p_mespro']) ? $request['p_mespro'] : 0;
        $p_areaid = ($request['p_areaid']) ? $request['p_areaid'] : 0;

        $results = DB::connection(self::$conexion)->select('exec siget.usp_ingresodiatt_sel ?,?,?', [
            $p_anypro,
            $p_mespro,
            $p_areaid

        ]);

        return response()->json($results);
    }

    public function reporteMultaCalendar(Request $request)
    {
        $p_anypro = ($request['p_anypro']) ? $request['p_anypro'] : 0;
        $p_mespro = ($request['p_mespro']) ? $request['p_mespro'] : 0;

        $results = DB::connection('pgsql')->select('SELECT * FROM postgres.uf_ingresosany_sel_dia_multas(?,?)', [
            $p_anypro,
            $p_mespro
        ]);

        // $results = DB::connection(self::$conexion)->select('exec siget.usp_ingresodiama_sel ?,?', [
        //     $p_anypro,
        //     $p_mespro,

        // ]);

        return response()->json($results);
    }

    public function reporteMultaEspecifica(Request $request)
    {
        $p_dia = ($request['p_dia']) ? $request['p_dia'] : 0;

        $results = DB::connection('pgsql')->select('SELECT * FROM postgres.uf_ingresosany_sel_dia_multas_detalle(?)', [
            $p_dia
        ]);

        return response()->json($results);
    }



    public function selTupaDia(Request $request)
    {
        $p_areaid = ($request['p_areaid']) ? $request['p_areaid'] : 0;
        $p_diapro = ($request['p_diapro']) ? $request['p_diapro'] : '';

        $results = DB::connection(self::$conexion)->select('exec siget.usp_ingresodiatt_dia ?,?', [
            $p_areaid,
            $p_diapro,

        ]);

        return response()->json($results);
    }

}