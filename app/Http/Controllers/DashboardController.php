<?php

namespace App\Http\Controllers;

use App\Models\CompTimeRequest;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $monthParam = $request->query('month', now()->format('Y-m'));
        $currentMonth = Carbon::createFromFormat('Y-m', $monthParam)?->startOfMonth() ?? now()->startOfMonth();

        $monthStart = $currentMonth->copy()->startOfMonth();
        $monthEnd = $currentMonth->copy()->endOfMonth();

        // Who's Out approved leaves overlapping currentMonth
        $teamLeaves = LeaveRequest::with('user')
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->orderBy('start_date')
            ->get();

        $holidays = Holiday::whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn ($h) => $h->date->toDateString());

        // Map day -> events for calendar grid (respecting individual date_ranges)
        $calendarEvents = [];
        foreach ($teamLeaves as $leave) {
            $ranges = $leave->date_ranges;
            if (empty($ranges) || ! is_array($ranges)) {
                $ranges = [[
                    'start_date' => $leave->start_date->toDateString(),
                    'end_date' => $leave->end_date->toDateString(),
                ]];
            }

            foreach ($ranges as $r) {
                $rStart = Carbon::parse($r['start_date'])->startOfDay()->max($monthStart);
                $rEnd = Carbon::parse($r['end_date'])->startOfDay()->min($monthEnd);
                $isHalfDay = isset($r['days'])
                    ? ((float) $r['days'] === 0.5)
                    : ((float) $leave->total_days === 0.5);
                $timeLabel = ($r['start_time'] ?? '08:00').'–'.($r['end_time'] ?? '16:00');
                $cursor = $rStart->copy();
                while ($cursor->lte($rEnd)) {
                    $key = $cursor->toDateString();
                    $calendarEvents[$key][] = [
                        'leave' => $leave,
                        'is_half_day' => $isHalfDay,
                        'time_label' => $timeLabel,
                    ];
                    $cursor->addDay();
                }
            }
        }

        $myPendingLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $myPendingCompTime = CompTimeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $approvalQueueCount = 0;
        if ($user->canApprove()) {
            $subordinateRoleSlugs = Role::where('approver_role_slug', $user->role)->pluck('slug')->all();
            $approvalQueueCount = LeaveRequest::where('status', 'pending')
                ->where(function ($q) use ($user, $subordinateRoleSlugs) {
                    $q->where('supervisor_id', $user->id);
                    if (! empty($subordinateRoleSlugs)) {
                        $q->orWhere(function ($sub) use ($subordinateRoleSlugs) {
                            $sub->whereNull('supervisor_id')
                                ->whereHas('user', fn ($uq) => $uq->whereIn('role', $subordinateRoleSlugs));
                        });
                    }
                })
                ->count();
        }

        $recentLeaves = LeaveRequest::with('supervisor')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $todayOut = LeaveRequest::with('user')
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->get();

        return view('dashboard', compact(
            'user',
            'currentMonth',
            'calendarEvents',
            'holidays',
            'myPendingLeaves',
            'myPendingCompTime',
            'approvalQueueCount',
            'recentLeaves',
            'todayOut'
        ));
    }

    public function notifications(): View
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markNotificationRead(string $id): RedirectResponse
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('dashboard');

        return redirect($url);
    }

    public function markAllNotificationsRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    public function bulkNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['string'],
            'action' => ['required', 'in:read,unread'],
        ]);

        $query = Auth::user()->notifications()->whereIn('id', $validated['ids']);

        if ($validated['action'] === 'read') {
            $count = $query->update(['read_at' => now()]);

            return back()->with('success', "{$count} notification(s) marked as read.");
        }

        $count = $query->update(['read_at' => null]);

        return back()->with('success', "{$count} notification(s) marked as unread.");
    }
}
