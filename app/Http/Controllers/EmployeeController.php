<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\ProductionEntry;
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

        $query = Employee::where('business_id', $bizId)->with('salaries')->orderBy('name');

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

        // Replace N+1 loops with batch aggregate queries keyed by employee_id
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
            ->selectRaw('employee_id,
                SUM(CASE WHEN type IN (\'salary\',\'advance\') THEN amount ELSE 0 END) as month_paid,
                SUM(CASE WHEN type = \'deduction\' THEN amount ELSE 0 END) as month_deducted')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        $attendanceStats = Attendance::whereIn('employee_id', $empIds)
            ->whereBetween('attendance_date', [$monthStart, $today])
            ->whereIn('status', ['present', 'late'])
            ->selectRaw('employee_id, COUNT(*) as days_present')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        foreach ($employees as $emp) {
            $prod = $productionStats->get($emp->id);
            $pay  = $paymentStats->get($emp->id);
            $att  = $attendanceStats->get($emp->id);

            $emp->today_production = $prod->today_qty ?? 0;
            $emp->month_production = $prod->month_qty ?? 0;
            $emp->month_earning    = $prod->month_earn ?? 0;
            $emp->month_advance    = (float)($pay->month_paid ?? 0);
            $emp->month_deducted   = (float)($pay->month_deducted ?? 0);
            $emp->days_present     = $att->days_present ?? 0;

            // Unified: net = base salary + production − paid + deductions.
            // Base uses the salary effective for this month (salary-revision aware).
            $emp->month_base = $emp->salaryForMonth($monthStart);
            $emp->month_net  = $emp->month_base + $emp->month_earning - $emp->month_advance - $emp->month_deducted;
            $emp->is_hybrid  = $emp->month_base > 0 && $emp->month_earning > 0;
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
            'type'   => 'nullable|in:salary,production,both',
        ]);

        $employee = Employee::create([
            'business_id'  => auth()->user()->business_id,
            'user_id'      => $request->user_id ?: null,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'designation'  => $request->designation,
            'department'   => $request->department,
            'type'         => $request->type ?? 'salary',
            'salary'       => $request->salary ?? 0,
            'joining_date' => $request->joining_date,
            'status'       => $request->status ?? 'active',
        ]);

        // Seed salary history (effective from the joining month).
        if ((float)($request->salary ?? 0) > 0) {
            $eff = $request->joining_date
                ? Carbon::parse($request->joining_date)->startOfMonth()
                : now()->startOfMonth();
            EmployeeSalary::create([
                'business_id'    => $employee->business_id,
                'employee_id'    => $employee->id,
                'amount'         => $request->salary,
                'effective_from' => $eff->toDateString(),
                'created_by'     => auth()->id(),
            ]);
        }

        return redirect()->route('employees.index')->with('success', 'Employee added!');
    }

    public function show(Request $request, Employee $employee)
    {
        $this->authorize($employee);

        // Month-aware: honour the selected month (defaults to the current one) so
        // past months can be reviewed instead of always showing the running month.
        $month      = $request->month ?? now()->format('Y-m');
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd   = Carbon::parse($month . '-01')->endOfMonth();

        $monthProduction = ProductionEntry::where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$monthStart, $monthEnd])->sum('quantity');

        $monthEarning = ProductionEntry::where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$monthStart, $monthEnd])->sum('amount');

        $monthAdvance = EmployeePayment::where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->whereIn('type', ['advance', 'deduction'])->sum('amount');

        $monthAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->whereIn('status', ['present', 'late'])->count();

        // Salary-employee extras: total paid this month and recent payments
        $monthTotalPaid   = EmployeePayment::where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->whereIn('type', ['salary', 'advance'])->sum('amount');
        $monthDeductions  = EmployeePayment::where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->where('type', 'deduction')->sum('amount');
        // Unified net = base salary + production earning − paid + deductions.
        // Base = the salary effective for this month (salary-revision aware).
        $monthBase        = $employee->salaryForMonth($monthStart);
        $monthNetPayable  = $monthBase + (float)$monthEarning - (float)$monthTotalPaid - (float)$monthDeductions;
        $isHybrid         = $monthBase > 0 && (float)$monthEarning > 0;

        $recentPayments   = EmployeePayment::where('employee_id', $employee->id)
            ->orderByDesc('payment_date')->limit(5)->get();

        $recentEntries = ProductionEntry::where('employee_id', $employee->id)
            ->with('productionRate')
            ->orderByDesc('entry_date')->limit(5)->get();

        $productionEntries = ProductionEntry::where('employee_id', $employee->id)
            ->with('productionRate')
            ->orderByDesc('entry_date')->paginate(20);

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

        $calendarStart = $monthStart->copy()->dayOfWeek;
        $daysInMonth   = $monthStart->copy()->daysInMonth;
        $attendanceMap = Attendance::where('employee_id', $employee->id)
            ->whereYear('attendance_date', $monthStart->year)
            ->whereMonth('attendance_date', $monthStart->month)
            ->get()->keyBy(fn($a) => $a->attendance_date->format('Y-m-d'));

        $monthLabel = $monthStart->format('F Y');

        return view('employees.show', compact(
            'employee', 'monthProduction', 'monthEarning', 'monthAdvance',
            'monthAttendance', 'monthTotalPaid', 'monthDeductions', 'monthNetPayable',
            'monthBase', 'isHybrid', 'month', 'monthLabel',
            'recentPayments', 'recentEntries', 'productionEntries', 'products',
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
        $request->validate([
            'name'                  => 'required|string|max:100',
            'type'                  => 'nullable|in:salary,production,both',
            'salary'                => 'nullable|numeric|min:0',
            'salary_effective_from' => 'nullable|date',
        ]);

        $newSalary = (float)($request->salary ?? 0);

        $employee->update([
            'user_id'      => $request->user_id ?: null,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'designation'  => $request->designation,
            'department'   => $request->department,
            'type'         => $request->type ?? $employee->type ?? 'salary',
            'salary'       => $newSalary,
            'joining_date' => $request->joining_date,
            'status'       => $request->status,
        ]);

        // Record a salary revision at the chosen effective month. The month is
        // authoritative — upsert it so a back-dated effective-from is honoured even
        // when the amount is unchanged. updateOrCreate keeps one row per month, so
        // re-saving the same month is a harmless no-op. Earlier months (different
        // effective_from rows) keep their own rate.
        if ($newSalary > 0) {
            $eff = $request->salary_effective_from
                ? Carbon::parse($request->salary_effective_from)->startOfMonth()
                : now()->startOfMonth();
            EmployeeSalary::updateOrCreate(
                ['employee_id' => $employee->id, 'effective_from' => $eff->toDateString()],
                ['business_id' => $employee->business_id, 'amount' => $newSalary, 'created_by' => auth()->id()]
            );
        }

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated!');
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

    /**
     * Set / reset a worker's app-login PIN (phone + PIN). Creates or updates the
     * linked employee-role User so the worker can log in via /worker-login.
     */
    public function setPin(Request $request, Employee $employee)
    {
        $this->authorize($employee);
        abort_unless(in_array(auth()->user()->role, ['owner', 'admin']), 403);

        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'pin'   => ['required', 'string', 'regex:/^\d{4,6}$/'],
        ], [
            'pin.regex' => 'PIN must be 4–6 digits.',
        ]);

        $bizId = $employee->business_id;
        $user  = $employee->user;

        // Refuse to overwrite a real staff/admin account's password with a worker PIN.
        if ($user && $user->role !== 'employee') {
            return back()->with('error', 'এই কর্মী একটি admin/staff account-এর সাথে যুক্ত — আলাদা worker PIN দিতে আগে unlink করুন।');
        }

        // Compare on the normalised key so "01712-345678" and "01712345678" are
        // recognised as the same number — both for the clash check and at login.
        $phoneKey = User::phoneKey($data['phone']);

        $clash = User::where('business_id', $bizId)
            ->where('role', 'employee')
            ->where('phone', 'like', '%' . $phoneKey)
            ->when($user, fn ($q) => $q->where('id', '!=', $user->id))
            ->get()
            ->contains(fn ($u) => User::phoneKey($u->phone) === $phoneKey);
        if ($clash) {
            return back()->with('error', 'এই ফোন নম্বর আরেকজন কর্মীর লগইনে ব্যবহৃত হচ্ছে।');
        }

        DB::transaction(function () use ($employee, $data, $bizId, $user) {
            if ($user) {
                $user->update([
                    'phone'       => $data['phone'],
                    'password'    => \Illuminate\Support\Facades\Hash::make($data['pin']),
                    'is_active'   => true,
                    'is_approved' => true,
                ]);
            } else {
                $user = User::create([
                    'business_id' => $bizId,
                    'name'        => $employee->name,
                    'phone'       => $data['phone'],
                    'email'       => null,
                    'password'    => \Illuminate\Support\Facades\Hash::make($data['pin']),
                    'role'        => 'employee',
                    'is_active'   => true,
                    'is_approved' => true,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
                $employee->update(['user_id' => $user->id]);
            }

            if ($employee->phone !== $data['phone']) {
                $employee->update(['phone' => $data['phone']]);
            }
        });

        return back()->with('success', 'Worker login সেট হয়েছে — কর্মী ফোন ' . $data['phone'] . ' ও এই PIN দিয়ে লগইন করতে পারবে।');
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