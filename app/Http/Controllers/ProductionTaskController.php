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
            'production_rate_id' => ['nullable', \Illuminate\Validation\Rule::exists('production_rates', 'id')->where('business_id', $bizId)->where('is_active', true)],
            'product_name'       => 'required|string|max:255',
            'assigned_quantity'  => 'required|integer|min:1',
            'deadline'           => 'nullable|date|after_or_equal:today',
            'assigned_date'      => 'nullable|date|before_or_equal:today',
        ]);

        $employee = Employee::where('business_id', $bizId)->findOrFail($request->employee_id);

        $stage = $request->stage ?? 'sewing';

        // Validate source_task if forwarding
        $sourceTaskId = null;
        if ($request->source_task_id) {
            $src = ProductionTask::where('business_id', $bizId)->find($request->source_task_id);
            if ($src) $sourceTaskId = $src->id;
        }

        ProductionTask::assignWithCurrentRate([
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
            'assigned_at'        => $request->filled('assigned_date')
                ? \Carbon\Carbon::parse($request->assigned_date)->setTimeFrom(now())
                : now(),
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
        $task->load(['employee', 'assignedBy', 'submissions' => fn($q) => $q->orderByDesc('submitted_at'), 'forwardedTasks.employee', 'sourceTask.employee']);

        // Data for the "Forward to next stage" panel (cutting→sewing→iron)
        $nextStage     = $task->nextStage();
        $nextStageLabel= $task->nextStageLabel();
        $nextEmployees = collect();
        $nextRates     = collect();
        if ($nextStage && $task->completed_quantity > 0) {
            $nextEmployees = Employee::where('business_id', $bizId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
            $nextRates = ProductionRate::where('business_id', $bizId)
                ->where('stage', $nextStage)
                ->where('is_active', true)
                ->orderBy('product_name')
                ->get();
        }

        return view('production.tasks.show', compact('task', 'nextStage', 'nextStageLabel', 'nextEmployees', 'nextRates'));
    }

    /**
     * Pipeline board: tasks laid out in stage columns (Cutting → Sewing → Iron)
     * so the whole factory's work-in-progress is visible on one screen.
     */
    public function pipeline(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $tasks = ProductionTask::where('business_id', $bizId)
            ->whereIn('stage', ProductionRate::PIPELINE)
            ->whereNotIn('status', ['cancelled'])
            ->whereDate('assigned_at', '>=', now()->subDays(90)->toDateString())
            ->with('employee')
            ->orderByDesc('assigned_at')
            ->get();

        $columns = [];
        foreach (ProductionRate::PIPELINE as $stage) {
            $columns[$stage] = $tasks->where('stage', $stage)->values();
        }

        $stageLabels = ProductionRate::STAGES;
        $stageLabelsBn = ['cutting' => 'কাটিং', 'sewing' => 'সেলাই', 'ironing' => 'ইস্ত্রি'];

        return view('production.pipeline', compact('columns', 'stageLabels', 'stageLabelsBn'));
    }

    /**
     * Forward completed pieces to the next stage in the pipeline
     * (cutting → sewing → ironing). Creates a linked task for the next stage.
     */
    public function forward(Request $request, ProductionTask $task)
    {
        abort_if($task->business_id !== auth()->user()->business_id, 403);

        $nextStage = $task->nextStage();
        abort_if(!$nextStage, 422, 'This is the last stage — nothing to forward to.');

        $bizId = auth()->user()->business_id;

        $forwardable = $task->forwardable_quantity;
        abort_if($forwardable <= 0, 422, 'No forwardable pieces. Either nothing is completed yet or all completed pieces are already forwarded.');

        $request->validate([
            'employee_id'        => ['required', Rule::exists('employees', 'id')->where('business_id', $bizId)],
            'production_rate_id' => ['nullable', Rule::exists('production_rates', 'id')->where('business_id', $bizId)->where('stage', $nextStage)->where('is_active', true)],
            'quantity'           => ['required', 'integer', 'min:1', "max:{$forwardable}"],
            'notes'              => 'nullable|string|max:500',
        ]);

        $worker = Employee::where('business_id', $bizId)->findOrFail($request->employee_id);

        $nextTask = ProductionTask::assignWithCurrentRate([
            'business_id'        => $bizId,
            'employee_id'        => $worker->id,
            'assigned_by'        => auth()->id(),
            'production_rate_id' => $request->production_rate_id ?: null,
            'stage'              => $nextStage,
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

        // Notify the worker who just got the forwarded work.
        \App\Models\EmployeeNotification::notify(
            $bizId, $worker->id, 'task',
            'নতুন কাজ পেয়েছেন (' . ($task->nextStageLabel() ?? ucfirst($nextStage)) . ')',
            $task->product_name . ($task->color ? ', ' . $task->color : '') . ($task->size ? ', সাইজ ' . $task->size : '') . ' — ' . $request->quantity . ' পিস',
            route('production.worker.dashboard')
        );

        return redirect()->route('production.tasks.show', $nextTask)
            ->with('success', ucfirst($nextStage) . " task assigned to {$worker->name} — {$request->quantity} pieces.");
    }

    public function update(Request $request, ProductionTask $task)
    {
        abort_if($task->business_id !== auth()->user()->business_id, 403);

        $request->validate([
            'assigned_quantity' => 'sometimes|integer|min:1',
            'status'            => 'sometimes|in:assigned,in_progress,partial,completed,cancelled',
            'deadline'          => 'nullable|date',
            'assigned_date'     => 'nullable|date|before_or_equal:today',
        ]);

        $task->update($request->only(['product_name', 'color', 'size', 'assigned_quantity', 'status', 'deadline', 'notes']));

        // Allow correcting the assigned date, even for already-completed tasks.
        if ($request->filled('assigned_date')) {
            $task->assigned_at = \Carbon\Carbon::parse($request->assigned_date)->setTimeFrom($task->assigned_at ?? now());
            $task->save();
        }

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
            'submitted_date'     => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        // Back-dated submission: the earning + submission are attributed to this day.
        $entryDate = $request->filled('submitted_date')
            ? \Carbon\Carbon::parse($request->submitted_date)->toDateString()
            : today()->toDateString();

        // Determine effective rate: use override if provided, else fall back to task rate
        $defaultRate  = $task->production_rate_id
                            ? ProductionRate::find($task->production_rate_id)?->rate ?? 0
                            : 0;
        $rateUsed     = $request->filled('rate_used') ? (float) $request->rate_used : $defaultRate;
        $amount       = $request->submitted_quantity * $rateUsed;

        // Create the payment entry first, then link the submission to it so an admin
        // can later edit/delete the submission and adjust this exact payment.
        $entry = ProductionEntry::create([
            'business_id'       => $task->business_id,
            'employee_id'       => $task->employee_id,
            'production_rate_id'=> $task->production_rate_id,
            'entry_date'        => $entryDate,
            'quantity'          => $request->submitted_quantity,
            'amount'            => $amount,
            'notes'             => "Task #{$task->id}: {$task->product_name}" . ($task->color ? " ({$task->color})" : '') . ($task->size ? " sz {$task->size}" : ''),
        ]);

        TaskSubmission::create([
            'business_id'        => $task->business_id,
            'production_task_id' => $task->id,
            'production_entry_id'=> $entry->id,
            'employee_id'        => $task->employee_id,
            'submitted_quantity' => $request->submitted_quantity,
            'rate_used'          => $rateUsed > 0 ? $rateUsed : null,
            'amount'             => $amount > 0 ? $amount : null,
            'quality_note'       => $request->quality_note,
            'submitted_at'       => $entryDate,
            'notes'              => $request->notes,
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

        // Admin: stay on whatever page the work was submitted from (a worker's task
        // list/dashboard, team overview, the task page…) so several tasks can be
        // submitted one after another without navigating back each time.
        return redirect()->back(fallback: route('production.tasks.show', $task->id))
            ->with('success', $message);
    }

    /**
     * Admin: edit a worker's submission. Adjusts the linked payment entry and the
     * task's completed quantity in lock-step. Workers cannot reach this route.
     */
    public function updateSubmission(Request $request, TaskSubmission $submission)
    {
        $bizId = auth()->user()->business_id;
        abort_if($submission->business_id !== $bizId, 403);
        abort_if(auth()->user()->role === 'employee', 403);

        $task = $submission->task;
        abort_if(!$task, 404);

        // completed minus this submission = the baseline the new qty sits on top of
        $completedExcl = (int) $task->completed_quantity - (int) $submission->submitted_quantity;
        $maxQty        = (int) $task->assigned_quantity - $completedExcl;

        $request->validate([
            'submitted_quantity' => ['required', 'integer', 'min:1', "max:{$maxQty}"],
            'rate_used'          => ['nullable', 'numeric', 'min:0'],
            'submitted_date'     => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $newQty = (int) $request->submitted_quantity;
        $entryDate = $request->filled('submitted_date')
            ? \Carbon\Carbon::parse($request->submitted_date)->toDateString()
            : null;

        // Can't drop completed below what's already forwarded to the next stage.
        $forwarded = $task->forwardedQuantity();
        if ($completedExcl + $newQty < $forwarded) {
            return back()->with('error', "Cannot reduce below the {$forwarded} pcs already forwarded to the next stage.");
        }

        $rateUsed = $request->filled('rate_used') ? (float) $request->rate_used : (float) ($submission->rate_used ?? 0);
        $amount   = $newQty * $rateUsed;

        if ($submission->production_entry_id) {
            ProductionEntry::where('id', $submission->production_entry_id)
                ->update(array_merge(
                    ['quantity' => $newQty, 'amount' => $amount],
                    $entryDate ? ['entry_date' => $entryDate] : []
                ));
        }

        $submission->update(array_merge([
            'submitted_quantity' => $newQty,
            'rate_used'          => $rateUsed > 0 ? $rateUsed : null,
            'amount'             => $amount > 0 ? $amount : null,
            'quality_note'       => $request->input('quality_note', $submission->quality_note),
            'notes'              => $request->input('notes', $submission->notes),
        ], $entryDate ? ['submitted_at' => $entryDate] : []));

        $task->completed_quantity = $completedExcl + $newQty;
        $task->save();
        $task->recalculateStatus();

        return back()->with('success', 'Submission updated — payment and progress adjusted.');
    }

    /**
     * Admin: delete a submission, reverse its payment entry and reduce the task's
     * completed quantity. Blocked if the pieces have already moved to the next stage.
     */
    public function deleteSubmission(TaskSubmission $submission)
    {
        $bizId = auth()->user()->business_id;
        abort_if($submission->business_id !== $bizId, 403);
        abort_if(auth()->user()->role === 'employee', 403);

        $task = $submission->task;

        if ($task) {
            $completedAfter = (int) $task->completed_quantity - (int) $submission->submitted_quantity;
            if ($completedAfter < $task->forwardedQuantity()) {
                return back()->with('error', 'Cannot delete — some of these pieces are already forwarded to the next stage.');
            }
        }

        if ($submission->production_entry_id) {
            ProductionEntry::where('id', $submission->production_entry_id)->delete();
        }

        if ($task) {
            $task->completed_quantity = max(0, (int) $task->completed_quantity - (int) $submission->submitted_quantity);
            $task->save();
            $task->recalculateStatus();
        }

        $submission->delete();

        return back()->with('success', 'Submission deleted and its payment reversed.');
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
