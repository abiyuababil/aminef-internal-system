@extends('layouts.app')
@section('title', 'National Holidays')

@section('content')
<ol class="breadcrumb">
    <li>HRD Management</li>
    <li>/</li>
    <li>National Holidays</li>
</ol>
<h1 class="page-title">National Holidays &amp; Joint Leave ({{ $year }})</h1>

<div class="form-grid-2">
    <div class="aminef-card">
        <div class="aminef-card-header">
            <h2 class="aminef-card-title">Holiday List ({{ $year }})</h2>
            <div style="display:flex;gap:8px;align-items:center;">
                <form method="GET" action="{{ route('hrd.holidays') }}" style="display:inline-block;">
                    <select name="year" class="form-control" style="width:auto;" onchange="this.form.submit()">
                        @for($y = now()->year - 1; $y <= now()->year + 2; $y++)
                            <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </form>
                <a href="https://www.aminef.or.id/holiday-calendar-2026" target="_blank" class="btn-action-uniform btn-action-neutral">AMINEF Reference</a>
            </div>
        </div>
        <div class="aminef-card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="text-center">Date</th>
                            <th class="text-left">Holiday Name</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($holidays as $h)
                            <tr>
                                <td class="text-center">{{ $h->date->format('d M Y') }}</td>
                                <td class="text-left"><strong>{{ $h->name }}</strong></td>
                                <td class="text-center">{{ $h->type === 'joint_leave' ? 'Cuti Bersama' : 'National Holiday' }}</td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('hrd.holidays.destroy', $h) }}" onsubmit="return confirm('Remove this holiday?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action-uniform btn-action-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="table-empty-state">No holidays configured for {{ $year }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="aminef-card">
        <div class="aminef-card-header">
            <h2 class="aminef-card-title">Add Holiday / Cuti Bersama</h2>
        </div>
        <div class="aminef-card-body">
            <form method="POST" action="{{ route('hrd.holidays.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Holiday Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Eid al-Fitr">
                </div>
                <div class="form-group">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-control" required>
                        <option value="national_holiday">National Holiday</option>
                        <option value="joint_leave">Joint Leave (Cuti Bersama)</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
