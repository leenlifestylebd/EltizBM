<?php

namespace App\Http\Controllers;

use App\Services\ReportQueryService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportExportController extends Controller
{
    public function __construct(private ReportQueryService $rqs) {}

    public function export(Request $request, string $type)
    {
        $format = $request->format ?? 'csv';

        return match ($type) {
            'profit-loss'         => $this->exportProfitLoss($request, $format),
            'expense-category'    => $this->exportExpenseCategory($request, $format),
            'income-source'       => $this->exportIncomeSource($request, $format),
            'business-overview'   => $this->exportBusinessOverview($request, $format),
            'stock-inventory'     => $this->exportStockInventory($request, $format),
            'customer-due'        => $this->exportCustomerDue($request, $format),
            'supplier-due'        => $this->exportSupplierDue($request, $format),
            'order-analytics'     => $this->exportOrderAnalytics($request, $format),
            'employee-production' => $this->exportEmployeeProduction($request, $format),
            'sales-channel'       => $this->exportSalesChannel($request, $format),
            default               => abort(404),
        };
    }

    // =============================================
    // PROFIT & LOSS
    // =============================================
    private function exportProfitLoss(Request $request, string $format)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $d = $this->rqs->getProfitLoss($bizId, $from, $to);

        if ($format === 'pdf') {
            $biz  = auth()->user()->business;
            $data = array_merge($d, compact('from', 'to', 'biz'));
            return Pdf::loadView('reports.exports.profit-loss-pdf', $data)
                      ->setPaper('a4', 'portrait')
                      ->download("profit-loss_{$from}_{$to}.pdf");
        }

        $rows = [
            ['Profit & Loss Statement'],
            ['Period', Carbon::parse($from)->format('d M Y').' - '.Carbon::parse($to)->format('d M Y')],
            ['Generated', now()->format('d M Y H:i')],
            [],
            ['INCOME'],
            ['Revenue from Sales',     number_format($d['revenue'], 2)],
            ['Cost of Goods Sold (COGS)', number_format(-$d['cogs'], 2)],
            ['Gross Profit',            number_format($d['grossProfit'], 2)],
            ['Gross Margin %', $d['revenue'] > 0 ? round(($d['grossProfit'] / $d['revenue']) * 100, 1).'%' : '0%'],
            [],
            ['DEDUCTIONS'],
            ['Other Income',       number_format($d['otherIncome'], 2)],
            ['Operating Expenses', number_format(-$d['expenses'], 2)],
            ['Production Wages',   number_format(-$d['productionWages'], 2)],
            ['Net Profit / Loss',  number_format($d['netProfit'], 2)],
            ['Net Margin %', $d['revenue'] > 0 ? round(($d['netProfit'] / $d['revenue']) * 100, 1).'%' : '0%'],
            [],
            ['EXPENSE BREAKDOWN BY CATEGORY'],
            ['Category', 'Transactions', 'Amount', '% Share'],
        ];
        foreach ($d['expenseBreakdown'] as $e) {
            $rows[] = [
                $e->category?->name ?? 'Uncategorized',
                $e->count,
                number_format($e->total, 2),
                $d['expenses'] > 0 ? round(($e->total / $d['expenses']) * 100, 1).'%' : '0%',
            ];
        }
        return $this->csvResponse($rows, "profit-loss_{$from}_{$to}.csv");
    }

    // =============================================
    // EXPENSE CATEGORY
    // =============================================
    private function exportExpenseCategory(Request $request, string $format)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $d = $this->rqs->getExpenseCategory($bizId, $from, $to);

        if ($format === 'pdf') {
            $biz  = auth()->user()->business;
            $data = array_merge($d, compact('from', 'to', 'biz'));
            return Pdf::loadView('reports.exports.expense-category-pdf', $data)
                      ->setPaper('a4', 'portrait')
                      ->download("expenses_{$from}_{$to}.pdf");
        }

        $rows = [
            ['Expense by Category — Detailed'],
            ['Period', Carbon::parse($from)->format('d M Y').' - '.Carbon::parse($to)->format('d M Y')],
            ['Total Expenses', 'BDT '.number_format($d['totalExpense'], 2)],
            [],
        ];
        foreach ($d['breakdown'] as $e) {
            $catName = $e->category?->name ?? 'Uncategorized';
            $share   = $d['totalExpense'] > 0 ? number_format(($e->total / $d['totalExpense']) * 100, 1).'%' : '0%';
            $rows[] = ["=== {$catName} === ({$e->count} transactions  |  BDT ".number_format($e->total, 2)."  |  {$share})"];
            $rows[] = ['  Date', '  Title', '  Method', '  Reference', '  Amount (BDT)'];
            foreach ($d['transactions']->get($e->category_id, collect()) as $txn) {
                $rows[] = [
                    '  '.$txn->transaction_date->format('d/m/Y'),
                    '  '.$txn->title,
                    '  '.ucfirst($txn->payment_method ?? '—'),
                    '  '.($txn->reference ?? '—'),
                    '  '.number_format($txn->amount, 2),
                ];
            }
            $rows[] = ['  ', '  ', '  ', '  Subtotal', '  '.number_format($e->total, 2)];
            $rows[] = [];
        }
        $rows[] = ['GRAND TOTAL', '', '', '', number_format($d['totalExpense'], 2)];
        return $this->csvResponse($rows, "expenses-detail_{$from}_{$to}.csv");
    }

    // =============================================
    // INCOME SOURCE
    // =============================================
    private function exportIncomeSource(Request $request, string $format)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $d   = $this->rqs->getIncomeSource($bizId, $from, $to);
        $biz = auth()->user()->business;

        if ($format === 'pdf') {
            return Pdf::loadView('reports.exports.income-source-pdf', array_merge($d, compact('from', 'to', 'biz')))
                      ->setPaper('a4', 'portrait')
                      ->download("income_{$from}_{$to}.pdf");
        }

        $rows = [
            ['Income Sources'],
            ['Period', Carbon::parse($from)->format('d M Y').' - '.Carbon::parse($to)->format('d M Y')],
            ['Total Income', 'BDT '.number_format($d['totalIncome'], 2)],
            [],
            ['#', 'Source', 'Transactions', 'Amount', '% Share', 'Avg per tx'],
        ];
        foreach ($d['breakdown'] as $i => $e) {
            $rows[] = [
                $i + 1,
                $e->category?->name ?? 'Uncategorized',
                $e->count,
                number_format($e->total, 2),
                $d['totalIncome'] > 0 ? number_format(($e->total / $d['totalIncome']) * 100, 1).'%' : '0%',
                $e->count > 0 ? number_format($e->total / $e->count, 2) : '0.00',
            ];
        }
        $rows[] = ['', 'TOTAL', $d['breakdown']->sum('count'), number_format($d['totalIncome'], 2), '100%', ''];
        return $this->csvResponse($rows, "income_{$from}_{$to}.csv");
    }

    // =============================================
    // BUSINESS OVERVIEW
    // =============================================
    private function exportBusinessOverview(Request $request, string $format)
    {
        $bizId = auth()->user()->business_id;
        $year  = (int) ($request->year ?? now()->year);

        $d   = $this->rqs->getBusinessOverview($bizId, $year);
        $biz = auth()->user()->business;

        if ($format === 'pdf') {
            return Pdf::loadView('reports.exports.business-overview-pdf', array_merge($d, compact('year', 'biz')))
                      ->setPaper('a4', 'landscape')
                      ->download("business-overview-{$year}.pdf");
        }

        $rows = [
            ['Business Overview — '.$year],
            ['Generated', now()->format('d M Y H:i')],
            [],
            ['Month', 'Revenue', 'Expenses', 'Other Income', 'Net Profit', 'Orders'],
        ];
        foreach ($d['months'] as $m) {
            $rows[] = [$m['month'], number_format($m['revenue'], 2), number_format($m['expense'], 2), number_format($m['income'], 2), number_format($m['profit'], 2), $m['orders']];
        }
        $months = $d['months'];
        $rows[] = ['TOTAL',
            number_format(collect($months)->sum('revenue'), 2),
            number_format(collect($months)->sum('expense'), 2),
            number_format(collect($months)->sum('income'), 2),
            number_format(collect($months)->sum('profit'), 2),
            collect($months)->sum('orders'),
        ];
        return $this->csvResponse($rows, "business-overview-{$year}.csv");
    }

    // =============================================
    // STOCK INVENTORY
    // =============================================
    private function exportStockInventory(Request $request, string $format)
    {
        $bizId    = auth()->user()->business_id;
        $d        = $this->rqs->getStockInventoryAll($bizId);
        $biz      = auth()->user()->business;
        $products = $d['products'];

        if ($format === 'pdf') {
            return Pdf::loadView('reports.exports.stock-inventory-pdf', compact('products', 'biz'))
                      ->setPaper('a4', 'landscape')
                      ->download("stock-inventory-".now()->format('Y-m-d').".pdf");
        }

        $rows = [
            ['Stock & Inventory Report'],
            ['Generated', now()->format('d M Y H:i')],
            [],
            ['#', 'Product', 'Category', 'SKU', 'Stock', 'Cost Price', 'Sell Price', 'Stock Value', 'Status'],
        ];
        foreach ($products as $i => $p) {
            $status = $p->stock <= 0 ? 'Out of Stock' : ($p->stock <= ($p->low_stock_alert ?? 5) ? 'Low Stock' : 'In Stock');
            $rows[] = [$i+1, $p->name, $p->category?->name ?? '—', $p->sku ?? '—', $p->stock, number_format($p->cost_price, 2), number_format($p->price, 2), number_format($p->stock * $p->cost_price, 2), $status];
        }
        return $this->csvResponse($rows, "stock-".now()->format('Y-m-d').".csv");
    }

    // =============================================
    // CUSTOMER DUE
    // =============================================
    private function exportCustomerDue(Request $request, string $format)
    {
        $bizId = auth()->user()->business_id;
        $d     = $this->rqs->getCustomerDue($bizId, paginate: false);
        $biz   = auth()->user()->business;

        if ($format === 'pdf') {
            return Pdf::loadView('reports.exports.customer-due-pdf', array_merge($d, compact('biz')))
                      ->setPaper('a4', 'portrait')
                      ->download("customer-due-".now()->format('Y-m-d').".pdf");
        }

        $rows = [
            ['Customer Due Report'],
            ['Generated', now()->format('d M Y H:i')],
            ['Total Due', 'BDT '.number_format($d['totalDue'], 2)],
            [],
            ['#', 'Customer', 'Phone', 'Total Purchase', 'Due Amount'],
        ];
        foreach ($d['customers'] as $i => $c) {
            $rows[] = [$i+1, $c->name, $c->phone ?? '—', number_format($c->total_purchase, 2), number_format($c->due_amount, 2)];
        }
        $rows[] = ['', 'TOTAL', '', '', number_format($d['totalDue'], 2)];
        return $this->csvResponse($rows, "customer-due-".now()->format('Y-m-d').".csv");
    }

    // =============================================
    // SUPPLIER DUE
    // =============================================
    private function exportSupplierDue(Request $request, string $format)
    {
        $bizId = auth()->user()->business_id;
        $d     = $this->rqs->getSupplierDue($bizId, paginate: false);
        $biz   = auth()->user()->business;

        if ($format === 'pdf') {
            return Pdf::loadView('reports.exports.supplier-due-pdf', array_merge($d, compact('biz')))
                      ->setPaper('a4', 'portrait')
                      ->download("supplier-due-".now()->format('Y-m-d').".pdf");
        }

        $rows = [
            ['Supplier Due Report'],
            ['Generated', now()->format('d M Y H:i')],
            ['Total Due', 'BDT '.number_format($d['totalDue'], 2)],
            [],
            ['#', 'Vendor', 'PO Count', 'Total Ordered', 'Due Amount'],
        ];
        foreach ($d['vendors'] as $i => $v) {
            $rows[] = [$i+1, $v->vendor?->name ?? '—', $v->po_count, number_format($v->total_ordered, 2), number_format($v->due_amount, 2)];
        }
        $rows[] = ['', 'TOTAL', $d['vendors']->sum('po_count'), '', number_format($d['totalDue'], 2)];
        return $this->csvResponse($rows, "supplier-due-".now()->format('Y-m-d').".csv");
    }

    // =============================================
    // ORDER ANALYTICS
    // =============================================
    private function exportOrderAnalytics(Request $request, string $format)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $d      = $this->rqs->getOrderAnalytics($bizId, $from, $to);
        $orders = $d['orders'];

        if ($format === 'pdf') {
            $biz = auth()->user()->business;
            $stats = [
                'total'     => $orders->count(),
                'revenue'   => $orders->whereIn('status', ['completed', 'delivered'])->sum('total'),
                'completed' => $orders->whereIn('status', ['completed', 'delivered'])->count(),
                'cancelled' => $orders->where('status', 'cancelled')->count(),
            ];
            return Pdf::loadView('reports.exports.order-analytics-pdf', compact('orders', 'stats', 'from', 'to', 'biz'))
                      ->setPaper('a4', 'landscape')
                      ->download("orders_{$from}_{$to}.pdf");
        }

        $rows = [
            ['Order Analytics'],
            ['Period', Carbon::parse($from)->format('d M Y').' - '.Carbon::parse($to)->format('d M Y')],
            [],
            ['#', 'Order #', 'Customer', 'Date', 'Status', 'Payment', 'Amount'],
        ];
        foreach ($orders as $i => $o) {
            $rows[] = [$i+1, $o->order_number ?? $o->id, $o->customer?->name ?? '—', Carbon::parse($o->created_at)->format('d M Y'), $o->status, $o->payment_method ?? '—', number_format($o->total, 2)];
        }
        return $this->csvResponse($rows, "orders_{$from}_{$to}.csv");
    }

    // =============================================
    // EMPLOYEE PRODUCTION
    // =============================================
    private function exportEmployeeProduction(Request $request, string $format)
    {
        ini_set('memory_limit', '256M');
        set_time_limit(120);

        $bizId = auth()->user()->business_id;
        $from  = $request->from     ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to       ?? now()->endOfMonth()->format('Y-m-d');
        $empId = $request->employee ? (int) $request->employee : null;

        $d   = $this->rqs->getEmployeeProduction($bizId, $from, $to, $empId);
        $biz = auth()->user()->business;

        if ($format === 'pdf') {
            return Pdf::loadView('reports.exports.employee-production-pdf', array_merge($d, compact('from', 'to', 'biz')))
                      ->setPaper('a4', 'portrait')
                      ->download("employee-production_{$from}_{$to}.pdf");
        }

        $rows = [
            ['Employee Production Report'],
            ['Period', Carbon::parse($from)->format('d M Y').' - '.Carbon::parse($to)->format('d M Y')],
            ['Total Pieces', (int) $d['totalQty'], 'Total Wages', 'BDT '.number_format((float) $d['totalAmount'], 2)],
            [],
            ['Employee', 'Date', 'Product', 'Color', 'Size', 'Quantity', 'Rate (BDT)', 'Amount (BDT)', 'Notes'],
        ];
        foreach ($d['entries'] as $entry) {
            $qty    = (int)   ($entry->quantity ?? 0);
            $amount = (float) ($entry->amount   ?? 0);
            $date   = $entry->entry_date ? $entry->entry_date->format('d/m/Y') : '—';
            $rows[] = [
                $entry->employee?->name ?? '—',
                $date,
                $entry->productionRate?->product_name ?? 'Manual',
                $entry->productionRate?->color ?? '—',
                $entry->productionRate?->size  ?? '—',
                $qty,
                $qty > 0 ? number_format($amount / $qty, 2) : '0.00',
                number_format($amount, 2),
                $entry->notes ?? '',
            ];
        }
        $rows[] = [];
        $rows[] = ['', '', '', '', 'TOTAL', (int) $d['totalQty'], '', number_format((float) $d['totalAmount'], 2)];
        return $this->csvResponse($rows, "employee-production_{$from}_{$to}.csv");
    }

    // =============================================
    // SALES BY CHANNEL
    // =============================================
    private function exportSalesChannel(Request $request, string $format)
    {
        $bizId = auth()->user()->business_id;
        $from  = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to    = $request->to   ?? now()->endOfMonth()->format('Y-m-d');

        $d   = $this->rqs->getSalesChannel($bizId, $from, $to);
        $biz = auth()->user()->business;

        if ($format === 'pdf') {
            return Pdf::loadView('reports.exports.sales-channel-pdf', array_merge($d, compact('from', 'to', 'biz')))
                      ->setPaper('a4', 'portrait')
                      ->download("sales-channel_{$from}_{$to}.pdf");
        }

        $rows = [
            ['Sales by Channel Report'],
            ['Period', Carbon::parse($from)->format('d M Y').' - '.Carbon::parse($to)->format('d M Y')],
            ['Total Revenue (completed)', 'BDT '.number_format($d['totalRevenue'], 2)],
            [],
            ['Channel', 'Total Orders', 'Completed', 'Cancelled', 'Completion %', 'Completed Revenue (BDT)', 'Revenue Share %'],
        ];
        foreach ($d['bySource'] as $ch) {
            $completion = $ch->total_count > 0 ? round(($ch->completed_count / $ch->total_count) * 100) : 0;
            $share      = $d['totalRevenue'] > 0 ? round(($ch->completed_revenue / $d['totalRevenue']) * 100, 1) : 0;
            $rows[] = [ucfirst($ch->source), $ch->total_count, $ch->completed_count, $ch->cancelled_count, $completion.'%', number_format($ch->completed_revenue, 2), $share.'%'];
        }
        $rows[] = ['TOTAL', $d['bySource']->sum('total_count'), $d['bySource']->sum('completed_count'), $d['bySource']->sum('cancelled_count'), '', number_format($d['totalRevenue'], 2), '100%'];
        return $this->csvResponse($rows, "sales-channel_{$from}_{$to}.csv");
    }

    // =============================================
    // HELPERS
    // =============================================
    private function csvResponse(array $rows, string $filename)
    {
        $bom = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel Bengali support
        return response()->streamDownload(function () use ($rows, $bom) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, $bom);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
