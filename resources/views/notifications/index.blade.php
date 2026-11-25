@extends('layouts.app')
@section('title', 'Notifications')

@section('content')
<ol class="breadcrumb">
    <li>Overview</li>
    <li>/</li>
    <li>Notifications</li>
</ol>
<h1 class="page-title">All Notifications</h1>

<div class="aminef-card">
    <div class="aminef-card-header">
        <h2 class="aminef-card-title">Notification History</h2>
    </div>
    <div class="aminef-card-body">
        <form method="POST" action="{{ route('notifications.bulk') }}" id="bulkNotifForm">
            @csrf
            <input type="hidden" name="action" id="bulkNotifAction" value="read">

            <div class="filter-toolbar" style="margin-bottom:12px;">
                <div class="filter-left">
                    <button type="button" class="btn-action-uniform btn-action-neutral" onclick="submitBulkNotif('read')">Mark Selected Read</button>
                    <button type="button" class="btn-action-uniform btn-action-neutral" onclick="submitBulkNotif('unread')">Mark Selected Unread</button>
                </div>
                <div class="filter-right" style="font-size:12px;color:#64748b;">
                    Select checkboxes below to bulk update status.
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:36px;">
                                <input type="checkbox" id="selectAllNotifs" onchange="toggleAllNotifs(this)">
                            </th>
                            <th class="text-left">Title</th>
                            <th class="text-left">Message</th>
                            <th class="text-center">Time</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notif)
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="notif-checkbox" name="ids[]" value="{{ $notif->id }}">
                                </td>
                                <td class="text-left"><strong>{{ $notif->data['title'] ?? '-' }}</strong></td>
                                <td class="text-left">{{ $notif->data['message'] ?? '-' }}</td>
                                <td class="text-center">{{ $notif->created_at->format('d M Y H:i') }}</td>
                                <td class="text-center">
                                    <span class="status-badge {{ $notif->read_at ? 'status-badge-info' : 'status-badge-warning' }}">
                                        {{ $notif->read_at ? 'Read' : 'Unread' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn-action-uniform btn-action-view" onclick="openSingleNotif('{{ route('notifications.read', $notif->id) }}')">Open</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="table-empty-state">No notifications found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <form method="POST" id="singleNotifOpenForm" style="display:none;">
            @csrf
        </form>

        <div class="pagination-footer">
            <div>Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} entries</div>
            <div class="pagination-links">{{ $notifications->links('partials.pagination') }}</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleAllNotifs(master) {
        document.querySelectorAll('.notif-checkbox').forEach(cb => {
            cb.checked = master.checked;
        });
    }

    function submitBulkNotif(action) {
        const checked = document.querySelectorAll('.notif-checkbox:checked');
        if (checked.length === 0) {
            alert('Please select at least one notification.');
            return;
        }
        document.getElementById('bulkNotifAction').value = action;
        document.getElementById('bulkNotifForm').submit();
    }

    function openSingleNotif(url) {
        const form = document.getElementById('singleNotifOpenForm');
        form.action = url;
        form.submit();
    }
</script>
@endpush
