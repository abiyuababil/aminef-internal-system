@extends('layouts.app')
@section('title', 'All Employee Leaves')

@section('content')
<ol class="breadcrumb">
    <li>HRD Management</li>
    <li>/</li>
    <li>All Employee Leaves</li>
</ol>
<h1 class="page-title">All Employee Leaves</h1>

<div class="aminef-card">
    <div class="aminef-card-header">
        <h2 class="aminef-card-title">All Employee Leave Requests</h2>
    </div>
    <div class="aminef-card-body">
        <form method="GET" action="{{ route('hrd.leaves') }}" class="filter-toolbar">
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
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search employee or dept...">
                <button type="submit" class="btn-action-uniform btn-action-edit">Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-left">Employee</th>
                        <th class="text-left">Type</th>
                        <th class="text-center">Dates</th>
                        <th class="text-right">Days</th>
                        <th class="text-left">Supervisor</th>
                        <th class="text-center">Attachment / Proof</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Files</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $i => $leave)
                        <tr>
                            <td class="text-center">{{ $leaves->firstItem() + $i }}</td>
                            <td class="text-left">
                                <strong>{{ $leave->user->name }}</strong>
                                <div style="font-size:11.5px;color:#64748b;">{{ $leave->user->department ?? '-' }}</div>
                            </td>
                            <td class="text-left">{{ $leave->typeLabel() }}</td>
                            <td class="text-center">{{ $leave->start_date->format('d M Y') }} &ndash; {{ $leave->end_date->format('d M Y') }}</td>
                            <td class="text-right"><strong>{{ $leave->total_days }}</strong></td>
                            <td class="text-left">{{ $leave->supervisor?->name ?? '-' }}</td>
                            <td class="text-center">
                                @if($leave->proof_file_path)
                                    <a href="{{ asset('storage/' . $leave->proof_file_path) }}" target="_blank" style="color:#0077c8;font-weight:600;">View Proof</a>
                                @elseif($leave->medical_note_path)
                                    <a href="{{ asset('storage/' . $leave->medical_note_path) }}" target="_blank" style="color:#0077c8;font-weight:600;">View Note</a>
                                @elseif($leave->medical_note_status === 'pending_upload')
                                    <span class="status-badge status-badge-warning">Pending Note</span>
                                @else
                                    --
                                @endif
                            </td>
                            <td class="text-center"><span class="status-badge {{ $leave->statusBadgeClass() }}">{{ $leave->statusLabel() }}</span></td>
                            <td class="text-center">
                                @if($leave->status === 'approved')
                                    <a href="{{ route('leaves.pdf', $leave) }}" target="_blank" class="btn-action-uniform btn-action-view">Preview PDF</a>
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="table-empty-state">No leave records found.</td></tr>
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
@endsection
