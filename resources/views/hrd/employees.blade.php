@extends('layouts.app')
@section('title', 'Employees & Balances')

@section('content')
<ol class="breadcrumb">
    <li>HRD Management</li>
    <li>/</li>
    <li>Employees &amp; Balances</li>
</ol>
<h1 class="page-title">Employees &amp; Leave Balances</h1>

<div class="aminef-card">
    <div class="aminef-card-header">
        <h2 class="aminef-card-title">Employee Directory</h2>
        <div style="display:flex;gap:8px;">
            <button type="button" class="btn-action-uniform btn-action-view" onclick="openModal('importEmployeeModal')">Import Excel</button>
            <button type="button" class="btn-action-uniform btn-action-create" onclick="openModal('addEmployeeModal')">+ Add Employee</button>
        </div>
    </div>
    <div class="aminef-card-body">
        <form method="GET" action="{{ route('hrd.employees') }}" class="filter-toolbar">
            <div class="filter-left">
                <span>Show:</span>
                <select name="limit" onchange="this.form.submit()">
                    @foreach([10, 25, 50, 100] as $opt)
                        <option value="{{ $opt }}" {{ $limit === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <select name="role" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="supervisor" {{ request('role') === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                    <option value="hrd" {{ request('role') === 'hrd' ? 'selected' : '' }}>HRD</option>
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
                        <th class="text-left">Department</th>
                        <th class="text-left">Supervisor</th>
                        <th class="text-right">Annual Leave</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $i => $emp)
                        <tr>
                            <td class="text-center">{{ $employees->firstItem() + $i }}</td>
                            <td class="text-left">
                                <strong>{{ $emp->name }}</strong>
                                <div style="font-size:11.5px;color:#64748b;">{{ $emp->email }}</div>
                            </td>
                            <td class="text-center"><span class="status-badge status-badge-info">{{ strtoupper($emp->role) }}</span></td>
                            <td class="text-left">{{ $emp->department ?? '-' }}</td>
                            <td class="text-left">{{ $emp->supervisor?->name ?? '-' }}</td>
                            <td class="text-right"><strong>{{ $emp->annual_leave_balance }}</strong></td>
                            <td class="text-center">
                                <span class="status-badge {{ $emp->is_active ? 'status-badge-success' : 'status-badge-danger' }}">
                                    {{ $emp->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div style="display:flex;gap:5px;justify-content:center;">
                                    <button type="button" class="btn-action-uniform btn-action-edit" onclick='openEditEmp(@json($emp))'>Edit</button>
                                    <button type="button" class="btn-action-uniform btn-action-amber" onclick="openAdjustModal({{ $emp->id }}, '{{ addslashes($emp->name) }}')">Adjust</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="table-empty-state">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-footer">
            <div>Showing {{ $employees->firstItem() ?? 0 }} to {{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }} entries</div>
            <div>{{ $employees->links('partials.pagination') }}</div>
        </div>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="aminef-modal-backdrop" id="addEmployeeModal">
    <div class="aminef-modal">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Add New Employee</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('addEmployeeModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <form method="POST" action="{{ route('hrd.employees.store') }}">
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
                        <label class="form-label">Initial Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="staff">Staff</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="hrd">HRD</option>
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
                <div class="form-group">
                    <label class="form-label">Direct Supervisor</label>
                    <select name="supervisor_id" class="form-control">
                        <option value="">-- None --</option>
                        @foreach($supervisors as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }} ({{ strtoupper($sup->role) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Initial Annual Leave Balance <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="annual_leave_balance" class="form-control" value="12" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Initial Comp-Time Balance <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="comp_time_balance" class="form-control" value="0" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addEmployeeModal')">Cancel</button>
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="aminef-modal-backdrop" id="editEmployeeModal">
    <div class="aminef-modal">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Edit Employee</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('editEmployeeModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <form method="POST" id="editEmpForm">
                @csrf
                @method('PUT')
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editEmpName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" id="editEmpRole" class="form-control" required>
                            <option value="staff">Staff</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="hrd">HRD</option>
                        </select>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" id="editEmpDept" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" id="editEmpPos" class="form-control">
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Direct Supervisor</label>
                        <select name="supervisor_id" id="editEmpSup" class="form-control">
                            <option value="">-- None --</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Status</label>
                        <select name="is_active" id="editEmpActive" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reset Password (Optional, leave blank to keep current)</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('editEmployeeModal')">Cancel</button>
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Adjust Balance Modal -->
<div class="aminef-modal-backdrop" id="adjustBalanceModal">
    <div class="aminef-modal" style="max-width:460px;">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Adjust Balance &mdash; <span id="adjEmpName"></span></h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('adjustBalanceModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <form method="POST" id="adjBalanceForm">
                @csrf
                <div class="form-group">
                    <label class="form-label">Balance Type <span class="text-danger">*</span></label>
                    <select name="balance_type" class="form-control" required>
                        <option value="annual">Annual Leave Balance</option>
                        <option value="comp_time">Comp-Time Balance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Adjustment Amount (+ to add, - to deduct) <span class="text-danger">*</span></label>
                    <input type="number" step="0.5" name="amount" class="form-control" required placeholder="e.g. 2 or -1">
                </div>
                <div class="form-group">
                    <label class="form-label">Reason (e.g. Excel Migration / Carry-over) <span class="text-danger">*</span></label>
                    <input type="text" name="reason" class="form-control" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('adjustBalanceModal')">Cancel</button>
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Employees Modal -->
<div class="aminef-modal-backdrop" id="importEmployeeModal">
    <div class="aminef-modal" style="max-width:520px;">
        <div class="aminef-modal-header">
            <h3 class="aminef-modal-title">Import Employees (Excel / CSV)</h3>
            <button type="button" class="aminef-modal-close" onclick="closeModal('importEmployeeModal')">&times;</button>
        </div>
        <div class="aminef-modal-body">
            <div class="alert alert-info" style="margin-bottom:14px;">
                Need the format? <a href="{{ route('hrd.employees.template') }}" style="font-weight:700;text-decoration:underline;">Download Excel Template (.xlsx)</a><br>
                <span style="font-size:12px;">If a role in the file does not exist yet, it will be <strong>auto-created</strong> with default staff menus (you can customize its permissions later in <em>Role &amp; Menu Access</em>).</span>
            </div>
            <form method="POST" action="{{ route('hrd.employees.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Select File (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('importEmployeeModal')">Cancel</button>
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditEmp(emp) {
        document.getElementById('editEmpForm').action = `/hrd/employees/${emp.id}`;
        document.getElementById('editEmpName').value = emp.name;
        document.getElementById('editEmpRole').value = emp.role;
        document.getElementById('editEmpDept').value = emp.department || '';
        document.getElementById('editEmpPos').value = emp.position || '';
        document.getElementById('editEmpSup').value = emp.supervisor_id || '';
        document.getElementById('editEmpActive').value = emp.is_active ? '1' : '0';
        openModal('editEmployeeModal');
    }

    function openAdjustModal(id, name) {
        document.getElementById('adjBalanceForm').action = `/hrd/employees/${id}/adjust-balance`;
        document.getElementById('adjEmpName').textContent = name;
        openModal('adjustBalanceModal');
    }
</script>
@endpush
