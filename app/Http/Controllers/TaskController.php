<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $bizId = auth()->user()->business_id;

        $query = Task::where('business_id', $bizId)
            ->with(['assignedTo','createdBy'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->orderByDesc('created_at');

        if ($status = $request->status) $query->where('status', $status);
        if ($priority = $request->priority) $query->where('priority', $priority);

        $tasks = $query->paginate(20);

        $stats = [
            'total'       => Task::where('business_id', $bizId)->count(),
            'todo'        => Task::where('business_id', $bizId)->where('status','todo')->count(),
            'in_progress' => Task::where('business_id', $bizId)->where('status','in_progress')->count(),
            'done'        => Task::where('business_id', $bizId)->where('status','done')->count(),
        ];

        $users = User::where('business_id', $bizId)->orderBy('name')->get();

        return view('tasks.index', compact('tasks','stats','users'));
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required|string|max:255']);

        Task::create([
            'business_id' => auth()->user()->business_id,
            'created_by'  => auth()->id(),
            'assigned_to' => $request->assigned_to ?: null,
            'title'       => $request->title,
            'description' => $request->description,
            'priority'    => $request->priority ?? 'medium',
            'status'      => 'todo',
            'due_date'    => $request->due_date ?: null,
        ]);

        return back()->with('success', 'Task created!');
    }

    public function update(Request $request, Task $task)
    {
        abort_if($task->business_id !== auth()->user()->business_id, 403);

        $task->update([
            'title'       => $request->title ?? $task->title,
            'description' => $request->description,
            'priority'    => $request->priority ?? $task->priority,
            'status'      => $request->status ?? $task->status,
            'due_date'    => $request->due_date ?: null,
            'assigned_to' => $request->assigned_to ?: null,
        ]);

        return back()->with('success', 'Task updated!');
    }

    public function destroy(Task $task)
    {
        abort_if($task->business_id !== auth()->user()->business_id, 403);
        $task->delete();
        return back()->with('success', 'Task deleted!');
    }
}
