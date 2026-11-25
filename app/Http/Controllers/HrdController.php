<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BalanceAdjustment;
use App\Models\CompTimeRequest;
use App\Models\DocumentTemplate;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrdController extends Controller
{
    private function authorizeHrd(): void
    {
        // Route-level `menu:*` middleware already enforces role-based menu permissions.
    }

    private function sanitizeSpreadsheetCell(?string $value): string
    {
        $str = (string) ($value ?? '');
        if ($str !== '' && preg_match('/^[=+\-@\t\r]/', $str) === 1) {
            return "'".$str;
        }

        return $str;
    }

    public function allLeaves(Request $request): View
    {
        $this->authorizeHrd();

        $tab = $request->query('tab', 'leaves');
        $limit = (int) $request->query('limit', 10);
        if (! in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }

        $leaveQuery = LeaveRequest::with(['user', 'supervisor']);
        if ($type = $request->query('type')) {
            $leaveQuery->where('type', $type);
        }
        if ($status = $request->query('status')) {
            $leaveQuery->where('status', $status);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $leaveQuery->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('department', 'like', "%{$search}%"));
        }
        $leaves = $leaveQuery->latest()->paginate($limit)->withQueryString();

        $compQuery = CompTimeRequest::with(['user', 'supervisor']);
        if ($status = $request->query('status')) {
            $compQuery->where('status', $status);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $compQuery->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }
        $compTimes = $compQuery->latest()->paginate($limit, ['*'], 'comp_page')->withQueryString();

        return view('hrd.leaves', compact('tab', 'limit', 'leaves', 'compTimes'));
    }

    public function employees(Request $request): View
    {
        $this->authorizeHrd();

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

        $employees = $query->orderBy('name')->paginate($limit)->withQueryString();
        $supervisors = User::whereIn('role', ['supervisor', 'hrd'])->orderBy('name')->get();

        return view('hrd.employees', compact('employees', 'supervisors', 'limit'));
    }

    public function storeEmployee(Request $request): RedirectResponse
    {
        $this->authorizeHrd();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:staff,supervisor,hrd'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'annual_leave_balance' => ['required', 'numeric', 'min:0', 'max:60'],
            'comp_time_balance' => ['required', 'numeric', 'min:0', 'max:60'],
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        AuditLog::record(Auth::id(), 'employee_created', 'user', $user->id);

        return back()->with('success', "Employee {$user->name} added successfully.");
    }

    public function updateEmployee(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeHrd();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', 'in:staff,supervisor,hrd'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['required', 'boolean'],
            'new_password' => ['nullable', 'string', 'min:8'],
        ]);

        $data = [
            'name' => $validated['name'],
            'role' => $validated['role'],
            'department' => $validated['department'],
            'position' => $validated['position'],
            'supervisor_id' => $validated['supervisor_id'] === (string) $employee->id ? null : $validated['supervisor_id'],
            'is_active' => (bool) $validated['is_active'],
        ];
        if (! empty($validated['new_password'])) {
            $data['password'] = Hash::make($validated['new_password']);
        }

        $employee->update($data);
        AuditLog::record(Auth::id(), 'employee_updated', 'user', $employee->id);

        return back()->with('success', "Employee {$employee->name} updated.");
    }

    public function adjustBalance(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeHrd();

        $validated = $request->validate([
            'balance_type' => ['required', 'in:annual,comp_time'],
            'amount' => ['required', 'numeric', 'min:-30', 'max:60'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $column = $validated['balance_type'] === 'annual' ? 'annual_leave_balance' : 'comp_time_balance';
        $newBalance = max(0, (float) $employee->{$column} + (float) $validated['amount']);
        $employee->update([$column => $newBalance]);

        BalanceAdjustment::create([
            'user_id' => $employee->id,
            'adjusted_by' => Auth::id(),
            'balance_type' => $validated['balance_type'],
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
        ]);

        AuditLog::record(Auth::id(), 'balance_adjusted', 'user', $employee->id, $validated);

        return back()->with('success', "Balance adjusted for {$employee->name}.");
    }

    public function downloadEmployeeTemplate(): StreamedResponse
    {
        $this->authorizeHrd();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employees Import');

        $headers = ['name', 'email', 'role', 'department', 'position', 'supervisor_email', 'annual_leave_balance', 'password'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            'Rina Wijaya',
            'rina@aminef.or.id',
            'staff',
            'Program & Grants',
            'Program Assistant',
            'supervisor@aminef.or.id',
            12,
            'password123',
        ], null, 'A2');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'AMINEF-Employees-Import-Template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importEmployees(Request $request): RedirectResponse
    {
        $this->authorizeHrd();

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (count($rows) < 2) {
            return back()->withErrors(['file' => 'The uploaded file has no data rows.']);
        }

        $header = array_map(fn ($h) => Str::slug(trim((string) $h), '_'), $rows[0]);
        $supervisorLinks = [];
        $importedCount = 0;
        $createdRoles = 0;

        // Pass 1: Auto-create missing roles & upsert employees
        foreach (array_slice($rows, 1) as $row) {
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = [];
            foreach ($header as $idx => $col) {
                if ($col !== '') {
                    $data[$col] = isset($row[$idx]) ? trim((string) $row[$idx]) : '';
                }
            }

            $email = strtolower($data['email'] ?? '');
            $name = $data['name'] ?? '';
            if ($email === '' || $name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $rawRole = $data['role'] ?: 'staff';
            $roleSlug = Str::slug($rawRole, '_');
            if (! Role::where('slug', $roleSlug)->exists()) {
                Role::create([
                    'slug' => $roleSlug,
                    'name' => Str::title(str_replace('_', ' ', $rawRole)),
                    'approver_role_slug' => 'supervisor',
                    'menu_permissions' => ['dashboard', 'profile', 'leaves'],
                    'is_system' => false,
                ]);
                $createdRoles++;
            }

            $balance = is_numeric($data['annual_leave_balance'] ?? null)
                ? (float) $data['annual_leave_balance']
                : 12.0;

            $existing = User::where('email', $email)->first();
            if ($existing) {
                $existing->update([
                    'name' => $name,
                    'role' => $roleSlug,
                    'department' => ($data['department'] ?? '') ?: $existing->department,
                    'position' => ($data['position'] ?? '') ?: $existing->position,
                    'annual_leave_balance' => $balance,
                ]);
            } else {
                $password = ($data['password'] ?? '') ?: 'password123';
                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role' => $roleSlug,
                    'department' => $data['department'] ?? null,
                    'position' => $data['position'] ?? null,
                    'annual_leave_balance' => $balance,
                    'comp_time_balance' => 0,
                    'is_active' => true,
                ]);
            }

            if (! empty($data['supervisor_email'])) {
                $supervisorLinks[$email] = strtolower($data['supervisor_email']);
            }
            $importedCount++;
        }

        // Pass 2: Link supervisor_id regardless of row order
        foreach ($supervisorLinks as $empEmail => $supEmail) {
            $supId = User::where('email', $supEmail)->value('id');
            if ($supId) {
                User::where('email', $empEmail)->update(['supervisor_id' => $supId]);
            }
        }

        AuditLog::record(Auth::id(), 'employees_imported', 'user', null, [
            'imported_count' => $importedCount,
            'created_roles' => $createdRoles,
        ]);

        return back()->with('success', "Imported {$importedCount} employee(s) successfully".($createdRoles > 0 ? " ({$createdRoles} new role(s) auto-created)." : '.'));
    }

    public function holidays(Request $request): View
    {
        $this->authorizeHrd();

        $year = (int) $request->query('year', now()->year);
        $holidays = Holiday::whereYear('date', $year)->orderBy('date')->get();

        return view('hrd.holidays', compact('holidays', 'year'));
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $this->authorizeHrd();

        $validated = $request->validate([
            'date' => ['required', 'date', 'unique:holidays,date'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:national_holiday,joint_leave'],
        ]);

        Holiday::create($validated);

        return back()->with('success', 'Holiday added.');
    }

    public function destroyHoliday(Holiday $holiday): RedirectResponse
    {
        $this->authorizeHrd();
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }

    public function templates(): View
    {
        $this->authorizeHrd();
        $templates = DocumentTemplate::with('updater')->get()->keyBy('key');

        return view('hrd.templates', compact('templates'));
    }

    public function uploadTemplate(Request $request): RedirectResponse
    {
        $this->authorizeHrd();

        $request->validate([
            'key' => ['required', 'in:comp_time_template,leave_template'],
            'title' => ['required', 'string', 'max:120'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $existing = DocumentTemplate::where('key', $request->key)->first();
        if ($existing && Storage::disk('public')->exists($existing->file_path)) {
            Storage::disk('public')->delete($existing->file_path);
        }

        $file = $request->file('file');
        $path = $file->store('templates', 'public');

        DocumentTemplate::updateOrCreate(
            ['key' => $request->key],
            [
                'title' => $request->title,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'updated_by' => Auth::id(),
            ]
        );

        return back()->with('success', 'Template updated successfully.');
    }

    public function reports(Request $request): View
    {
        $this->authorizeHrd();

        $year = (int) $request->query('year', now()->year);
        $employees = $this->buildReportRows($year);

        return view('hrd.reports', compact('employees', 'year'));
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorizeHrd();

        $year = (int) $request->query('year', now()->year);
        $rows = $this->buildReportRows($year);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Leave Recap {$year}");

        $headers = ['Employee Name', 'Department', 'Role', 'Annual Used', 'Annual Remaining', 'Comp-Time Days', 'Sick Leave Days'];
        $sheet->fromArray($headers, null, 'A1');

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $this->sanitizeSpreadsheetCell($row['user']->name),
                $this->sanitizeSpreadsheetCell($row['user']->department ?? '-'),
                strtoupper($row['user']->role),
                $row['annual_used'],
                (float) $row['user']->annual_leave_balance,
                $row['comp_used'],
                $row['sick_used'],
            ], null, "A{$rowIndex}");
            $rowIndex++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "AMINEF-Leave-Recap-{$year}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorizeHrd();

        $year = (int) $request->query('year', now()->year);
        $employees = $this->buildReportRows($year);

        $pdf = Pdf::loadView('pdf.hrd-recap', compact('employees', 'year'))->setPaper('a4', 'landscape');

        return $pdf->stream("AMINEF-Leave-Recap-{$year}.pdf");
    }

    private function buildReportRows(int $year): array
    {
        $users = User::orderBy('name')->get();
        $rows = [];

        foreach ($users as $u) {
            $approvedLeaves = LeaveRequest::where('user_id', $u->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $year)
                ->get();

            $compEarned = CompTimeRequest::where('user_id', $u->id)
                ->where('status', 'approved')
                ->whereYear('work_date', $year)
                ->sum('days_requested');

            $rows[] = [
                'user' => $u,
                'annual_used' => (float) $approvedLeaves->where('type', 'annual')->sum('total_days'),
                'comp_used' => (float) $approvedLeaves->where('type', 'comp_time')->sum('total_days'),
                'sick_used' => (float) $approvedLeaves->where('type', 'sick')->sum('total_days'),
                'comp_earned' => (float) $compEarned,
            ];
        }

        return $rows;
    }
}
