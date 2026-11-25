@extends('layouts.app')
@section('title', 'Comp-Time')

@section('content')
<ol class="breadcrumb">
    <li>Leave &amp; Comp-Time</li>
    <li>/</li>
    <li>Comp-Time</li>
</ol>
<h1 class="page-title">Comp-Time Requests (Overtime Replacement)</h1>

<div class="aminef-card">
    <div class="aminef-card-header">
        <h2 class="aminef-card-title">Your Comp-Time Balance: {{ $user->comp_time_balance }} day(s)</h2>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('comp-time.template') }}" class="btn-action-uniform btn-action-view">Download Template</a>
            <button type="button" class="btn-action-uniform btn-action-create" onclick="openModal('compTimeModal')">+ Add Request</button>
        </div>
    </div>
    <div class="aminef-card-body">
        <div class="alert alert-info" style="margin-bottom:14px;">
            <strong>How Comp-Time works:</strong> Download the template, fill in your overtime details, and upload the completed file here. Approved Comp-Time adds to your <strong>Comp-Time Balance</strong> and does <strong>not</strong> reduce Annual Leave.
        </div>

        <form method="GET" action="{{ route('comp-time.index') }}" class="filter-toolbar">
            <div class="filter-left">
                <span>Show:</span>
                <select name="limit" onchange="this.form.submit()">
                    @foreach([10, 25, 50, 100] as $opt)
                        <option value="{{ $opt }}" {{ $limit === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <span>entries</span>
                <select name="status" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="filter-right">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search reason...">
                <button type="submit" class="btn-action-uniform btn-action-edit">Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Overtime Date</th>
                        <th class="text-right">Days Earned</th>
                        <th class="text-left">Reason</th>
                        <th class="text-center">Uploaded Form</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($compTimes as $i => $ct)
                        <tr>
                            <td class="text-center">{{ $compTimes->firstItem() + $i }}</td>
                            <td class="text-center">{{ $ct->work_date->format('d M Y') }}</td>
                            <td class="text-right"><strong>{{ $ct->days_requested }}</strong></td>
                            <td class="text-left">
                                {{ \Illuminate\Support\Str::limit($ct->reason, 65) }}
                                @if($ct->rejection_reason)
                                    <div style="font-size:11.5px;color:#b91c1c;">Rejected: {{ $ct->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ asset('storage/' . $ct->template_file_path) }}" target="_blank" style="color:#0070ba;font-weight:600;">View File</a>
                            </td>
                            <td class="text-center"><span class="status-badge {{ $ct->statusBadgeClass() }}">{{ $ct->statusLabel() }}</span></td>
                            <td class="text-center">
                                @if($ct->status === 'approved' && $ct->approved_pdf_path)
                                    <a href="{{ route('comp-time.pdf', $ct) }}" class="btn-action-uniform btn-action-view">PDF</a>
                                @else
                                    <span style="color:#94a3b8;">--</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-empty-state">No Comp-Time requests submitted yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-footer">
            <div>Showing {{ $compTimes->firstItem() ?? 0 }} to {{ $compTimes->lastItem() ?? 0 }} of {{ $compTimes->total() }} entries</div>
            <div>{{ $compTimes->links('partials.pagination') }}</div>
        </div>
    </div>
</div>

<!-- Add Comp-Time Modal -->
<div class="aminef-modal-backdrop" id="compTimeModal">
    <div class="aminef-modal">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Submit Comp-Time Request</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('compTimeModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <form method="POST" action="{{ route('comp-time.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Overtime Work Date <span class="text-danger">*</span></label>
                        <input type="date" name="work_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Days Requested <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" min="0.5" max="30" name="days_requested" class="form-control" value="1" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Upload Filled Comp-Time Template (PDF/Word/Excel) <span class="text-danger">*</span></label>
                    <input type="file" name="template_file" class="form-control" required accept=".pdf,.doc,.docx,.xls,.xlsx">
                </div>
                <div class="form-group">
                    <label class="form-label">Overtime Summary / Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="3" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Electronic Signature <span class="text-danger">*</span></label>
                    @if($user->signature_path)
                        <div style="padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;font-size:12px;">
                            Using your saved profile signature.
                            <img src="{{ asset('storage/' . $user->signature_path) }}" alt="Signature" style="display:block;max-height:55px;margin-top:6px;">
                        </div>
                    @else
                        <div class="sig-pad-wrap">
                            <canvas id="ctSigCanvas" class="sig-canvas"></canvas>
                        </div>
                        <input type="hidden" name="signature_data" id="ctSigInput">
                        <button type="button" id="ctSigClear" class="btn-secondary" style="margin-top:6px;padding:3px 10px;font-size:11.5px;">Clear</button>
                    @endif
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('compTimeModal')">Cancel</button>
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    initSignaturePad('ctSigCanvas', 'ctSigInput', 'ctSigClear');
</script>
@endpush
