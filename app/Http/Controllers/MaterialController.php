<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialPurchaseRequest;
use App\Models\AccountCategory;
use App\Models\MaterialPurchase;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $month = $request->month ?? now()->format('Y-m');
        $cat   = $request->category ?? '';

        $from = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
        $to   = Carbon::parse($month . '-01')->endOfMonth()->toDateString();

        $query = MaterialPurchase::where('business_id', $bizId)
            ->whereBetween('purchase_date', [$from, $to]);

        if ($cat) {
            $query->where('category', $cat);
        }

        $purchases = $query->orderBy('purchase_date', 'desc')->get();

        $totalAmount = $purchases->sum('amount');

        // Category breakdown
        $byCategory = $purchases->groupBy('category')->map(fn($g) => [
            'count'  => $g->count(),
            'amount' => $g->sum('amount'),
        ])->sortByDesc('amount');

        // Built-in categories + any custom ones this business has already used, so
        // user-created categories keep showing up in the dropdowns and filter.
        $categories = MaterialPurchase::$categories;
        MaterialPurchase::where('business_id', $bizId)->distinct()->pluck('category')
            ->filter()
            ->each(function ($c) use (&$categories) {
                if (!isset($categories[$c])) $categories[$c] = ucfirst($c);
            });

        return view('materials.index', compact(
            'purchases', 'totalAmount', 'byCategory',
            'categories', 'month', 'cat', 'from', 'to'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_name'     => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'category'      => 'required|string',
            'amount'        => 'required_without_all:quantity,rate|nullable|numeric|min:0',
            'quantity'      => 'nullable|numeric|min:0',
            'rate'          => 'nullable|numeric|min:0',
        ]);

        // Calculate amount: if qty+rate given, compute total; else use manual amount
        $amount = $request->amount;
        if ($request->filled('quantity') && $request->filled('rate')) {
            $amount = (float)$request->quantity * (float)$request->rate;
        }

        if (!$amount || $amount <= 0) {
            return back()->withErrors(['amount' => 'Please enter an amount or quantity + rate.'])->withInput();
        }

        $bizId = auth()->user()->business_id;

        // A typed-in "new category" wins over the dropdown selection.
        $category = trim((string) $request->category_new) ?: $request->category;

        DB::transaction(function () use ($request, $amount, $bizId, $category) {
            // 1. Save the material purchase record
            $purchase = MaterialPurchase::create([
                'business_id'    => $bizId,
                'created_by'     => auth()->id(),
                'purchase_date'  => $request->purchase_date,
                'item_name'      => $request->item_name,
                'category'       => $category,
                'shop_name'      => $request->shop_name,
                'color'          => $request->color,
                'quantity'       => $request->quantity,
                'rate'           => $request->rate,
                'amount'         => $amount,
                'payment_method' => $request->payment_method ?? 'cash',
                'notes'          => $request->notes,
            ]);

            // 2. Auto-post to expense transactions so P&L stays accurate
            $categoryLabel = MaterialPurchase::$categories[$category] ?? ucfirst($category);

            // Find or auto-create the matching account category
            $accountCat = AccountCategory::firstOrCreate(
                ['business_id' => $bizId, 'name' => $categoryLabel, 'type' => 'expense'],
                ['business_id' => $bizId, 'name' => $categoryLabel, 'type' => 'expense']
            );

            Transaction::create([
                'business_id'      => $bizId,
                'created_by'       => auth()->id(),
                'category_id'      => $accountCat->id,
                'type'             => 'expense',
                'title'            => $request->item_name . ($request->shop_name ? ' — ' . $request->shop_name : ''),
                'amount'           => $amount,
                'payment_method'   => $request->payment_method ?? 'cash',
                'reference'        => 'MAT-' . $purchase->id,
                'notes'            => $request->notes,
                'transaction_date' => $request->purchase_date,
            ]);
        });

        return back()->with('success', "Purchase added & expense recorded: {$request->item_name} — ৳" . number_format($amount));
    }

    public function update(Request $request, MaterialPurchase $material)
    {
        abort_if($material->business_id !== auth()->user()->business_id, 403);

        $request->validate([
            'item_name'     => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'category'      => 'required|string',
            'amount'        => 'required_without_all:quantity,rate|nullable|numeric|min:0',
            'quantity'      => 'nullable|numeric|min:0',
            'rate'          => 'nullable|numeric|min:0',
        ]);

        $amount = $request->amount;
        if ($request->filled('quantity') && $request->filled('rate')) {
            $amount = (float) $request->quantity * (float) $request->rate;
        }
        if (!$amount || $amount <= 0) {
            return back()->withErrors(['amount' => 'Please enter an amount or quantity + rate.'])->withInput();
        }

        $bizId = $material->business_id;

        // A typed-in "new category" wins over the dropdown selection.
        $category = trim((string) $request->category_new) ?: $request->category;

        DB::transaction(function () use ($request, $material, $amount, $bizId, $category) {
            $material->update([
                'purchase_date'  => $request->purchase_date,
                'item_name'      => $request->item_name,
                'category'       => $category,
                'shop_name'      => $request->shop_name,
                'color'          => $request->color,
                'quantity'       => $request->quantity,
                'rate'           => $request->rate,
                'amount'         => $amount,
                'payment_method' => $request->payment_method ?? $material->payment_method,
                'notes'          => $request->notes,
            ]);

            // Keep the linked expense transaction in sync (amount, date, category, title).
            $categoryLabel = MaterialPurchase::$categories[$category] ?? ucfirst($category);
            $accountCat = AccountCategory::firstOrCreate(
                ['business_id' => $bizId, 'name' => $categoryLabel, 'type' => 'expense'],
                ['business_id' => $bizId, 'name' => $categoryLabel, 'type' => 'expense']
            );

            Transaction::where('business_id', $bizId)
                ->where('reference', 'MAT-' . $material->id)
                ->where('type', 'expense')
                ->update([
                    'category_id'      => $accountCat->id,
                    'title'            => $request->item_name . ($request->shop_name ? ' — ' . $request->shop_name : ''),
                    'amount'           => $amount,
                    'payment_method'   => $request->payment_method ?? 'cash',
                    'transaction_date' => $request->purchase_date,
                    'notes'            => $request->notes,
                ]);
        });

        return back()->with('success', "Purchase updated: {$request->item_name} — ৳" . number_format($amount));
    }

    public function destroy(MaterialPurchase $material)
    {
        // Deleting financial/operational records is admin-only; the UI hides these
        // buttons for other roles but the endpoint must enforce it too.
        abort_unless(auth()->user()->isAdmin(), 403, 'Only an admin can delete this.');
        abort_if($material->business_id !== auth()->user()->business_id, 403);

        DB::transaction(function () use ($material) {
            // Remove the linked expense transaction if it exists
            Transaction::where('business_id', $material->business_id)
                ->where('reference', 'MAT-' . $material->id)
                ->where('type', 'expense')
                ->delete();

            $material->delete();
        });

        return back()->with('success', 'Entry and linked expense deleted.');
    }

    public function export(Request $request)
    {
        $bizId  = auth()->user()->business_id;
        $format = $request->format ?? 'csv';
        $month  = $request->month ?? now()->format('Y-m');
        $cat    = $request->category ?? '';

        $from = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
        $to   = Carbon::parse($month . '-01')->endOfMonth()->toDateString();

        $query = MaterialPurchase::where('business_id', $bizId)
            ->whereBetween('purchase_date', [$from, $to]);
        if ($cat) $query->where('category', $cat);

        $purchases   = $query->orderBy('purchase_date')->get();
        $totalAmount = $purchases->sum('amount');
        $byCategory  = $purchases->groupBy('category')->map(fn($g, $cat) => (object)[
            'category' => $cat,
            'count'    => $g->count(),
            'total'    => $g->sum('amount'),
        ])->sortByDesc('total');

        $biz = auth()->user()->business;
        $categories = MaterialPurchase::$categories;

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('materials.export-pdf', compact(
                'purchases', 'totalAmount', 'byCategory', 'categories', 'month', 'biz', 'from', 'to'
            ))->setPaper('a4', 'portrait');
            return $pdf->download("materials_{$month}.pdf");
        }

        // CSV
        $bom  = "\xEF\xBB\xBF";
        $filename = "materials_{$month}.csv";
        return response()->streamDownload(function () use ($purchases, $totalAmount, $bom, $month, $from, $to) {
            $h = fopen('php://output', 'w');
            fwrite($h, $bom);
            fputcsv($h, ['Material Purchase Report']);
            fputcsv($h, ['Month', Carbon::parse($from)->format('F Y')]);
            fputcsv($h, ['Total', 'BDT ' . number_format($totalAmount, 2)]);
            fputcsv($h, []);
            fputcsv($h, ['#', 'Date', 'Item', 'Category', 'Shop', 'Color', 'Qty', 'Rate', 'Amount (BDT)', 'Method', 'Notes']);
            foreach ($purchases as $i => $p) {
                fputcsv($h, [
                    $i + 1,
                    $p->purchase_date->format('d/m/Y'),
                    $p->item_name,
                    $p->category_label,
                    $p->shop_name ?? '—',
                    $p->color ?? '—',
                    $p->quantity ?? '—',
                    $p->rate ?? '—',
                    number_format($p->amount, 2),
                    ucfirst($p->payment_method),
                    $p->notes ?? '',
                ]);
            }
            fputcsv($h, []);
            fputcsv($h, ['', '', '', '', '', '', '', 'TOTAL', number_format($totalAmount, 2)]);
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
