<?php

namespace App\Http\Controllers;

use App\Mail\CompTimeApprovedMail;
use App\Mail\LeaveApprovedMail;
use App\Models\AuditLog;
use App\Models\CompTimeRequest;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PortalNotification;
use App\Services\PdfGeneratorService;
use App\Services\SignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user->canApprove(), 403);

        $tab = $request->query('tab', 'leaves');

        $subordinateRoleSlugs = Role::where('approver_role_slug', $user->role)->pluck('slug')->all();

        $leaveQuery = LeaveRequest::with(['user', 'supervisor'])
            ->where('status', 'pending')
            ->where(function ($q) use ($user, $subordinateRoleSlugs) {
                $q->where('supervisor_id', $user->id);
                if (! empty($subordinateRoleSlugs)) {
                    $q->orWhere(function ($sub) use ($subordinateRoleSlugs) {
                        $sub->whereNull('supervisor_id')
                            ->whereHas('user', fn ($uq) => $uq->whereIn('role', $subordinateRoleSlugs));
                    });
                }
            });

        $compQuery = CompTimeRequest::with(['user', 'supervisor'])
            ->where('status', 'pending')
            ->where('supervisor_id', $user->id);

        $pendingLeaves = $leaveQuery->latest()->get();
        $pendingCompTimes = $compQuery->latest()->get();

        return view('approvals.index', compact('user', 'tab', 'pendingLeaves', 'pendingCompTimes'));
    }

    public function approveLeave(
        Request $request,
        LeaveRequest $leave,
        SignatureService $signatureService,
        PdfGeneratorService $pdfService
    ): RedirectResponse {
        $approver = Auth::user();
        $isAuthorized = $leave->supervisor_id
            ? $leave->supervisor_id === $approver->id
            : $approver->role === $leave->user->roleConfig()?->approver_role_slug;
        abort_unless($isAuthorized, 403, 'You are not the designated approver for this request.');
        abort_unless($leave->status === 'pending', 422, 'Leave request is not pending.');

        $signaturePath = $signatureService->resolveOrSave($approver, $request->input('signature_data'));
        if (! $signaturePath) {
            return back()->withErrors(['signature_data' => 'Please provide your electronic signature (it will be saved to your profile).']);
        }

        $insufficientBalance = false;
        DB::transaction(function () use ($leave, $approver, $signaturePath, &$insufficientBalance) {
            $requester = User::lockForUpdate()->findOrFail($leave->user_id);

            if ($leave->type === 'annual') {
                if ((float) $requester->annual_leave_balance < (float) $leave->total_days) {
                    $insufficientBalance = true;

                    return;
                }
                $requester->decrement('annual_leave_balance', (float) $leave->total_days);
            }

            $leave->update([
                'status' => 'approved',
                'supervisor_id' => $leave->supervisor_id ?? $approver->id,
                'supervisor_signature_path' => $signaturePath,
                'approved_at' => now(),
            ]);

            AuditLog::record($approver->id, 'leave_approved', 'leave_request', $leave->id);
        });

        if ($insufficientBalance) {
            return back()->withErrors(['balance' => 'Cannot approve: employee no longer has enough Annual Leave balance.']);
        }

        $leave->refresh()->load(['user', 'supervisor']);
        $pdfService->generateLeavePdf($leave);

        // Send Outlook .ics + signed PDF email to Staff, Supervisor, and HRD
        Mail::to($leave->user->email)->send(new LeaveApprovedMail($leave, 'Staff'));
        if ($leave->supervisor && $leave->supervisor->email !== $leave->user->email) {
            Mail::to($leave->supervisor->email)->send(new LeaveApprovedMail($leave, 'Supervisor'));
        }
        $hrdUsers = User::where('role', 'hrd')->get();
        foreach ($hrdUsers as $hrd) {
            if (! in_array($hrd->email, [$leave->user->email, $leave->supervisor?->email], true)) {
                Mail::to($hrd->email)->send(new LeaveApprovedMail($leave, 'HRD'));
            }
            $hrd->notify(new PortalNotification(
                'Approved Leave',
                "{$leave->user->name}'s {$leave->typeLabel()} ({$leave->start_date->format('d M Y')} - {$leave->end_date->format('d M Y')}) was approved by {$approver->name}.",
                route('hrd.leaves'),
                false
            ));
        }

        $leave->user->notify(new PortalNotification(
            'Leave Request Approved',
            "Your {$leave->typeLabel()} ({$leave->start_date->format('d M Y')} - {$leave->end_date->format('d M Y')}) has been approved by {$approver->name}.",
            route('leaves.index'),
            false
        ));

        return back()->with('success', 'Leave request approved, balance updated, PDF & Outlook Calendar invite sent.');
    }

    public function rejectLeave(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $approver = Auth::user();
        $isAuthorized = $leave->supervisor_id
            ? $leave->supervisor_id === $approver->id
            : $approver->role === $leave->user->roleConfig()?->approver_role_slug;
        abort_unless($isAuthorized, 403, 'You are not the designated approver for this request.');
        abort_unless($leave->status === 'pending', 422);

        $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $leave->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        AuditLog::record($approver->id, 'leave_rejected', 'leave_request', $leave->id, [
            'reason' => $request->rejection_reason,
        ]);

        $leave->user->notify(new PortalNotification(
            'Leave Request Rejected',
            "Your {$leave->typeLabel()} ({$leave->start_date->format('d M Y')} - {$leave->end_date->format('d M Y')}) was rejected. Reason: {$request->rejection_reason}",
            route('leaves.index')
        ));

        return back()->with('success', 'Leave request rejected.');
    }

    public function approveCancel(LeaveRequest $leave): RedirectResponse
    {
        $approver = Auth::user();
        $isAuthorized = $leave->supervisor_id
            ? $leave->supervisor_id === $approver->id
            : $approver->role === $leave->user->roleConfig()?->approver_role_slug;
        abort_unless($isAuthorized, 403, 'You are not the designated approver for this request.');
        abort_unless($leave->status === 'cancel_requested', 422);

        DB::transaction(function () use ($leave, $approver) {
            $requester = User::lockForUpdate()->findOrFail($leave->user_id);

            if ($leave->type === 'annual') {
                $requester->increment('annual_leave_balance', (float) $leave->total_days);
            }

            $leave->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            AuditLog::record($approver->id, 'leave_cancel_approved', 'leave_request', $leave->id);
        });

        $leave->user->notify(new PortalNotification(
            'Leave Cancellation Approved',
            "Your cancellation request for {$leave->typeLabel()} ({$leave->start_date->format('d M Y')} - {$leave->end_date->format('d M Y')}) has been approved and your balance has been restored.",
            route('leaves.index')
        ));

        return back()->with('success', 'Leave cancellation approved and balance restored.');
    }

    public function approveCompTime(
        Request $request,
        CompTimeRequest $compTime,
        SignatureService $signatureService,
        PdfGeneratorService $pdfService
    ): RedirectResponse {
        $approver = Auth::user();
        abort_unless($approver->isHrd() || $compTime->supervisor_id === $approver->id, 403);
        abort_unless($compTime->status === 'pending', 422);

        $signaturePath = $signatureService->resolveOrSave($approver, $request->input('signature_data'));
        if (! $signaturePath) {
            return back()->withErrors(['signature_data' => 'Please provide your electronic signature (it will be saved to your profile).']);
        }

        DB::transaction(function () use ($compTime, $approver, $signaturePath) {
            $requester = User::lockForUpdate()->findOrFail($compTime->user_id);
            // Comp-Time adds to comp_time_balance and NEVER reduces annual_leave_balance
            $requester->increment('comp_time_balance', (float) $compTime->days_requested);

            $compTime->update([
                'status' => 'approved',
                'supervisor_id' => $compTime->supervisor_id ?? $approver->id,
                'supervisor_signature_path' => $signaturePath,
                'approved_at' => now(),
            ]);

            AuditLog::record($approver->id, 'comp_time_approved', 'comp_time_request', $compTime->id);
        });

        $compTime->refresh()->load(['user', 'supervisor']);
        $pdfService->generateCompTimePdf($compTime);

        Mail::to($compTime->user->email)->send(new CompTimeApprovedMail($compTime));
        foreach (User::where('role', 'hrd')->get() as $hrd) {
            if ($hrd->email !== $compTime->user->email) {
                Mail::to($hrd->email)->send(new CompTimeApprovedMail($compTime));
            }
            $hrd->notify(new PortalNotification(
                'Approved Comp-Time',
                "{$compTime->user->name} earned {$compTime->days_requested} Comp-Time day(s), approved by {$approver->name}.",
                route('hrd.leaves'),
                false
            ));
        }

        $compTime->user->notify(new PortalNotification(
            'Comp-Time Request Approved',
            "Your Comp-Time request ({$compTime->days_requested} day(s)) was approved by {$approver->name} and added to your Comp-Time balance.",
            route('leaves.index', ['tab' => 'comp_time']),
            false
        ));

        return back()->with('success', 'Comp-Time approved, balance credited, and PDF sent.');
    }

    public function rejectCompTime(Request $request, CompTimeRequest $compTime): RedirectResponse
    {
        $approver = Auth::user();
        abort_unless($approver->isHrd() || $compTime->supervisor_id === $approver->id, 403);
        abort_unless($compTime->status === 'pending', 422);

        $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $compTime->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        AuditLog::record($approver->id, 'comp_time_rejected', 'comp_time_request', $compTime->id);

        $compTime->user->notify(new PortalNotification(
            'Comp-Time Request Rejected',
            "Your Comp-Time request was rejected. Reason: {$request->rejection_reason}",
            route('comp-time.index')
        ));

        return back()->with('success', 'Comp-Time request rejected.');
    }
}
