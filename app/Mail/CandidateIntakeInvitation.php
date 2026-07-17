<?php

namespace App\Mail;

use App\Models\ReportOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidateIntakeInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ReportOrder $reportOrder,
        public readonly string $inviteUrl,
    ) {}

    public function envelope(): Envelope
    {
        $organizationName = $this->reportOrder->organization?->name ?? 'your prospective employer';

        return new Envelope(
            subject: "Background screening information requested by {$organizationName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.candidate-intake-invitation',
            with: [
                'reportOrder' => $this->reportOrder,
                'inviteUrl' => $this->inviteUrl,
                'organizationName' => $this->reportOrder->organization?->name ?? 'your prospective employer',
                'subjectName' => $this->reportOrder->subject_name,
            ],
        );
    }
}
