<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ProductionRate;
use App\Models\ProductionEntry;
use App\Models\EmployeePayment;
use App\Models\Transaction;
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

    public function store(Request $request)
    {
        $request->validate([
            'employee_id'        => 'required|exists:employees,id',
            'production_rate_id' => 'required|exists:production_rates,id',
            'entry_date'         => 'required|date',
            'quantity'           => 'required|integer|min:1',
        ]);

        $rate   = ProductionRate::findOrFail($request->production_rate_id);
        $amount = $rate->rate * $request->quantity;

        $employee = Employee::findOrFail($request->employee_id);

        ProductionEntry::create([
            'business_id'        => $employee->business_id,
            'employee_id'        => $request->employee_id,
            'production_rate_id' => $request->production_rate_id,
            'entry_date'         => $request->entry_date,
            'quantity'           => $request->quantity,
            'amount'             => $amount,
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
            ->map(function ($emp) {
                if ($emp->type === 'production') {
                    $totalEarning = $emp->productionEntries->sum('amount');
                } else {
                    $presentDays  = $emp->attendances->whereIn('status', ['present', 'half_day'])->count();
                    $workingDays  = $emp->working_days ?: 26;
                    $perDay       = $emp->salary / $workingDays;
                    $totalEarning = $perDay * $presentDays;
                }

                $totalAdvance = $emp->payments->where('type', 'advance')->sum('amount');
                $totalBonus   = $emp->payments->where('type', 'bonus')->sum('amount');
                $totalDeduct  = $emp->payments->where('type', 'deduction')->sum('amount');
                $totalPaid    = $emp->payments->where('type', 'salary')->sum('amount');
                $netPayable   = $totalEarning + $totalBonus - $totalAdvance - $totalDeduct - $totalPaid;

                $emp->total_earning = $totalEarning;
                $emp->total_advance = $totalAdvance;
                $emp->total_bonus   = $totalBonus;
                $emp->total_deduct  = $totalDeduct;
                $emp->total_paid    = $totalPaid;
                $emp->net_payable   = max(0, $netPayable);

                return $emp;
            });

        return view('production.payroll', compact('employees', 'month'));
    }

    public function storePayment(Request $request)
    {
        $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'amount'         => 'required|numeric|min:1',
            'type'           => 'required|in:advance,salary,bonus,deduction',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string',
        ]);

        EmployeePayment::create([
            'business_id'    => auth()->user()->business_id,
            'employee_id'    => $request->employee_id,
            'created_by'     => auth()->id(),
            'payment_date'   => $request->payment_date,
            'amount'         => $request->amount,
            'type'           => $request->type,
            'payment_method' => $request->payment_method,
            'notes'          => $request->notes,
        ]);

        Transaction::create([
            'business_id'      => auth()->user()->business_id,
            'created_by'       => auth()->id(),
            'type'             => 'expense',
            'title'            => ucfirst($request->type) . ' payment to ' . Employee::find($request->employee_id)->name,
            'amount'           => $request->amount,
            'payment_method'   => $request->payment_method,
            'transaction_date' => $request->payment_date,
        ]);

        return back()->with('success', 'Payment recorded!');
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
        $netPayable   = $totalEarning - $totalAdvance;

        $dailyReport = ProductionEntry::where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$from, $to])
            ->selectRaw('entry_date as date, SUM(quantity) as total_qty, SUM(amount) as total_amount')
            ->groupBy('entry_date')
            ->orderByDesc('entry_date')
            ->get();

        $productReport = ProductionEntry::where('employee_id', $employee->id)
            ->whereBetween('entry_date', [$from, $to])
            ->join('production_rates', 'production_entries.production_rate_id', '=', 'production_rates.id')
            ->selectRaw('production_rates.product_name, SUM(production_entries.quantity) as total_qty, SUM(production_entries.amount) as total_amount')
            ->groupBy('production_rates.product_name')
            ->orderByDesc('total_amount')
            ->get();

        return view('production.worker-dashboard', compact(
            'employee', 'allEmployees', 'entries', 'payments', 'rates',
            'totalEarning', 'totalQty', 'totalAdvance', 'netPayable',
            'from', 'to', 'dailyReport', 'productReport'
        ));
    }
}