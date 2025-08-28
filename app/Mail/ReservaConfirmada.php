<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservaConfirmada extends Mailable
{
    use Queueable, SerializesModels;

    public $reserva;

    public function __construct($reserva)
    {
        $this->reserva = $reserva;
    }

    public function build()
    {
        return $this->from('no-reply@muniplibre.gob.pe', 'Veterinaria MPL')
            ->subject('Confirmación de tu reserva')
            ->view('emails.reserva_success'); // Blade puede usar $reserva directamente
    }
}
