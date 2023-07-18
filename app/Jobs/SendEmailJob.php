<?php

namespace App\Jobs;

use App\Mail\LendingMail;
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
        Mail::to('rgmf@riseup.net')->send(
            new LendingMail($this->studentId, $this->academicYearId)
        );
    }
}
