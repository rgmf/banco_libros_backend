<?php

namespace App\Jobs;

use App\Mail\LendingMail;
use App\Mail\LendingErrorMail;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $studentId,
        private int $academicYearId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $student = Student::find($this->studentId);
        if (!$student->email_mother && !$student->email_father) {
            Mail::to('bancodelibros@ieslaencanta.com')
                ->send(new LendingErrorMail(
                    $this->studentId,
                    $this->academicYearId,
                    "No tenemos información de e-mails de este estudiante"
                ));
            return;
        }

        $recipients = [];

        if ($student->email_mother) {
            if ($student->email_mother !== "roman@letero.es") {
                return;
            }
            $recipients[] = $student->email_mother;
        }

        if ($student->email_father) {
            if ($student->email_father !== "rmartinezf@ieslaencanta.com") {
                return;
            }
            $recipients[] = $student->email_father;
        }

        Mail::to($recipients)
            ->bcc('bancodelibros@ieslaencanta.com')
            ->send(
                new LendingMail($this->studentId, $this->academicYearId)
            );
    }
}
