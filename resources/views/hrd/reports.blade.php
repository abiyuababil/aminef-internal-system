@extends('layouts.app')
@section('title', 'Leave Recap')

@section('content')
<ol class="breadcrumb">
    <li>HRD Management</li>
    <li>/</li>
    <li>Leave Recap</li>
</ol>
<h1 class="page-title">Leave Recap ({{ $year }})</h1>

<div class="aminef-card">
    <div class="aminef-card-header">
        <h2 class="aminef-card-title">Annual Employee Leave Summary</h2>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <form method="GET" action="{{ route('hrd.reports') }}" style="display:inline-block;">
                <select name="year" class="form-control" style="height:30px;padding:2px 8px;" onchange="this.form.submit()">
                    @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <button type="button" class="btn-action-uniform btn-action-create" onclick="openModal('excelPreviewModal')">Preview Excel</button>
            <a href="{{ route('hrd.reports.pdf', ['year' => $year]) }}" target="_blank" class="btn-action-uniform btn-action-view">Preview PDF</a>
        </div>
    </div>
    <div class="aminef-card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-left">Employee Name</th>
                        <th class="text-left">Department</th>
                        <th class="text-right">Annual Used</th>
                        <th class="text-right">Annual Remaining</th>
                        <th class="text-right">Comp-Time Days</th>
                        <th class="text-right">Sick Days</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $i => $row)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td class="text-left"><strong>{{ $row['user']->name }}</strong></td>
                            <td class="text-left">{{ $row['user']->department ?? '-' }}</td>
                            <td class="text-right">{{ $row['annual_used'] }}</td>
                            <td class="text-right"><strong>{{ $row['user']->annual_leave_balance }}</strong></td>
                            <td class="text-right">{{ $row['comp_used'] }}</td>
                            <td class="text-right">{{ $row['sick_used'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Excel Sheet Preview Modal -->
<div class="aminef-modal-backdrop" id="excelPreviewModal">
    <div class="aminef-modal" style="max-width:900px;">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Excel Preview &mdash; AMINEF-Leave-Recap-{{ $year }}.xlsx</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('excelPreviewModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <div class="table-responsive" style="border:1px solid #cbd5e1;border-radius:4px;">
                <table class="data-table" style="font-family:Consolas, monospace;font-size:12px;">
                    <thead>
                        <tr style="background:#e2e8f0;">
                            <th class="text-center" style="width:36px;"></th>
                            <th class="text-center">A</th>
                            <th class="text-center">B</th>
                            <th class="text-center">C</th>
                            <th class="text-center">D</th>
                            <th class="text-center">E</th>
                            <th class="text-center">F</th>
                            <th class="text-center">G</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background:#f8fafc;font-weight:700;">
                            <td class="text-center" style="background:#e2e8f0;">1</td>
                            <td>Employee Name</td>
                            <td>Department</td>
                            <td>Role</td>
                            <td class="text-right">Annual Used</td>
                            <td class="text-right">Annual Remaining</td>
                            <td class="text-right">Comp-Time Days</td>
                            <td class="text-right">Sick Leave Days</td>
                        </tr>
                        @foreach($employees as $i => $row)
                            <tr>
                                <td class="text-center" style="background:#e2e8f0;font-weight:700;">{{ $i + 2 }}</td>
                                <td>{{ $row['user']->name }}</td>
                                <td>{{ $row['user']->department ?? '-' }}</td>
                                <td>{{ strtoupper($row['user']->role) }}</td>
                                <td class="text-right">{{ $row['annual_used'] }}</td>
                                <td class="text-right">{{ (float) $row['user']->annual_leave_balance }}</td>
                                <td class="text-right">{{ $row['comp_used'] }}</td>
                                <td class="text-right">{{ $row['sick_used'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('excelPreviewModal')">Close</button>
                <a href="{{ route('hrd.reports.excel', ['year' => $year]) }}" class="btn-action-uniform btn-action-create">Download .xlsx</a>
            </div>
        </div>
    </div>
</div>
@endsection
