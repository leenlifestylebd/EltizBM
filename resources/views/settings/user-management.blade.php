@extends('layouts.app')
@section('title', 'User Management')

@section('content')

<div class="max-w-3xl mx-auto">

    {{-- Tabs --}}
    <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl overflow-x-auto scrollbar-hide">
        @foreach([
            ['route'=>'settings.general',        'label'=>'Profile'],
            ['route'=>'settings.business',       'label'=>'Business'],
            ['route'=>'settings.user-management','label'=>'Users'],
            ['route'=>'settings.print-templates','label'=>'Print'],['route'=>'settings.account-categories','label'=>'Categories'],
        ] as $tab)
        <a href="{{ route($tab['route']) }}"
           class="flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap
                  {{ request()->routeIs($tab['route']) ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300' }}">
            {{ $tab['label'] }}
        </a>
        @endforeach
    </div>

    {{-- Pending Users --}}
    @if($pendingUsers->count() > 0)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-5 mb-4">
        <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-400 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Pending Approval ({{ $pendingUsers->count() }})
        </h3>
        <div class="space-y-2">
            @foreach($pendingUsers as $pending)
            <div class="flex items-center gap-3 bg-white dark:bg-slate-800 rounded-xl p-3 border border-yellow-100 dark:border-yellow-800">
                <div class="w-9 h-9 rounded-full bg-yellow-200 dark:bg-yellow-800 flex items-center justify-center text-yellow-700 dark:text-yellow-300 font-bold text-sm flex-shrink-0">
                    {{ strtoupper(substr($pending->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $pending->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $pending->email }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Registered {{ $pending->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <form method="POST" action="{{ route('settings.users.approve', $pending) }}" class="flex items-center gap-2">
                        @csrf
                        <select name="role"
                                class="px-2 py-1.5 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="staff"    {{ $pending->role === 'staff'    ? 'selected':'' }}>Staff</option>
                            <option value="manager"  {{ $pending->role === 'manager'  ? 'selected':'' }}>Manager</option>
                            <option value="admin"    {{ $pending->role === 'admin'    ? 'selected':'' }}>Admin</option>
                            <option value="employee" {{ $pending->role === 'employee' ? 'selected':'' }}>Employee</option>
                        </select>
                        <button type="submit"
                                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                            âœ… Approve
                        </button>
                    </form>
                    <form method="POST" action="{{ route('settings.users.reject', $pending) }}" onsubmit="return confirm('Reject {{ $pending->name }}?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="px-3 py-1.5 bg-red-100 hover:bg-red-600 text-red-600 hover:text-white text-xs font-medium rounded-lg transition-colors">
                            âœ•
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Add User Form --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 mb-4" x-data="{ open: false }">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Team Members ({{ $users->count() }})</h3>
            <button @click="open = !open"
                    class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add User
            </button>
        </div>

        <div x-show="open" x-cloak class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-700">
            <form method="POST" action="{{ route('settings.users.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Role <span class="text-red-500">*</span></label>
                        <select name="role" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="manager">Manager</option>
                            <option value="staff">Staff</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required minlength="8"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>

                {{-- Module access (manager/staff). Leave all unchecked = full access. --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                        Module Access
                        <span class="text-gray-400 font-normal">— leave all unchecked for full access</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @foreach(\App\Models\User::MODULES as $key => $label)
                        <label class="flex items-center gap-2 px-2.5 py-1.5 border border-gray-200 dark:border-slate-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="text-xs text-gray-700 dark:text-slate-300">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                    Add Team Member
                </button>
            </form>
        </div>
    </div>

    {{-- Users List --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @forelse($users as $user)
            <div class="flex items-center gap-3 px-4 py-4 hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group" x-data="{ edit: false }">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0
                    {{ ['bg-primary-500','bg-purple-500','bg-green-500','bg-orange-500','bg-pink-500'][crc32($user->name) % 5] }}">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0" x-show="!edit">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                        @if($user->id === auth()->id())
                            <span class="text-xs bg-primary-100 text-primary-600 px-1.5 py-0.5 rounded">You</span>
                        @endif
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $user->role === 'owner'   ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{ $user->role === 'admin'   ? 'bg-red-100 text-red-700' : '' }}
                            {{ $user->role === 'manager' ? 'bg-primary-100 text-primary-700' : '' }}
                            {{ $user->role === 'staff'   ? 'bg-green-100 text-green-700' : '' }}
                            {{ $user->role === 'employee'? 'bg-gray-100 text-gray-600' : '' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                        @if(!$user->is_active)
                            <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">Inactive</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $user->email }}</p>
                    @if($user->approved_at)
                        <p class="text-xs text-green-500 mt-0.5">âœ… Approved {{ $user->approved_at->diffForHumans() }}</p>
                    @endif
                </div>

                <div x-show="edit" x-cloak class="flex-1 min-w-0">
                    <form method="POST" action="{{ route('settings.users.update', $user) }}" class="flex flex-wrap gap-2">
                        @csrf @method('PUT')
                        <input type="text" name="name" value="{{ $user->name }}"
                               class="flex-1 min-w-32 px-2 py-1.5 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        @if($user->id !== auth()->id())
                        <select name="role" class="text-xs px-2 py-1.5 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 focus:outline-none">
                            @foreach(['admin','manager','staff','employee'] as $r)
                                <option value="{{ $r }}" {{ $user->role === $r ? 'selected':'' }}>{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                        @endif
                        @if($user->role !== 'owner')
                        <div class="w-full">
                            <p class="text-xs text-gray-400 mb-1">Module access — none checked = full access (ignored for Admin):</p>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                @php $userPerms = $user->permissions ?? []; @endphp
                                @foreach(\App\Models\User::MODULES as $key => $label)
                                <label class="flex items-center gap-1.5 px-2 py-1 border border-gray-200 dark:border-slate-600 rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700">
                                    <input type="checkbox" name="permissions[]" value="{{ $key }}" {{ in_array($key, $userPerms) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-xs text-gray-700 dark:text-slate-300">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <button type="submit" class="px-2.5 py-1.5 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700">Save</button>
                        <button type="button" @click="edit=false" class="px-2.5 py-1.5 bg-gray-200 dark:bg-slate-600 text-gray-700 text-xs rounded-lg">Cancel</button>
                    </form>
                </div>

                <div class="flex items-center gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity" x-show="!edit">
                    <button @click="edit=true"
                            class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    @if($user->id !== auth()->id() && $user->role !== 'owner')
                    <form method="POST" action="{{ route('settings.users.destroy', $user) }}" onsubmit="return confirm('Remove {{ $user->name }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-4 py-12 text-center text-sm text-gray-400">No team members yet</div>
            @endforelse
        </div>
    </div>

    {{-- Role Info --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mt-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Role Permissions</h3>
        <div class="space-y-2 text-xs">
            @foreach([
                ['role'=>'Owner',   'color'=>'yellow', 'desc'=>'Full access to everything including billing and user management'],
                ['role'=>'Admin',   'color'=>'red',    'desc'=>'Full access except cannot delete the business'],
                ['role'=>'Manager', 'color'=>'primary',   'desc'=>'Orders, products, inventory, reports, employees. No billing.'],
                ['role'=>'Staff',   'color'=>'green',  'desc'=>'Orders, products, customers, POS. No reports or settings.'],
                ['role'=>'Employee','color'=>'gray',   'desc'=>'Production logs and attendance only'],
            ] as $r)
            <div class="flex items-start gap-2">
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0 bg-{{ $r['color'] }}-100 text-{{ $r['color'] }}-700">{{ $r['role'] }}</span>
                <p class="text-gray-500 dark:text-slate-400">{{ $r['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

</div>

@endsection
