@extends('layouts.app')
@section('title', isset($employee) ? 'Edit Employee' : 'Add Employee')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('employees.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
            {{ isset($employee) ? 'Edit ' . $employee->name : 'Add New Employee' }}
        </h2>
    </div>

    <form method="POST"
          action="{{ isset($employee) ? route('employees.update', $employee) : route('employees.store') }}">
        @csrf
        @if(isset($employee)) @method('PUT') @endif

        <div class="space-y-4">

            {{-- Personal Info --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Personal Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', isset($employee) ? $employee->name : '') }}" required
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                               placeholder="Employee full name">
                        @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', isset($employee) ? $employee->phone : '') }}"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                               placeholder="01XXXXXXXXX">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email', isset($employee) ? $employee->email : '') }}"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                               placeholder="email@example.com">
                    </div>
                </div>
            </div>

            {{-- Job Info --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4"
                 x-data="{ empType: '{{ old('type', isset($employee) ? $employee->type : 'salary') }}' }">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Job Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Designation</label>
                        <input type="text" name="designation" value="{{ old('designation', isset($employee) ? $employee->designation : '') }}"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                               placeholder="e.g. Production Worker, Supervisor">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Department</label>
                        <input type="text" name="department" value="{{ old('department', isset($employee) ? $employee->department : '') }}"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                               placeholder="e.g. Production, Packing, Sales">
                    </div>
                    <div x-show="empType === 'salary' || empType === 'both'" x-cloak>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Monthly Salary (৳)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">৳</span>
                            <input type="number" name="salary" :disabled="empType !== 'salary' && empType !== 'both'"
                                   value="{{ old('salary', isset($employee) ? $employee->salaryForMonth(now()) : '') }}" min="0" step="0.01"
                                   class="w-full pl-7 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                   placeholder="0.00">
                        </div>
                        <p x-show="empType === 'both'" class="text-xs text-purple-600 dark:text-purple-400 mt-1">Base salary — the worker also earns piece-rate for production on top.</p>
                    </div>
                    @if(isset($employee))
                        <div class="mt-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Salary effective from</label>
                            <input type="month" name="salary_effective_from" value="{{ old('salary_effective_from', now()->format('Y-m')) }}"
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">This month applies to salary changes and switching to production-only pay. Earlier salary records remain unchanged.</p>
                        </div>
                    @endif
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Joining Date</label>
                        <input type="date" name="joining_date" value="{{ old('joining_date', isset($employee) ? $employee->joining_date?->format('Y-m-d') : '') }}"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Employee Type</label>
                        <select name="type" x-model="empType" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            @php $currentType = old('type', isset($employee) ? $employee->type : 'salary'); @endphp
                            <option value="salary"     {{ $currentType === 'salary'     ? 'selected':'' }}>💰 Fixed Salary</option>
                            <option value="production" {{ $currentType === 'production' ? 'selected':'' }}>🏭 Production Based</option>
                            <option value="both"       {{ $currentType === 'both'       ? 'selected':'' }}>💰+✂️ Salary + Production</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Status</label>
                        <select name="status" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            @php $currentStatus = old('status', isset($employee) ? $employee->status : 'active'); @endphp
                            <option value="active"   {{ $currentStatus === 'active'   ? 'selected':'' }}>Active</option>
                            <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected':'' }}>Inactive</option>
                            <option value="resigned" {{ $currentStatus === 'resigned' ? 'selected':'' }}>Resigned</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Link User Account</label>
                        <select name="user_id" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">No linked account</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id', isset($employee) ? $employee->user_id : '') == $user->id ? 'selected':'' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 rounded-lg text-sm transition-colors">
                    {{ isset($employee) ? 'Update Employee' : 'Add Employee' }}
                </button>
                <a href="{{ route('employees.index') }}"
                   class="px-4 py-2.5 border border-gray-200 dark:border-slate-600 text-sm text-gray-600 dark:text-slate-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>

@endsection