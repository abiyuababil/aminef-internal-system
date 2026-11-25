<?php

namespace App\Services;

use App\Models\LeaveRequest;

class OutlookIcsService
{
    /**
     * Generate an RFC 5545 .ics calendar invite for an approved leave request.
     */
    public function generateForLeave(LeaveRequest $leave): string
    {
        $leave->loadMissing(['user', 'supervisor']);

        $uid = 'leave-'.$leave->id.'@'.parse_url(config('app.url'), PHP_URL_HOST ?: 'aminef.local');
        $dtStamp = now()->utc()->format('Ymd\THis\Z');
        $dtStart = $leave->start_date->format('Ymd');
        // All-day DTEND in iCalendar is exclusive (+1 day)
        $dtEnd = $leave->end_date->copy()->addDay()->format('Ymd');

        $summary = sprintf('[%s] %s (%s day%s)',
            $leave->typeLabel(),
            $leave->user->name,
            rtrim(rtrim(number_format((float) $leave->total_days, 1), '0'), '.'),
            $leave->total_days > 1 ? 's' : ''
        );

        $descriptionLines = [
            'Employee: '.$leave->user->name.' ('.($leave->user->department ?? '-').')',
            'Leave Type: '.$leave->typeLabel(),
            'Dates: '.$leave->start_date->format('d M Y').' - '.$leave->end_date->format('d M Y'),
            'Total Working Days: '.$leave->total_days,
            'PIC Handover: '.($leave->pic_handover ?: '-'),
            'Reason: '.str_replace(["\r\n", "\n", "\r"], ' ', $leave->reason),
            'Approved By: '.($leave->supervisor?->name ?? 'Supervisor'),
        ];
        $description = implode('\n', $descriptionLines);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AMINEF Staff Portal//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.$dtStamp,
            'DTSTART;VALUE=DATE:'.$dtStart,
            'DTEND;VALUE=DATE:'.$dtEnd,
            'SUMMARY:'.$this->escapeText($summary),
            'DESCRIPTION:'.$this->escapeText($description),
            'STATUS:CONFIRMED',
            'TRANSP:OPAQUE',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    private function escapeText(string $text): string
    {
        return str_replace(['\\', ';', ','], ['\\\\', '\;', '\,'], $text);
    }
}
