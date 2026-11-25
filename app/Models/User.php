<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'position',
        'supervisor_id',
        'annual_leave_balance',
        'comp_time_balance',
        'signature_path',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'annual_leave_balance' => 'decimal:1',
            'comp_time_balance' => 'decimal:1',
            'is_active' => 'boolean',
        ];
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function compTimeRequests(): HasMany
    {
        return $this->hasMany(CompTimeRequest::class);
    }

    public function balanceAdjustments(): HasMany
    {
        return $this->hasMany(BalanceAdjustment::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isHrd(): bool
    {
        return $this->role === 'hrd';
    }

    private ?Role $cachedRoleConfig = null;

    private bool $roleConfigLoaded = false;

    public function roleConfig(): ?Role
    {
        if (! $this->roleConfigLoaded) {
            $this->cachedRoleConfig = Role::where('slug', $this->role)->first();
            $this->roleConfigLoaded = true;
        }

        return $this->cachedRoleConfig;
    }

    public function hasMenuAccess(string $menuKey): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->roleConfig();
        if (! $role) {
            // Sensible defaults when roles table is not seeded (e.g., isolated unit tests)
            return match ($menuKey) {
                'dashboard', 'profile', 'leaves' => true,
                'approvals' => in_array($this->role, ['supervisor', 'hrd'], true),
                'hrd_leaves', 'hrd_employees', 'hrd_holidays', 'hrd_reports', 'hrd_templates' => $this->role === 'hrd',
                default => false,
            };
        }

        return in_array($menuKey, $role->menu_permissions ?? [], true);
    }

    public function canApprove(): bool
    {
        return $this->hasMenuAccess('approvals');
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials ?: 'U';
    }
}
