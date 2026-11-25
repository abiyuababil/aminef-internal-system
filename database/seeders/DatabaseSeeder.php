<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'slug' => 'admin',
                'name' => 'Administrator',
                'approver_role_slug' => null,
                'menu_permissions' => array_keys(Role::availableMenus()),
                'is_system' => true,
            ],
            [
                'slug' => 'hrd',
                'name' => 'HRD',
                'approver_role_slug' => 'admin',
                'menu_permissions' => ['dashboard', 'profile', 'leaves', 'approvals', 'hrd_leaves', 'hrd_employees', 'hrd_holidays', 'hrd_reports', 'hrd_templates'],
                'is_system' => true,
            ],
            [
                'slug' => 'supervisor',
                'name' => 'Supervisor',
                'approver_role_slug' => 'hrd',
                'menu_permissions' => ['dashboard', 'profile', 'leaves', 'approvals'],
                'is_system' => true,
            ],
            [
                'slug' => 'staff',
                'name' => 'Staff',
                'approver_role_slug' => 'supervisor',
                'menu_permissions' => ['dashboard', 'profile', 'leaves'],
                'is_system' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(['slug' => $roleData['slug']], $roleData);
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@aminef.or.id'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'department' => 'IT & Operations',
                'position' => 'Portal Administrator',
                'annual_leave_balance' => 12.0,
                'comp_time_balance' => 0.0,
                'is_active' => true,
            ]
        );

        $hrd = User::updateOrCreate(
            ['email' => 'hrd@aminef.or.id'],
            [
                'name' => 'Siti Rahma (HRD)',
                'password' => Hash::make('password123'),
                'role' => 'hrd',
                'department' => 'Human Resources',
                'position' => 'HRD Manager',
                'supervisor_id' => $admin->id,
                'annual_leave_balance' => 12.0,
                'comp_time_balance' => 0.0,
                'is_active' => true,
            ]
        );

        $supervisor = User::updateOrCreate(
            ['email' => 'supervisor@aminef.or.id'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'role' => 'supervisor',
                'department' => 'Program & Grants',
                'position' => 'Program Manager',
                'supervisor_id' => $hrd->id,
                'annual_leave_balance' => 12.0,
                'comp_time_balance' => 0.0,
                'is_active' => true,
            ]
        );

        $staff = User::updateOrCreate(
            ['email' => 'staff@aminef.or.id'],
            [
                'name' => 'Abiyu Pratama',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'department' => 'Program & Grants',
                'position' => 'Program Officer',
                'supervisor_id' => $supervisor->id,
                'annual_leave_balance' => 10.0,
                'comp_time_balance' => 0.0,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'nadia@aminef.or.id'],
            [
                'name' => 'Nadia Kusuma',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'department' => 'Finance & Admin',
                'position' => 'Finance Officer',
                'supervisor_id' => $supervisor->id,
                'annual_leave_balance' => 12.0,
                'comp_time_balance' => 0.0,
                'is_active' => true,
            ]
        );

        $holidays = [
            ['date' => '2026-01-01', 'name' => "New Year's Day", 'type' => 'national_holiday'],
            ['date' => '2026-01-16', 'name' => "Isra Mi'raj of Prophet Muhammad SAW", 'type' => 'national_holiday'],
            ['date' => '2026-01-19', 'name' => "Martin Luther King, Jr.'s Birthday", 'type' => 'national_holiday'],
            ['date' => '2026-02-16', 'name' => "Washington's Birthday", 'type' => 'national_holiday'],
            ['date' => '2026-02-17', 'name' => 'Chinese New Year 2577', 'type' => 'national_holiday'],
            ['date' => '2026-03-18', 'name' => 'Idul Fitri 1447 Hijriyah (Collective Leave)', 'type' => 'joint_leave'],
            ['date' => '2026-03-19', 'name' => "Balinese Hindu's New Year / Silent Day", 'type' => 'national_holiday'],
            ['date' => '2026-03-20', 'name' => 'Idul Fitri 1447 Hijriyah (Collective Leave)', 'type' => 'joint_leave'],
            ['date' => '2026-03-23', 'name' => 'Idul Fitri 1447 Hijriyah', 'type' => 'national_holiday'],
            ['date' => '2026-03-24', 'name' => 'Idul Fitri 1447 Hijriyah', 'type' => 'national_holiday'],
            ['date' => '2026-04-03', 'name' => 'Good Friday', 'type' => 'national_holiday'],
            ['date' => '2026-05-01', 'name' => 'International Labor Day', 'type' => 'national_holiday'],
            ['date' => '2026-05-14', 'name' => 'Ascension of Christ', 'type' => 'national_holiday'],
            ['date' => '2026-05-25', 'name' => 'Memorial Day', 'type' => 'national_holiday'],
            ['date' => '2026-05-27', 'name' => 'Idul Adha 1447 Hijriyah', 'type' => 'national_holiday'],
            ['date' => '2026-06-01', 'name' => 'Pancasila Day', 'type' => 'national_holiday'],
            ['date' => '2026-06-16', 'name' => 'Islamic New Year 1448 Hijriyah', 'type' => 'national_holiday'],
            ['date' => '2026-06-19', 'name' => 'Juneteenth National Independence Day', 'type' => 'national_holiday'],
            ['date' => '2026-07-03', 'name' => 'U.S. Independence Day (Observed)', 'type' => 'national_holiday'],
            ['date' => '2026-08-17', 'name' => 'Indonesian Independence Day', 'type' => 'national_holiday'],
            ['date' => '2026-08-25', 'name' => 'Birthday of Prophet Muhammad SAW', 'type' => 'national_holiday'],
            ['date' => '2026-09-07', 'name' => 'Labor Day (US)', 'type' => 'national_holiday'],
            ['date' => '2026-10-12', 'name' => 'Columbus Day', 'type' => 'national_holiday'],
            ['date' => '2026-11-11', 'name' => 'Veterans Day', 'type' => 'national_holiday'],
            ['date' => '2026-11-26', 'name' => 'Thanksgiving Day', 'type' => 'national_holiday'],
            ['date' => '2026-12-25', 'name' => 'Christmas Day', 'type' => 'national_holiday'],
        ];
        foreach ($holidays as $holiday) {
            $existing = Holiday::whereDate('date', $holiday['date'])->first();
            if ($existing) {
                $existing->update($holiday);
            } else {
                Holiday::create($holiday);
            }
        }

        if (LeaveRequest::count() === 0) {
            LeaveRequest::create([
                'user_id' => $staff->id,
                'supervisor_id' => $supervisor->id,
                'type' => 'annual',
                'start_date' => now()->addDays(3)->toDateString(),
                'end_date' => now()->addDays(4)->toDateString(),
                'total_days' => 2.0,
                'reason' => 'Family gathering out of town',
                'pic_handover' => 'Nadia Kusuma',
                'medical_note_status' => 'not_required',
                'status' => 'approved',
                'approved_at' => now()->subDay(),
            ]);

            LeaveRequest::create([
                'user_id' => $staff->id,
                'supervisor_id' => $supervisor->id,
                'type' => 'sick',
                'start_date' => now()->subDays(2)->toDateString(),
                'end_date' => now()->subDays(1)->toDateString(),
                'total_days' => 2.0,
                'reason' => 'High fever and doctor advised rest',
                'medical_note_status' => 'pending_upload',
                'status' => 'pending',
            ]);
        }
    }
}
