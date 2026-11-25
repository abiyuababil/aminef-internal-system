<?php

namespace Tests\Feature;

use App\Mail\LeaveApprovedMail;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\PortalNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeavePortalTest extends TestCase
{
    use RefreshDatabase;

    private string $fakePngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public function test_login_and_dashboard_access(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@aminef.or.id',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->get('/staff/login')->assertOk();
        $this->get('/forgot-password')->assertOk();

        $this->post('/login', [
            'email' => 'staff@aminef.or.id',
            'password' => 'secret123',
        ])->assertRedirect('/dashboard');

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_annual_leave_flow_with_holidays_signature_pdf_and_outlook_ics(): void
    {
        Storage::fake('public');
        Mail::fake();

        $hrd = User::factory()->create(['role' => 'hrd', 'email' => 'hrd@aminef.or.id']);
        $supervisor = User::factory()->create(['role' => 'supervisor', 'email' => 'sup@aminef.or.id']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'email' => 'staff@aminef.or.id',
            'supervisor_id' => $supervisor->id,
            'annual_leave_balance' => 12.0,
        ]);

        // Monday 2026-12-07 to Wednesday 2026-12-09 (3 days), with Dec 8 as a holiday => 2 working days
        Holiday::create([
            'date' => '2026-12-08',
            'name' => 'Test Holiday',
            'type' => 'national_holiday',
        ]);

        $this->actingAs($staff)->post('/leaves', [
            'type' => 'annual',
            'start_date' => '2026-12-07',
            'end_date' => '2026-12-09',
            'reason' => 'Family trip',
            'pic_handover' => 'Nadia',
            'signature_data' => $this->fakePngBase64,
        ])->assertRedirect('/leaves');

        $leave = LeaveRequest::firstOrFail();
        $this->assertEquals(2.0, (float) $leave->total_days);
        $this->assertNotNull($staff->fresh()->signature_path);

        // Supervisor approves with e-signature
        $this->actingAs($supervisor)->post("/approvals/leaves/{$leave->id}/approve", [
            'signature_data' => $this->fakePngBase64,
        ])->assertSessionHasNoErrors();

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals(10.0, (float) $staff->fresh()->annual_leave_balance);
        $this->assertNotNull($leave->approved_pdf_path);
        Storage::disk('public')->assertExists($leave->approved_pdf_path);

        Mail::assertSent(LeaveApprovedMail::class, 3); // Staff, Supervisor, HRD

        // Download Outlook .ics
        $icsRes = $this->actingAs($staff)->get("/leaves/{$leave->id}/ics");
        $icsRes->assertOk();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $icsRes->getContent());
    }

    public function test_sick_leave_over_one_day_allows_later_medical_note_upload(): void
    {
        Storage::fake('public');

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'supervisor_id' => $supervisor->id,
            'signature_path' => 'signatures/existing.png',
        ]);
        Storage::disk('public')->put('signatures/existing.png', 'png');

        $this->actingAs($staff)->post('/leaves', [
            'type' => 'sick',
            'start_date' => '2026-12-14',
            'end_date' => '2026-12-15',
            'reason' => 'Flu',
        ])->assertRedirect('/leaves');

        $leave = LeaveRequest::firstOrFail();
        $this->assertEquals('pending_upload', $leave->medical_note_status);

        $this->actingAs($staff)->post("/leaves/{$leave->id}/medical-note", [
            'medical_note' => UploadedFile::fake()->create('doctor.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertEquals('uploaded', $leave->fresh()->medical_note_status);
    }

    public function test_comp_time_leave_with_weekend_proof_does_not_reduce_annual_leave(): void
    {
        Storage::fake('public');
        Mail::fake();

        $hrd = User::factory()->create(['role' => 'hrd']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'supervisor_id' => $supervisor->id,
            'annual_leave_balance' => 12.0,
        ]);

        $this->actingAs($staff)->post('/leaves', [
            'type' => 'comp_time',
            'start_date' => '2026-12-07',
            'end_date' => '2026-12-07',
            'reason' => 'Replacement for weekend event work',
            'proof_file' => UploadedFile::fake()->create('weekend-proof.pdf', 100, 'application/pdf'),
            'signature_data' => $this->fakePngBase64,
        ])->assertRedirect('/leaves');

        $leave = LeaveRequest::firstOrFail();
        $this->assertNotNull($leave->proof_file_path);
        Storage::disk('public')->assertExists($leave->proof_file_path);

        $this->actingAs($supervisor)->post("/approvals/leaves/{$leave->id}/approve", [
            'signature_data' => $this->fakePngBase64,
        ])->assertSessionHasNoErrors();

        $this->assertEquals('approved', $leave->fresh()->status);
        $this->assertEquals(12.0, (float) $staff->fresh()->annual_leave_balance);
        Mail::assertSent(LeaveApprovedMail::class);
    }

    public function test_pending_leave_can_be_cancelled_but_approved_leave_cannot_be_cancelled(): void
    {
        Storage::fake('public');

        $hrd = User::factory()->create(['role' => 'hrd']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'supervisor_id' => $supervisor->id,
            'annual_leave_balance' => 10.0,
        ]);

        $pendingLeave = LeaveRequest::create([
            'user_id' => $staff->id,
            'supervisor_id' => $supervisor->id,
            'type' => 'annual',
            'start_date' => '2026-12-21',
            'end_date' => '2026-12-22',
            'total_days' => 2.0,
            'reason' => 'Trip',
            'status' => 'pending',
        ]);

        $this->actingAs($staff)->post("/leaves/{$pendingLeave->id}/cancel", [
            'cancel_reason' => 'Schedule changed',
        ])->assertSessionHasNoErrors();

        $this->assertEquals('cancelled', $pendingLeave->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $hrd->id,
        ]);

        $approvedLeave = LeaveRequest::create([
            'user_id' => $staff->id,
            'supervisor_id' => $supervisor->id,
            'type' => 'annual',
            'start_date' => '2026-12-23',
            'end_date' => '2026-12-24',
            'total_days' => 2.0,
            'reason' => 'Approved trip',
            'status' => 'approved',
        ]);

        $this->actingAs($staff)->post("/leaves/{$approvedLeave->id}/cancel")
            ->assertSessionHasErrors('status');

        $this->assertEquals('approved', $approvedLeave->fresh()->status);
        $this->assertEquals(10.0, (float) $staff->fresh()->annual_leave_balance);

        $this->actingAs($hrd)->get('/hrd/reports/excel')->assertOk();
        $this->actingAs($hrd)->get('/hrd/reports/pdf')->assertOk();
    }

    public function test_admin_can_manage_roles_menu_access_and_master_staff(): void
    {
        $this->seed();

        $this->get('/admin/login')->assertOk();

        $admin = User::where('email', 'admin@aminef.or.id')->firstOrFail();
        $staff = User::where('email', 'staff@aminef.or.id')->firstOrFail();

        // Staff cannot access admin routes
        $this->actingAs($staff)->get('/admin/roles')->assertStatus(403);
        $this->actingAs($staff)->get('/admin/staff')->assertStatus(403);

        // Admin creates a custom role that needs approval from HRD and has approvals menu access
        $this->actingAs($admin)->post('/admin/roles', [
            'name' => 'Program Lead',
            'approver_role_slug' => 'hrd',
            'menu_permissions' => ['dashboard', 'profile', 'leaves', 'approvals'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'slug' => 'program_lead',
            'approver_role_slug' => 'hrd',
        ]);

        // Admin creates a staff member with the custom role
        $this->actingAs($admin)->post('/admin/staff', [
            'name' => 'Custom Lead User',
            'email' => 'lead@aminef.or.id',
            'password' => 'password123',
            'role' => 'program_lead',
            'department' => 'Programs',
            'position' => 'Lead',
            'annual_leave_balance' => 12,
        ])->assertSessionHasNoErrors();

        $leadUser = User::where('email', 'lead@aminef.or.id')->firstOrFail();
        $this->assertTrue($leadUser->hasMenuAccess('approvals'));
        $this->assertFalse($leadUser->hasMenuAccess('admin_roles'));
    }

    public function test_hrd_can_import_employees_with_auto_role_creation_and_supervisor_linking(): void
    {
        $this->seed();
        $hrd = User::where('email', 'hrd@aminef.or.id')->firstOrFail();

        $this->actingAs($hrd)->get('/hrd/employees/template')->assertOk();

        // Put staff row BEFORE supervisor row to verify 2-pass supervisor linking + auto role creation
        $csv = implode("\n", [
            'name,email,role,department,position,supervisor_email,annual_leave_balance,password',
            'Import Staff,importstaff@aminef.or.id,Finance Officer,Finance,Officer,importsup@aminef.or.id,14,secret123',
            'Import Sup,importsup@aminef.or.id,supervisor,Finance,Manager,,12,secret123',
        ]);

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($hrd)->post('/hrd/employees/import', [
            'file' => $file,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('roles', ['slug' => 'finance_officer']);
        $sup = User::where('email', 'importsup@aminef.or.id')->firstOrFail();
        $emp = User::where('email', 'importstaff@aminef.or.id')->firstOrFail();
        $this->assertEquals($sup->id, $emp->supervisor_id);
        $this->assertEquals(14.0, (float) $emp->annual_leave_balance);
    }

    public function test_notifications_can_be_bulk_marked_read_and_unread(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $user->notify(new PortalNotification('Title 1', 'Msg 1', '/dashboard', false));
        $user->notify(new PortalNotification('Title 2', 'Msg 2', '/dashboard', false));

        $ids = $user->notifications()->pluck('id')->all();
        $this->assertCount(2, $ids);

        $this->actingAs($user)->post('/notifications/bulk-action', [
            'ids' => $ids,
            'action' => 'read',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(0, $user->unreadNotifications()->count());

        $this->actingAs($user)->post('/notifications/bulk-action', [
            'ids' => [$ids[0]],
            'action' => 'unread',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(1, $user->unreadNotifications()->count());
    }

    public function test_hrd_cannot_approve_staff_leave_unless_set_as_role_approver(): void
    {
        $this->seed();
        $hrd = User::where('email', 'hrd@aminef.or.id')->firstOrFail();
        $supervisor = User::where('email', 'supervisor@aminef.or.id')->firstOrFail();
        $staff = User::where('email', 'staff@aminef.or.id')->firstOrFail();

        $leave = LeaveRequest::create([
            'user_id' => $staff->id,
            'supervisor_id' => $supervisor->id,
            'type' => 'annual',
            'start_date' => '2026-12-28',
            'end_date' => '2026-12-29',
            'total_days' => 2.0,
            'reason' => 'Year end break',
            'status' => 'pending',
        ]);

        // HRD is NOT the designated approver for staff role (supervisor is)
        $this->actingAs($hrd)->post("/approvals/leaves/{$leave->id}/approve", [
            'signature_data' => $this->fakePngBase64,
        ])->assertStatus(403);

        // Designated supervisor CAN approve
        $this->actingAs($supervisor)->post("/approvals/leaves/{$leave->id}/approve", [
            'signature_data' => $this->fakePngBase64,
        ])->assertSessionHasNoErrors();

        $this->assertEquals('approved', $leave->fresh()->status);
    }

    public function test_half_day_leave_calculates_point_five_days(): void
    {
        Storage::fake('public');
        Mail::fake();

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'supervisor_id' => $supervisor->id,
            'annual_leave_balance' => 12.0,
        ]);

        // Single working day (Monday 2026-12-07) with 4 hours (08:00 - 12:00) => 0.5 day
        $this->actingAs($staff)->post('/leaves', [
            'type' => 'annual',
            'ranges' => [[
                'start_date' => '2026-12-07',
                'end_date' => '2026-12-07',
                'start_time' => '08:00',
                'end_time' => '12:00',
            ]],
            'reason' => 'Half-day morning leave',
            'signature_data' => $this->fakePngBase64,
        ])->assertRedirect('/leaves');

        $leave = LeaveRequest::firstOrFail();
        $this->assertEquals(0.5, (float) $leave->total_days);

        $this->actingAs($supervisor)->post("/approvals/leaves/{$leave->id}/approve", [
            'signature_data' => $this->fakePngBase64,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(11.5, (float) $staff->fresh()->annual_leave_balance);
    }

    public function test_login_rate_limiting_and_unauthorized_access_guards(): void
    {
        $this->seed();
        $staff = User::where('email', 'staff@aminef.or.id')->firstOrFail();
        $otherStaff = User::where('email', 'nadia@aminef.or.id')->firstOrFail();

        // Unauthorized route checks
        $this->actingAs($staff)->get('/hrd/employees')->assertStatus(403);
        $this->actingAs($staff)->get('/hrd/reports')->assertStatus(403);
        $this->actingAs($staff)->get('/approvals')->assertStatus(403);

        // Cannot cancel or view another staff member's leave
        $otherLeave = LeaveRequest::create([
            'user_id' => $otherStaff->id,
            'supervisor_id' => $staff->supervisor_id,
            'type' => 'annual',
            'start_date' => '2026-12-30',
            'end_date' => '2026-12-30',
            'total_days' => 1.0,
            'reason' => 'Personal',
            'status' => 'approved',
        ]);
        $this->actingAs($staff)->get("/leaves/{$otherLeave->id}/pdf")->assertStatus(403);
        $this->actingAs($staff)->post("/leaves/{$otherLeave->id}/cancel")->assertStatus(403);

        // Brute-force login rate limiter (5 attempts then locked out)
        auth()->logout();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'ratelimit@aminef.or.id',
                'password' => 'wrongpass',
            ])->assertSessionHasErrors('email');
        }
        $res = $this->post('/login', [
            'email' => 'ratelimit@aminef.or.id',
            'password' => 'wrongpass',
        ]);
        $res->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    public function test_double_approval_cannot_cause_negative_annual_leave_balance(): void
    {
        Storage::fake('public');
        Mail::fake();

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'supervisor_id' => $supervisor->id,
            'annual_leave_balance' => 5.0,
        ]);

        $leave1 = LeaveRequest::create([
            'user_id' => $staff->id,
            'supervisor_id' => $supervisor->id,
            'type' => 'annual',
            'start_date' => '2026-12-14',
            'end_date' => '2026-12-16',
            'total_days' => 3.0,
            'reason' => 'Trip 1',
            'status' => 'pending',
        ]);
        $leave2 = LeaveRequest::create([
            'user_id' => $staff->id,
            'supervisor_id' => $supervisor->id,
            'type' => 'annual',
            'start_date' => '2026-12-21',
            'end_date' => '2026-12-23',
            'total_days' => 3.0,
            'reason' => 'Trip 2',
            'status' => 'pending',
        ]);

        $this->actingAs($supervisor)->post("/approvals/leaves/{$leave1->id}/approve", [
            'signature_data' => $this->fakePngBase64,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(2.0, (float) $staff->fresh()->annual_leave_balance);

        // Second approval must fail because remaining balance is 2.0 < 3.0
        $this->actingAs($supervisor)->post("/approvals/leaves/{$leave2->id}/approve")
            ->assertSessionHasErrors('balance');
        $this->assertEquals('pending', $leave2->fresh()->status);
        $this->assertEquals(2.0, (float) $staff->fresh()->annual_leave_balance);
    }
}
