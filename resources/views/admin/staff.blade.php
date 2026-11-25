@extends('layouts.app')
@section('title', 'Master Data Staff')

@section('content')
<ol class="breadcrumb">
    <li>System Admin</li>
    <li>/</li>
    <li>Master Data Staff</li>
</ol>
<h1 class="page-title">Master Data Staff</h1>

<div class="aminef-card">
    <div class="aminef-card-header">
        <h2 class="aminef-card-title">All Registered Staff &amp; Accounts</h2>
        <button type="button" class="btn-action-uniform btn-action-create" onclick="openModal('addStaffModal')">+ Add Staff</button>
    </div>
    <div class="aminef-card-body">
        <form method="GET" action="{{ route('admin.staff') }}" class="filter-toolbar">
            <div class="filter-left">
                <span>Show:</span>
                <select name="limit" onchange="this.form.submit()">
                    @foreach([10, 25, 50, 100] as $opt)
                        <option value="{{ $opt }}" {{ $limit === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <select name="role" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    @foreach($roles as $r)
                        <option value="{{ $r->slug }}" {{ request('role') === $r->slug ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-right">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, email, dept...">
                <button type="submit" class="btn-action-uniform btn-action-edit">Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-left">Name &amp; Email</th>
                        <th class="text-center">Role</th>
                        <th class="text-left">Department / Position</th>
                        <th class="text-left">Direct Approver</th>
                        <th class="text-right">Annual Leave</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffList as $i => $s)
                        <tr>
                            <td class="text-center">{{ $staffList->firstItem() + $i }}</td>
                            <td class="text-left">
                                <strong>{{ $s->name }}</strong>
                                <div style="font-size:11.5px;color:#64748b;">{{ $s->email }}</div>
                            </td>
                            <td class="text-center"><span class="status-badge status-badge-info">{{ strtoupper($s->role) }}</span></td>
                            <td class="text-left">{{ $s->department ?? '-' }} <span style="color:#94a3b8;">/ {{ $s->position ?? '-' }}</span></td>
                            <td class="text-left">{{ $s->supervisor?->name ?? 'Auto by Role Rule' }}</td>
                            <td class="text-right"><strong>{{ $s->annual_leave_balance }}</strong></td>
                            <td class="text-center">
                                <span class="status-badge {{ $s->is_active ? 'status-badge-success' : 'status-badge-danger' }}">
                                    {{ $s->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-action-uniform btn-action-edit" onclick='openEditStaffModal(@json($s))'>Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="table-empty-state">No staff found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-footer">
            <div>Showing {{ $staffList->firstItem() ?? 0 }} to {{ $staffList->lastItem() ?? 0 }} of {{ $staffList->total() }} entries</div>
            <div>{{ $staffList->links('partials.pagination') }}</div>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="aminef-modal-backdrop" id="addStaffModal">
    <div class="aminef-modal">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Add Master Data Staff</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('addStaffModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <form method="POST" action="{{ route('admin.staff.store') }}">
                @csrf
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-control" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->slug }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" class="form-control">
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Direct Approver (Optional)</label>
                        <select name="supervisor_id" class="form-control">
                            <option value="">-- Follow Role Approval Rule --</option>
                            @foreach($approvers as $ap)
                                <option value="{{ $ap->id }}">{{ $ap->name }} ({{ strtoupper($ap->role) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Annual Leave Balance <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="annual_leave_balance" class="form-control" value="12" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addStaffModal')">Cancel</button>
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="aminef-modal-backdrop" id="editStaffModal">
    <div class="aminef-modal">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Edit Staff</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('editStaffModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <form method="POST" id="editStaffForm">
                @csrf
                @method('PUT')
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="esName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" id="esRole" class="form-control" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->slug }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" id="esDept" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" id="esPos" class="form-control">
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Direct Approver</label>
                        <select name="supervisor_id" id="esSup" class="form-control">
                            <option value="">-- Follow Role Approval Rule --</option>
                            @foreach($approvers as $ap)
                                <option value="{{ $ap->id }}">{{ $ap->name }} ({{ strtoupper($ap->role) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Annual Leave Balance <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="annual_leave_balance" id="esBal" class="form-control" required>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="is_active" id="esActive" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reset Password (Optional)</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('editStaffModal')">Cancel</button>
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditStaffModal(s) {
        document.getElementById('editStaffForm').action = `/admin/staff/${s.id}`;
        document.getElementById('esName').value = s.name;
        document.getElementById('esRole').value = s.role;
        document.getElementById('esDept').value = s.department || '';
        document.getElementById('esPos').value = s.position || '';
        document.getElementById('esSup').value = s.supervisor_id || '';
        document.getElementById('esBal').value = s.annual_leave_balance;
        document.getElementById('esActive').value = s.is_active ? '1' : '0';
        openModal('editStaffModal');
    }
</script>
@endpush
