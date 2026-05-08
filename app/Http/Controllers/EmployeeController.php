<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ProductionLog;
use App\Models\Attendance;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    // =============================================
    // INDEX
    // =============================================
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

        $employees = $query->paginate(18);

        // Add today's production and month production
        $today      = today();
        $monthStart = now()->startOfMonth();

        foreach ($employees as $emp) {
            $emp->today_production = ProductionLog::where('employee_id', $emp->id)
                ->whereDate('log_date', $today)->sum('quantity');
            $emp->month_production = ProductionLog::where('employee_id', $emp->id)
                ->whereBetween('log_date', [$monthStart, $today])->sum('quantity');
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

    // =============================================
    // CREATE / STORE
    // =============================================
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

    // =============================================
    // SHOW
    // =============================================
    public function show(Employee $employee)
    {
        $this->authorize($employee);

        $products = Product::where('business_id', auth()->user()->business_id)->get();

        // Stats
        $monthStart      = now()->startOfMonth();
        $monthEnd        = now()->endOfMonth();
        $monthProduction = ProductionLog::where('employee_id', $employee->id)
            ->whereBetween('log_date', [$monthStart, $monthEnd])->sum('quantity');

        $monthAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->where('status', 'present')->count();

        $avgQuality = ProductionLog::where('employee_id', $employee->id)
            ->where('quality_rate', '>', 0)->avg('quality_rate') ?? 0;

        // Recent logs
        $recentLogs = ProductionLog::where('employee_id', $employee->id)
            ->with('product')->orderByDesc('log_date')->limit(5)->get();

        $productionLogs = ProductionLog::where('employee_id', $employee->id)
            ->with('product')->orderByDesc('log_date')->paginate(20);

        $attendanceRecords = Attendance::where('employee_id', $employee->id)
            ->orderByDesc('attendance_date')->paginate(20);

        // Last 7 days production for bar chart
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $qty  = ProductionLog::where('employee_id', $employee->id)
                ->whereDate('log_date', $date)->sum('quantity');
            $last7Days->push(['label' => $date->format('D'), 'qty' => $qty, 'date' => $date->format('Y-m-d')]);
        }

        // Calendar data for current month
        $calendarStart = now()->startOfMonth()->dayOfWeek;
        $daysInMonth   = now()->daysInMonth;
        $attendanceMap = Attendance::where('employee_id', $employee->id)
            ->whereYear('attendance_date', now()->year)
            ->whereMonth('attendance_date', now()->month)
            ->get()->keyBy(fn($a) => $a->attendance_date->format('Y-m-d'));

        return view('employees.show', compact(
            'employee', 'products', 'monthProduction', 'monthAttendance',
            'avgQuality', 'recentLogs', 'productionLogs', 'attendanceRecords',
            'last7Days', 'calendarStart', 'daysInMonth', 'attendanceMap'
        ));
    }

    // =============================================
    // EDIT / UPDATE
    // =============================================
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

    // =============================================
    // PRODUCTION LOG
    // =============================================
    public function production(Employee $employee)
    {
        $this->authorize($employee);
        return redirect()->route('employees.show', $employee)->with('tab', 'production');
    }

    public function storeProduction(Request $request, Employee $employee)
    {
        $this->authorize($employee);

        $request->validate([
            'task_name' => 'required|string|max:255',
            'quantity'  => 'required|integer|min:0',
            'log_date'  => 'required|date',
        ]);

        ProductionLog::create([
            'business_id'     => auth()->user()->business_id,
            'employee_id'     => $employee->id,
            'product_id'      => $request->product_id ?: null,
            'task_name'       => $request->task_name,
            'quantity'        => $request->quantity,
            'target_quantity' => $request->target_quantity ?? 0,
            'quality_rate'    => $request->quality_rate ?? 100,
            'notes'           => $request->notes,
            'log_date'        => $request->log_date,
            'start_time'      => $request->start_time,
            'end_time'        => $request->end_time,
        ]);

        return back()->with('success', 'Production logged!');
    }

    // =============================================
    // ATTENDANCE
    // =============================================
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

    // =============================================
    // PRODUCTION OVERVIEW (all employees)
    // =============================================
    public function productionOverview(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $date  = $request->date ? Carbon::parse($request->date) : today();

        $employees = Employee::where('business_id', $bizId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $employeeStats = [];
        $totalQty      = 0;
        $workersLogged = 0;
        $qualitySum    = 0;
        $qualityCount  = 0;
        $targetMet     = 0;
        $targetTotal   = 0;

        foreach ($employees as $emp) {
            $logs = ProductionLog::where('employee_id', $emp->id)
                ->whereDate('log_date', $date)
                ->with('product')
                ->get();

            $attendance = Attendance::where('employee_id', $emp->id)
                ->whereDate('attendance_date', $date)
                ->first();

            $qty    = $logs->sum('quantity');
            $target = $logs->sum('target_quantity');
            $quality= $logs->where('quality_rate', '>', 0)->avg('quality_rate') ?? 0;

            if ($qty > 0) $workersLogged++;
            $totalQty += $qty;

            if ($quality > 0) {
                $qualitySum += $quality;
                $qualityCount++;
            }

            if ($target > 0) {
                $targetTotal++;
                if ($qty >= $target) $targetMet++;
            }

            $employeeStats[] = [
                'employee'    => $emp,
                'logs'        => $logs,
                'attendance'  => $attendance,
                'total_qty'   => $qty,
                'target_qty'  => $target,
                'avg_quality' => round($quality),
            ];
        }

        $summary = [
            'total_qty'       => $totalQty,
            'workers_logged'  => $workersLogged,
            'target_achieved' => $targetTotal > 0 ? round(($targetMet / $targetTotal) * 100) : 0,
            'avg_quality'     => $qualityCount > 0 ? round($qualitySum / $qualityCount) : 0,
        ];

        return view('production.overview', compact('employeeStats', 'summary', 'date'));
    }

    // =============================================
    // ATTENDANCE OVERVIEW (all employees)
    // =============================================
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
            'present'  => $records->where('status', 'present')->count(),
            'absent'   => $records->where('status', 'absent')->count(),
            'late'     => $records->where('status', 'late')->count(),
            'not_marked' => $employees->count() - $records->count(),
        ];

        return view('attendance.overview', compact('employees', 'records', 'summary', 'date'));
    }

    private function authorize(Employee $employee): void
    {
        abort_if($employee->business_id !== auth()->user()->business_id, 403);
    }
}
