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

<div class="max-w-2xl mx-auto space-y-4" x-data="{ editOpen: false, submitOpen: false, forwardOpen: {{ request()->is('*#forward') || session('openForward') ? 'true' : 'false' }} }">

    {{-- Task Header Card --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
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
            <div class="flex items-center gap-2 flex-wrap justify-end">
                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $stageBadge }}">
                    {{ ucfirst($task->stage ?? 'sewing') }}
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                    {{ ucfirst(str_replace('_',' ',$task->status)) }}
                </span>
            </div>
        </div>

        {{-- Product info --}}
        <div class="flex flex-wrap gap-2 mb-4">
            <span class="px-2.5 py-1 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-lg text-xs font-medium">
                {{ $task->product_name }}
            </span>
            @if($task->color)
            <span class="px-2.5 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 rounded-lg text-xs font-medium">
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
                <div class="bg-primary-500 h-2.5 rounded-full transition-all" style="width: {{ $task->completion_percent }}%"></div>
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

        {{-- Source task link --}}
        @if($task->source_task_id && $task->sourceTask)
        <div class="mt-3 p-2.5 bg-orange-50 dark:bg-orange-900/20 rounded-lg flex items-center gap-2">
            <span class="text-orange-500">🔥</span>
            <p class="text-xs text-orange-700 dark:text-orange-300">
                Forwarded from <a href="{{ route('production.tasks.show', $task->source_task_id) }}" class="font-semibold underline">Sewing Task #{{ $task->source_task_id }}</a>
                ({{ $task->sourceTask->employee->name }})
            </p>
        </div>
        @endif

        {{-- Record completed work (admin/owner submits on the worker's behalf) --}}
        @if(in_array(auth()->user()->role, ['admin','owner']) && !in_array($task->status, ['completed','cancelled']) && $task->pending_quantity > 0)
        @php $assignedRate = $task->productionRate?->rate ?? 0; @endphp
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-700">
            <button @click="submitOpen = !submitOpen" type="button"
                    class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-green-700 dark:text-green-400 border border-green-300 dark:border-green-800 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20">
                ✅ Record Completed Work
                <span class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 px-1.5 py-0.5 rounded-full text-xs font-bold">{{ $task->pending_quantity }} pending</span>
            </button>

            <form x-show="submitOpen" x-cloak method="POST" action="{{ route('production.tasks.submit', $task) }}"
                  class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2 items-end">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Completed pieces <span class="text-red-500">*</span></label>
                    <input type="number" name="submitted_quantity" min="1" max="{{ $task->pending_quantity }}" value="{{ $task->pending_quantity }}" required
                           class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Rate (৳/pc)</label>
                    <input type="number" name="rate_used" min="0" step="0.01" placeholder="{{ $assignedRate ?: 'optional' }}"
                           class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Quality note</label>
                    <input type="text" name="quality_note"
                           class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                </div>
                <div class="flex gap-1.5">
                    <button type="submit" class="flex-1 px-3 py-1.5 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700">Save</button>
                    <button type="button" @click="submitOpen = false" class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-lg">Cancel</button>
                </div>
                @if($assignedRate <= 0)
                <p class="col-span-full text-xs text-amber-600 dark:text-amber-400">No rate set for this task — earning will be ৳0 unless you enter a rate.</p>
                @endif
            </form>
        </div>
        @endif

        {{-- Forward to next stage button --}}
        @if($nextStage && $task->forwardable_quantity > 0)
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-700" id="forward">
            <button @click="forwardOpen = !forwardOpen"
                    class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-orange-600 border border-orange-300 rounded-lg hover:bg-orange-50 dark:hover:bg-orange-900/20">
                → Forward to {{ $nextStageLabel }}
                <span class="bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 px-1.5 py-0.5 rounded-full text-xs font-bold">{{ $task->forwardable_quantity }} pcs</span>
            </button>
        </div>
        @elseif($nextStage && $task->completed_quantity > 0)
        <div class="mt-3">
            <p class="text-xs text-gray-400">✓ All {{ $task->completed_quantity }} completed pieces already forwarded to {{ strtolower($nextStageLabel) }}.</p>
        </div>
        @endif

        {{-- Edit toggle --}}
        @if(!in_array($task->status, ['completed','cancelled']))
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-700">
            <button @click="editOpen = !editOpen"
                    class="text-xs text-primary-600 hover:underline">
                <span x-text="editOpen ? 'Hide Edit' : 'Edit Task'"></span>
            </button>
        </div>
        @endif
    </div>

    {{-- Edit Form (Alpine toggle) --}}
    <div x-show="editOpen" x-cloak class="bg-white dark:bg-slate-800 rounded-xl border border-primary-200 dark:border-primary-800 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Edit Task</h3>
        <form method="POST" action="{{ route('production.tasks.update', $task) }}" class="space-y-3">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Assigned Qty</label>
                    <input type="number" name="assigned_quantity" value="{{ $task->assigned_quantity }}" min="{{ $task->completed_quantity ?: 1 }}"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
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
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Assign Date</label>
                    <input type="date" name="assigned_date" value="{{ optional($task->assigned_at)->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Deadline</label>
                    <input type="date" name="deadline" value="{{ $task->deadline?->toDateString() }}"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">{{ $task->notes }}</textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-primary-600 text-white text-sm font-medium py-2 rounded-lg hover:bg-primary-700">Save Changes</button>
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
            @php
                $assignedRate = $task->productionRate?->rate ?? 0;
                $rateChanged  = $sub->rate_used !== null && $assignedRate > 0 && (float)$sub->rate_used !== (float)$assignedRate;
            @endphp
            <div class="px-4 py-3" x-data="{ editing: false }">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $sub->submitted_quantity }} pieces</p>
                            @if($rateChanged)
                            <span class="text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-1.5 py-0.5 rounded">
                                Rate override
                            </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $sub->submitted_at->format('d M Y, D') }}</p>
                        @if($sub->rate_used !== null)
                        <p class="text-xs text-gray-500 mt-0.5">
                            ৳{{ number_format($sub->rate_used, 2) }}/piece
                            @if($rateChanged)
                            <span class="text-amber-600">(assigned: ৳{{ number_format($assignedRate, 2) }})</span>
                            @endif
                        </p>
                        @endif
                        @if($sub->quality_note)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $sub->quality_note }}</p>
                        @endif
                        @if($sub->notes)
                        <p class="text-xs text-gray-400 mt-0.5 italic">{{ $sub->notes }}</p>
                        @endif
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-bold text-green-600">+{{ $sub->submitted_quantity }} pcs</p>
                        @if($sub->amount !== null && $sub->amount > 0)
                        <p class="text-xs font-semibold text-green-600 dark:text-green-400">৳{{ number_format($sub->amount, 2) }}</p>
                        @endif
                        @if(in_array(auth()->user()->role, ['admin','owner']))
                        <div class="flex items-center gap-1 justify-end mt-1.5">
                            <button type="button" @click="editing = !editing" class="text-xs text-primary-600 hover:underline">Edit</button>
                            <span class="text-gray-300">·</span>
                            <form method="POST" action="{{ route('production.tasks.submission.delete', $sub) }}"
                                  onsubmit="return confirm('Delete this submission and reverse its payment?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:underline">Delete</button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>

                @if(in_array(auth()->user()->role, ['admin','owner']))
                {{-- Admin inline edit --}}
                <form x-show="editing" x-cloak method="POST" action="{{ route('production.tasks.submission.update', $sub) }}"
                      class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-700 grid grid-cols-2 sm:grid-cols-4 gap-2 items-end">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Quantity</label>
                        <input type="number" name="submitted_quantity" value="{{ $sub->submitted_quantity }}" min="1"
                               class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Rate (৳)</label>
                        <input type="number" name="rate_used" value="{{ $sub->rate_used }}" min="0" step="0.01" placeholder="{{ $assignedRate }}"
                               class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Quality note</label>
                        <input type="text" name="quality_note" value="{{ $sub->quality_note }}"
                               class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                    </div>
                    <div class="flex gap-1.5">
                        <button type="submit" class="flex-1 px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg hover:bg-primary-700">Save</button>
                        <button type="button" @click="editing = false" class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-lg">Cancel</button>
                    </div>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Forward to next-stage Modal --}}
    @if($nextStage && $nextEmployees->isNotEmpty())
    <div x-show="forwardOpen" x-cloak
         class="bg-white dark:bg-slate-800 rounded-xl border border-orange-200 dark:border-orange-800 p-5">
        <h3 class="text-sm font-semibold text-orange-700 dark:text-orange-400 mb-4 flex items-center gap-1.5">
            → Assign {{ $nextStageLabel }} Task
            <span class="text-xs font-normal text-gray-500">(forwardable: {{ $task->forwardable_quantity }} pcs)</span>
        </h3>
        <form method="POST" action="{{ route('production.tasks.forward', $task) }}" class="space-y-3">
            @csrf

            {{-- Next-stage Worker --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">{{ $nextStageLabel }} Worker <span class="text-red-500">*</span></label>
                <select name="employee_id" required
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-400">
                    <option value="">Select {{ $nextStageLabel }} Worker</option>
                    @foreach($nextEmployees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }}{{ $emp->designation ? ' — '.$emp->designation : '' }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Next-stage Rate --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">
                    {{ $nextStageLabel }} Rate <span class="text-gray-400">(optional)</span>
                </label>
                <select name="production_rate_id"
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-400">
                    <option value="">-- Rate ছাড়াই assign করুন --</option>
                    @foreach($nextRates as $rate)
                    <option value="{{ $rate->id }}">
                        {{ $rate->product_name }}{{ $rate->variant_name ? ' — '.$rate->variant_name : '' }}{{ $rate->color ? ' ('.$rate->color.')' : '' }} — ৳{{ number_format($rate->rate) }}/{{ $rate->unit }}
                    </option>
                    @endforeach
                </select>
                @if($nextRates->isEmpty())
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                    {{ $nextStageLabel }} rate নেই — <a href="{{ route('production.rates') }}" class="underline">এখানে যোগ করুন</a>।
                    Rate ছাড়াও assign করা যাবে।
                </p>
                @endif
            </div>

            {{-- Quantity --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">
                    Pieces to forward <span class="text-red-500">*</span>
                    <span class="text-gray-400">(max {{ $task->forwardable_quantity }})</span>
                </label>
                <input type="number" name="quantity"
                       value="{{ $task->forwardable_quantity }}"
                       min="1" max="{{ $task->forwardable_quantity }}" required
                       class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-400">
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Notes <span class="text-gray-400">(optional)</span></label>
                <input type="text" name="notes" placeholder="Any instructions for iron man..."
                       class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-400">
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                    → Assign {{ $nextStageLabel }} Task
                </button>
                <button type="button" @click="forwardOpen = false"
                        class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 text-sm rounded-lg">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Forwarded (next-stage) Tasks --}}
    @if($nextStage && $task->forwardedTasks->isNotEmpty())
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                → {{ $nextStageLabel }} Tasks
            </h3>
            <span class="text-xs text-gray-500">{{ $task->forwardedTasks->count() }} task(s)</span>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @foreach($task->forwardedTasks as $ironTask)
            @php
                $ironStatus = match($ironTask->status) {
                    'completed' => 'bg-green-100 text-green-700',
                    'partial'   => 'bg-yellow-100 text-yellow-700',
                    'cancelled' => 'bg-red-100 text-red-700',
                    default     => 'bg-gray-100 text-gray-600',
                };
            @endphp
            <div class="px-4 py-3 flex items-center justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $ironTask->employee->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $ironTask->completed_quantity }}/{{ $ironTask->assigned_quantity }} pcs
                        · {{ $ironTask->assigned_at->format('d M') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $ironStatus }}">
                        {{ ucfirst(str_replace('_',' ',$ironTask->status)) }}
                    </span>
                    <a href="{{ route('production.tasks.show', $ironTask) }}"
                       class="text-xs text-primary-600 hover:underline">View</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

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
