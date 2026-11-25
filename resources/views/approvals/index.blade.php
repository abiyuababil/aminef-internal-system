@extends('layouts.app')
@section('title', 'Leave Approval')

@section('content')
    <ol class="breadcrumb">
        <li>Leave Management</li>
        <li>/</li>
        <li>Leave Approval</li>
    </ol>
    <h1 class="page-title">Leave Approval</h1>

    <div class="aminef-card">
        <div class="aminef-card-header">
            <h2 class="aminef-card-title">Pending Leave Approvals</h2>
        </div>
        <div class="aminef-card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-left">Employee</th>
                                <th class="text-left">Type</th>
                                <th class="text-center">Dates</th>
                                <th class="text-right">Days</th>
                                <th class="text-left">Reason / PIC</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingLeaves as $i => $leave)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td class="text-left">
                                        <strong>{{ $leave->user->name }}</strong>
                                        <div style="font-size:11.5px;color:#64748b;">{{ $leave->user->department ?? '-' }}</div>
                                    </td>
                                    <td class="text-left">{{ $leave->typeLabel() }}</td>
                                    <td class="text-center">{{ $leave->start_date->format('d M Y') }} &ndash;
                                        {{ $leave->end_date->format('d M Y') }}</td>
                                    <td class="text-right"><strong>{{ $leave->total_days }}</strong></td>
                                    <td class="text-left">
                                        {{ $leave->reason }}
                                        @if($leave->proof_file_path)
                                            <div style="font-size:11.5px;margin-top:3px;"><a
                                                    href="{{ asset('storage/' . $leave->proof_file_path) }}" target="_blank"
                                                    style="color:#0077c8;font-weight:600;">View Weekend/Overtime Proof</a></div>
                                        @endif
                                        @if($leave->pic_handover)
                                            <div style="font-size:11.5px;color:#64748b;">PIC: {{ $leave->pic_handover }}</div>
                                        @endif
                                        @if($leave->cancel_reason)
                                            <div style="font-size:11.5px;color:#b45309;">Cancel reason: {{ $leave->cancel_reason }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center"><span
                                            class="status-badge {{ $leave->statusBadgeClass() }}">{{ $leave->statusLabel() }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div style="display:flex;gap:6px;justify-content:center;">
                                            <button type="button" class="btn-action-uniform btn-action-create"
                                                onclick="openApproveModal({{ Js::from(route('approvals.leaves.approve', $leave)) }}, {{ Js::from($leave->user->name) }})">Approve</button>
                                            <button type="button" class="btn-action-uniform btn-action-delete"
                                                onclick="openRejectModal({{ Js::from(route('approvals.leaves.reject', $leave)) }})">Reject</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="table-empty-state">No pending leave approvals.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Approve with Electronic Signature Modal -->
    <div class="aminef-modal-backdrop" id="approveModal">
        <div class="aminef-modal" style="max-width:500px;">
            <div class="aminef-modal-header">
                <h3 class="aminef-modal-title">Approve Request &mdash; <span id="approveTargetName"></span></h3>
                <button type="button" class="aminef-modal-close" onclick="closeModal('approveModal')">&times;</button>
            </div>
            <div class="aminef-modal-body">
                <form method="POST" id="approveForm">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Supervisor Electronic Signature <span class="text-danger">*</span></label>
                        @if($user->signature_path)
                            <div
                                style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;font-size:12px;">
                                Your saved profile signature will be embedded on the approved PDF:
                                <img src="{{ asset('storage/' . $user->signature_path) }}" alt="Saved Signature"
                                    style="display:block;max-height:60px;margin-top:6px;">
                            </div>
                        @else
                            <div class="sig-pad-wrap">
                                <canvas id="approveSigCanvas" class="sig-canvas"></canvas>
                            </div>
                            <input type="hidden" name="signature_data" id="approveSigInput">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
                                <span style="font-size:11.5px;color:#64748b;">Draw once; it will be saved to your profile
                                    automatically.</span>
                                <button type="button" id="approveSigClear" class="btn-secondary"
                                    style="padding:3px 10px;font-size:11.5px;">Clear</button>
                            </div>
                        @endif
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('approveModal')">Cancel</button>
                        <button type="submit" class="btn-save-green">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="aminef-modal-backdrop" id="rejectModal">
        <div class="aminef-modal" style="max-width:460px;">
            <div class="aminef-modal-header">
                <h3 class="aminef-modal-title">Reject Request</h3>
                <button type="button" class="aminef-modal-close" onclick="closeModal('rejectModal')">&times;</button>
            </div>
            <div class="aminef-modal-body">
                <form method="POST" id="rejectForm">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" rows="3" class="form-control" required
                            placeholder="Explain why this request is rejected"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
                        <button type="submit" class="btn-action-uniform btn-action-delete">Reject Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        initSignaturePad('approveSigCanvas', 'approveSigInput', 'approveSigClear');

        function openApproveModal(actionUrl, name) {
            document.getElementById('approveForm').action = actionUrl;
            document.getElementById('approveTargetName').textContent = name;
            openModal('approveModal');
        }

        function openRejectModal(actionUrl) {
            document.getElementById('rejectForm').action = actionUrl;
            openModal('rejectModal');
        }
    </script>
@endpush