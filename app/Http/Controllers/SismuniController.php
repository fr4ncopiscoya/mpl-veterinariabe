<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SismuniController extends Controller
{

    public function getNumeroImpresionCajero($caja_id = 975)
    {
        $results = DB::connection('pgsql_sismuni')
            ->select('SELECT * FROM postgres.f_caja_generar_nroimpresion_cajera(?)', [$caja_id]);

        Log::info('getNumeroImpresionCajero result:', $results); // 👈 loguea en storage/logs/laravel.log
        // dd($results);
        return $results;
    }

    public function insertCabeceraTusne($p_doccli, $p_nomcli, $p_direccion, $p_totaltarjeta, $p_numtarjeta)
    {
        $results = DB::connection('pgsql_sismuni')->select(
            'SELECT * FROM postgres.f_caja_graba_pagocab_veterinaria(?,?,?,?,?)',
            [$p_doccli, $p_nomcli, $p_direccion, $p_totaltarjeta, $p_numtarjeta]
        );

        Log::info('insertCabeceraTusne result:', $results);
        // dd($results);
        return $results;
    }

    public function insertDetalleTusne($p_idem, $p_item, $p_cantidad, $p_monto, $p_parpresu, $p_concepto, $p_rubro, $p_numimpre)
    {
        $results = DB::connection('pgsql_sismuni')->select(
            'SELECT * FROM postgres.f_caja_graba_pagodet_veterinaria(?,?,?,?,?,?,?,?)',
            [$p_idem, $p_item, $p_cantidad, $p_monto, $p_parpresu, $p_concepto, $p_rubro, $p_numimpre]
        );

        Log::info('insertDetalleTusne result:', $results);
        // dd($results);
        return $results;
    }
}
