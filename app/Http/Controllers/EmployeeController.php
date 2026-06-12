<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ProductionEntry;
use App\Models\ProductionLog;
use App\Models\EmployeePayment;
use App\Models\Attendance;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $query = Employee::where('business_id', $bizId)->orderBy('name');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $employees  = $query->paginate(18);
        $today      = today()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        // Replace N+1 loops with 2 batch aggregate queries keyed by employee_id
        $empIds = $employees->pluck('id')->toArray();

        $productionStats = ProductionEntry::whereIn('employee_id', $empIds)
            ->whereBetween('entry_date', [$monthStart, $today])
            ->selectRaw('employee_id,
                SUM(quantity) as month_qty,
                SUM(amount) as month_earn,
                SUM(CASE WHEN entry_date = ? THEN quantity ELSE 0 END) as today_qty', [$today])
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        $paymentStats = EmployeePayment::whereIn('employee_id', $empIds)
            ->whereBetween('payment_date', [$monthStart, $today])
            ->selectRaw('employee_id, SUM(amount) as month_paid')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        foreach ($employees as $emp) {
            $prod = $productionStats->get($emp->id);
            $pay  = $paymentStats->get($emp->id);
            $emp->today_production = $prod->today_qty ?? 0;
            $emp->month_production = $prod->month_qty ?? 0;
            $emp->month_earning    = $prod->month_earn ?? 0;
            $emp->month_advance    = $pay->month_paid ?? 0;
            $emp->month_net        = $emp->month_earning - $emp->month_advance;
        }

        $stats = [
            'total'         => Employee::where('business_id', $bizId)->count(),
            'active'        => Employee::where('business_id', $bizId)->where('status', 'active')->count(),
            'present_today' => Attendance::whereHas('employee', fn($q) => $q->where('business_id', $bizId))
                                ->whereDate('attendance_date', $today)
                                ->where('status', 'present')
                                ->count(),
        ];

        return view('employees.index', compact('employees', 'stats'));
    }

    public function create()
    {
        $bizId = auth()->user()->business_id;
        $users = User::where('business_id', $bizId)->orderBy('name')->get();
        return view('employees.form', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'phone'  => 'nullable|string|max:20',
            'salary' => 'nullable|numeric|min:0',
        ]);

        $isProduction = ($request->type ?? 'salary') === 'production';

        Employee::create([
            'business_id'  => auth()->user()->business_id,
            'user_id'      => $request->user_id ?: null,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'designation'  => $request->designation,
            'department'   => $request->department,
            'type'         => $request->type ?? 'salary',
            'salary'       => $isProduction ? 0 : ($request->salary ?? 0),
            'joining_date' => $request->joining_date,
            'status'       => $request->status ?? 'active',
        ]);

        return redirect()->route('employees.index')->with('success', 'Employee added!');
    }

    // =============================================
    // ONE-CLICK LOGIN ACCESS (create + link a worker user, set a PIN)
    // =============================================
    public function enableLogin(Request $request, Employee $employee)
    {
        $this->authorize($employee);

        $request->validate([
            'pin' => 'required|digits_between:4,6',
        ]);

        $bizId = auth()->user()->business_id;

        // Reuse the already-linked user, or one matching the employee's phone, else create.
        $user = $employee->user
            ?? ($employee->phone
                ? User::where('business_id', $bizId)->where('phone', $employee->phone)->first()
                : null);

        if (!$user) {
            // A login record is required to have an email; workers never type it — they
            // sign in with phone + PIN — so we synthesise a unique placeholder.
            $slug  = $employee->business->slug ?? ('biz' . $bizId);
            $email = 'emp' . $employee->id . '.' . ($employee->phone ?: \Illuminate\Support\Str::random(5)) . '@' . $slug . '.worker';

            $user = User::create([
                'business_id' => $bizId,
                'name'        => $employee->name,
                'email'       => strtolower($email),
                'phone'       => $employee->phone,
                'role'        => 'employee',
                'password'    => \Illuminate\Support\Facades\Hash::make($request->pin),
                'is_active'   => true,
                'is_approved' => 1,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        } else {
            $user->update([
                'role'      => 'employee',
                'phone'     => $employee->phone ?: $user->phone,
                'password'  => \Illuminate\Support\Facades\Hash::make($request->pin),
                'is_active' => true,
                'is_approved' => 1,
            ]);
        }

        $employee->update(['user_id' => $user->id]);

        return back()->with('success', $employee->name . ' can now log in with phone ' . ($employee->phone ?: '—') . ' and the PIN you set.');
    }

    public function resetPin(Request $request, Employee $employee)
    {
        $this->authorize($employee);
        $request->validate(['pin' => 'required|digits_between:4,6']);

        abort_if(!$employee->user_id, 422, 'This employee has no login yet.');

        User::where('id', $employee->user_id)
            ->where('business_id', auth()->user()->business_id)
            ->update(['password' => \Illuminate\Support\Facades\Hash::make($request->pin), 'is_active' => true]);

        return back()->with('success', 'PIN updated for ' . $employee->name . '.');
    }

    public function show(Employee $employee)
    {
        $this->authorize($employee);

        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        $monthProduction = ProductionEntry::where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$monthStart, $monthEnd])->sum('quantity');

        $monthEarning = ProductionEntry::where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$monthStart, $monthEnd])->sum('amount');

        $monthAdvance = EmployeePayment::where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->whereIn('type', ['advance', 'deduction'])->sum('amount');

        $monthAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->where('status', 'present')->count();

        $recentEntries = ProductionEntry::where('employee_id', $employee->id)
            ->with('productionRate')
            ->orderByDesc('entry_date')->limit(5)->get();

        $productionEntries = ProductionEntry::where('employee_id', $employee->id)
            ->with('productionRate')
            ->orderByDesc('entry_date')->paginate(20);

        $recentLogs = ProductionLog::where('employee_id', $employee->id)
            ->with('product')
            ->orderByDesc('log_date')->limit(5)->get();

        $productionLogs = ProductionLog::where('employee_id', $employee->id)
            ->with('product')
            ->orderByDesc('log_date')->paginate(20);

        $avgQuality = ProductionLog::where('employee_id', $employee->id)->avg('quality_rate') ?? 0;

        $products = Product::where('business_id', auth()->user()->business_id)
            ->orderBy('name')->get();

        $attendanceRecords = Attendance::where('employee_id', $employee->id)
            ->orderByDesc('attendance_date')->paginate(20);

        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $qty  = ProductionEntry::where('employee_id', $employee->id)
                ->whereDate('entry_date', $date)->sum('quantity');
            $last7Days->push(['label' => $date->format('D'), 'qty' => $qty, 'date' => $date->format('Y-m-d')]);
        }

        $calendarStart = now()->startOfMonth()->dayOfWeek;
        $daysInMonth   = now()->daysInMonth;
        $attendanceMap = Attendance::where('employee_id', $employee->id)
            ->whereYear('attendance_date', now()->year)
            ->whereMonth('attendance_date', now()->month)
            ->get()->keyBy(fn($a) => $a->attendance_date->format('Y-m-d'));

        return view('employees.show', compact(
            'employee', 'monthProduction', 'monthEarning', 'monthAdvance',
            'monthAttendance', 'recentEntries', 'productionEntries',
            'recentLogs', 'productionLogs', 'avgQuality', 'products',
            'attendanceRecords', 'last7Days', 'calendarStart', 'daysInMonth', 'attendanceMap'
        ));
    }

    public function edit(Employee $employee)
    {
        $this->authorize($employee);
        $bizId = auth()->user()->business_id;
        $users = User::where('business_id', $bizId)->orderBy('name')->get();
        return view('employees.form', compact('employee', 'users'));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorize($employee);
        $request->validate(['name' => 'required|string|max:100']);

        $isProduction = ($request->type ?? $employee->type) === 'production';

        $employee->update([
            'user_id'      => $request->user_id ?: null,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'designation'  => $request->designation,
            'department'   => $request->department,
            'type'         => $request->type ?? $employee->type,
            'salary'       => $isProduction ? 0 : ($request->salary ?? 0),
            'joining_date' => $request->joining_date,
            'status'       => $request->status,
        ]);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated!');
    }

    public function destroy(Employee $employee)
    {
        $this->authorize($employee);

        // Block if any active tasks exist (not completed/cancelled)
        $activeTasks = \App\Models\ProductionTask::where('employee_id', $employee->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        if ($activeTasks > 0) {
            return back()->with('error', "{$employee->name}-এর {$activeTasks}টি active task আছে। Delete করার আগে task গুলো complete বা cancel করুন।");
        }

        // Deactivate linked user account (do not delete — they may have other data)
        if ($employee->user_id) {
            User::where('id', $employee->user_id)
                ->where('business_id', auth()->user()->business_id)
                ->update(['is_active' => false]);
        }

        $name = $employee->name;
        $employee->delete(); // SoftDelete — all history preserved

        $this->logActivity('deleted', $employee, ['name' => $name], []);

        return redirect()->route('employees.index')
            ->with('success', "{$name} deleted. All payroll and production history is preserved.");
    }

    public function production(Employee $employee)
    {
        $this->authorize($employee);
        return redirect()->route('employees.show', $employee)->with('tab', 'production');
    }

    public function attendance(Employee $employee)
    {
        $this->authorize($employee);
        return redirect()->route('employees.show', $employee);
    }

    public function storeAttendance(Request $request, Employee $employee)
    {
        $this->authorize($employee);

        $request->validate([
            'attendance_date' => 'required|date',
            'status'          => 'required|in:present,absent,late,half_day,holiday',
        ]);

        Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'attendance_date' => $request->attendance_date],
            [
                'business_id' => auth()->user()->business_id,
                'status'      => $request->status,
                'check_in'    => $request->check_in,
                'check_out'   => $request->check_out,
                'notes'       => $request->notes,
            ]
        );

        return back()->with('success', 'Attendance saved!');
    }

    public function attendanceOverview(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $date  = $request->date ? Carbon::parse($request->date) : today();

        $employees = Employee::where('business_id', $bizId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $records = Attendance::whereHas('employee', fn($q) => $q->where('business_id', $bizId))
            ->whereDate('attendance_date', $date)
            ->with('employee')
            ->get()
            ->keyBy('employee_id');

        $summary = [
            'present'    => $records->where('status', 'present')->count(),
            'absent'     => $records->where('status', 'absent')->count(),
            'late'       => $records->where('status', 'late')->count(),
            'not_marked' => $employees->count() - $records->count(),
        ];

        return view('attendance.overview', compact('employees', 'records', 'summary', 'date'));
    }

    private function authorize(Employee $employee): void
    {
        abort_if($employee->business_id !== auth()->user()->business_id, 403);
    }
}