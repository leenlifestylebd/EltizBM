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
        $monthEnd = now()->endOfMonth()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        // Replace N+1 loops with batch aggregate queries keyed by employee_id
        $empIds = $employees->pluck('id')->toArray();

        $productionStats = ProductionEntry::whereIn('employee_id', $empIds)
            ->whereBetween('entry_date', [$monthStart, Carbon::parse($monthEnd)->endOfDay()])
            ->selectRaw('employee_id,
                SUM(quantity) as month_qty,
                SUM(amount) as month_earn,
                SUM(CASE WHEN entry_date = ? THEN quantity ELSE 0 END) as today_qty', [$today])
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        $attendanceStats = Attendance::whereIn('employee_id', $empIds)
            ->whereBetween('attendance_date', [$monthStart, Carbon::parse($monthEnd)->endOfDay()])
            ->whereIn('status', ['present', 'late'])
            ->selectRaw('employee_id, COUNT(*) as days_present')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        foreach ($employees as $emp) {
            $prod = $productionStats->get($emp->id);
            $ledger = $emp->payrollLedger($monthStart, $monthEnd);
            $att  = $attendanceStats->get($emp->id);

            $emp->today_production = $prod->today_qty ?? 0;
            $emp->month_production = $prod->month_qty ?? 0;
            $emp->month_earning    = $prod->month_earn ?? 0;
            $emp->month_advance    = $ledger['paid'];
            $emp->month_deducted   = $ledger['deduction'];
            $emp->days_present     = $att->days_present ?? 0;

            // Unified: net = base salary + production − paid − deductions.
            // Base uses the salary effective for this month (salary-revision aware).
            $emp->month_base = $emp->salaryForMonth($monthStart);
            $emp->month_net  = $ledger['closing'];
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
            'joining_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'type'   => 'nullable|in:salary,production,both',
        ]);

        DB::transaction(function () use ($request) {
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
        });

        return redirect()->route('employees.index')->with('success', 'Employee added!');
    }

    public function show(Employee $employee)
    {
        $this->authorize($employee);

        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        $monthProduction = ProductionEntry::where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$monthStart, Carbon::parse($monthEnd)->endOfDay()])->sum('quantity');

        $monthEarning = ProductionEntry::where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$monthStart, Carbon::parse($monthEnd)->endOfDay()])->sum('amount');

        $monthAdvance = EmployeePayment::where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$monthStart, Carbon::parse($monthEnd)->endOfDay()])
            ->whereIn('type', ['advance', 'deduction'])->sum('amount');

        $monthAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$monthStart, Carbon::parse($monthEnd)->endOfDay()])
            ->whereIn('status', ['present', 'late'])->count();

        // Salary-employee extras: total paid this month and recent payments
        $monthTotalPaid   = EmployeePayment::where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$monthStart, Carbon::parse($monthEnd)->endOfDay()])
            ->whereIn('type', ['salary', 'advance'])->sum('amount');
        $monthDeductions  = EmployeePayment::where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$monthStart, Carbon::parse($monthEnd)->endOfDay()])
            ->where('type', 'deduction')->sum('amount');
        // Unified net = base salary + production earning − paid − deductions.
        // Base = the salary effective for this month (salary-revision aware).
        $monthBase        = $employee->salaryForMonth($monthStart);
        $monthNetPayable  = $employee->payrollLedger($monthStart, $monthEnd)['closing'];
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

        $calendarStart = now()->startOfMonth()->dayOfWeek;
        $daysInMonth   = now()->daysInMonth;
        $attendanceMap = Attendance::where('employee_id', $employee->id)
            ->whereYear('attendance_date', now()->year)
            ->whereMonth('attendance_date', now()->month)
            ->get()->keyBy(fn($a) => $a->attendance_date->format('Y-m-d'));

        return view('employees.show', compact(
            'employee', 'monthProduction', 'monthEarning', 'monthAdvance',
            'monthAttendance', 'monthTotalPaid', 'monthDeductions', 'monthNetPayable',
            'monthBase', 'isHybrid',
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
            'joining_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($request, $employee) {
            $employee = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $oldSalary = (float) $employee->salary;
            $oldStart = $employee->salaryStartMonth();
            $hadHistory = $employee->salaries->isNotEmpty();
            $newType = $request->type ?? $employee->type;
            $toProduction = $newType === 'production'
                && $employee->type !== 'production';
            $recordSalary = $request->has('salary') || $toProduction;
            $newSalary = $newType === 'production' ? 0.0 : (float) ($request->salary ?? $oldSalary);
            $effective = $request->salary_effective_from
                ? Carbon::parse($request->salary_effective_from)->startOfMonth()
                : now()->startOfMonth();

            $employee->fill([
                'user_id' => $request->has('user_id') ? ($request->user_id ?: null) : $employee->user_id,
                'name' => $request->name,
                'phone' => $request->input('phone', $employee->phone),
                'email' => $request->input('email', $employee->email),
                'designation' => $request->input('designation', $employee->designation),
                'department' => $request->input('department', $employee->department),
                'type' => $newType,
                'joining_date' => $request->input('joining_date', $employee->joining_date),
                'status' => $request->status ?? $employee->status,
            ]);

            if ($recordSalary) {
                // Preserve the legacy rate before writing the first raise/zero revision.
                if (!$hadHistory && $oldStart && $oldStart->lt($effective)) {
                    EmployeeSalary::create([
                        'business_id' => $employee->business_id, 'employee_id' => $employee->id,
                        'effective_from' => $oldStart->toDateString(), 'amount' => $oldSalary,
                        'created_by' => auth()->id(),
                    ]);
                }
                $revision = EmployeeSalary::where('employee_id', $employee->id)
                    ->whereDate('effective_from', $effective->toDateString())
                    ->orderByDesc('id')->first() ?? new EmployeeSalary;
                $revision->fill([
                    'employee_id' => $employee->id, 'effective_from' => $effective->toDateString(),
                    'business_id' => $employee->business_id, 'amount' => $newSalary, 'created_by' => auth()->id(),
                ])->save();
                $employee->unsetRelation('salaries');
                // A future raise must not become today's displayed salary.
                $employee->salary = $employee->salaryForMonth(now());
            }
            $employee->save();
        });

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
