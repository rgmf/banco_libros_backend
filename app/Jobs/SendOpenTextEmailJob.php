<?php

namespace App\Jobs;

use App\Mail\ErrorMail;
use App\Mail\OpenTextMail;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOpenTextEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Student $student,
        private string $text
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->student->email_mother && !$this->student->email_father) {
            Mail::to('bancodelibros@ieslaencanta.com')
                ->send(
                    new ErrorMail(
                        "No tenemos información de e-mails de este estudiante: " . 
                        $this->student->name . " " . $this->student->lastname1 . " " . $this->student->lastname2
                    )
                );
            return;
        }

        $recipients = [];

        if ($this->student->email_mother) {
            $recipients[] = $this->student->email_mother;
        }

        if ($this->student->email_father) {
            $recipients[] = $this->student->email_father;
        }

        Mail::to($recipients)
            ->bcc('bancodelibros@ieslaencanta.com')
            ->send(
                new OpenTextMail($this->text)
            );
    }
}
