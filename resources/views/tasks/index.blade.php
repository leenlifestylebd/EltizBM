@extends('layouts.app')
@section('title', 'Tasks')

@section('content')

<div class="flex items-center justify-between mb-4" x-data>
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Tasks</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $tasks->total() }} total tasks</p>
    </div>
    <button @click="$dispatch('open-task-modal')"
            class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Task
    </button>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    <a href="{{ route('tasks.index') }}"
       class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-gray-100 dark:border-slate-700 text-center hover:shadow-sm transition-all">
        <p class="text-xl font-bold text-gray-700 dark:text-white">{{ $stats['total'] }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Total</p>
    </a>
    <a href="{{ route('tasks.index', ['status'=>'todo']) }}"
       class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-yellow-100 dark:border-yellow-900/30 text-center hover:shadow-sm transition-all">
        <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['todo'] }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Pending</p>
    </a>
    <a href="{{ route('tasks.index', ['status'=>'in_progress']) }}"
       class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-blue-100 dark:border-blue-900/30 text-center hover:shadow-sm transition-all">
        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['in_progress'] }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">In Progress</p>
    </a>
    <a href="{{ route('tasks.index', ['status'=>'done']) }}"
       class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-green-100 dark:border-green-900/30 text-center hover:shadow-sm transition-all">
        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $stats['done'] }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Done</p>
    </a>
</div>

{{-- Status Filter --}}
<div class="flex gap-1.5 mb-3 overflow-x-auto pb-1">
    @foreach([''=>'All','todo'=>'Pending','in_progress'=>'In Progress','done'=>'Done','cancelled'=>'Cancelled'] as $val => $label)
    <a href="{{ route('tasks.index', ['status'=>$val, 'priority'=>request('priority')]) }}"
       class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap
              {{ request('status','') === $val ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- Priority Filter --}}
<div class="flex gap-1.5 mb-4 overflow-x-auto pb-1">
    @foreach([''=>'All Priority','urgent'=>'🔴 Urgent','high'=>'🟠 High','medium'=>'🟡 Medium','low'=>'🟢 Low'] as $val => $label)
    <a href="{{ route('tasks.index', ['priority'=>$val, 'status'=>request('status')]) }}"
       class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap
              {{ request('priority','') === $val ? 'bg-purple-600 text-white' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- Task List --}}
<div class="space-y-2">
    @forelse($tasks as $task)
    @php
        $priorityColor = match($task->priority) {
            'urgent' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'high'   => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            'medium' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            'low'    => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            default  => 'bg-gray-100 text-gray-600',
        };
        $statusColor = match($task->status) {
            'todo'        => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300',
            'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'done'        => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'cancelled'   => 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
            default       => 'bg-gray-100 text-gray-600',
        };
        $priorityIcon  = ['urgent'=>'🔴','high'=>'🟠','medium'=>'🟡','low'=>'🟢'][$task->priority] ?? '';
        $statusLabel   = ['todo'=>'Pending','in_progress'=>'In Progress','done'=>'Done','cancelled'=>'Cancelled'][$task->status] ?? ucfirst($task->status);
        $isOverdue     = $task->due_date && $task->due_date->isPast() && $task->status !== 'done';
    @endphp

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 hover:shadow-sm transition-all group"
         x-data="{ editing: false }">

        {{-- View Mode --}}
        <div x-show="!editing" class="flex items-start gap-3">

            {{-- Done Toggle --}}
            <form method="POST" action="{{ route('tasks.update', $task) }}" class="mt-0.5 flex-shrink-0">
                @csrf @method('PUT')
                <input type="hidden" name="status" value="{{ $task->status === 'done' ? 'todo' : 'done' }}">
                <input type="hidden" name="title" value="{{ $task->title }}">
                <input type="hidden" name="priority" value="{{ $task->priority }}">
                <button type="submit"
                        class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all
                               {{ $task->status === 'done' ? 'bg-green-500 border-green-500' : 'border-gray-300 dark:border-slate-500 hover:border-green-400' }}">
                    @if($task->status === 'done')
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    @endif
                </button>
            </form>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2 flex-wrap">
                    <p class="text-sm font-medium {{ $task->status === 'done' ? 'line-through text-gray-400 dark:text-slate-500' : 'text-gray-900 dark:text-white' }}">
                        {{ $task->title }}
                    </p>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $priorityColor }}">
                            {{ $priorityIcon }} {{ ucfirst($task->priority) }}
                        </span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                @if($task->description)
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">{{ $task->description }}</p>
                @endif

                <div class="flex items-center gap-3 mt-2 flex-wrap">
                    @if($task->due_date)
                        <span class="flex items-center gap-1 text-xs {{ $isOverdue ? 'text-red-500' : 'text-gray-400 dark:text-slate-500' }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $task->due_date->format('d M Y') }}
                            @if($isOverdue) <span class="font-medium">(Overdue!)</span> @endif
                        </span>
                    @endif
                    @if($task->assignedTo)
                        <span class="flex items-center gap-1 text-xs text-gray-400 dark:text-slate-500">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ $task->assignedTo->name }}
                        </span>
                    @endif
                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ $task->created_at->diffForHumans() }}</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                <button @click="editing = true"
                        class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
        </div>

        {{-- Edit Mode --}}
        <div x-show="editing" x-cloak>
            <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-3">
                @csrf @method('PUT')
                <input type="text" name="title" value="{{ $task->title }}" required
                       class="w-full px-3 py-2 text-sm border border-blue-300 dark:border-blue-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <textarea name="description" rows="2"
                          class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                          placeholder="Description...">{{ $task->description }}</textarea>
                <div class="grid grid-cols-3 gap-2">
                    <select name="priority" class="px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                        @foreach(['urgent'=>'🔴 Urgent','high'=>'🟠 High','medium'=>'🟡 Medium','low'=>'🟢 Low'] as $v => $l)
                            <option value="{{ $v }}" {{ $task->priority === $v ? 'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                        @foreach(['todo'=>'Pending','in_progress'=>'In Progress','done'=>'Done','cancelled'=>'Cancelled'] as $v => $l)
                            <option value="{{ $v }}" {{ $task->status === $v ? 'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="due_date" value="{{ $task->due_date?->format('Y-m-d') }}"
                           class="px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">Save</button>
                    <button type="button" @click="editing = false" class="px-4 py-2 bg-gray-200 dark:bg-slate-600 text-gray-700 dark:text-slate-200 text-sm rounded-lg hover:bg-gray-300 transition-colors">Cancel</button>
                </div>
            </form>
        </div>

    </div>
    @empty
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-16 text-center">
        <svg class="w-12 h-12 text-gray-200 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        <p class="text-sm text-gray-400 dark:text-slate-500">No tasks found</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Click "New Task" to create one</p>
    </div>
    @endforelse
</div>

@if($tasks->hasPages())
<div class="mt-4">{{ $tasks->withQueryString()->links() }}</div>
@endif

{{-- New Task Modal --}}
<div x-data="{ open: false }" @open-task-modal.window="open = true">
    <div x-show="open" x-cloak
         class="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-4">
        <div @click.outside="open = false"
             class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">New Task</h3>
                <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>
            <form method="POST" action="{{ route('tasks.store') }}" class="p-5 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="What needs to be done?">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Description</label>
                    <textarea name="description" rows="2"
                              class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                              placeholder="Optional details..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Priority</label>
                        <select name="priority" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="medium" selected>🟡 Medium</option>
                            <option value="urgent">🔴 Urgent</option>
                            <option value="high">🟠 High</option>
                            <option value="low">🟢 Low</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Due Date</label>
                        <input type="date" name="due_date"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Assign To</label>
                    <select name="assigned_to" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Unassigned</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                    Create Task
                </button>
            </form>
        </div>
    </div>
</div>

@endsection