<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PagoExtraConfirmado extends Mailable
{
    use Queueable, SerializesModels;

    public $pagoExtra;

    /**
     * Create a new message instance.
     */
    public function __construct($pagoExtra)
    {
        $this->pagoExtra = $pagoExtra;
    }

    public function build()
    {
        return $this->from('no-reply@muniplibre.gob.pe', 'Veterinaria MPL')
            ->subject('Confirmación de tu pago')
            ->view('emails.pagoextra_success');
    }
}
