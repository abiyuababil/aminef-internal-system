<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use App\Services\OutlookIcsService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LeaveRequest $leave,
        public string $recipientRoleLabel = 'Staff',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '[Approved Leave] %s - %s (%s to %s)',
                $this->leave->user->name,
                $this->leave->typeLabel(),
                $this->leave->start_date->format('d M Y'),
                $this->leave->end_date->format('d M Y')
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave-approved',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        $icsContent = app(OutlookIcsService::class)->generateForLeave($this->leave);
        $attachments[] = Attachment::fromData(fn () => $icsContent, 'leave-calendar-'.$this->leave->id.'.ics')
            ->withMime('text/calendar; charset=utf-8; method=REQUEST');

        if ($this->leave->approved_pdf_path) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->leave->approved_pdf_path)
                ->as('approved-leave-form-'.$this->leave->id.'.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
