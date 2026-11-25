<?php

namespace App\Mail;

use App\Models\CompTimeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompTimeApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CompTimeRequest $compTime,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '[Approved Comp-Time] %s (%s day%s)',
                $this->compTime->user->name,
                $this->compTime->days_requested,
                $this->compTime->days_requested > 1 ? 's' : ''
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.comp-time-approved',
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        if ($this->compTime->approved_pdf_path) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->compTime->approved_pdf_path)
                ->as('approved-comp-time-'.$this->compTime->id.'.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
