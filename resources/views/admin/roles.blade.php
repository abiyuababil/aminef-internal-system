@extends('layouts.app')
@section('title', 'Role & Menu Access')

@section('content')
<ol class="breadcrumb">
    <li>System Admin</li>
    <li>/</li>
    <li>Role &amp; Menu Access</li>
</ol>
<h1 class="page-title">Role Master, Approval Hierarchy &amp; Menu Access</h1>

<div class="aminef-card">
    <div class="aminef-card-header">
        <h2 class="aminef-card-title">Roles Configuration</h2>
        <button type="button" class="btn-action-uniform btn-action-create" onclick="openModal('addRoleModal')">+ Add Role</button>
    </div>
    <div class="aminef-card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-left">Role Name</th>
                        <th class="text-left">Slug</th>
                        <th class="text-left">Needs Approval From Role</th>
                        <th class="text-left">Allowed Menus</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $i => $r)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td class="text-left"><strong>{{ $r->name }}</strong></td>
                            <td class="text-left"><code>{{ $r->slug }}</code></td>
                            <td class="text-left">
                                @if($r->approver_role_slug)
                                    <span class="status-badge status-badge-info">{{ strtoupper($r->approver_role_slug) }}</span>
                                @else
                                    <span style="color:#94a3b8;">-- None (Top Level) --</span>
                                @endif
                            </td>
                            <td class="text-left" style="font-size:12px;">
                                @foreach(($r->menu_permissions ?? []) as $m)
                                    <span style="display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:3px;padding:1px 6px;margin:1px;">{{ $availableMenus[$m] ?? $m }}</span>
                                @endforeach
                            </td>
                            <td class="text-center">
                                <div style="display:flex;gap:5px;justify-content:center;">
                                    <button type="button" class="btn-action-uniform btn-action-edit" onclick='openEditRoleModal(@json($r))'>Edit</button>
                                    @if(! $r->is_system)
                                        <form method="POST" action="{{ route('admin.roles.destroy', $r) }}" onsubmit="return confirm('Delete this role?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action-uniform btn-action-delete">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="aminef-modal-backdrop" id="addRoleModal">
    <div class="aminef-modal">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Add New Role</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('addRoleModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Finance Lead">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Needs Approval From Role</label>
                        <select name="approver_role_slug" class="form-control">
                            <option value="">-- None / Direct Supervisor Only --</option>
                            @foreach($roles as $opt)
                                <option value="{{ $opt->slug }}">{{ $opt->name }} ({{ $opt->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Menu Access Permissions</label>
                    <div class="form-grid-2" style="background:#f8fafc;padding:12px;border:1px solid #e2e8f0;border-radius:4px;">
                        @foreach($availableMenus as $key => $label)
                            <label style="display:flex;align-items:center;gap:7px;font-size:12.5px;cursor:pointer;">
                                <input type="checkbox" name="menu_permissions[]" value="{{ $key }}" {{ in_array($key, ['dashboard','profile','leaves'], true) ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addRoleModal')">Cancel</button>
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="aminef-modal-backdrop" id="editRoleModal">
    <div class="aminef-modal">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Edit Role &amp; Menu Access</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('editRoleModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <form method="POST" id="editRoleForm">
                @csrf
                @method('PUT')
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editRoleName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Needs Approval From Role</label>
                        <select name="approver_role_slug" id="editRoleApprover" class="form-control">
                            <option value="">-- None / Top Level --</option>
                            @foreach($roles as $opt)
                                <option value="{{ $opt->slug }}">{{ $opt->name }} ({{ $opt->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Menu Access Permissions</label>
                    <div class="form-grid-2" style="background:#f8fafc;padding:12px;border:1px solid #e2e8f0;border-radius:4px;">
                        @foreach($availableMenus as $key => $label)
                            <label style="display:flex;align-items:center;gap:7px;font-size:12.5px;cursor:pointer;">
                                <input type="checkbox" class="edit-role-menu-cb" name="menu_permissions[]" value="{{ $key }}">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('editRoleModal')">Cancel</button>
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditRoleModal(role) {
        document.getElementById('editRoleForm').action = `/admin/roles/${role.id}`;
        document.getElementById('editRoleName').value = role.name;
        document.getElementById('editRoleApprover').value = role.approver_role_slug || '';
        const perms = role.menu_permissions || [];
        document.querySelectorAll('.edit-role-menu-cb').forEach(cb => {
            cb.checked = perms.includes(cb.value);
        });
        openModal('editRoleModal');
    }
</script>
@endpush
