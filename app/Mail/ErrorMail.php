<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\Lending;

class ErrorMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(private string $errorMessage) {}

    public function build()
    {
        $this->subject("IES La Encantá: error al enviar información");
        $errorMessage = $this->errorMessage;
        return $this->view('emails.errormessaging', compact('errorMessage'));
    }
}