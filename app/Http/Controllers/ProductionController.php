<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreProductionEntryRequest;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\ProductionRate;
use App\Models\ProductionEntry;
use App\Models\EmployeePayment;
use App\Models\ProductionTask;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProductionController extends Controller
{
    public function rates()
    {
        $bizId = auth()->user()->business_id;
        $rates = ProductionRate::where('business_id', $bizId)
            ->orderBy('product_name')
            ->get();
        return view('production.rates', compact('rates'));
    }

    public function storeRate(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'rate'         => 'required|numeric|min:0',
        ]);

        ProductionRate::create([
            'business_id'  => auth()->user()->business_id,
            'product_name' => $request->product_name,
            'variant_name' => $request->variant_name,
            'color'        => $request->color,
            'size'         => $request->size,
            'rate'         => $request->rate,
            'unit'         => $request->unit ?? 'piece',
            'is_active'    => true,
        ]);

        return back()->with('success', 'Rate added!');
    }

    public function updateRate(Request $request, ProductionRate $rate)
    {
        abort_if($rate->business_id !== auth()->user()->business_id, 403);
        $rate->update($request->only(['product_name', 'variant_name', 'color', 'size', 'rate', 'unit', 'is_active']));
        return back()->with('success', 'Rate updated!');
    }

    public function destroyRate(ProductionRate $rate)
    {
        abort_if($rate->business_id !== auth()->user()->business_id, 403);
        $rate->delete();
        return back()->with('success', 'Rate deleted!');
    }

    public function index(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $date  = $request->date ?? today()->toDateString();

        $employees = Employee::where('business_id', $bizId)
            ->where('type', 'production')
            ->where('status', 'active')
            ->get();

        $rates = ProductionRate::where('business_id', $bizId)
            ->where('is_active', true)
            ->get();

        $entries = ProductionEntry::where('business_id', $bizId)
            ->whereDate('entry_date', $date)
            ->with(['employee', 'productionRate'])
            ->get();

        $totalToday = $entries->sum('amount');

        return view('production.index', compact('employees', 'rates', 'entries', 'date', 'totalToday'));
    }

    public function store(StoreProductionEntryRequest $request)
    {

        $rate   = ProductionRate::findOrFail($request->production_rate_id);
        $amount = $rate->rate * $request->quantity;

        $employee = Employee::findOrFail($request->employee_id);

        // A worker (employee role) may only file entries against their own linked
        // employee record — never against a co-worker.
        if (auth()->user()->role === 'employee') {
            $self = Employee::where('business_id', auth()->user()->business_id)
                ->where('user_id', auth()->id())
                ->first();
            abort_if(!$self || $self->id !== $employee->id, 403, 'You can only submit your own production.');
        }

        ProductionEntry::create([
            'business_id'        => $employee->business_id,
            'employee_id'        => $request->employee_id,
            'production_rate_id' => $request->production_rate_id,
            'entry_date'         => $request->entry_date,
            'quantity'           => $request->quantity,
            'amount'             => $amount,
            'size'               => $request->size,
            'color'              => $request->color,
            'notes'              => $request->notes,
        ]);

        if (auth()->user()->role === 'employee') {
            return redirect()->route('production.worker.dashboard')
                ->with('success', "✅ Entry added! Amount: ৳{$amount}");
        }

        return back()->with('success', "Entry added! Amount: ৳{$amount}");
    }

    public function destroy(ProductionEntry $entry)
    {
        abort_if($entry->business_id !== auth()->user()->business_id, 403);
        $entry->delete();
        return back()->with('success', 'Entry deleted!');
    }

    public function payroll(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $month = $request->month ?? today()->format('Y-m');

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate   = Carbon::parse($month . '-01')->endOfMonth();

        $employees = Employee::where('business_id', $bizId)
            ->where('status', 'active')
            ->with([
                'productionEntries' => fn($q) => $q->whereBetween('entry_date', [$startDate, $endDate]),
                'payments'          => fn($q) => $q->whereBetween('payment_date', [$startDate, $endDate]),
                'attendances'       => fn($q) => $q->whereBetween('attendance_date', [$startDate, $endDate]),
            ])
            ->get()
            ->map(function ($emp) use ($startDate, $endDate) {
                // Professional running ledger: prior balance carries in, overpay/advance
                // is shown (not hidden), and the closing balance is the true amount due.
                $L = $emp->payrollLedger($startDate, $endDate);

                $emp->opening_balance = $L['opening'];   // + owed from before, − advance carried
                $emp->total_earning   = $L['earned'];
                $emp->total_advance   = $L['advance'];
                $emp->total_bonus     = $L['bonus'];
                $emp->total_deduct    = $L['deduction'];
                $emp->total_paid      = $L['paid'];
                $emp->net_payable     = $L['closing'];   // + due to worker, − worker over-advanced

                return $emp;
            });

        // Load existing PayrollPeriod records for this month (to show finalize status)
        $empIds = $employees->pluck('id');
        $payrollPeriods = PayrollPeriod::where('business_id', $bizId)
            ->where('period_month', $month)
            ->whereIn('employee_id', $empIds)
            ->get()
            ->keyBy('employee_id');

        return view('production.payroll', compact('employees', 'month', 'payrollPeriods'));
    }

    // =============================================
    // WORKER EFFICIENCY REPORT
    // =============================================
    public function efficiency(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');
        $empId = $request->employee ?? null;

        $employees = Employee::where('business_id', $bizId)
            ->where('type', 'production')
            ->orderBy('name')->get();

        $query = ProductionEntry::where('production_entries.business_id', $bizId)
            ->whereBetween('entry_date', [$from, $to])
            ->with(['employee', 'productionRate']);

        if ($empId) {
            $query->where('employee_id', $empId);
        }

        $entries = $query->orderBy('entry_date')->get();

        // Per-employee aggregation
        $workerStats = $entries->groupBy('employee_id')->map(function ($group) use ($from, $to) {
            $emp       = $group->first()->employee;
            $totalQty  = $group->sum('quantity');
            $totalAmt  = $group->sum('amount');
            $days      = $group->groupBy('entry_date')->count(); // working days

            // Daily pieces
            $dailyPieces = $group->groupBy('entry_date')
                ->map(fn($g) => $g->sum('quantity'))
                ->sortKeys();

            $avgPerDay   = $days > 0 ? round($totalQty / $days, 1) : 0;
            $maxDay      = $dailyPieces->max() ?? 0;
            $minDay      = $dailyPieces->min() ?? 0;

            // Product breakdown
            $products = $group->groupBy(fn($e) => $e->productionRate?->product_name ?? 'Manual')
                ->map(fn($g) => [
                    'name'    => $g->first()->productionRate?->product_name ?? 'Manual',
                    'qty'     => $g->sum('quantity'),
                    'amount'  => $g->sum('amount'),
                    'entries' => $g->count(),
                ])->sortByDesc('qty')->values();

            return [
                'employee'    => $emp,
                'total_qty'   => $totalQty,
                'total_amount'=> $totalAmt,
                'working_days'=> $days,
                'avg_per_day' => $avgPerDay,
                'max_day'     => $maxDay,
                'min_day'     => $minDay,
                'daily_pieces'=> $dailyPieces,
                'products'    => $products,
            ];
        })->sortByDesc('total_qty')->values();

        $totalQty    = $entries->sum('quantity');
        $totalAmount = $entries->sum('amount');

        // Chart: daily output across all workers (or selected worker)
        $dates = [];
        $current = \Carbon\Carbon::parse($from);
        $end     = \Carbon\Carbon::parse($to);
        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $chartLabels = array_map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'), $dates);
        $chartData   = array_map(fn($d) => (int) $entries->where('entry_date', $d)->sum('quantity'), $dates);

        return view('production.efficiency', compact(
            'employees', 'workerStats', 'totalQty', 'totalAmount',
            'chartLabels', 'chartData', 'from', 'to', 'empId'
        ));
    }

    public function storePayment(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $request->validate([
            'employee_id'    => ['required', \Illuminate\Validation\Rule::exists('employees', 'id')->where('business_id', $bizId)],
            'amount'         => 'required|numeric|min:1',
            'type'           => 'required|in:advance,salary,bonus,deduction',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string',
        ]);

        $employee = Employee::where('business_id', $bizId)->findOrFail($request->employee_id);

        EmployeePayment::create([
            'business_id'    => $bizId,
            'employee_id'    => $employee->id,
            'created_by'     => auth()->id(),
            'payment_date'   => $request->payment_date,
            'amount'         => $request->amount,
            'type'           => $request->type,
            'payment_method' => $request->payment_method,
            'notes'          => $request->notes,
        ]);

        // Only advance and salary move cash out of the business, so only those become
        // expense transactions. Bonus (extra owed) and deduction (reduces what's owed)
        // are payroll adjustments — they affect the worker's ledger, not the cash book,
        // and become an expense only when actually paid out as salary.
        if (in_array($request->type, ['advance', 'salary'])) {
            Transaction::create([
                'business_id'      => $bizId,
                'created_by'       => auth()->id(),
                'type'             => 'expense',
                'title'            => ucfirst($request->type) . ' payment to ' . $employee->name,
                'amount'           => $request->amount,
                'payment_method'   => $request->payment_method,
                'reference'        => 'EMPPAY-' . $employee->id,
                'transaction_date' => $request->payment_date,
            ]);
        }

        return back()->with('success', 'Payment recorded!');
    }

    /**
     * Finalize a payroll period for one employee — locks it and creates a PayrollPeriod record.
     */
    public function finalizePayroll(Request $request, Employee $employee)
    {
        $bizId = auth()->user()->business_id;
        abort_if($employee->business_id !== $bizId, 403);

        $request->validate([
            'month'         => 'required|date_format:Y-m',
            'gross_earning' => 'required|numeric|min:0',
            'total_pieces'  => 'nullable|numeric|min:0',
            'total_paid'    => 'required|numeric|min:0',
            'net_due'       => 'required|numeric',
            'notes'         => 'nullable|string|max:500',
        ]);

        $period = PayrollPeriod::firstOrNew([
            'business_id' => $bizId,
            'employee_id' => $employee->id,
            'period_month'=> $request->month,
        ]);

        if ($period->is_finalized) {
            return back()->with('error', 'This period is already finalized.');
        }

        $period->fill([
            'gross_earning' => $request->gross_earning,
            'total_pieces'  => $request->total_pieces ?? 0,
            'total_paid'    => $request->total_paid,
            'net_due'       => $request->net_due,
        ])->save();

        $period->finalize(auth()->id(), $request->notes ?? '');

        return back()->with('success', "Payroll for {$employee->name} ({$request->month}) finalized and locked.");
    }

    /**
     * Unlock a finalized payroll period (admin only).
     */
    public function unfinalizePayroll(PayrollPeriod $payrollPeriod)
    {
        abort_if($payrollPeriod->business_id !== auth()->user()->business_id, 403);

        $payrollPeriod->update([
            'is_finalized' => false,
            'finalized_by' => null,
            'finalized_at' => null,
        ]);

        return back()->with('success', 'Payroll period unlocked.');
    }

    public function adminOverview(Request $request)
    {
        $bizId  = auth()->user()->business_id;
        $period = $request->period ?? 'monthly';
        $month  = null; // default; set inside switch for monthly period

        // Determine date range based on period
        switch ($period) {
            case 'daily':
                $date  = $request->date ? Carbon::parse($request->date) : today();
                $from  = $date->copy()->startOfDay();
                $to    = $date->copy()->endOfDay();
                $label = $date->format('d M Y, l');
                break;
            case 'weekly':
                $week  = $request->week ?? now()->format('Y-\WW');
                [$yr, $wk] = explode('-W', $week);
                $from  = Carbon::now()->setISODate((int)$yr, (int)$wk)->startOfWeek();
                $to    = $from->copy()->endOfWeek();
                $label = $from->format('d M') . ' – ' . $to->format('d M Y');
                break;
            default: // monthly
                $month = $request->month ?? now()->format('Y-m');
                $from  = Carbon::parse($month . '-01')->startOfMonth();
                $to    = Carbon::parse($month . '-01')->endOfMonth();
                $label = $from->format('F Y');
                break;
        }

        $fromDate = $from->toDateString();
        $toDate   = $to->toDateString();

        // All active employees
        $employees = Employee::where('business_id', $bizId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Tasks in period (use assigned_at for task filter)
        $allTasks = ProductionTask::where('business_id', $bizId)
            ->whereNotIn('status', ['cancelled'])
            ->whereDate('assigned_at', '>=', $fromDate)
            ->whereDate('assigned_at', '<=', $toDate)
            ->get();

        // Earnings (production entries) in period
        $earnings = ProductionEntry::where('production_entries.business_id', $bizId)
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->selectRaw('employee_id, SUM(quantity) as total_qty, SUM(amount) as total_amount')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id')
            ->map(fn($row) => ['total_qty' => (float)$row->total_qty, 'total_amount' => (float)$row->total_amount])
            ->toArray();

        // Advances in period
        $advances = \App\Models\EmployeePayment::where('business_id', $bizId)
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->whereIn('type', ['advance', 'deduction'])
            ->selectRaw('employee_id, SUM(amount) as total')
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        // Per-employee stats
        $employeeStats = $employees->map(function ($emp) use ($allTasks, $earnings, $advances) {
            $tasks   = $allTasks->where('employee_id', $emp->id);
            $earning = $earnings[$emp->id] ?? null;
            $advance = $advances[$emp->id] ?? 0;
            $totalAmt = $earning['total_amount'] ?? 0;

            return [
                'employee'       => $emp,
                'total_tasks'    => $tasks->count(),
                'assigned'       => $tasks->where('status', 'assigned')->count(),
                'in_progress'    => $tasks->where('status', 'in_progress')->count(),
                'partial'        => $tasks->where('status', 'partial')->count(),
                'completed'      => $tasks->where('status', 'completed')->count(),
                'total_assigned' => $tasks->sum('assigned_quantity'),
                'total_done'     => $tasks->sum('completed_quantity'),
                'total_pending'  => $tasks->sum('assigned_quantity') - $tasks->sum('completed_quantity'),
                'total_qty'      => $earning['total_qty'] ?? 0,
                'total_earning'  => $totalAmt,
                'advance'        => $advance,
                'net_payable'    => $totalAmt - $advance,
                'completion_pct' => $tasks->sum('assigned_quantity') > 0
                    ? round(($tasks->sum('completed_quantity') / $tasks->sum('assigned_quantity')) * 100)
                    : 0,
            ];
        })->sortByDesc('total_earning');

        // Business summary
        $summary = [
            'total_employees' => $employees->count(),
            'total_tasks'     => $allTasks->count(),
            'total_assigned'  => $allTasks->where('status', 'assigned')->count(),
            'total_partial'   => $allTasks->whereIn('status', ['partial', 'in_progress'])->count(),
            'total_completed' => $allTasks->where('status', 'completed')->count(),
            'total_earning'   => array_sum(array_column($earnings, 'total_amount')),
            'total_pieces'    => array_sum(array_column($earnings, 'total_qty')),
            'total_advance'   => $advances->sum(),
        ];

        // Month list (last 12 months)
        $months = collect();
        for ($i = 0; $i < 12; $i++) {
            $m = now()->subMonths($i);
            $months->push(['value' => $m->format('Y-m'), 'label' => $m->format('M Y')]);
        }

        // Quick presets
        $presets = [
            ['label' => 'আজ',         'period' => 'daily',   'date'  => today()->toDateString()],
            ['label' => 'গতকাল',      'period' => 'daily',   'date'  => today()->subDay()->toDateString()],
            ['label' => 'এই সপ্তাহ', 'period' => 'weekly',  'week'  => now()->format('Y-\WW')],
            ['label' => 'গত সপ্তাহ', 'period' => 'weekly',  'week'  => now()->subWeek()->format('Y-\WW')],
            ['label' => 'এই মাস',     'period' => 'monthly', 'month' => now()->format('Y-m')],
            ['label' => 'গত মাস',     'period' => 'monthly', 'month' => now()->subMonth()->format('Y-m')],
        ];

        return view('production.admin-overview', compact(
            'employeeStats', 'summary', 'period', 'label',
            'months', 'presets',
            'month', 'fromDate', 'toDate'
        ));
    }

    public function workerDashboard(Request $request)
    {
        $bizId  = auth()->user()->business_id;
        $userId = auth()->id();
        $role   = auth()->user()->role;

        $allEmployees = Employee::where('business_id', $bizId)->get();

        // Admin/Owner can switch between employees
        if (in_array($role, ['admin', 'owner'])) {
            $employeeId = $request->employee_id;
            $employee   = $employeeId
                ? Employee::where('business_id', $bizId)->findOrFail($employeeId)
                : $allEmployees->first();

            if (!$employee) {
                return redirect()->route('production.index')->with('error', 'No employees found.');
            }
        } else {
            $employee = Employee::where('business_id', $bizId)
                ->where('user_id', $userId)
                ->first();

            if (!$employee) {
                auth()->logout();
                return redirect()->route('login')
                    ->withErrors(['email' => 'No employee profile linked. Contact admin.']);
            }
        }

        $from = $request->from ?? today()->startOfMonth()->toDateString();
        $to   = $request->to   ?? today()->toDateString();

        $rates = ProductionRate::where('business_id', $bizId)
            ->where('is_active', true)
            ->get();

        $entries = ProductionEntry::where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$from, $to])
            ->with('productionRate')
            ->orderByDesc('entry_date')
            ->get();

        $payments = EmployeePayment::where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$from, $to])
            ->orderByDesc('payment_date')
            ->get();

        $totalEarning = $entries->sum('amount');
        $totalQty     = $entries->sum('quantity');
        $totalAdvance = $payments->whereIn('type', ['advance', 'deduction'])->sum('amount');

        // Professional balance: carry the prior balance in and don't hide overpay.
        $ledger         = $employee->payrollLedger($from, $to);
        $openingBalance = $ledger['opening'];   // + due carried in, − advance carried in
        $netPayable     = $ledger['closing'];   // + still owed to worker, − worker over-advanced

        $dailyReport = ProductionEntry::where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$from, $to])
            ->selectRaw('entry_date as date, SUM(quantity) as total_qty, SUM(amount) as total_amount')
            ->groupBy('entry_date')
            ->orderByDesc('entry_date')
            ->get();

        $productReport = ProductionEntry::where('production_entries.employee_id', $employee->id)
            ->whereBetween('production_entries.entry_date', [$from, $to])
            ->leftJoin('production_rates', 'production_entries.production_rate_id', '=', 'production_rates.id')
            ->selectRaw('COALESCE(production_rates.product_name, production_entries.notes, "Manual Entry") as product_name, SUM(production_entries.quantity) as total_qty, SUM(production_entries.amount) as total_amount')
            ->groupBy(\DB::raw('COALESCE(production_rates.product_name, production_entries.notes, "Manual Entry")'))
            ->orderByDesc('total_amount')
            ->get();

        $myTasks = ProductionTask::where('business_id', $bizId)
            ->where('employee_id', $employee->id)
            ->whereNotIn('status', ['cancelled'])
            ->withCount('submissions')
            ->orderByDesc('assigned_at')
            ->get();

        $taskStats = [
            'total'       => $myTasks->count(),
            'completed'   => $myTasks->where('status', 'completed')->count(),
            'partial'     => $myTasks->where('status', 'partial')->count(),
            'in_progress' => $myTasks->where('status', 'in_progress')->count(),
            'assigned'    => $myTasks->where('status', 'assigned')->count(),
        ];

        return view('production.worker-dashboard', compact(
            'employee', 'allEmployees', 'entries', 'payments', 'rates',
            'totalEarning', 'totalQty', 'totalAdvance', 'netPayable', 'openingBalance',
            'from', 'to', 'dailyReport', 'productReport',
            'myTasks', 'taskStats'
        ));
    }

    // =============================================
    // WORKER LEDGER — all workers monthly summary
    // =============================================
    public function workerLedger(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $month = $request->month ?? now()->format('Y-m');

        $from = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
        $to   = Carbon::parse($month . '-01')->endOfMonth()->toDateString();

        $employees = Employee::where('business_id', $bizId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Earnings (production entries) for the month
        $earnings = ProductionEntry::where('business_id', $bizId)
            ->whereBetween('entry_date', [$from, $to])
            ->selectRaw('employee_id, SUM(quantity) as total_qty, SUM(amount) as total_earned')
            ->groupBy('employee_id')
            ->pluck('total_earned', 'employee_id');

        $earningQty = ProductionEntry::where('business_id', $bizId)
            ->whereBetween('entry_date', [$from, $to])
            ->selectRaw('employee_id, SUM(quantity) as total_qty')
            ->groupBy('employee_id')
            ->pluck('total_qty', 'employee_id');

        // Payments made for the month
        $paid = EmployeePayment::where('business_id', $bizId)
            ->whereBetween('payment_date', [$from, $to])
            ->selectRaw('employee_id, SUM(amount) as total_paid')
            ->groupBy('employee_id')
            ->pluck('total_paid', 'employee_id');

        // All-time balance (cumulative)
        $allTimeEarned = ProductionEntry::where('business_id', $bizId)
            ->selectRaw('employee_id, SUM(amount) as total')
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        $allTimePaid = EmployeePayment::where('business_id', $bizId)
            ->selectRaw('employee_id, SUM(amount) as total')
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        $workers = $employees->map(function ($emp) use ($earnings, $earningQty, $paid, $allTimeEarned, $allTimePaid) {
            $monthEarned  = (float) ($earnings[$emp->id] ?? 0);
            $monthQty     = (int)   ($earningQty[$emp->id] ?? 0);
            $monthPaid    = (float) ($paid[$emp->id] ?? 0);
            $monthBalance = $monthEarned - $monthPaid; // positive = due, negative = overpay

            $cumulativeEarned  = (float) ($allTimeEarned[$emp->id] ?? 0);
            $cumulativePaid    = (float) ($allTimePaid[$emp->id] ?? 0);
            $cumulativeBalance = $cumulativeEarned - $cumulativePaid;

            return [
                'employee'          => $emp,
                'month_earned'      => $monthEarned,
                'month_qty'         => $monthQty,
                'month_paid'        => $monthPaid,
                'month_balance'     => $monthBalance,   // >0 due, <0 overpay
                'cumulative_earned' => $cumulativeEarned,
                'cumulative_paid'   => $cumulativePaid,
                'cumulative_balance'=> $cumulativeBalance,
            ];
        })->sortByDesc('month_earned')->values();

        $totals = [
            'earned'  => $workers->sum('month_earned'),
            'qty'     => $workers->sum('month_qty'),
            'paid'    => $workers->sum('month_paid'),
            'balance' => $workers->sum('month_balance'),
        ];

        // Month list for picker
        $months = collect();
        for ($i = 0; $i < 18; $i++) {
            $m = now()->subMonths($i);
            $months->push(['value' => $m->format('Y-m'), 'label' => $m->format('F Y')]);
        }

        return view('production.worker-ledger', compact(
            'workers', 'totals', 'month', 'months', 'from', 'to'
        ));
    }

    // =============================================
    // WORKER LEDGER DETAIL — one worker's full ledger
    // =============================================
    public function workerLedgerDetail(Request $request, Employee $employee)
    {
        $bizId = auth()->user()->business_id;
        abort_if($employee->business_id !== $bizId, 403);

        $month = $request->month ?? now()->format('Y-m');
        $from  = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
        $to    = Carbon::parse($month . '-01')->endOfMonth()->toDateString();

        // Production entries for this month
        $entries = ProductionEntry::where('business_id', $bizId)
            ->where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$from, $to])
            ->with('productionRate')
            ->orderBy('entry_date')
            ->get();

        // Payments for this month
        $payments = EmployeePayment::where('business_id', $bizId)
            ->where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date')
            ->get();

        // All-time cumulative balance (carry-over from previous months)
        $allTimeEarned = ProductionEntry::where('business_id', $bizId)
            ->where('employee_id', $employee->id)
            ->where('entry_date', '<', $from)
            ->sum('amount');

        $allTimePaid = EmployeePayment::where('business_id', $bizId)
            ->where('employee_id', $employee->id)
            ->where('payment_date', '<', $from)
            ->sum('amount');

        $carryOver = (float)$allTimePaid - (float)$allTimeEarned; // positive = overpay brought forward

        // This month totals
        $monthEarned = $entries->sum('amount');
        $monthPaid   = $payments->sum('amount');
        $totalBill   = $monthEarned - $carryOver; // effective bill after carryover
        $balance     = $monthEarned - $monthPaid; // >0 due, <0 overpay this month
        $runningDue  = (float)$allTimeEarned + $monthEarned - (float)$allTimePaid - $monthPaid; // all-time

        // Product breakdown (grouped by product name/color/size)
        $productBreakdown = $entries->groupBy(fn($e) => implode('|', array_filter([
            $e->productionRate?->product_name ?? 'Manual',
            $e->productionRate?->color ?? '',
            $e->productionRate?->size  ?? '',
        ])))->map(fn($g) => [
            'product_name' => $g->first()->productionRate?->product_name ?? 'Manual Entry',
            'color'        => $g->first()->productionRate?->color ?? '—',
            'size'         => $g->first()->productionRate?->size  ?? '—',
            'rate'         => $g->first()->productionRate?->rate  ?? 0,
            'qty'          => $g->sum('quantity'),
            'amount'       => $g->sum('amount'),
        ])->sortByDesc('amount')->values();

        // Month list for picker
        $months = collect();
        for ($i = 0; $i < 18; $i++) {
            $m = now()->subMonths($i);
            $months->push(['value' => $m->format('Y-m'), 'label' => $m->format('F Y')]);
        }

        return view('production.worker-ledger-detail', compact(
            'employee', 'entries', 'payments', 'productBreakdown',
            'monthEarned', 'monthPaid', 'balance', 'carryOver', 'runningDue',
            'month', 'months', 'from', 'to'
        ));
    }

    // =============================================
    // WORKER LEDGER EXPORT
    // =============================================
    public function workerLedgerExport(Request $request, Employee $employee)
    {
        $bizId = auth()->user()->business_id;
        abort_if($employee->business_id !== $bizId, 403);

        $format = $request->format ?? 'csv';
        $month  = $request->month ?? now()->format('Y-m');
        $from   = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
        $to     = Carbon::parse($month . '-01')->endOfMonth()->toDateString();

        $entries  = ProductionEntry::where('business_id', $bizId)->where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$from, $to])->with('productionRate')->orderBy('entry_date')->get();
        $payments = EmployeePayment::where('business_id', $bizId)->where('employee_id', $employee->id)
            ->whereBetween('payment_date', [$from, $to])->orderBy('payment_date')->get();

        $prevEarned = ProductionEntry::where('business_id', $bizId)->where('employee_id', $employee->id)
            ->where('entry_date', '<', $from)->sum('amount');
        $prevPaid   = EmployeePayment::where('business_id', $bizId)->where('employee_id', $employee->id)
            ->where('payment_date', '<', $from)->sum('amount');
        $carryOver  = (float)$prevPaid - (float)$prevEarned;

        $monthEarned = $entries->sum('amount');
        $monthPaid   = $payments->sum('amount');
        $runningDue  = (float)$prevEarned + $monthEarned - (float)$prevPaid - $monthPaid;

        $biz = auth()->user()->business;

        if ($format === 'pdf') {
            $productBreakdown = $entries->groupBy(fn($e) => implode('|', array_filter([
                $e->productionRate?->product_name ?? 'Manual',
                $e->productionRate?->color ?? '',
                $e->productionRate?->size  ?? '',
            ])))->map(fn($g) => [
                'product_name' => $g->first()->productionRate?->product_name ?? 'Manual Entry',
                'color'        => $g->first()->productionRate?->color ?? '—',
                'size'         => $g->first()->productionRate?->size  ?? '—',
                'rate'         => $g->first()->productionRate?->rate  ?? 0,
                'qty'          => $g->sum('quantity'),
                'amount'       => $g->sum('amount'),
            ])->values();

            $pdf = Pdf::loadView('production.worker-ledger-pdf', compact(
                'employee', 'entries', 'payments', 'productBreakdown',
                'monthEarned', 'monthPaid', 'carryOver', 'runningDue', 'month', 'from', 'to', 'biz'
            ))->setPaper('a4', 'portrait');
            return $pdf->download("{$employee->name}_{$month}_ledger.pdf");
        }

        // CSV
        $bom = "\xEF\xBB\xBF";
        $filename = "{$employee->name}_{$month}_ledger.csv";
        return response()->streamDownload(function () use ($employee, $entries, $payments, $monthEarned, $monthPaid, $carryOver, $runningDue, $bom, $month) {
            $h = fopen('php://output', 'w');
            fwrite($h, $bom);
            fputcsv($h, ['Worker Ledger — ' . $employee->name]);
            fputcsv($h, ['Month', \Carbon\Carbon::parse($month . '-01')->format('F Y')]);
            fputcsv($h, []);
            if ($carryOver != 0) {
                fputcsv($h, ['Carry Over from Previous', $carryOver > 0 ? 'Overpay: ' . number_format($carryOver, 2) : 'Due: ' . number_format(abs($carryOver), 2)]);
            }
            fputcsv($h, []);
            fputcsv($h, ['=== PRODUCTION ENTRIES ===']);
            fputcsv($h, ['Date', 'Product', 'Color', 'Size', 'Qty', 'Rate', 'Amount']);
            foreach ($entries as $e) {
                fputcsv($h, [
                    $e->entry_date->format('d/m/Y'),
                    $e->productionRate?->product_name ?? 'Manual',
                    $e->productionRate?->color ?? '—',
                    $e->productionRate?->size  ?? '—',
                    $e->quantity,
                    $e->productionRate?->rate ?? '—',
                    number_format($e->amount, 2),
                ]);
            }
            fputcsv($h, ['', '', '', '', $entries->sum('quantity'), '', number_format($monthEarned, 2)]);
            fputcsv($h, []);
            fputcsv($h, ['=== PAYMENTS RECEIVED ===']);
            fputcsv($h, ['Date', 'Amount', 'Type', 'Method', 'Notes']);
            foreach ($payments as $p) {
                fputcsv($h, [
                    $p->payment_date->format('d/m/Y'),
                    number_format($p->amount, 2),
                    ucfirst($p->type),
                    ucfirst($p->payment_method),
                    $p->notes ?? '',
                ]);
            }
            fputcsv($h, ['', number_format($monthPaid, 2)]);
            fputcsv($h, []);
            fputcsv($h, ['=== SUMMARY ===']);
            fputcsv($h, ['Total Earned (P.Bill)', number_format($monthEarned, 2)]);
            fputcsv($h, ['Total Paid', number_format($monthPaid, 2)]);
            fputcsv($h, [$runningDue >= 0 ? 'Due (All-time)' : 'Overpay (All-time)', number_format(abs($runningDue), 2)]);
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}