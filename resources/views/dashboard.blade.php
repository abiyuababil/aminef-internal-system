@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<ol class="breadcrumb">
    <li>Overview</li>
    <li>/</li>
    <li>Dashboard</li>
</ol>
<h1 class="page-title">Dashboard</h1>

<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-label">Annual Leave Balance</div>
        <div class="stat-value">{{ rtrim(rtrim(number_format((float) $user->annual_leave_balance, 1), '0'), '.') }} <span style="font-size:14px;font-weight:500;color:#64748b;">days</span></div>
        <div class="stat-sub">Available for {{ now()->year }}</div>
    </div>
    <div class="stat-box amber">
        <div class="stat-label">My Pending Requests</div>
        <div class="stat-value">{{ $myPendingLeaves }}</div>
        <div class="stat-sub">Waiting for supervisor approval</div>
    </div>
    @if($user->canApprove())
        <div class="stat-box blue">
            <div class="stat-label">Leave Approval</div>
            <div class="stat-value">{{ $approvalQueueCount }}</div>
            <div class="stat-sub"><a href="{{ route('approvals.index') }}">Review pending leave approvals &rarr;</a></div>
        </div>
    @else
        <div class="stat-box blue">
            <div class="stat-label">Out Today (Team)</div>
            <div class="stat-value">{{ $todayOut->count() }}</div>
            <div class="stat-sub">{{ $todayOut->isEmpty() ? 'Full team available' : $todayOut->pluck('user.name')->implode(', ') }}</div>
        </div>
    @endif
</div>

<div class="aminef-card">
    <div class="aminef-card-header">
        <h2 class="aminef-card-title">Team Leave Calendar (Who's Out) &mdash; {{ $currentMonth->format('F Y') }}</h2>
        <div style="display:flex;gap:6px;align-items:center;">
            <a href="{{ route('dashboard', ['month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}" class="btn-action-uniform btn-action-neutral">&larr; Prev</a>
            <a href="{{ route('dashboard', ['month' => now()->format('Y-m')]) }}" class="btn-action-uniform btn-action-neutral">Today</a>
            <a href="{{ route('dashboard', ['month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}" class="btn-action-uniform btn-action-neutral">Next &rarr;</a>
        </div>
    </div>
    <div class="aminef-card-body" style="padding:14px;">
        @php
            $startDayOfWeek = $currentMonth->copy()->startOfMonth()->dayOfWeekIso; // 1 (Mon) .. 7 (Sun)
            $daysInMonth = $currentMonth->daysInMonth;
            $endDayOfWeek = $currentMonth->copy()->endOfMonth()->dayOfWeekIso;
        @endphp
        <div class="table-responsive">
        <div class="calendar-grid">
            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $idx => $dayName)
                <div class="cal-head {{ $idx >= 5 ? 'weekend' : '' }}">{{ $dayName }}</div>
            @endforeach

            @for($blank = 1; $blank < $startDayOfWeek; $blank++)
                <div class="cal-cell muted"></div>
            @endfor

            @for($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $dateObj = $currentMonth->copy()->day($day);
                    $dateKey = $dateObj->toDateString();
                    $isWeekend = $dateObj->isWeekend();
                    $holiday = $holidays->get($dateKey);
                    $events = $calendarEvents[$dateKey] ?? [];
                @endphp
                <div class="cal-cell {{ $isWeekend ? 'weekend' : '' }} {{ $dateObj->isToday() ? 'today' : '' }}">
                    <div class="cal-day-num">
                        <span>{{ $day }}</span>
                        @if($dateObj->isToday())
                            <span style="font-size:10px;background:#0077c8;color:#fff;padding:1px 6px;border-radius:999px;">Today</span>
                        @endif
                    </div>
                    @if($holiday)
                        <div class="cal-holiday" title="{{ $holiday->name }}">{{ $holiday->name }}</div>
                    @endif
                    @foreach($events as $ev)
                        @php
                            $evLeave = $ev['leave'];
                            $isHalf = $ev['is_half_day'];
                            $timeLabel = $ev['time_label'];
                        @endphp
                        <div class="cal-event {{ $evLeave->type }}" title="{{ $evLeave->user->name }} ({{ $evLeave->typeLabel() }}{{ $isHalf ? ' • Half-Day ' . $timeLabel : '' }})">
                            <span class="cal-event-name">{{ $evLeave->user->name }}</span>
                            @if($isHalf)
                                <span class="cal-event-time">Half-Day &bull; {{ $timeLabel }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endfor

            @for($trail = $endDayOfWeek; $trail < 7; $trail++)
                <div class="cal-cell muted"></div>
            @endfor
        </div>
        </div>
        <div style="display:flex;gap:16px;margin-top:10px;font-size:11.5px;color:#475569;">
            <span><span class="cal-event annual" style="display:inline-block;padding:1px 6px;">Annual Leave</span></span>
            <span><span class="cal-event comp_time" style="display:inline-block;padding:1px 6px;">Comp-Time</span></span>
            <span><span class="cal-event sick" style="display:inline-block;padding:1px 6px;">Sick Leave</span></span>
            <span><span class="cal-holiday" style="display:inline-block;padding:1px 6px;">National Holiday</span></span>
        </div>
    </div>
</div>

<div class="aminef-card">
    <div class="aminef-card-header">
        <h2 class="aminef-card-title">My Recent Leave Requests</h2>
        <a href="{{ route('leaves.index') }}" class="btn-action-uniform btn-action-view">View All</a>
    </div>
    <div class="aminef-card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-left">Leave Type</th>
                        <th class="text-center">Dates</th>
                        <th class="text-right">Working Days</th>
                        <th class="text-left">Supervisor</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLeaves as $i => $leave)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td class="text-left">
                                {{ $leave->typeLabel() }}
                                @if((float) $leave->total_days === 0.5 && !empty($leave->date_ranges[0]))
                                    <span class="status-badge status-badge-info" style="margin-left:4px;font-size:10.5px;">Half-Day {{ $leave->date_ranges[0]['start_time'] ?? '' }}&ndash;{{ $leave->date_ranges[0]['end_time'] ?? '' }}</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $leave->start_date->format('d M Y') }} &ndash; {{ $leave->end_date->format('d M Y') }}</td>
                            <td class="text-right"><strong>{{ $leave->total_days }}</strong></td>
                            <td class="text-left">{{ $leave->supervisor?->name ?? '-' }}</td>
                            <td class="text-center"><span class="status-badge {{ $leave->statusBadgeClass() }}">{{ $leave->statusLabel() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="table-empty-state">No leave requests submitted yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
