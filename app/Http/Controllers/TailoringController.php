<?php
namespace App\Http\Controllers;

use App\Models\TailoringOrder;
use App\Models\Customer;
use App\Models\ProductionTask;
use App\Models\Employee;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class TailoringController extends Controller
{
    private function bizId(): int
    {
        return auth()->user()->business_id;
    }

    // ── LIST ──────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $bizId = $this->bizId();
        $q = TailoringOrder::forBusiness($bizId)->with('customer','createdBy');

        if ($request->stage) {
            $q->where('stage', $request->stage);
        }
        if ($request->search) {
            $s = $request->search;
            $q->where(function ($query) use ($s) {
                $query->where('order_number', 'like', "%$s%")
                      ->orWhere('customer_name', 'like', "%$s%")
                      ->orWhere('customer_phone', 'like', "%$s%");
            });
        }

        $orders = $q->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stageCounts = TailoringOrder::forBusiness($bizId)
            ->selectRaw('stage, count(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage');

        return view('tailoring.index', compact('orders', 'stageCounts'));
    }

    // ── CREATE FORM ───────────────────────────────────────────────────────────
    public function create()
    {
        $customers = Customer::where('business_id', $this->bizId())->orderBy('name')->get();
        return view('tailoring.create', compact('customers'));
    }

    // ── STORE ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'price'         => 'required|numeric|min:0',
        ]);

        $advance = $request->advance_paid ?? 0;

        $order = TailoringOrder::create([
            'business_id'      => $this->bizId(),
            'customer_id'      => $request->customer_id ?: null,
            'customer_name'    => $request->customer_name,
            'customer_phone'   => $request->customer_phone,
            'height'           => $request->height,
            'body_fit'         => $request->body_fit,
            'body_loose'       => $request->body_loose,
            'shoulder'         => $request->shoulder,
            'sleeve'           => $request->sleeve,
            'sleeve_open'      => $request->sleeve_open,
            'neck'             => $request->neck,
            'round_gher'       => $request->round_gher,
            'measurement_note' => $request->measurement_note,
            'fabric_type'      => $request->fabric_type,
            'color'            => $request->color,
            'design_note'      => $request->design_note,
            'price'            => $request->price,
            'advance_paid'     => $advance,
            'stage'            => 'pending',
            'delivery_date'    => $request->delivery_date,
            'created_by'       => auth()->id(),
        ]);

        // Record advance payment as income transaction
        if ($advance > 0) {
            Transaction::create([
                'business_id'      => $this->bizId(),
                'created_by'       => auth()->id(),
                'type'             => 'income',
                'title'            => 'Tailoring Advance – ' . $order->customer_name . ' (' . $order->order_number . ')',
                'amount'           => $advance,
                'payment_method'   => $request->payment_method ?? 'cash',
                'reference'        => 'TAILORING-' . $order->id,
                'notes'            => $request->notes,
                'transaction_date' => now()->toDateString(),
            ]);
        }

        return redirect()->route('tailoring.show', $order)->with('success', 'Tailoring order created!');
    }

    // ── SHOW ──────────────────────────────────────────────────────────────────
    public function show(TailoringOrder $tailoring)
    {
        abort_unless($tailoring->business_id === $this->bizId(), 403);
        $tailoring->load('tasks.employee', 'tasks.assignedBy', 'createdBy');
        $employees = Employee::where('business_id', $this->bizId())->where('status','active')->orderBy('name')->get();
        return view('tailoring.show', compact('tailoring', 'employees'));
    }

    // ── EDIT FORM ─────────────────────────────────────────────────────────────
    public function edit(TailoringOrder $tailoring)
    {
        abort_unless($tailoring->business_id === $this->bizId(), 403);
        $customers = Customer::where('business_id', $this->bizId())->orderBy('name')->get();
        return view('tailoring.edit', compact('tailoring', 'customers'));
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────
    public function update(Request $request, TailoringOrder $tailoring)
    {
        abort_unless($tailoring->business_id === $this->bizId(), 403);
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'price'         => 'required|numeric|min:0',
        ]);

        $tailoring->update([
            'customer_name'    => $request->customer_name,
            'customer_phone'   => $request->customer_phone,
            'customer_id'      => $request->customer_id ?: null,
            'height'           => $request->height,
            'body_fit'         => $request->body_fit,
            'body_loose'       => $request->body_loose,
            'shoulder'         => $request->shoulder,
            'sleeve'           => $request->sleeve,
            'sleeve_open'      => $request->sleeve_open,
            'neck'             => $request->neck,
            'round_gher'       => $request->round_gher,
            'measurement_note' => $request->measurement_note,
            'fabric_type'      => $request->fabric_type,
            'color'            => $request->color,
            'design_note'      => $request->design_note,
            'price'            => $request->price,
            'advance_paid'     => $request->advance_paid ?? 0,
            'delivery_date'    => $request->delivery_date,
        ]);

        return redirect()->route('tailoring.show', $tailoring)->with('success', 'Order updated!');
    }

    // ── ADVANCE STAGE ─────────────────────────────────────────────────────────
    public function advanceStage(TailoringOrder $tailoring)
    {
        abort_unless($tailoring->business_id === $this->bizId(), 403);
        $next = $tailoring->next_stage;
        if ($next) {
            $tailoring->update(['stage' => $next]);
        }
        return back()->with('success', 'Stage updated to ' . ucfirst($next ?? $tailoring->stage));
    }

    // ── SET STAGE ─────────────────────────────────────────────────────────────
    public function setStage(Request $request, TailoringOrder $tailoring)
    {
        abort_unless($tailoring->business_id === $this->bizId(), 403);
        $request->validate(['stage' => 'required|in:pending,cutting,stitching,finishing,ready,delivered,cancelled']);
        $tailoring->update(['stage' => $request->stage]);
        return back()->with('success', 'Stage changed to ' . ucfirst($request->stage));
    }

    // ── ASSIGN WORKER (create production task linked to this order) ───────────
    public function assignWorker(Request $request, TailoringOrder $tailoring)
    {
        abort_unless($tailoring->business_id === $this->bizId(), 403);
        $request->validate([
            'employee_id'       => ['required', \Illuminate\Validation\Rule::exists('employees', 'id')->where('business_id', $this->bizId())],
            'task_stage'        => 'required|string',
            'assigned_quantity' => 'required|integer|min:1',
        ]);

        ProductionTask::create([
            'business_id'       => $this->bizId(),
            'tailoring_order_id'=> $tailoring->id,
            'employee_id'       => $request->employee_id,
            'assigned_by'       => auth()->id(),
            'product_name'      => $tailoring->order_number . ' — ' . ucfirst($request->task_stage),
            'color'             => $tailoring->color,
            'size'              => null,
            'assigned_quantity' => $request->assigned_quantity,
            'completed_quantity'=> 0,
            'status'            => 'assigned',
            'deadline'          => $tailoring->delivery_date,
            'notes'             => $request->notes,
            'assigned_at'       => now(),
        ]);

        return back()->with('success', 'Worker assigned for ' . ucfirst($request->task_stage));
    }

    // ── SLIP PDF ──────────────────────────────────────────────────────────────
    public function slip(TailoringOrder $tailoring)
    {
        abort_unless($tailoring->business_id === $this->bizId(), 403);
        $tailoring->load('business', 'createdBy');

        $pdf = Pdf::loadView('tailoring.slip', compact('tailoring'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'     => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'dpi'             => 120,
                'isPhpEnabled'    => true,
                'margin_top'      => 0,
                'margin_bottom'   => 0,
                'margin_left'     => 0,
                'margin_right'    => 0,
            ]);

        $filename = 'Tailoring-' . $tailoring->order_number . '.pdf';
        return $pdf->download($filename);
    }

    // ── RECEIVE PAYMENT ───────────────────────────────────────────────────────
    public function receivePayment(Request $request, TailoringOrder $tailoring)
    {
        abort_unless($tailoring->business_id === $this->bizId(), 403);

        $request->validate([
            'amount'         => 'required|numeric|min:0.01|max:' . $tailoring->due_amount,
            'payment_method' => 'required|in:cash,bkash,nagad,rocket,bank,card,other',
        ]);

        $amount = (float) $request->amount;

        // Update advance_paid on the order
        $tailoring->increment('advance_paid', $amount);

        // Record as income transaction
        Transaction::create([
            'business_id'      => $this->bizId(),
            'created_by'       => auth()->id(),
            'type'             => 'income',
            'title'            => 'Tailoring Payment – ' . $tailoring->customer_name . ' (' . $tailoring->order_number . ')',
            'amount'           => $amount,
            'payment_method'   => $request->payment_method,
            'reference'        => 'TAILORING-' . $tailoring->id,
            'notes'            => $request->notes,
            'transaction_date' => $request->transaction_date ?? now()->toDateString(),
        ]);

        // Auto-mark as delivered if fully paid and stage is ready
        if ($tailoring->fresh()->due_amount <= 0 && $tailoring->stage === 'ready') {
            $tailoring->update(['stage' => 'delivered']);
        }

        return back()->with('success', '৳' . number_format($amount, 0) . ' payment received successfully!');
    }

    // ── DELETE ────────────────────────────────────────────────────────────────
    public function destroy(TailoringOrder $tailoring)
    {
        // Deleting financial/operational records is admin-only; the UI hides these
        // buttons for other roles but the endpoint must enforce it too.
        abort_unless(auth()->user()->isAdmin(), 403, 'Only an admin can delete this.');
        abort_unless($tailoring->business_id === $this->bizId(), 403);
        $tailoring->delete();
        return redirect()->route('tailoring.index')->with('success', 'Order deleted.');
    }
}
