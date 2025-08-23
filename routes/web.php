<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\VCardController;
use App\Http\Controllers\MailingController;
use App\Http\Controllers\PideController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VeterinariaController;
use App\Http\Controllers\NiubizController;
use App\Http\Controllers\ReservaController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return 'API REST - VET  ';
});

Route::prefix('veterinaria')->group(function () {
    // Route::post('login', [PideController::class, 'loginAuth']);
    Route::post('sel-reniec', [VeterinariaController::class, 'getReniec']);
    Route::post('sel-cextranjeria', [VeterinariaController::class, 'getCarnetExtranjeria']);
    // Route::post('sel-servicios', [VeterinariaController::class, 'getServicios']);
    Route::post('sel-allservicios', [VeterinariaController::class, 'getServicios']);
    Route::post('sel-fechas', [VeterinariaController::class, 'getFechasDisponibles']);
    Route::post('sel-horarios', [VeterinariaController::class, 'getHorariosDisponibles']);
    Route::post('sel-servicios', [VeterinariaController::class, 'getServiciosDisponibles']);
    Route::post('sel-razas', [VeterinariaController::class, 'getRazas']);
    Route::post('sel-reserva', [VeterinariaController::class, 'getReservaCita']);
    Route::post('ins-reserva', [VeterinariaController::class, 'insReservaCita']);
    Route::post('upd-reservaestado', [VeterinariaController::class, 'updReservaEstado']);
    Route::post('upd-liquidacionpago', [VeterinariaController::class, 'updLiquidacionPago']);

    Route::post('auth-login', [VeterinariaController::class, 'loginUser']);
});

Route::prefix('niubiz')->group(function () {
    Route::post('session-token', [NiubizController::class, 'createSessionToken']);
    Route::post('process-payment/{reserva_id}/{pay_amount}/{url_address}', [NiubizController::class, 'processPayment']);

    // Solo POST a /niubiz/process-payment
    // Route::post('/niubiz/process-payment', [NiubizController::class, 'processPayment']);
    // Route::post('/niubiz/process-payment', function (Request $request) {
    //     dd($request->all());
    // });
});

Route::prefix('reservas')->group(function () {
    Route::post('actualizar-pago/{reserva_id}', [ReservaController::class, 'actualizarEstadoPago']);
});
