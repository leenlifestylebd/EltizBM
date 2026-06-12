@extends('layouts.app')
@section('title', 'Task Assignment')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Task Assignment</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Assign and track worker tasks</p>
    </div>
    <a href="{{ route('production.tasks.create') }}"
       class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
        + Assign Task
    </a>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Total Tasks</p>
        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-primary-100 dark:border-primary-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Assigned</p>
        <p class="text-xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['assigned'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-yellow-100 dark:border-yellow-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Partial</p>
        <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['partial'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-green-100 dark:border-green-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Completed</p>
        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed'] }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Worker</label>
            <select name="employee_id" onchange="this.form.submit()"
                    class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                <option value="">All Workers</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Stage</label>
            <select name="stage" onchange="this.form.submit()"
                    class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                <option value="">All Stages</option>
                <option value="sewing"  {{ request('stage') === 'sewing'  ? 'selected' : '' }}>Sewing</option>
                <option value="ironing" {{ request('stage') === 'ironing' ? 'selected' : '' }}>Ironing</option>
                <option value="folding" {{ request('stage') === 'folding' ? 'selected' : '' }}>Folding</option>
                <option value="packing" {{ request('stage') === 'packing' ? 'selected' : '' }}>Packing</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" onchange="this.form.submit()"
                    class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                <option value="">All Status</option>
                @foreach(['assigned','in_progress','partial','completed','cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
        </div>
        <button type="submit" class="px-3 py-2 bg-primary-600 text-white text-xs rounded-lg hover:bg-primary-700">Filter</button>
        @if(request()->hasAny(['employee_id','status','from','to']))
        <a href="{{ route('production.tasks.index') }}" class="px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-xs rounded-lg hover:bg-gray-200">Clear</a>
        @endif
    </form>
</div>

{{-- Tasks Table (Desktop) --}}
<div class="hidden md:block bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden mb-4">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Worker</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Stage</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Product</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Assigned</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Done</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Pending</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Progress</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Status</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Deadline</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($tasks as $task)
                @php
                    $pct = $task->completion_percent;
                    $statusColor = match($task->status) {
                        'completed'   => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                        'partial'     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                        'in_progress' => 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400',
                        'cancelled'   => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                        default       => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300',
                    };
                    $stageBadge = match($task->stage ?? 'sewing') {
                        'ironing' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                        'folding' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                        'packing' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                        default   => 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400',
                    };
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $task->employee->name }}</p>
                        <p class="text-xs text-gray-400">{{ $task->assigned_at->format('d M') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $stageBadge }}">
                            {{ ucfirst($task->stage ?? 'sewing') }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm text-gray-900 dark:text-white">{{ $task->product_name }}</p>
                        @if($task->color || $task->size)
                        <p class="text-xs text-gray-400">{{ implode(' · ', array_filter([$task->color, $task->size])) }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">{{ $task->assigned_quantity }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-green-600">{{ $task->completed_quantity }}</td>
                    <td class="px-4 py-3 text-center font-semibold {{ $task->pending_quantity > 0 ? 'text-orange-600' : 'text-gray-400' }}">{{ $task->pending_quantity }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-100 dark:bg-slate-700 rounded-full h-1.5 min-w-16">
                                <div class="bg-primary-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 w-8">{{ $pct }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                            {{ ucfirst(str_replace('_',' ',$task->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-xs {{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                        {{ $task->deadline ? $task->deadline->format('d M') : '—' }}
                        @if($task->isOverdue()) <span class="block text-red-500">Overdue</span> @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                            <a href="{{ route('production.tasks.show', $task) }}"
                               class="px-2.5 py-1 text-xs text-primary-600 border border-primary-200 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20">
                                View
                            </a>
                            @if(($task->stage ?? 'sewing') === 'sewing' && $task->forwardable_quantity > 0)
                            <a href="{{ route('production.tasks.show', $task) }}#forward"
                               class="px-2.5 py-1 text-xs text-orange-600 border border-orange-200 rounded-lg hover:bg-orange-50 dark:hover:bg-orange-900/20">
                                🔥 Iron
                            </a>
                            @endif
                            <form method="POST" action="{{ route('production.tasks.destroy', $task) }}"
                                  onsubmit="return confirm('এই task টি delete করবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 text-xs text-red-600 border border-red-200 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="px-4 py-12 text-center text-sm text-gray-400">No tasks found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Tasks Cards (Mobile) --}}
<div class="md:hidden space-y-2 mb-4">
    @forelse($tasks as $task)
    @php
        $pct = $task->completion_percent;
        $statusColor = match($task->status) {
            'completed'   => 'bg-green-100 text-green-700',
            'partial'     => 'bg-yellow-100 text-yellow-700',
            'in_progress' => 'bg-primary-100 text-primary-700',
            'cancelled'   => 'bg-red-100 text-red-700',
            default       => 'bg-gray-100 text-gray-600',
        };
        $stageBadgeMobile = match($task->stage ?? 'sewing') {
            'ironing' => 'bg-orange-100 text-orange-700',
            'folding' => 'bg-purple-100 text-purple-700',
            'packing' => 'bg-teal-100 text-teal-700',
            default   => 'bg-primary-100 text-primary-700',
        };
    @endphp
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3">
        <div class="flex items-start justify-between mb-2">
            <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $task->employee->name }}</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="px-1.5 py-0.5 rounded text-xs font-medium {{ $stageBadgeMobile }}">{{ ucfirst($task->stage ?? 'sewing') }}</span>
                    <span class="text-xs text-gray-500">{{ $task->product_name }}{{ $task->color ? ' · '.$task->color : '' }}{{ $task->size ? ' · '.$task->size : '' }}</span>
                </div>
            </div>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                {{ ucfirst(str_replace('_',' ',$task->status)) }}
            </span>
        </div>
        <div class="mb-2">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>{{ $task->completed_quantity }}/{{ $task->assigned_quantity }} pcs</span>
                <span>{{ $pct }}%</span>
            </div>
            <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-1.5">
                <div class="bg-primary-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
            </div>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">Pending: <strong class="{{ $task->pending_quantity > 0 ? 'text-orange-600' : 'text-green-600' }}">{{ $task->pending_quantity }}</strong></span>
            <div class="flex gap-1.5 flex-wrap justify-end">
                @if(($task->stage ?? 'sewing') === 'sewing' && $task->forwardable_quantity > 0)
                <a href="{{ route('production.tasks.show', $task) }}#forward"
                   class="px-2.5 py-1 text-xs text-orange-600 border border-orange-200 rounded-lg">🔥 Iron</a>
                @endif
                <a href="{{ route('production.tasks.show', $task) }}"
                   class="px-2.5 py-1 text-xs text-primary-600 border border-primary-200 rounded-lg">View</a>
                <form method="POST" action="{{ route('production.tasks.destroy', $task) }}"
                      onsubmit="return confirm('Delete করবেন?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-2.5 py-1 text-xs text-red-600 border border-red-200 rounded-lg">Delete</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 py-12 text-center">
        <p class="text-sm text-gray-400">No tasks found</p>
    </div>
    @endforelse
</div>

{{ $tasks->links() }}

@endsection
