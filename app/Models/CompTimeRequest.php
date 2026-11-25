<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompTimeRequest extends Model
{
    protected $fillable = [
        'user_id',
        'supervisor_id',
        'work_date',
        'days_requested',
        'template_file_path',
        'reason',
        'staff_signature_path',
        'supervisor_signature_path',
        'approved_pdf_path',
        'status',
        'rejection_reason',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'days_requested' => 'decimal:1',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'approved' => 'status-badge-success',
            'pending' => 'status-badge-warning',
            'rejected' => 'status-badge-danger',
            default => 'status-badge-info',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }
}
