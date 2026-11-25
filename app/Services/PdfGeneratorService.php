<?php

namespace App\Services;

use App\Models\CompTimeRequest;
use App\Models\LeaveRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfGeneratorService
{
    public function generateLeavePdf(LeaveRequest $leave): string
    {
        $leave->loadMissing(['user', 'supervisor']);

        $staffSigDataUri = $this->toDataUri($leave->staff_signature_path);
        $supervisorSigDataUri = $this->toDataUri($leave->supervisor_signature_path);

        $pdf = Pdf::loadView('pdf.leave-form', [
            'leave' => $leave,
            'staffSigDataUri' => $staffSigDataUri,
            'supervisorSigDataUri' => $supervisorSigDataUri,
        ])->setPaper('a4');

        $path = 'approved_pdfs/leave_'.$leave->id.'.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        $leave->update(['approved_pdf_path' => $path]);

        return $path;
    }

    public function generateCompTimePdf(CompTimeRequest $compTime): string
    {
        $compTime->loadMissing(['user', 'supervisor']);

        $staffSigDataUri = $this->toDataUri($compTime->staff_signature_path);
        $supervisorSigDataUri = $this->toDataUri($compTime->supervisor_signature_path);

        $pdf = Pdf::loadView('pdf.comp-time-form', [
            'compTime' => $compTime,
            'staffSigDataUri' => $staffSigDataUri,
            'supervisorSigDataUri' => $supervisorSigDataUri,
        ])->setPaper('a4');

        $path = 'approved_pdfs/comptime_'.$compTime->id.'.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        $compTime->update(['approved_pdf_path' => $path]);

        return $path;
    }

    private function toDataUri(?string $storagePath): ?string
    {
        if (! $storagePath || ! Storage::disk('public')->exists($storagePath)) {
            return null;
        }
        $bytes = Storage::disk('public')->get($storagePath);

        return 'data:image/png;base64,'.base64_encode($bytes);
    }
}
