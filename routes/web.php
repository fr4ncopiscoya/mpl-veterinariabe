<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\VCardController;
use App\Http\Controllers\MailingController;
use App\Http\Controllers\PideController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VeterinariaController;

Route::get('/', function () {
    return 'API REST - VET  ';
});

Route::prefix('veterinaria')->group(function () {
    // Route::post('login', [PideController::class, 'loginAuth']);
    Route::post('sel-reniec', [VeterinariaController::class, 'getReniec']);
    Route::post('sel-cextranjeria', [VeterinariaController::class, 'getCarnetExtranjeria']);
    // Route::post('sel-servicios', [VeterinariaController::class, 'getServicios']);
    Route::post('sel-fechas', [VeterinariaController::class, 'getFechasDisponibles']);
    Route::post('sel-horarios', [VeterinariaController::class, 'getHorariosDisponibles']);
    Route::post('sel-servicios', [VeterinariaController::class, 'getServiciosDisponibles']);
    Route::post('sel-razas', [VeterinariaController::class, 'getRazas']);
    Route::post('ins-reserva', [VeterinariaController::class, 'insReservaCita']);

    Route::post('acceso', [ReporteController::class, 'loginUser']);
});

Route::prefix('v1')->group(function () {
    // Route::post('vcard-member', [VCardController::class, 'vCardMemberSearch']);
    // Route::post('contact-message', [MailingController::class, 'webSendContactMail']);
    // Route::post('add-subscriber', [SubscriptionController::class, 'addSubscriber']);
});
