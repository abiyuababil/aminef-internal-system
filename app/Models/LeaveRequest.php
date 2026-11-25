<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'supervisor_id',
        'type',
        'purpose',
        'start_date',
        'end_date',
        'total_days',
        'date_ranges',
        'reason',
        'pic_handover',
        'medical_note_path',
        'medical_note_status',
        'proof_file_path',
        'staff_signature_path',
        'supervisor_signature_path',
        'approved_pdf_path',
        'status',
        'rejection_reason',
        'cancel_reason',
        'approved_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'decimal:1',
            'date_ranges' => 'array',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function typeLabel(): string
    {
        return match ($this->type) {
            'annual' => 'Annual Leave',
            'sick' => 'Sick Leave',
            'comp_time' => 'Compensatory Time Off',
            'leave_with_pay' => 'Leave With Pay',
            'leave_without_pay' => 'Leave Without Pay',
            default => ucfirst($this->type),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'approved' => 'status-badge-success',
            'pending', 'cancel_requested' => 'status-badge-warning',
            'rejected', 'cancelled' => 'status-badge-danger',
            default => 'status-badge-info',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancel_requested' => 'Cancel Requested',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }
}
