<?php
namespace App\Http\Controllers;
use App\Models\Transaction;
use App\Models\AccountCategory;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AccountController extends Controller
{
    public function overview(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $period = $request->period ?? 'month';
        [$from, $to] = $this->getPeriodDates($period);
        $query = Transaction::where('business_id', $bizId)->whereBetween('transaction_date', [$from, $to]);
        $summary = [
            'income'        => (clone $query)->where('type','income')->sum('amount'),
            'expense'       => (clone $query)->where('type','expense')->sum('amount'),
            'income_count'  => (clone $query)->where('type','income')->count(),
            'expense_count' => (clone $query)->where('type','expense')->count(),
            'count'         => $query->count(),
        ];
        $summary['profit'] = $summary['income'] - $summary['expense'];
        $recentTransactions = Transaction::where('business_id', $bizId)->with('category')->orderByDesc('transaction_date')->limit(10)->get();
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $monthlyData[] = [
                'label'   => $m->format('M'),
                'income'  => Transaction::where('business_id',$bizId)->where('type','income')->whereYear('transaction_date',$m->year)->whereMonth('transaction_date',$m->month)->sum('amount'),
                'expense' => Transaction::where('business_id',$bizId)->where('type','expense')->whereYear('transaction_date',$m->year)->whereMonth('transaction_date',$m->month)->sum('amount'),
            ];
        }
        return view('accounts.overview', compact('summary','recentTransactions','monthlyData','period'));
    }
    public function income(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $query = Transaction::where('business_id',$bizId)->where('type','income')->with('category')->orderByDesc('transaction_date');
        if ($month = $request->month) { [$y,$m] = explode('-',$month); $query->whereYear('transaction_date',$y)->whereMonth('transaction_date',$m); }
        if ($cat = $request->category) $query->where('category_id',$cat);
        $transactions = $query->paginate(20);
        $categories   = AccountCategory::where('business_id',$bizId)->where('type','income')->get();
        $totalAmount  = Transaction::where('business_id',$bizId)->where('type','income')->sum('amount');
        return view('accounts.income', compact('transactions','categories','totalAmount'));
    }
    public function storeIncome(Request $request)
    {
        $request->validate(['title'=>'required','amount'=>'required|numeric|min:0','transaction_date'=>'required|date']);
        Transaction::create(['business_id'=>auth()->user()->business_id,'category_id'=>$request->category_id?:null,'created_by'=>auth()->id(),'type'=>'income','title'=>$request->title,'amount'=>$request->amount,'payment_method'=>$request->payment_method??'cash','reference'=>$request->reference,'notes'=>$request->notes,'transaction_date'=>$request->transaction_date]);
        return back()->with('success', 'Income recorded!');
    }
    public function expenses(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $query = Transaction::where('business_id',$bizId)->where('type','expense')->with('category')->orderByDesc('transaction_date');
        if ($month = $request->month) { [$y,$m] = explode('-',$month); $query->whereYear('transaction_date',$y)->whereMonth('transaction_date',$m); }
        if ($cat = $request->category) $query->where('category_id',$cat);
        $transactions = $query->paginate(20);
        $categories   = AccountCategory::where('business_id',$bizId)->where('type','expense')->get();
        $totalAmount  = Transaction::where('business_id',$bizId)->where('type','expense')->sum('amount');
        return view('accounts.expenses', compact('transactions','categories','totalAmount'));
    }
    public function storeExpense(Request $request)
    {
        $request->validate(['title'=>'required','amount'=>'required|numeric|min:0','transaction_date'=>'required|date']);
        Transaction::create(['business_id'=>auth()->user()->business_id,'category_id'=>$request->category_id?:null,'created_by'=>auth()->id(),'type'=>'expense','title'=>$request->title,'amount'=>$request->amount,'payment_method'=>$request->payment_method??'cash','notes'=>$request->notes,'transaction_date'=>$request->transaction_date]);
        return back()->with('success', 'Expense recorded!');
    }
    public function salary(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $month = $request->month ?? now()->format('Y-m');
        $employees = Employee::where('business_id',$bizId)->where('status','active')->orderBy('name')->get();
        [$y,$m] = explode('-',$month);
        $daysInMonth = Carbon::parse($month)->daysInMonth;
        $salaryData = [];
        foreach ($employees as $emp) {
            $daysPresent = Attendance::where('employee_id',$emp->id)->whereYear('attendance_date',$y)->whereMonth('attendance_date',$m)->whereIn('status',['present','late'])->count();
            $paid = Transaction::where('business_id',$bizId)->where('type','expense')->where('reference','SALARY-'.$emp->id.'-'.$month)->exists();
            $salaryData[$emp->id] = ['days_present'=>$daysPresent>0?$daysPresent:$daysInMonth,'paid'=>$paid];
        }
        return view('accounts.salary', compact('employees','month','salaryData'));
    }
    public function storeSalary(Request $request)
    {
        $request->validate(['employee_id'=>'required','amount'=>'required|numeric','month'=>'required']);
        Transaction::create(['business_id'=>auth()->user()->business_id,'created_by'=>auth()->id(),'type'=>'expense','title'=>$request->title,'amount'=>$request->amount,'payment_method'=>'cash','reference'=>'SALARY-'.$request->employee_id.'-'.$request->month,'transaction_date'=>now()->endOfMonth()->format('Y-m-d')]);
        return back()->with('success', 'Salary paid!');
    }
    public function paymentTransfer()
    {
        $bizId = auth()->user()->business_id;
        $transfers = Transaction::where('business_id',$bizId)->where('type','expense')->where('reference','LIKE','TRANSFER-%')->orderByDesc('transaction_date')->paginate(20);
        return view('accounts.payment-transfer', compact('transfers'));
    }
    public function storeTransfer(Request $request)
    {
        $request->validate(['title'=>'required','amount'=>'required|numeric','transaction_date'=>'required|date']);
        Transaction::create(['business_id'=>auth()->user()->business_id,'created_by'=>auth()->id(),'type'=>'expense','title'=>$request->title.' (Transfer: '.ucfirst($request->from_method).' → '.ucfirst($request->to_method).')','amount'=>$request->amount,'payment_method'=>$request->from_method,'reference'=>'TRANSFER-'.now()->timestamp,'notes'=>$request->notes,'transaction_date'=>$request->transaction_date]);
        return back()->with('success', 'Transfer recorded!');
    }
    public function destroyTransaction(Transaction $transaction)
    {
        abort_if($transaction->business_id !== auth()->user()->business_id, 403);
        $transaction->delete();
        return back()->with('success', 'Transaction deleted.');
    }
    private function getPeriodDates(string $period): array
    {
        return match($period) {
            'today' => [today(), today()],
            'week'  => [now()->startOfWeek(), now()->endOfWeek()],
            'year'  => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
