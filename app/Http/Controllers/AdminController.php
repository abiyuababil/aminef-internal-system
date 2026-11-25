<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function roles(): View
    {
        $roles = Role::orderBy('id')->get();
        $availableMenus = Role::availableMenus();

        return view('admin.roles', compact('roles', 'availableMenus'));
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:50', 'unique:roles,slug'],
            'approver_role_slug' => ['nullable', 'string', 'exists:roles,slug'],
            'menu_permissions' => ['nullable', 'array'],
            'menu_permissions.*' => ['string'],
        ]);

        $slug = ! empty($validated['slug']) ? Str::slug($validated['slug'], '_') : Str::slug($validated['name'], '_');
        if (Role::where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => "Role slug '{$slug}' already exists."]);
        }

        $role = Role::create([
            'slug' => $slug,
            'name' => $validated['name'],
            'approver_role_slug' => $validated['approver_role_slug'] ?: null,
            'menu_permissions' => $validated['menu_permissions'] ?? ['dashboard', 'profile', 'leaves'],
            'is_system' => false,
        ]);

        AuditLog::record(Auth::id(), 'role_created', 'role', $role->id);

        return back()->with('success', "Role '{$role->name}' created.");
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'approver_role_slug' => ['nullable', 'string', 'exists:roles,slug'],
            'menu_permissions' => ['nullable', 'array'],
            'menu_permissions.*' => ['string'],
        ]);

        $approverSlug = $validated['approver_role_slug'] ?: null;
        if ($approverSlug === $role->slug) {
            $approverSlug = null;
        }

        $role->update([
            'name' => $validated['name'],
            'approver_role_slug' => $approverSlug,
            'menu_permissions' => $validated['menu_permissions'] ?? [],
        ]);

        AuditLog::record(Auth::id(), 'role_updated', 'role', $role->id);

        return back()->with('success', "Role '{$role->name}' permissions & approval rule updated.");
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => 'Built-in system roles cannot be deleted.']);
        }
        if (User::where('role', $role->slug)->exists()) {
            return back()->withErrors(['role' => 'Cannot delete role while staff members are still assigned to it.']);
        }

        $role->delete();
        AuditLog::record(Auth::id(), 'role_deleted', 'role', $role->id);

        return back()->with('success', 'Role deleted.');
    }

    public function staff(Request $request): View
    {
        $limit = (int) $request->query('limit', 10);
        if (! in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }

        $query = User::with('supervisor');
        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('department', 'like', "%{$search}%"));
        }

        $staffList = $query->orderBy('name')->paginate($limit)->withQueryString();
        $roles = Role::orderBy('name')->get();
        $approvers = User::where('is_active', true)->orderBy('name')->get();

        return view('admin.staff', compact('staffList', 'roles', 'approvers', 'limit'));
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'exists:roles,slug'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'annual_leave_balance' => ['required', 'numeric', 'min:0', 'max:60'],
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'comp_time_balance' => 0,
            'is_active' => true,
        ]);

        AuditLog::record(Auth::id(), 'admin_staff_created', 'user', $user->id);

        return back()->with('success', "Staff '{$user->name}' added.");
    }

    public function updateStaff(Request $request, User $staff): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', 'exists:roles,slug'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'annual_leave_balance' => ['required', 'numeric', 'min:0', 'max:60'],
            'is_active' => ['required', 'boolean'],
            'new_password' => ['nullable', 'string', 'min:8'],
        ]);

        $data = [
            'name' => $validated['name'],
            'role' => $validated['role'],
            'department' => $validated['department'],
            'position' => $validated['position'],
            'supervisor_id' => $validated['supervisor_id'] === (string) $staff->id ? null : $validated['supervisor_id'],
            'annual_leave_balance' => $validated['annual_leave_balance'],
            'is_active' => (bool) $validated['is_active'],
        ];
        if (! empty($validated['new_password'])) {
            $data['password'] = Hash::make($validated['new_password']);
        }

        $staff->update($data);
        AuditLog::record(Auth::id(), 'admin_staff_updated', 'user', $staff->id);

        return back()->with('success', "Staff '{$staff->name}' updated.");
    }

    public function destroyStaff(User $staff): RedirectResponse
    {
        if ($staff->id === Auth::id()) {
            return back()->withErrors(['staff' => 'You cannot delete your own active account.']);
        }

        $name = $staff->name;
        $id = $staff->id;
        $staff->delete();

        AuditLog::record(Auth::id(), 'admin_staff_deleted', 'user', $id, ['name' => $name]);

        return back()->with('success', "Staff '{$name}' deleted.");
    }
}
