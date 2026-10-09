<?php
namespace App\Http\Controllers;
use App\Models\Transaction;
use App\Models\AccountCategory;
use App\Models\Employee;
use App\Models\Attendance;
use App\Helpers\DbHelper;
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
        // Single grouped query for the 6-month chart instead of 12 separate queries.
        $grouped = Transaction::where('business_id', $bizId)
            ->where('transaction_date', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw(DbHelper::yearMonth('transaction_date') . ' as ym, type, SUM(amount) as total')
            ->groupBy('ym', 'type')
            ->get()
            ->groupBy('ym');

        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $g = $grouped[$m->format('Y-m')] ?? collect();
            $monthlyData[] = [
                'label'   => $m->format('M'),
                'income'  => (float) ($g->firstWhere('type', 'income')->total ?? 0),
                'expense' => (float) ($g->firstWhere('type', 'expense')->total ?? 0),
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
    public function paymentTransfer()
    {
        $bizId = auth()->user()->business_id;
        $transfers = Transaction::where('business_id',$bizId)->where('type','expense')->where('reference','LIKE','TRANSFER-%')->orderByDesc('transaction_date')->paginate(20);
        return view('accounts.payment-transfer', compact('transfers'));
    }
    public function storeTransfer(Request $request)
    {
        $request->validate([
            'title'            => 'required',
            'amount'           => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'from_method'      => 'required|string|different:to_method',
            'to_method'        => 'required|string',
        ]);

        $bizId = auth()->user()->business_id;
        $ref   = 'TRANSFER-' . now()->timestamp;
        $label = $request->title . ' (Transfer: ' . ucfirst($request->from_method) . ' → ' . ucfirst($request->to_method) . ')';

        // A transfer moves money between accounts: it leaves the source (expense)
        // and arrives in the destination (income). Both legs share the TRANSFER-
        // reference so reports can net them to zero — a transfer is not profit/loss,
        // but it must move the per-method account balances.
        \Illuminate\Support\Facades\DB::transaction(function () use ($bizId, $request, $ref, $label) {
            Transaction::create([
                'business_id'      => $bizId,
                'created_by'       => auth()->id(),
                'type'             => 'expense',
                'title'            => $label,
                'amount'           => $request->amount,
                'payment_method'   => $request->from_method,
                'reference'        => $ref,
                'notes'            => $request->notes,
                'transaction_date' => $request->transaction_date,
            ]);

            Transaction::create([
                'business_id'      => $bizId,
                'created_by'       => auth()->id(),
                'type'             => 'income',
                'title'            => $label,
                'amount'           => $request->amount,
                'payment_method'   => $request->to_method,
                'reference'        => $ref,
                'notes'            => $request->notes,
                'transaction_date' => $request->transaction_date,
            ]);
        });

        return back()->with('success', 'Transfer recorded!');
    }
    public function destroyTransaction(Transaction $transaction)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_if($transaction->business_id !== auth()->user()->business_id, 403);
        $this->logActivity('deleted', $transaction, [
            'type'   => $transaction->type,
            'title'  => $transaction->title,
            'amount' => $transaction->amount,
            'date'   => (string) $transaction->transaction_date,
        ]);
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
