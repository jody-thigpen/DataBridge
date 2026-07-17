<?php

namespace App\Mail;

use App\Models\ReportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidateIntakeInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ReportRequest $reportRequest,
        public readonly string $inviteUrl,
    ) {}

    public function envelope(): Envelope
    {
        $organizationName = $this->reportRequest->organization?->name ?? 'your prospective employer';

        return new Envelope(
            subject: "Background screening information requested by {$organizationName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.candidate-intake-invitation',
            with: [
                'reportRequest' => $this->reportRequest,
                'inviteUrl' => $this->inviteUrl,
                'organizationName' => $this->reportRequest->organization?->name ?? 'your prospective employer',
                'subjectName' => $this->reportRequest->subject_name,
            ],
        );
    }
}
