<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\Lending;

class LendingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private int $studentId, private int $academicYearId
    ) {}

    public function build()
    {
        $lendings = Lending::with('student')
            ->with('bookCopy')
            ->with('bookCopy.book')
            ->with('bookCopy.status')
            ->with('bookCopy.observations')
            ->where('student_id', $this->studentId)
            ->where('academic_year_id', $this->academicYearId)
            ->get();
        $this->subject("IES La Encantá: préstamo del Banco de Libros");
        return $this->view('emails.lending', compact('lendings'));
    }
}
