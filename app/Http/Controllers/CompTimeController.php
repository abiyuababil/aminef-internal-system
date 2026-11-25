<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CompTimeRequest;
use App\Models\DocumentTemplate;
use App\Models\User;
use App\Notifications\PortalNotification;
use App\Services\SignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompTimeController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('leaves.index', ['tab' => 'comp_time']);
    }

    public function store(Request $request, SignatureService $signatureService): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'work_date' => ['required', 'date'],
            'days_requested' => ['required', 'numeric', 'min:0.5', 'max:30'],
            'reason' => ['required', 'string', 'max:1000'],
            'template_file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
            'signature_data' => ['nullable', 'string'],
        ]);

        $signaturePath = $signatureService->resolveOrSave($user, $validated['signature_data'] ?? null);
        if (! $signaturePath) {
            return back()->withInput()->withErrors(['signature_data' => 'Please provide your electronic signature (it will be saved to your profile).']);
        }

        $templateFilePath = $request->file('template_file')->store('comp_time_submissions', 'public');
        $supervisorId = $user->supervisor_id ?: User::where('role', 'hrd')->value('id');

        $compTime = CompTimeRequest::create([
            'user_id' => $user->id,
            'supervisor_id' => $supervisorId,
            'work_date' => $validated['work_date'],
            'days_requested' => $validated['days_requested'],
            'template_file_path' => $templateFilePath,
            'reason' => $validated['reason'],
            'staff_signature_path' => $signaturePath,
            'status' => 'pending',
        ]);

        AuditLog::record($user->id, 'comp_time_submitted', 'comp_time_request', $compTime->id, [
            'days_requested' => $compTime->days_requested,
        ]);

        if ($supervisor = User::find($supervisorId)) {
            $supervisor->notify(new PortalNotification(
                'New Comp-Time Request',
                "{$user->name} submitted a Comp-Time request for {$compTime->days_requested} day(s) (overtime date: {$compTime->work_date->format('d M Y')}).",
                route('approvals.index')
            ));
        }

        return redirect()->route('leaves.index', ['tab' => 'comp_time'])->with('success', 'Comp-Time request submitted to your supervisor.');
    }

    public function downloadTemplate(): Response
    {
        $template = DocumentTemplate::where('key', 'comp_time_template')->first();

        if ($template && Storage::disk('public')->exists($template->file_path)) {
            return Storage::disk('public')->download($template->file_path, $template->original_name);
        }

        // Clean fallback template if HRD has not uploaded a custom file yet
        $fallback = implode("\r\n", [
            'AMINEF - COMP-TIME REQUEST FORM (TEMPLATE)',
            '=========================================',
            'Employee Name   :',
            'Department      :',
            'Supervisor Name :',
            'Overtime Date   :',
            'Total Hours/Days:',
            'Task / Reason   :',
            '',
            'Please fill in the details above, save this file, and upload it in the Comp-Time menu.',
        ]);

        return response($fallback, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="AMINEF-Comp-Time-Template.txt"',
        ]);
    }

    public function downloadPdf(CompTimeRequest $compTime): StreamedResponse
    {
        $user = Auth::user();
        abort_unless($compTime->user_id === $user->id || $compTime->supervisor_id === $user->id || $user->isHrd(), 403);
        abort_unless($compTime->approved_pdf_path && Storage::disk('public')->exists($compTime->approved_pdf_path), 404);

        return Storage::disk('public')->download($compTime->approved_pdf_path, 'approved-comp-time-'.$compTime->id.'.pdf');
    }
}
