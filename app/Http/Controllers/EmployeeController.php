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
        $today      = today();
        $monthStart = now()->startOfMonth();

        foreach ($employees as $emp) {
            $emp->today_production = ProductionEntry::where('employee_id', $emp->id)
                ->whereDate('entry_date', $today)->sum('quantity');
            $emp->month_production = ProductionEntry::where('employee_id', $emp->id)
                ->whereBetween('entry_date', [$monthStart, $today])->sum('quantity');
            $emp->month_earning    = ProductionEntry::where('employee_id', $emp->id)
                ->whereBetween('entry_date', [$monthStart, $today])->sum('amount');
            $emp->month_advance    = EmployeePayment::where('employee_id', $emp->id)
                ->whereBetween('payment_date', [$monthStart, $today])
                ->whereIn('type', ['advance', 'deduction'])->sum('amount');
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

        Employee::create([
            'business_id'  => auth()->user()->business_id,
            'user_id'      => $request->user_id ?: null,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'designation'  => $request->designation,
            'department'   => $request->department,
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
            ->where('status', 'present')->count();

        $recentEntries = ProductionEntry::where('employee_id', $employee->id)
            ->with('productionRate')
            ->orderByDesc('entry_date')->limit(5)->get();

        $productionEntries = ProductionEntry::where('employee_id', $employee->id)
            ->with('productionRate')
            ->orderByDesc('entry_date')->paginate(20);

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

        $employee->update([
            'user_id'      => $request->user_id ?: null,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'designation'  => $request->designation,
            'department'   => $request->department,
            'salary'       => $request->salary ?? 0,
            'joining_date' => $request->joining_date,
            'status'       => $request->status,
        ]);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated!');
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