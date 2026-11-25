<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\PortalNotification;
use App\Services\OutlookIcsService;
use App\Services\PdfGeneratorService;
use App\Services\SignatureService;
use App\Services\WorkingDaysService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $limit = (int) $request->query('limit', 10);
        if (! in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }

        $query = LeaveRequest::with('supervisor')
            ->where('user_id', $user->id);

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhere('pic_handover', 'like', "%{$search}%");
            });
        }

        $leaves = $query->latest()->paginate($limit)->withQueryString();

        return view('leaves.index', compact('user', 'leaves', 'limit'));
    }

    public function previewDays(Request $request, WorkingDaysService $workingDays): JsonResponse
    {
        if ($request->has('ranges') && is_array($request->input('ranges'))) {
            $total = 0.0;
            foreach ($request->input('ranges') as $r) {
                if (! empty($r['start_date']) && ! empty($r['end_date']) && $r['end_date'] >= $r['start_date']) {
                    $total += $workingDays->calculateRange(
                        $r['start_date'],
                        $r['end_date'],
                        $r['start_time'] ?? null,
                        $r['end_time'] ?? null
                    );
                }
            }

            return response()->json(['total_days' => $total]);
        }

        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $days = $workingDays->calculate($request->start_date, $request->end_date);

        return response()->json(['total_days' => $days]);
    }

    public function store(
        Request $request,
        WorkingDaysService $workingDays,
        SignatureService $signatureService
    ): RedirectResponse {
        $user = Auth::user();

        $validated = $request->validate([
            'type' => ['required', 'in:annual,sick,comp_time,leave_with_pay,leave_without_pay'],
            'purpose' => ['nullable', 'in:medical,other,maternity'],
            'start_date' => ['required_without:ranges', 'nullable', 'date'],
            'end_date' => ['required_without:ranges', 'nullable', 'date', 'after_or_equal:start_date'],
            'ranges' => ['nullable', 'array', 'min:1', 'max:7'],
            'ranges.*.start_date' => ['required_with:ranges', 'date'],
            'ranges.*.end_date' => ['required_with:ranges', 'date', 'after_or_equal:ranges.*.start_date'],
            'ranges.*.start_time' => ['nullable', 'string', 'max:10'],
            'ranges.*.end_time' => ['nullable', 'string', 'max:10'],
            'reason' => ['required', 'string', 'max:1000'],
            'pic_handover' => ['nullable', 'string', 'max:120'],
            'medical_note' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'proof_file' => ['required_if:type,comp_time', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:20480'],
            'signature_data' => ['nullable', 'string'],
        ]);

        $rawRanges = $validated['ranges'] ?? [[
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]];

        $normalizedRanges = [];
        $totalDays = 0.0;
        $minStart = null;
        $maxEnd = null;

        foreach ($rawRanges as $range) {
            $startTime = $range['start_time'] ?: '08:00';
            $endTime = $range['end_time'] ?: '16:00';
            $days = $workingDays->calculateRange($range['start_date'], $range['end_date'], $startTime, $endTime);
            $totalDays += $days;
            $normalizedRanges[] = [
                'start_date' => $range['start_date'],
                'end_date' => $range['end_date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'days' => $days,
            ];
            $minStart = $minStart === null || $range['start_date'] < $minStart ? $range['start_date'] : $minStart;
            $maxEnd = $maxEnd === null || $range['end_date'] > $maxEnd ? $range['end_date'] : $maxEnd;
        }

        if ($totalDays <= 0) {
            return back()->withInput()->withErrors(['start_date' => 'Selected range has 0 working days (weekends/holidays only).']);
        }

        if ($validated['type'] === 'annual' && (float) $user->annual_leave_balance < $totalDays) {
            return back()->withInput()->withErrors(['type' => "Insufficient Annual Leave balance ({$user->annual_leave_balance} days remaining)."]);
        }

        $signaturePath = $signatureService->resolveOrSave($user, $validated['signature_data'] ?? null);
        if (! $signaturePath) {
            return back()->withInput()->withErrors(['signature_data' => 'Please provide your electronic signature (it will be saved to your profile for future requests).']);
        }

        $proofFilePath = null;
        if ($validated['type'] === 'comp_time' && $request->hasFile('proof_file')) {
            $proofFilePath = $request->file('proof_file')->store('comp_time_proofs', 'public');
        }

        $medicalNotePath = null;
        $medicalNoteStatus = 'not_required';
        if ($validated['type'] === 'sick' && $totalDays > 1) {
            if ($request->hasFile('medical_note')) {
                $medicalNotePath = $request->file('medical_note')->store('medical_notes', 'public');
                $medicalNoteStatus = 'uploaded';
            } else {
                // Allowed to submit first, upload doctor's note later
                $medicalNoteStatus = 'pending_upload';
            }
        } elseif ($validated['type'] === 'sick' && $request->hasFile('medical_note')) {
            $medicalNotePath = $request->file('medical_note')->store('medical_notes', 'public');
            $medicalNoteStatus = 'uploaded';
        }

        $supervisorId = $user->supervisor_id;
        if (! $supervisorId && ($approverRole = $user->roleConfig()?->approver_role_slug)) {
            $supervisorId = User::where('role', $approverRole)->where('is_active', true)->value('id');
        }
        if (! $supervisorId) {
            $supervisorId = User::whereIn('role', ['hrd', 'admin'])->where('id', '!=', $user->id)->value('id');
        }

        $purpose = $validated['purpose'] ?? ($validated['type'] === 'sick' ? 'medical' : 'other');

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'supervisor_id' => $supervisorId,
            'type' => $validated['type'],
            'purpose' => $purpose,
            'start_date' => $minStart,
            'end_date' => $maxEnd,
            'total_days' => $totalDays,
            'date_ranges' => $normalizedRanges,
            'reason' => $validated['reason'],
            'pic_handover' => $validated['pic_handover'] ?? null,
            'medical_note_path' => $medicalNotePath,
            'medical_note_status' => $medicalNoteStatus,
            'proof_file_path' => $proofFilePath,
            'staff_signature_path' => $signaturePath,
            'status' => 'pending',
        ]);

        AuditLog::record($user->id, 'leave_submitted', 'leave_request', $leave->id, [
            'type' => $leave->type,
            'total_days' => $leave->total_days,
        ]);

        if ($supervisor = User::find($supervisorId)) {
            $supervisor->notify(new PortalNotification(
                'New Leave Request',
                "{$user->name} submitted a {$leave->typeLabel()} request ({$leave->start_date->format('d M Y')} - {$leave->end_date->format('d M Y')}, {$leave->total_days} day(s)).",
                route('approvals.index')
            ));
        }

        // For Sick Leave > 1 day, immediately inform HRD as well
        if ($leave->type === 'sick' && $leave->total_days > 1) {
            $hrdUsers = User::where('role', 'hrd')->where('id', '!=', $supervisorId)->get();
            foreach ($hrdUsers as $hrd) {
                $hrd->notify(new PortalNotification(
                    'Sick Leave (>1 Day) Notice',
                    "{$user->name} submitted Sick Leave for {$leave->total_days} days ({$leave->start_date->format('d M Y')} - {$leave->end_date->format('d M Y')}). Doctor's note status: {$leave->medical_note_status}.",
                    route('hrd.leaves')
                ));
            }
        }

        return redirect()->route('leaves.index')->with('success', 'Leave request submitted to your supervisor.');
    }

    public function uploadMedicalNote(Request $request, LeaveRequest $leave): RedirectResponse
    {
        abort_unless($leave->user_id === Auth::id(), 403);

        $request->validate([
            'medical_note' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($leave->medical_note_path && Storage::disk('public')->exists($leave->medical_note_path)) {
            Storage::disk('public')->delete($leave->medical_note_path);
        }

        $path = $request->file('medical_note')->store('medical_notes', 'public');
        $leave->update([
            'medical_note_path' => $path,
            'medical_note_status' => 'uploaded',
        ]);

        AuditLog::record(Auth::id(), 'medical_note_uploaded', 'leave_request', $leave->id);

        return back()->with('success', 'Medical note uploaded successfully.');
    }

    public function cancel(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($leave->user_id === $user->id, 403);

        if ($leave->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending leave requests can be cancelled.']);
        }

        $cancelReason = trim((string) $request->input('cancel_reason', ''));

        $leave->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => $cancelReason ?: 'Cancelled by staff',
        ]);

        AuditLog::record($user->id, 'leave_cancelled', 'leave_request', $leave->id, [
            'reason' => $leave->cancel_reason,
        ]);

        $hrdUsers = User::where('role', 'hrd')->get();
        foreach ($hrdUsers as $hrd) {
            $hrd->notify(new PortalNotification(
                'Leave Cancelled',
                "{$user->name} cancelled their pending {$leave->typeLabel()} ({$leave->start_date->format('d M Y')} - {$leave->end_date->format('d M Y')}).".($cancelReason ? " Reason: {$cancelReason}" : ''),
                route('hrd.leaves')
            ));
        }

        return back()->with('success', 'Pending leave request cancelled.');
    }

    public function downloadPdf(LeaveRequest $leave, PdfGeneratorService $pdfService): BinaryFileResponse
    {
        $user = Auth::user();
        abort_unless($leave->user_id === $user->id || $leave->supervisor_id === $user->id || $user->isHrd() || $user->isAdmin(), 403);
        abort_unless($leave->status === 'approved', 404);

        $path = $pdfService->generateLeavePdf($leave);
        $fullPath = Storage::disk('public')->path($path);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="approved-leave-'.$leave->id.'.pdf"',
        ]);
    }

    public function downloadIcs(LeaveRequest $leave, OutlookIcsService $icsService): Response
    {
        $user = Auth::user();
        abort_unless($leave->user_id === $user->id || $leave->supervisor_id === $user->id || $user->isHrd(), 403);
        abort_unless($leave->status === 'approved', 404);

        $ics = $icsService->generateForLeave($leave);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="leave-calendar-'.$leave->id.'.ics"',
        ]);
    }
}
