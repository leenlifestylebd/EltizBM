@extends('layouts.app')
@section('title', 'Task Detail')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Task Detail</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $task->employee->name }} — {{ $task->product_name }}</p>
    </div>
    <a href="{{ route('production.tasks.index') }}"
       class="px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50">
        Back
    </a>
</div>

@if(session('success'))
<div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
    <p class="text-xs text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif

<div class="max-w-2xl mx-auto space-y-4" x-data="{ editOpen: false }">

    {{-- Task Header Card --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                    {{ strtoupper(substr($task->employee->name, 0, 2)) }}
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $task->employee->name }}</p>
                    <p class="text-xs text-gray-400">{{ $task->employee->designation ?? 'Worker' }}</p>
                </div>
            </div>
            @php
                $statusColor = match($task->status) {
                    'completed'   => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    'partial'     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    'cancelled'   => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    default       => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300',
                };
            @endphp
            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                {{ ucfirst(str_replace('_',' ',$task->status)) }}
            </span>
        </div>

        {{-- Product info --}}
        <div class="flex flex-wrap gap-2 mb-4">
            <span class="px-2.5 py-1 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-lg text-xs font-medium">
                {{ $task->product_name }}
            </span>
            @if($task->color)
            <span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-medium">
                {{ $task->color }}
            </span>
            @endif
            @if($task->size)
            <span class="px-2.5 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded-lg text-xs font-medium">
                Size: {{ $task->size }}
            </span>
            @endif
        </div>

        {{-- Progress bar --}}
        <div class="mb-4">
            <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                <span>{{ $task->completed_quantity }} / {{ $task->assigned_quantity }} pieces completed</span>
                <span class="font-semibold text-gray-700 dark:text-slate-200">{{ $task->completion_percent }}%</span>
            </div>
            <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-2.5">
                <div class="bg-blue-500 h-2.5 rounded-full transition-all" style="width: {{ $task->completion_percent }}%"></div>
            </div>
        </div>

        {{-- Stats row --}}
        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="text-center p-2 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-xs text-gray-500 mb-0.5">Assigned</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $task->assigned_quantity }}</p>
            </div>
            <div class="text-center p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <p class="text-xs text-gray-500 mb-0.5">Completed</p>
                <p class="text-lg font-bold text-green-600">{{ $task->completed_quantity }}</p>
            </div>
            <div class="text-center p-2 {{ $task->pending_quantity > 0 ? 'bg-orange-50 dark:bg-orange-900/20' : 'bg-gray-50 dark:bg-slate-700/50' }} rounded-lg">
                <p class="text-xs text-gray-500 mb-0.5">Pending</p>
                <p class="text-lg font-bold {{ $task->pending_quantity > 0 ? 'text-orange-600' : 'text-gray-400' }}">{{ $task->pending_quantity }}</p>
            </div>
        </div>

        {{-- Meta info --}}
        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
            <span>Assigned by: <strong class="text-gray-700 dark:text-slate-300">{{ $task->assignedBy->name ?? 'Admin' }}</strong></span>
            <span>Date: <strong class="text-gray-700 dark:text-slate-300">{{ $task->assigned_at->format('d M Y') }}</strong></span>
            @if($task->deadline)
            <span class="{{ $task->isOverdue() ? 'text-red-600 font-semibold' : '' }}">
                Deadline: <strong>{{ $task->deadline->format('d M Y') }}</strong>
                @if($task->isOverdue()) (Overdue) @endif
            </span>
            @endif
        </div>

        @if($task->notes)
        <div class="mt-3 p-2.5 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
            <p class="text-xs text-gray-600 dark:text-slate-300">{{ $task->notes }}</p>
        </div>
        @endif

        {{-- Edit toggle --}}
        @if(!in_array($task->status, ['completed','cancelled']))
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-700">
            <button @click="editOpen = !editOpen"
                    class="text-xs text-blue-600 hover:underline">
                <span x-text="editOpen ? 'Hide Edit' : 'Edit Task'"></span>
            </button>
        </div>
        @endif
    </div>

    {{-- Edit Form (Alpine toggle) --}}
    <div x-show="editOpen" x-cloak class="bg-white dark:bg-slate-800 rounded-xl border border-blue-200 dark:border-blue-800 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Edit Task</h3>
        <form method="POST" action="{{ route('production.tasks.update', $task) }}" class="space-y-3">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Assigned Qty</label>
                    <input type="number" name="assigned_quantity" value="{{ $task->assigned_quantity }}" min="{{ $task->completed_quantity ?: 1 }}"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                        @foreach(['assigned','in_progress','partial','completed','cancelled'] as $s)
                        <option value="{{ $s }}" {{ $task->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Deadline</label>
                <input type="date" name="deadline" value="{{ $task->deadline?->toDateString() }}"
                       class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">{{ $task->notes }}</textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white text-sm font-medium py-2 rounded-lg hover:bg-blue-700">Save Changes</button>
                <button type="button" @click="editOpen = false" class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 text-sm rounded-lg">Cancel</button>
            </div>
        </form>
    </div>

    {{-- Submission History --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Submission History</h3>
            <span class="text-xs text-gray-500">{{ $task->submissions->count() }} submission(s)</span>
        </div>
        @if($task->submissions->isEmpty())
        <div class="px-4 py-10 text-center">
            <p class="text-sm text-gray-400">No submissions yet</p>
        </div>
        @else
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @foreach($task->submissions as $sub)
            <div class="px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $sub->submitted_quantity }} pieces</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $sub->submitted_at->format('d M Y, D') }}</p>
                    @if($sub->quality_note)
                    <p class="text-xs text-gray-500 mt-0.5">{{ $sub->quality_note }}</p>
                    @endif
                    @if($sub->notes)
                    <p class="text-xs text-gray-400 mt-0.5 italic">{{ $sub->notes }}</p>
                    @endif
                </div>
                <span class="text-sm font-bold text-green-600">+{{ $sub->submitted_quantity }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Cancel Task --}}
    @if(!in_array($task->status, ['completed','cancelled']))
    <div class="text-center">
        <form method="POST" action="{{ route('production.tasks.destroy', $task) }}"
              onsubmit="return confirm('Cancel this task?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs text-red-500 hover:underline">Cancel Task</button>
        </form>
    </div>
    @endif

</div>

@endsection
