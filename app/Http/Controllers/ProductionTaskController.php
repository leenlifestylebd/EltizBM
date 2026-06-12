<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeNotification;
use App\Models\ProductionEntry;
use App\Models\ProductionRate;
use App\Models\ProductionTask;
use App\Models\TaskSubmission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductionTaskController extends Controller
{
    public function index(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $query = ProductionTask::where('business_id', $bizId)
            ->with(['employee', 'assignedBy'])
            ->orderByDesc('assigned_at');

        if ($empId  = $request->employee_id) $query->where('employee_id', $empId);
        if ($status = $request->status)      $query->where('status', $status);
        if ($stage  = $request->stage)       $query->where('stage', $stage);
        if ($from   = $request->from)        $query->whereDate('assigned_at', '>=', $from);
        if ($to     = $request->to)          $query->whereDate('assigned_at', '<=', $to);

        $tasks = $query->paginate(20)->withQueryString();

        $employees = Employee::where('business_id', $bizId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $stats = [
            'total'     => ProductionTask::where('business_id', $bizId)->count(),
            'assigned'  => ProductionTask::where('business_id', $bizId)->where('status', 'assigned')->count(),
            'partial'   => ProductionTask::where('business_id', $bizId)->where('status', 'partial')->count(),
            'completed' => ProductionTask::where('business_id', $bizId)->where('status', 'completed')->count(),
        ];

        return view('production.tasks.index', compact('tasks', 'employees', 'stats'));
    }

    public function create(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $employees = Employee::where('business_id', $bizId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $rates = ProductionRate::where('business_id', $bizId)
            ->where('is_active', true)
            ->orderBy('product_name')
            ->get();

        // Pre-fill when forwarding from a sewing task
        $sourceTask = null;
        if ($request->from) {
            $sourceTask = ProductionTask::where('business_id', $bizId)
                ->where('stage', 'sewing')
                ->find($request->from);
        }

        return view('production.tasks.create', compact('employees', 'rates', 'sourceTask'));
    }

    public function store(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $request->validate([
            'employee_id'        => ['required', \Illuminate\Validation\Rule::exists('employees', 'id')->where('business_id', $bizId)],
            // Rate is optional — assign work even before a pay rate is set up. Without a
            // rate the worker's submission simply earns 0 until a rate is added.
            'production_rate_id' => ['nullable', \Illuminate\Validation\Rule::exists('production_rates', 'id')->where('business_id', $bizId)],
            'product_name'       => 'required|string|max:255',
            'assigned_quantity'  => 'required|integer|min:1',
            'deadline'           => 'nullable|date|after_or_equal:today',
        ]);

        $employee = Employee::where('business_id', $bizId)->findOrFail($request->employee_id);

        $stage = $request->stage ?? 'sewing';

        // Validate source_task if forwarding
        $sourceTaskId = null;
        if ($request->source_task_id) {
            $src = ProductionTask::where('business_id', $bizId)->find($request->source_task_id);
            if ($src) $sourceTaskId = $src->id;
        }

        ProductionTask::create([
            'business_id'        => $bizId,
            'employee_id'        => $employee->id,
            'assigned_by'        => auth()->id(),
            'production_rate_id' => $request->production_rate_id ?: null,
            'stage'              => $stage,
            'source_task_id'     => $sourceTaskId,
            'product_name'       => $request->product_name,
            'color'              => $request->color,
            'size'               => $request->size,
            'assigned_quantity'  => $request->assigned_quantity,
            'completed_quantity' => 0,
            'status'             => 'assigned',
            'deadline'           => $request->deadline ?: null,
            'notes'              => $request->notes,
            'assigned_at'        => now(),
        ]);

        $details = $request->product_name
            . ($request->color ? ', ' . $request->color : '')
            . ($request->size ? ', সাইজ ' . $request->size : '')
            . ' — ' . $request->assigned_quantity . ' পিস'
            . ($request->deadline ? ' (ডেডলাইন: ' . \Carbon\Carbon::parse($request->deadline)->format('d M') . ')' : '');

        EmployeeNotification::notify(
            $bizId, $employee->id, 'task',
            'নতুন কাজ পেয়েছেন',
            $details,
            route('production.worker.dashboard')
        );

        return redirect()->route('production.tasks.index')
            ->with('success', 'Task assigned to ' . $employee->name . '!');
    }

    public function show(ProductionTask $task)
    {
        abort_if($task->business_id !== auth()->user()->business_id, 403);

        $bizId = auth()->user()->business_id;
        $task->load(['employee', 'assignedBy', 'submissions' => fn($q) => $q->orderByDesc('submitted_at'), 'forwardedTasks.employee']);

        // Data for "Forward to Iron" modal (only relevant for sewing tasks with completed pieces)
        $ironEmployees = collect();
        $ironRates     = collect();
        if ($task->stage === 'sewing' && $task->completed_quantity > 0) {
            $ironEmployees = Employee::where('business_id', $bizId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
            $ironRates = ProductionRate::where('business_id', $bizId)
                ->where('stage', 'ironing')
                ->where('is_active', true)
                ->orderBy('product_name')
                ->get();
        }

        return view('production.tasks.show', compact('task', 'ironEmployees', 'ironRates'));
    }

    public function forwardToIron(Request $request, ProductionTask $task)
    {
        abort_if($task->business_id !== auth()->user()->business_id, 403);
        abort_if($task->stage !== 'sewing', 422, 'Only sewing tasks can be forwarded to ironing.');

        $bizId = auth()->user()->business_id;

        $forwardable = $task->forwardable_quantity;
        abort_if($forwardable <= 0, 422, 'No forwardable pieces. Either nothing is completed yet or all completed pieces are already forwarded.');

        $request->validate([
            'employee_id'        => ['required', Rule::exists('employees', 'id')->where('business_id', $bizId)],
            'production_rate_id' => ['nullable', Rule::exists('production_rates', 'id')->where('business_id', $bizId)->where('stage', 'ironing')],
            'quantity'           => ['required', 'integer', 'min:1', "max:{$forwardable}"],
            'notes'              => 'nullable|string|max:500',
        ]);

        $ironWorker = Employee::where('business_id', $bizId)->findOrFail($request->employee_id);

        $ironTask = ProductionTask::create([
            'business_id'        => $bizId,
            'employee_id'        => $ironWorker->id,
            'assigned_by'        => auth()->id(),
            'production_rate_id' => $request->production_rate_id ?: null,
            'stage'              => 'ironing',
            'source_task_id'     => $task->id,
            'product_name'       => $task->product_name,
            'color'              => $task->color,
            'size'               => $task->size,
            'assigned_quantity'  => $request->quantity,
            'completed_quantity' => 0,
            'status'             => 'assigned',
            'notes'              => $request->notes,
            'assigned_at'        => now(),
        ]);

        return redirect()->route('production.tasks.show', $ironTask)
            ->with('success', "Iron task assigned to {$ironWorker->name} — {$request->quantity} pieces.");
    }

    public function update(Request $request, ProductionTask $task)
    {
        abort_if($task->business_id !== auth()->user()->business_id, 403);

        $request->validate([
            'assigned_quantity' => 'sometimes|integer|min:1',
            'status'            => 'sometimes|in:assigned,in_progress,partial,completed,cancelled',
            'deadline'          => 'nullable|date',
        ]);

        $task->update($request->only(['product_name', 'color', 'size', 'assigned_quantity', 'status', 'deadline', 'notes']));

        return back()->with('success', 'Task updated!');
    }

    public function destroy(ProductionTask $task)
    {
        abort_if($task->business_id !== auth()->user()->business_id, 403);
        $task->update(['status' => 'cancelled']);
        $task->delete();

        EmployeeNotification::notify(
            $task->business_id, $task->employee_id, 'task',
            'কাজ বাতিল হয়েছে',
            $task->product_name . ' — ' . $task->assigned_quantity . ' পিস',
            route('production.worker.dashboard')
        );

        return back()->with('success', 'Task cancelled.');
    }

    public function submit(Request $request, ProductionTask $task)
    {
        abort_if($task->business_id !== auth()->user()->business_id, 403);

        $bizId = auth()->user()->business_id;
        $role  = auth()->user()->role;

        if ($role === 'employee') {
            $employee = Employee::where('business_id', $bizId)
                ->where('user_id', auth()->id())
                ->firstOrFail();
            abort_if($task->employee_id !== $employee->id, 403);
        }

        $remaining = $task->assigned_quantity - $task->completed_quantity;

        $request->validate([
            'submitted_quantity' => ['required', 'integer', 'min:1', "max:{$remaining}"],
            'rate_used'          => ['nullable', 'numeric', 'min:0'],
        ]);

        // Determine effective rate: use override if provided, else fall back to task rate
        $defaultRate  = $task->production_rate_id
                            ? ProductionRate::find($task->production_rate_id)?->rate ?? 0
                            : 0;
        $rateUsed     = $request->filled('rate_used') ? (float) $request->rate_used : $defaultRate;
        $amount       = $request->submitted_quantity * $rateUsed;

        TaskSubmission::create([
            'business_id'        => $task->business_id,
            'production_task_id' => $task->id,
            'employee_id'        => $task->employee_id,
            'submitted_quantity' => $request->submitted_quantity,
            'rate_used'          => $rateUsed > 0 ? $rateUsed : null,
            'amount'             => $amount > 0 ? $amount : null,
            'quality_note'       => $request->quality_note,
            'submitted_at'       => today()->toDateString(),
            'notes'              => $request->notes,
        ]);

        ProductionEntry::create([
            'business_id'       => $task->business_id,
            'employee_id'       => $task->employee_id,
            'production_rate_id'=> $task->production_rate_id,
            'entry_date'        => today()->toDateString(),
            'quantity'          => $request->submitted_quantity,
            'amount'            => $amount,
            'notes'             => "Task #{$task->id}: {$task->product_name}" . ($task->color ? " ({$task->color})" : '') . ($task->size ? " sz {$task->size}" : ''),
        ]);

        $task->increment('completed_quantity', $request->submitted_quantity);
        $task->refresh();
        $task->recalculateStatus();

        $pending = $task->pending_quantity;
        $message = "Submitted {$request->submitted_quantity} pieces";
        $message .= $amount > 0 ? ", earned ৳" . number_format($amount, 2) . "." : ".";
        $message .= $pending > 0 ? " {$pending} remaining." : " Task fully completed!";

        if ($role === 'employee') {
            return redirect()->route('production.worker.dashboard')->with('success', $message);
        }

        return redirect()->route('production.tasks.show', $task->id)->with('success', $message);
    }

    public function myTasks(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $employee = Employee::where('business_id', $bizId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $tasks = ProductionTask::where('business_id', $bizId)
            ->where('employee_id', $employee->id)
            ->whereNotIn('status', ['cancelled'])
            ->with(['submissions', 'productionRate'])
            ->orderByDesc('assigned_at')
            ->get();

        return view('production.tasks.my-tasks', compact('tasks', 'employee'));
    }
}
