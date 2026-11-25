@extends('layouts.app')
@section('title', 'Leave Requests')

@section('content')
<ol class="breadcrumb">
    <li>Leave Management</li>
    <li>/</li>
    <li>Leave Requests</li>
</ol>
<h1 class="page-title">My Leave Requests</h1>

<div class="aminef-card">
    <div class="aminef-card-header">
        <h2 class="aminef-card-title">Annual Leave Balance: {{ $user->annual_leave_balance }} day(s)</h2>
        <button type="button" class="btn-action-uniform btn-action-create" onclick="openModal('leaveModal')">
            + Add Request
        </button>
    </div>
    <div class="aminef-card-body">
        <form method="GET" action="{{ route('leaves.index') }}" class="filter-toolbar">
            <div class="filter-left">
                <span>Show:</span>
                <select name="limit" onchange="this.form.submit()">
                    @foreach([10, 25, 50, 100] as $opt)
                        <option value="{{ $opt }}" {{ $limit === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <span>entries</span>

                <select name="type" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="annual" {{ request('type') === 'annual' ? 'selected' : '' }}>Annual Leave</option>
                    <option value="comp_time" {{ request('type') === 'comp_time' ? 'selected' : '' }}>Compensatory Time Off</option>
                    <option value="leave_with_pay" {{ request('type') === 'leave_with_pay' ? 'selected' : '' }}>Leave With Pay</option>
                    <option value="leave_without_pay" {{ request('type') === 'leave_without_pay' ? 'selected' : '' }}>Leave Without Pay</option>
                    <option value="sick" {{ request('type') === 'sick' ? 'selected' : '' }}>Sick Leave</option>
                </select>

                <select name="status" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="filter-right">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search reason or PIC...">
                <button type="submit" class="btn-action-uniform btn-action-edit">Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-left">Leave Type</th>
                        <th class="text-center">Dates</th>
                        <th class="text-right">Days</th>
                        <th class="text-left">Reason &amp; PIC</th>
                        <th class="text-center">Attachment / Proof</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $i => $leave)
                        <tr>
                            <td class="text-center">{{ $leaves->firstItem() + $i }}</td>
                            <td class="text-left"><strong>{{ $leave->typeLabel() }}</strong></td>
                            <td class="text-center">{{ $leave->start_date->format('d M Y') }} &ndash; {{ $leave->end_date->format('d M Y') }}</td>
                            <td class="text-right"><strong>{{ $leave->total_days }}</strong></td>
                            <td class="text-left">
                                <div>{{ \Illuminate\Support\Str::limit($leave->reason, 60) }}</div>
                                @if($leave->pic_handover)
                                    <div style="font-size:11.5px;color:#64748b;">PIC: {{ $leave->pic_handover }}</div>
                                @endif
                                @if($leave->rejection_reason)
                                    <div style="font-size:11.5px;color:#b91c1c;">Rejected: {{ $leave->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($leave->type === 'comp_time')
                                    @if($leave->proof_file_path)
                                        <a href="{{ asset('storage/' . $leave->proof_file_path) }}" target="_blank" style="color:#0077c8;font-weight:600;">View Proof</a>
                                    @else
                                        <span style="color:#94a3b8;">--</span>
                                    @endif
                                @elseif($leave->medical_note_status === 'not_required')
                                    <span style="color:#94a3b8;">--</span>
                                @elseif($leave->medical_note_status === 'pending_upload')
                                    <button type="button" class="btn-action-uniform btn-action-amber" onclick="openUploadNoteModal({{ $leave->id }})">Upload Note</button>
                                @else
                                    <a href="{{ asset('storage/' . $leave->medical_note_path) }}" target="_blank" style="color:#0077c8;font-weight:600;">View Note</a>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="status-badge {{ $leave->statusBadgeClass() }}">{{ $leave->statusLabel() }}</span>
                            </td>
                            <td class="text-center">
                                <div style="display:flex;gap:5px;justify-content:center;flex-wrap:wrap;">
                                    @if($leave->status === 'approved')
                                        <a href="{{ route('leaves.pdf', $leave) }}" target="_blank" class="btn-action-uniform btn-action-view">Preview PDF</a>
                                        <a href="{{ route('leaves.ics', $leave) }}" class="btn-action-uniform btn-action-neutral">Outlook .ics</a>
                                    @elseif($leave->status === 'pending')
                                        <button type="button" class="btn-action-uniform btn-action-delete" onclick="openCancelModal({{ $leave->id }}, false)">Cancel</button>
                                    @else
                                        <span style="color:#94a3b8;">--</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="table-empty-state">No leave requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-footer">
            <div>Showing {{ $leaves->firstItem() ?? 0 }} to {{ $leaves->lastItem() ?? 0 }} of {{ $leaves->total() }} entries</div>
            <div>{{ $leaves->links('partials.pagination') }}</div>
        </div>
    </div>
</div>

<!-- Add Leave Request Modal -->
<div class="aminef-modal-backdrop" id="leaveModal">
    <div class="aminef-modal" style="max-width:680px;">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Submit Leave Request</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('leaveModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <form method="POST" action="{{ route('leaves.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select name="type" id="leaveTypeSelect" class="form-control" required onchange="toggleLeaveTypeFields()">
                            <option value="annual">Annual Leave (Balance: {{ $user->annual_leave_balance }} d)</option>
                            <option value="comp_time">Compensatory Time Off (Weekend / Overtime Proof)</option>
                            <option value="leave_with_pay">Leave With Pay</option>
                            <option value="leave_without_pay">Leave Without Pay</option>
                            <option value="sick">Sick Leave</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Purpose <span class="text-danger">*</span></label>
                        <select name="purpose" id="leavePurposeSelect" class="form-control" required>
                            <option value="other">Other</option>
                            <option value="medical">Medical/Dental/Optical examination or Treatment</option>
                            <option value="maternity">Maternity Leave</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">PIC Handover (Designated Responsible Individual)</label>
                    <input type="text" name="pic_handover" class="form-control" placeholder="Employee full name covering your duties">
                </div>

                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label class="form-label" style="margin:0;">Date &amp; Time Ranges <span class="text-danger">*</span></label>
                        <button type="button" class="btn-secondary" style="padding:3px 10px;font-size:11.5px;" onclick="addDateRangeRow()">+ Add Date Range</button>
                    </div>
                    <div id="dateRangesContainer" style="display:flex;flex-direction:column;gap:8px;">
                        <div class="date-range-row">
                            <div>
                                <div style="font-size:11px;color:#64748b;margin-bottom:3px;">From Date</div>
                                <input type="date" name="ranges[0][start_date]" class="form-control range-start" required onchange="handleRangeStartChange(this)">
                            </div>
                            <div>
                                <div style="font-size:11px;color:#64748b;margin-bottom:3px;">To Date</div>
                                <input type="date" name="ranges[0][end_date]" class="form-control range-end" required onchange="calculateWorkingDays()">
                            </div>
                            <div>
                                <div style="font-size:11px;color:#64748b;margin-bottom:3px;">From Time</div>
                                <select name="ranges[0][start_time]" class="form-control range-start-time" onchange="calculateWorkingDays()">
                                    @for($h = 7; $h <= 21; $h++)
                                        @foreach(['00', '30'] as $m)
                                            @php $t = sprintf('%02d:%s', $h, $m); @endphp
                                            <option value="{{ $t }}" {{ $t === '08:00' ? 'selected' : '' }}>{{ $t }}</option>
                                        @endforeach
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <div style="font-size:11px;color:#64748b;margin-bottom:3px;">To Time</div>
                                <select name="ranges[0][end_time]" class="form-control range-end-time" onchange="calculateWorkingDays()">
                                    @for($h = 7; $h <= 21; $h++)
                                        @foreach(['00', '30'] as $m)
                                            @php $t = sprintf('%02d:%s', $h, $m); @endphp
                                            <option value="{{ $t }}" {{ $t === '16:00' ? 'selected' : '' }}>{{ $t }}</option>
                                        @endforeach
                                    @endfor
                                </select>
                            </div>
                            <div class="date-range-remove-wrap" style="width:34px;"></div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info" id="daysPreviewBanner" style="display:none;margin-bottom:12px;">
                    Calculated Working Days (excluding weekends &amp; national holidays): <strong id="daysPreviewCount">0</strong> day(s).
                </div>
                <div class="alert alert-danger" id="balanceWarningBanner" style="display:none;margin-bottom:12px;"></div>

                <div class="form-group" id="medicalNoteGroup" style="display:none;">
                    <label class="form-label">Doctor's Note (Optional on submit for &gt; 1 day; can upload after returning to office)</label>
                    <input type="file" name="medical_note" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <div class="form-group" id="compTimeProofGroup" style="display:none;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;gap:8px;flex-wrap:wrap;">
                        <label class="form-label" style="margin:0;">Proof / Comp-Time Form <span class="text-danger">*</span></label>
                        <a href="{{ route('comp-time.template') }}" class="btn-action-uniform btn-action-view" style="height:26px;font-size:11.5px;padding:0 10px;">Download Comp-Time Template</a>
                    </div>
                    <input type="file" name="proof_file" id="compTimeProofInput" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                    <div style="font-size:11.5px;color:#64748b;margin-top:4px;">Download the template above, fill it in (or attach weekend/overtime proof), then upload it here. Does not reduce Annual Leave balance.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="3" class="form-control" required placeholder="Brief reason for leave"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Electronic Signature <span class="text-danger">*</span></label>
                    @if($user->signature_path)
                        <div style="padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;font-size:12px;color:#334155;">
                            Using your saved profile signature.
                            <img src="{{ asset('storage/' . $user->signature_path) }}" alt="Saved Signature" style="display:block;max-height:55px;margin-top:6px;">
                        </div>
                    @else
                        <div class="sig-pad-wrap">
                            <canvas id="leaveSigCanvas" class="sig-canvas"></canvas>
                        </div>
                        <input type="hidden" name="signature_data" id="leaveSigInput">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
                            <span style="font-size:11.5px;color:#64748b;">Draw once; it will be saved to your profile automatically.</span>
                            <button type="button" id="leaveSigClear" class="btn-secondary" style="padding:3px 10px;font-size:11.5px;">Clear</button>
                        </div>
                    @endif
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('leaveModal')">Cancel</button>
                    <button type="submit" id="leaveSaveBtn" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Medical Note Modal -->
<div class="aminef-modal-backdrop" id="uploadNoteModal">
    <div class="aminef-modal" style="max-width:460px;">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Upload Doctor's Note</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('uploadNoteModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <form method="POST" id="uploadNoteForm" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Doctor's Recommendation Letter (PDF/Image) <span class="text-danger">*</span></label>
                    <input type="file" name="medical_note" class="form-control" required accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('uploadNoteModal')">Cancel</button>
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Leave Modal -->
<div class="aminef-modal-backdrop" id="cancelModal">
    <div class="aminef-modal" style="max-width:460px;">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Cancel Leave Request</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('cancelModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <form method="POST" id="cancelLeaveForm">
                @csrf
                <p id="cancelNoticeText" style="font-size:13px;color:#334155;margin-top:0;"></p>
                <div class="form-group" id="cancelReasonWrap">
                    <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                    <textarea name="cancel_reason" id="cancelReasonInput" rows="3" class="form-control"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('cancelModal')">Close</button>
                    <button type="submit" class="btn-action-uniform btn-action-delete">Confirm Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    initSignaturePad('leaveSigCanvas', 'leaveSigInput', 'leaveSigClear');

    const annualLeaveBalance = {{ (float) $user->annual_leave_balance }};
    let lastCalculatedDays = 0;

    function validateLeaveBalanceUI() {
        const type = document.getElementById('leaveTypeSelect').value;
        const saveBtn = document.getElementById('leaveSaveBtn');
        const warnBanner = document.getElementById('balanceWarningBanner');

        if (type === 'annual' && lastCalculatedDays > annualLeaveBalance) {
            warnBanner.textContent = `Insufficient Annual Leave balance: you requested ${lastCalculatedDays} day(s), but only have ${annualLeaveBalance} day(s) remaining.`;
            warnBanner.style.display = 'block';
            saveBtn.disabled = true;
            saveBtn.style.opacity = '0.55';
            saveBtn.style.cursor = 'not-allowed';
        } else {
            warnBanner.style.display = 'none';
            saveBtn.disabled = false;
            saveBtn.style.opacity = '1';
            saveBtn.style.cursor = 'pointer';
        }
    }

    function toggleLeaveTypeFields() {
        const type = document.getElementById('leaveTypeSelect').value;
        document.getElementById('medicalNoteGroup').style.display = type === 'sick' ? 'block' : 'none';
        const ctGroup = document.getElementById('compTimeProofGroup');
        const ctInput = document.getElementById('compTimeProofInput');
        ctGroup.style.display = type === 'comp_time' ? 'block' : 'none';
        ctInput.required = (type === 'comp_time');

        const purposeSel = document.getElementById('leavePurposeSelect');
        if (type === 'sick') {
            purposeSel.value = 'medical';
        }
        validateLeaveBalanceUI();
    }

    function buildTimeOptions(selected) {
        let html = '';
        for (let h = 7; h <= 21; h++) {
            for (const m of ['00', '30']) {
                const t = String(h).padStart(2, '0') + ':' + m;
                html += `<option value="${t}" ${t === selected ? 'selected' : ''}>${t}</option>`;
            }
        }
        return html;
    }

    let rangeIndex = 1;
    function addDateRangeRow() {
        const container = document.getElementById('dateRangesContainer');
        if (container.querySelectorAll('.date-range-row').length >= 7) return;
        const idx = rangeIndex++;
        const row = document.createElement('div');
        row.className = 'date-range-row';
        row.innerHTML = `
            <div><input type="date" name="ranges[${idx}][start_date]" class="form-control range-start" required onchange="handleRangeStartChange(this)"></div>
            <div><input type="date" name="ranges[${idx}][end_date]" class="form-control range-end" required onchange="calculateWorkingDays()"></div>
            <div><select name="ranges[${idx}][start_time]" class="form-control range-start-time" onchange="calculateWorkingDays()">${buildTimeOptions('08:00')}</select></div>
            <div><select name="ranges[${idx}][end_time]" class="form-control range-end-time" onchange="calculateWorkingDays()">${buildTimeOptions('16:00')}</select></div>
            <div class="date-range-remove-wrap"><button type="button" class="btn-action-uniform btn-action-delete" style="min-width:34px;width:34px;height:33px;padding:0;" onclick="this.closest('.date-range-row').remove();calculateWorkingDays();">&times;</button></div>
        `;
        container.appendChild(row);
    }

    function handleRangeStartChange(startInput) {
        const row = startInput.closest('.date-range-row');
        const endInput = row?.querySelector('.range-end');
        if (endInput && startInput.value) {
            endInput.min = startInput.value;
            if (!endInput.value || endInput.value < startInput.value) {
                endInput.value = startInput.value;
            }
        }
        calculateWorkingDays();
    }

    async function calculateWorkingDays() {
        const rows = document.querySelectorAll('.date-range-row');
        const params = new URLSearchParams();
        let hasValid = false;
        rows.forEach((row, i) => {
            const s = row.querySelector('.range-start')?.value;
            const e = row.querySelector('.range-end')?.value;
            const st = row.querySelector('.range-start-time')?.value || '08:00';
            const et = row.querySelector('.range-end-time')?.value || '16:00';
            if (s && e && e >= s) {
                params.append(`ranges[${i}][start_date]`, s);
                params.append(`ranges[${i}][end_date]`, e);
                params.append(`ranges[${i}][start_time]`, st);
                params.append(`ranges[${i}][end_time]`, et);
                hasValid = true;
            }
        });
        if (!hasValid) return;

        const res = await fetch(`{{ route('leaves.preview-days') }}?${params.toString()}`);
        if (res.ok) {
            const data = await res.json();
            lastCalculatedDays = parseFloat(data.total_days) || 0;
            document.getElementById('daysPreviewCount').textContent = data.total_days;
            document.getElementById('daysPreviewBanner').style.display = 'block';
            validateLeaveBalanceUI();
        }
    }

    function openUploadNoteModal(leaveId) {
        document.getElementById('uploadNoteForm').action = `/leaves/${leaveId}/medical-note`;
        openModal('uploadNoteModal');
    }

    function openCancelModal(leaveId, wasApproved) {
        document.getElementById('cancelLeaveForm').action = `/leaves/${leaveId}/cancel`;
        const notice = document.getElementById('cancelNoticeText');
        const reasonInput = document.getElementById('cancelReasonInput');
        if (wasApproved) {
            notice.textContent = 'Are you sure you want to cancel this approved leave? Your annual leave balance will be restored and HRD will be notified.';
        } else {
            notice.textContent = 'Are you sure you want to cancel this pending leave request? HRD will be notified.';
        }
        reasonInput.required = false;
        openModal('cancelModal');
    }
</script>
@endpush
