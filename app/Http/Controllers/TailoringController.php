<?php
namespace App\Http\Controllers;

use App\Models\TailoringOrder;
use App\Models\Customer;
use App\Models\ProductionTask;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        TailoringOrder::create([
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
            'advance_paid'     => $request->advance_paid ?? 0,
            'stage'            => 'pending',
            'delivery_date'    => $request->delivery_date,
            'created_by'       => auth()->id(),
        ]);

        return redirect()->route('tailoring.index')->with('success', 'Tailoring order created!');
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
            'employee_id'       => 'required|exists:employees,id',
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

    // ── DELETE ────────────────────────────────────────────────────────────────
    public function destroy(TailoringOrder $tailoring)
    {
        abort_unless($tailoring->business_id === $this->bizId(), 403);
        $tailoring->delete();
        return redirect()->route('tailoring.index')->with('success', 'Order deleted.');
    }
}
