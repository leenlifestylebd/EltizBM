<?php

namespace App\Http\Controllers;

use App\Models\Employee;
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
                SUM(CASE WHEN type IN (\'salary\',\'advance\',\'bonus\') THEN amount ELSE 0 END) as month_paid,
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
            // Handles salary-only, production-only, and salary+production workers.
            $emp->month_base = (float)$emp->salary > 0 ? (float)$emp->salary : 0;
            $emp->month_net  = $emp->month_base + $emp->month_earning - $emp->month_advance + $emp->month_deducted;
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

        Employee::create([
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

        return redirect()->route('employees.index')->with('success', 'Employee added!');
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
            ->whereIn('status', ['present', 'late'])->count();

        // Salary-employee extras: total paid this month and recent payments
        $monthTotalPaid   = EmployeePayment::where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->whereIn('type', ['salary', 'advance', 'bonus'])->sum('amount');
        $monthDeductions  = EmployeePayment::where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->where('type', 'deduction')->sum('amount');
        // Unified net = base salary + production earning − paid + deductions.
        $monthBase        = (float)$employee->salary > 0 ? (float)$employee->salary : 0;
        $monthNetPayable  = $monthBase + (float)$monthEarning - (float)$monthTotalPaid + (float)$monthDeductions;
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
            'name' => 'required|string|max:100',
            'type' => 'nullable|in:salary,production,both',
        ]);

        $employee->update([
            'user_id'      => $request->user_id ?: null,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'designation'  => $request->designation,
            'department'   => $request->department,
            'type'         => $request->type ?? $employee->type ?? 'salary',
            'salary'       => $request->salary ?? 0,
            'joining_date' => $request->joining_date,
            'status'       => $request->status,
        ]);

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