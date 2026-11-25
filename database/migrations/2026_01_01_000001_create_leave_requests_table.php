<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // annual, sick, comp_time
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 1);
            $table->text('reason');
            $table->string('pic_handover')->nullable();
            $table->string('medical_note_path')->nullable();
            $table->string('medical_note_status')->default('not_required'); // not_required, pending_upload, uploaded
            $table->string('staff_signature_path')->nullable();
            $table->string('supervisor_signature_path')->nullable();
            $table->string('approved_pdf_path')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancel_requested, cancelled
            $table->text('rejection_reason')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
