<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Log;

class OpenTextMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(private string $openText) {}

    public function build()
    {
        $this->subject("IES La Encantá: notificacion del Banco de Libros");
        $text = $this->openText;
        return $this->view('emails.opentext', compact('text'));
    }
}
