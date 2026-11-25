<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'approver_role_slug',
        'menu_permissions',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'menu_permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public static function availableMenus(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'profile' => 'My Profile',
            'leaves' => 'Leave Requests',
            'approvals' => 'Leave Approval',
            'hrd_leaves' => 'HRD: All Employee Leaves',
            'hrd_employees' => 'HRD: Employees & Balances',
            'hrd_holidays' => 'HRD: National Holidays',
            'hrd_reports' => 'HRD: Leave Recap',
            'hrd_templates' => 'HRD: Templates',
            'admin_roles' => 'Admin: Role & Menu Access',
            'admin_staff' => 'Admin: Master Data Staff',
        ];
    }
}
