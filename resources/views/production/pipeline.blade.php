@extends('layouts.app')
@section('title', 'Production Pipeline')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">🏭 Production Pipeline</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Cutting → Sewing → Iron — last 90 days</p>
    </div>
    <a href="{{ route('production.tasks.create') }}"
       class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Assign Task
    </a>
</div>

{{-- Stage columns --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    @php
        $stageMeta = [
            'cutting' => ['emoji' => '✂️', 'ring' => 'border-blue-200 dark:border-blue-900/40',   'head' => 'text-blue-700 dark:text-blue-400'],
            'sewing'  => ['emoji' => '🧵', 'ring' => 'border-primary-200 dark:border-primary-900/40','head' => 'text-primary-700 dark:text-primary-400'],
            'ironing' => ['emoji' => '🔥', 'ring' => 'border-orange-200 dark:border-orange-900/40', 'head' => 'text-orange-700 dark:text-orange-400'],
        ];
    @endphp

    @foreach($columns as $stage => $tasks)
    @php $meta = $stageMeta[$stage] ?? ['emoji' => '•', 'ring' => 'border-gray-200', 'head' => 'text-gray-700']; @endphp
    <div class="bg-gray-50 dark:bg-slate-900/40 rounded-xl border {{ $meta['ring'] }} overflow-hidden">
        {{-- Column header --}}
        <div class="px-3 py-2.5 bg-white dark:bg-slate-800 border-b {{ $meta['ring'] }} flex items-center justify-between sticky top-0">
            <h3 class="text-sm font-semibold {{ $meta['head'] }} flex items-center gap-1.5">
                <span>{{ $meta['emoji'] }}</span>
                {{ $stageLabels[$stage] ?? ucfirst($stage) }}
                <span class="text-gray-400 dark:text-slate-500 font-normal">{{ $stageLabelsBn[$stage] ?? '' }}</span>
            </h3>
            <span class="text-xs font-medium text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded-full">{{ $tasks->count() }}</span>
        </div>

        {{-- Cards --}}
        <div class="p-2 space-y-2 max-h-[70vh] overflow-y-auto">
            @forelse($tasks as $task)
            @php
                $statusColor = match($task->status) {
                    'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    'partial'   => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    default     => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300',
                };
                $pct = $task->completion_percent;
            @endphp
            <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-100 dark:border-slate-700 p-3 {{ $task->isOverdue() ? 'ring-1 ring-red-300 dark:ring-red-800' : '' }}">
                <div class="flex items-start justify-between gap-2 mb-1.5">
                    <p class="text-sm font-medium text-gray-900 dark:text-white leading-tight">
                        {{ $task->product_name }}
                        @if($task->color || $task->size)
                            <span class="text-xs text-gray-400 dark:text-slate-500">{{ collect([$task->color, $task->size])->filter()->join(' · ') }}</span>
                        @endif
                    </p>
                    <span class="flex-shrink-0 px-1.5 py-0.5 rounded text-[10px] font-medium {{ $statusColor }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                </div>

                <p class="text-xs text-gray-500 dark:text-slate-400 mb-2">
                    👤 {{ $task->employee?->name ?? '—' }}
                    @if($task->isOverdue())<span class="text-red-500 font-medium">· overdue</span>@endif
                </p>

                {{-- Progress --}}
                <div class="mb-2">
                    <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-slate-400 mb-1">
                        <span>{{ $task->completed_quantity }}/{{ $task->assigned_quantity }} pcs</span>
                        <span>{{ $pct }}%</span>
                    </div>
                    <div class="h-1.5 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full {{ $pct >= 100 ? 'bg-green-500' : 'bg-primary-500' }} rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <a href="{{ route('production.tasks.show', $task) }}"
                       class="flex-1 text-center px-2 py-1 text-xs text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30">View</a>
                    @if($task->canForward())
                    <a href="{{ route('production.tasks.show', $task) }}#forward"
                       class="flex-1 text-center px-2 py-1 text-xs text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30">
                        → {{ $task->nextStageLabel() }} ({{ $task->forwardable_quantity }})
                    </a>
                    @endif
                </div>
            </div>
            @empty
            <div class="py-10 text-center text-xs text-gray-400 dark:text-slate-500">No tasks</div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>

@endsection
