<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — AMINEF Staff Portal</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/aminef.css') }}">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
@php
    $authUser = auth()->user();
    $unreadCount = $authUser->unreadNotifications()->count();
    $recentNotifications = $authUser->notifications()->latest()->limit(5)->get();
    $pendingApprovalBadge = 0;
    if ($authUser->canApprove()) {
        $subordinateRoleSlugs = \App\Models\Role::where('approver_role_slug', $authUser->role)->pluck('slug')->all();
        $pendingApprovalBadge = \App\Models\LeaveRequest::where('status', 'pending')
            ->where(function ($q) use ($authUser, $subordinateRoleSlugs) {
                $q->where('supervisor_id', $authUser->id);
                if (! empty($subordinateRoleSlugs)) {
                    $q->orWhere(function ($sub) use ($subordinateRoleSlugs) {
                        $sub->whereNull('supervisor_id')
                            ->whereHas('user', fn ($uq) => $uq->whereIn('role', $subordinateRoleSlugs));
                    });
                }
            })
            ->count();
    }
@endphp

<header class="topbar">
    <div style="display:flex;align-items:center;">
        <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open Navigation">
            <i data-lucide="menu" style="width:18px;height:18px;"></i>
        </button>
        <a href="{{ route('dashboard') }}" class="brand-lockup">
            <img src="{{ asset('images/logo-aminef-2.png') }}" alt="AMINEF" style="height:30px;width:auto;object-fit:contain;">
            <span class="brand-dot">&bull;</span>
            <span class="brand-sub">Staff Portal</span>
        </a>
    </div>

    <div class="topbar-right">
        <div class="dropdown-wrap">
            <button type="button" class="notif-btn" id="notifToggleBtn" aria-label="Notifications">
                <i data-lucide="bell" style="width:16px;height:16px;"></i>
                @if($unreadCount > 0)
                    <span class="notif-badge">{{ $unreadCount }}</span>
                @endif
            </button>
            <div class="flyout-card" id="notifFlyout">
                <div class="flyout-header">
                    <span>Notifications @if($unreadCount > 0)<span style="background:#0077c8;color:#fff;padding:1px 6px;border-radius:999px;font-size:10px;margin-left:4px;">{{ $unreadCount }}</span>@endif</span>
                    @if($unreadCount > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" style="background:none;border:none;color:#0077c8;font-size:11px;font-weight:600;cursor:pointer;">Mark all read</button>
                        </form>
                    @endif
                </div>
                <div class="notif-scroll">
                    @forelse($recentNotifications as $notif)
                        <form method="POST" action="{{ route('notifications.read', $notif->id) }}">
                            @csrf
                            <button type="submit" class="notif-item {{ $notif->read_at ? 'read' : '' }}">
                                <div class="notif-item-icon">
                                    <i data-lucide="bell-ring" style="width:14px;height:14px;"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div class="notif-item-title">{{ $notif->data['title'] ?? 'Notification' }}</div>
                                    <div class="notif-item-desc">{{ \Illuminate\Support\Str::limit($notif->data['message'] ?? '', 95) }}</div>
                                    <div class="notif-item-time">{{ $notif->created_at->diffForHumans() }}</div>
                                </div>
                                @if(! $notif->read_at)
                                    <span class="notif-unread-dot" title="Unread"></span>
                                @endif
                            </button>
                        </form>
                    @empty
                        <div style="padding:24px 18px;text-align:center;color:#94a3b8;font-size:12px;">No notifications yet.</div>
                    @endforelse
                </div>
                <div class="flyout-footer">
                    <a href="{{ route('notifications.index') }}">Show All Notification</a>
                </div>
            </div>
        </div>

        <div class="topbar-divider"></div>

        <div class="dropdown-wrap">
            <button type="button" class="profile-trigger" id="profileToggleBtn">
                <div class="profile-text-wrap">
                    <div class="profile-greet">Welcome {{ $authUser->name }}</div>
                    <div class="profile-sub">{{ strtoupper($authUser->role) }} &bull; {{ $authUser->department ?? 'AMINEF Office' }}</div>
                </div>
                <div class="avatar-circle">{{ $authUser->initials() }}</div>
                <i data-lucide="chevron-down" class="profile-chevron" style="width:14px;height:14px;"></i>
            </button>
            <div class="flyout-card" id="profileFlyout" style="width:240px;">
                <div style="padding:14px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #e2e8f0;">
                    <div class="avatar-circle" style="width:38px;height:38px;font-size:13px;">{{ $authUser->initials() }}</div>
                    <div style="min-width:0;">
                        <div style="font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $authUser->name }}</div>
                        <div style="font-size:11.5px;color:#64748b;">{{ $authUser->position ?? strtoupper($authUser->role) }}</div>
                    </div>
                </div>
                <a href="{{ route('profile.index') }}" class="profile-menu-item">
                    <i data-lucide="user" style="width:15px;height:15px;color:#0070ba;"></i>
                    My Profile
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="profile-menu-item" style="border-top:1px solid #f1f5f9;">
                        <i data-lucide="log-out" style="width:15px;height:15px;color:#dc2626;"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<div class="mobile-sidebar-backdrop" id="mobileSidebarBackdrop"></div>
<div class="app-shell">
    <aside class="sidebar" id="portalSidebar">
        <button type="button" class="sidebar-nav-toggle-row" id="sidebarToggleBtn" title="Toggle Navigation">
            <span class="sidebar-nav-toggle-left">
                <i data-lucide="menu" style="width:16px;height:16px;"></i>
                <span class="sidebar-nav-label">Main Navigation</span>
            </span>
            <i data-lucide="chevrons-left" class="sidebar-collapse-arrow" style="width:15px;height:15px;color:#64748b;"></i>
        </button>

        <div class="nav-category">Overview</div>
        @if($authUser->hasMenuAccess('dashboard'))
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                <i data-lucide="layout-dashboard" style="width:15px;height:15px;"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        @endif
        @if($authUser->hasMenuAccess('profile'))
            <a href="{{ route('profile.index') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" title="My Profile">
                <i data-lucide="user-circle" style="width:15px;height:15px;"></i>
                <span class="nav-text">My Profile</span>
            </a>
        @endif

        @if($authUser->hasMenuAccess('leaves') || $authUser->hasMenuAccess('approvals'))
            <div class="nav-category">Leave Management</div>
            @if($authUser->hasMenuAccess('leaves'))
                <a href="{{ route('leaves.index') }}" class="nav-link {{ request()->routeIs('leaves.*') || request()->routeIs('comp-time.*') ? 'active' : '' }}" title="Leave Requests">
                    <i data-lucide="calendar-days" style="width:15px;height:15px;"></i>
                    <span class="nav-text">Leave Requests</span>
                </a>
            @endif
            @if($authUser->hasMenuAccess('approvals'))
                <a href="{{ route('approvals.index') }}" class="nav-link {{ request()->routeIs('approvals.*') ? 'active' : '' }}" title="Leave Approval">
                    <i data-lucide="check-square" style="width:15px;height:15px;"></i>
                    <span class="nav-text">Leave Approval</span>
                    @if($pendingApprovalBadge > 0)
                        <span class="nav-badge">{{ $pendingApprovalBadge }}</span>
                    @endif
                </a>
            @endif
        @endif

        @if($authUser->hasMenuAccess('hrd_leaves') || $authUser->hasMenuAccess('hrd_employees') || $authUser->hasMenuAccess('hrd_holidays') || $authUser->hasMenuAccess('hrd_reports') || $authUser->hasMenuAccess('hrd_templates'))
            <div class="nav-category">HRD Management</div>
            @if($authUser->hasMenuAccess('hrd_leaves'))
                <a href="{{ route('hrd.leaves') }}" class="nav-link {{ request()->routeIs('hrd.leaves') ? 'active' : '' }}" title="All Employee Leaves">
                    <i data-lucide="clipboard-list" style="width:15px;height:15px;"></i>
                    <span class="nav-text">All Employee Leaves</span>
                </a>
            @endif
            @if($authUser->hasMenuAccess('hrd_employees'))
                <a href="{{ route('hrd.employees') }}" class="nav-link {{ request()->routeIs('hrd.employees*') ? 'active' : '' }}" title="Employees & Balances">
                    <i data-lucide="users" style="width:15px;height:15px;"></i>
                    <span class="nav-text">Employees &amp; Balances</span>
                </a>
            @endif
            @if($authUser->hasMenuAccess('hrd_holidays'))
                <a href="{{ route('hrd.holidays') }}" class="nav-link {{ request()->routeIs('hrd.holidays*') ? 'active' : '' }}" title="National Holidays">
                    <i data-lucide="calendar-off" style="width:15px;height:15px;"></i>
                    <span class="nav-text">National Holidays</span>
                </a>
            @endif
            @if($authUser->hasMenuAccess('hrd_reports'))
                <a href="{{ route('hrd.reports') }}" class="nav-link {{ request()->routeIs('hrd.reports*') ? 'active' : '' }}" title="Leave Recap">
                    <i data-lucide="file-spreadsheet" style="width:15px;height:15px;"></i>
                    <span class="nav-text">Leave Recap</span>
                </a>
            @endif
            @if($authUser->hasMenuAccess('hrd_templates'))
                <a href="{{ route('hrd.templates') }}" class="nav-link {{ request()->routeIs('hrd.templates*') ? 'active' : '' }}" title="Templates">
                    <i data-lucide="file-text" style="width:15px;height:15px;"></i>
                    <span class="nav-text">Templates</span>
                </a>
            @endif
        @endif

        @if($authUser->hasMenuAccess('admin_roles') || $authUser->hasMenuAccess('admin_staff'))
            <div class="nav-category">System Admin</div>
            @if($authUser->hasMenuAccess('admin_roles'))
                <a href="{{ route('admin.roles') }}" class="nav-link {{ request()->routeIs('admin.roles*') ? 'active' : '' }}" title="Role & Menu Access">
                    <i data-lucide="shield" style="width:15px;height:15px;"></i>
                    <span class="nav-text">Role &amp; Menu Access</span>
                </a>
            @endif
            @if($authUser->hasMenuAccess('admin_staff'))
                <a href="{{ route('admin.staff') }}" class="nav-link {{ request()->routeIs('admin.staff*') ? 'active' : '' }}" title="Master Data Staff">
                    <i data-lucide="user-cog" style="width:15px;height:15px;"></i>
                    <span class="nav-text">Master Data Staff</span>
                </a>
            @endif
        @endif
    </aside>

    <main class="main-content">
        @yield('content')
    </main>
</div>

<div class="toast-container" id="toastContainer" aria-live="polite">
    @if(session('success'))
        <div class="toast-item toast-success" data-auto-dismiss="4500">
            <i data-lucide="check-circle-2" class="toast-icon" style="width:18px;height:18px;color:#16a34a;"></i>
            <div class="toast-body">
                <div class="toast-title">Success</div>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="toast-close" onclick="dismissToast(this.closest('.toast-item'))">&times;</button>
            <div class="toast-progress"></div>
        </div>
    @endif
    @if($errors->any())
        <div class="toast-item toast-error" data-auto-dismiss="6000">
            <i data-lucide="alert-circle" class="toast-icon" style="width:18px;height:18px;color:#dc2626;"></i>
            <div class="toast-body">
                <div class="toast-title">Action Failed</div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
            <button type="button" class="toast-close" onclick="dismissToast(this.closest('.toast-item'))">&times;</button>
            <div class="toast-progress"></div>
        </div>
    @endif
</div>

<script>
    lucide.createIcons({ attrs: { 'stroke-width': 1.8 } });

    // Right-Side Toast Notifications (Appear & Auto-Dismiss)
    function dismissToast(el) {
        if (!el || el.classList.contains('hide')) return;
        el.classList.remove('show');
        el.classList.add('hide');
        setTimeout(() => el.remove(), 300);
    }

    document.querySelectorAll('.toast-item').forEach((toast) => {
        requestAnimationFrame(() => toast.classList.add('show'));
        const duration = parseInt(toast.dataset.autoDismiss || '4500', 10);
        setTimeout(() => dismissToast(toast), duration);
    });

    // Sidebar Collapse Persistence + Floating Tooltip in Collapsed Mode
    const sidebar = document.getElementById('portalSidebar');
    const floatingTooltip = document.createElement('div');
    floatingTooltip.className = 'sidebar-floating-tooltip';
    document.body.appendChild(floatingTooltip);

    if (localStorage.getItem('aminef_sidebar_collapsed') === '1') {
        sidebar.classList.add('collapsed');
    }
    document.getElementById('sidebarToggleBtn').addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        floatingTooltip.style.display = 'none';
        localStorage.setItem('aminef_sidebar_collapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
    });

    // Mobile Off-Canvas Drawer
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileBackdrop = document.getElementById('mobileSidebarBackdrop');
    function closeMobileMenu() {
        sidebar.classList.remove('mobile-open');
        mobileBackdrop.classList.remove('open');
    }
    mobileMenuBtn?.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
        mobileBackdrop.classList.toggle('open', sidebar.classList.contains('mobile-open'));
    });
    mobileBackdrop?.addEventListener('click', closeMobileMenu);
    sidebar.querySelectorAll('.nav-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) closeMobileMenu();
        });
    });

    sidebar.querySelectorAll('.nav-link').forEach((link) => {
        link.addEventListener('mouseenter', () => {
            if (!sidebar.classList.contains('collapsed')) return;
            const label = link.getAttribute('title') || link.querySelector('.nav-text')?.textContent?.trim();
            if (!label) return;
            const rect = link.getBoundingClientRect();
            floatingTooltip.textContent = label;
            floatingTooltip.style.display = 'block';
            floatingTooltip.style.left = (rect.right + 10) + 'px';
            floatingTooltip.style.top = (rect.top + (rect.height - floatingTooltip.offsetHeight) / 2) + 'px';
        });
        link.addEventListener('mouseleave', () => {
            floatingTooltip.style.display = 'none';
        });
    });

    // Flyout Dropdowns
    const notifBtn = document.getElementById('notifToggleBtn');
    const notifFlyout = document.getElementById('notifFlyout');
    const profileBtn = document.getElementById('profileToggleBtn');
    const profileFlyout = document.getElementById('profileFlyout');

    notifBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        profileFlyout.classList.remove('open');
        profileBtn.classList.remove('open');
        notifFlyout.classList.toggle('open');
    });
    profileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        notifFlyout.classList.remove('open');
        profileFlyout.classList.toggle('open');
        profileBtn.classList.toggle('open', profileFlyout.classList.contains('open'));
    });
    document.addEventListener('click', () => {
        notifFlyout.classList.remove('open');
        profileFlyout.classList.remove('open');
        profileBtn.classList.remove('open');
    });
    [notifFlyout, profileFlyout].forEach(el => el.addEventListener('click', e => e.stopPropagation()));

    // Open native calendar/time picker when clicking anywhere on date/time inputs
    document.addEventListener('click', (e) => {
        const input = e.target.closest('input[type="date"], input[type="time"]');
        if (input && typeof input.showPicker === 'function') {
            try { input.showPicker(); } catch (_) {}
        }
    });

    // Modal Helper
    function openModal(id) { document.getElementById(id)?.classList.add('open'); }
    function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

    // Reusable Electronic Signature Canvas Helper
    function initSignaturePad(canvasId, inputId, clearBtnId) {
        const canvas = document.getElementById(canvasId);
        const input = document.getElementById(inputId);
        const clearBtn = document.getElementById(clearBtnId);
        if (!canvas || !input) return;

        const ctx = canvas.getContext('2d');
        let drawing = false;
        let hasDrawn = false;

        function resize() {
            const rect = canvas.getBoundingClientRect();
            if (rect.width > 0) {
                canvas.width = Math.round(rect.width);
                canvas.height = Math.round(rect.height) || 130;
            } else {
                canvas.width = 480;
                canvas.height = 130;
            }
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#0f172a';
        }
        resize();

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            const scaleX = rect.width > 0 ? canvas.width / rect.width : 1;
            const scaleY = rect.height > 0 ? canvas.height / rect.height : 1;
            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY,
            };
        }

        function start(e) {
            if (!hasDrawn) {
                const rect = canvas.getBoundingClientRect();
                if (rect.width > 0 && canvas.width !== Math.round(rect.width)) {
                    resize();
                }
            }
            drawing = true;
            hasDrawn = true;
            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }
        function move(e) {
            if (!drawing) return;
            e.preventDefault();
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        }
        function end() {
            if (!drawing) return;
            drawing = false;
            if (hasDrawn) input.value = canvas.toDataURL('image/png');
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        window.addEventListener('touchend', end);

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                input.value = '';
                hasDrawn = false;
            });
        }
    }
</script>
@stack('scripts')
</body>
</html>
