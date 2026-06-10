@extends('super-admin.layout')
@section('title', 'All Users')

@section('content')

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white">All Users</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $users->total() }} total</p>
    </div>
    <form method="GET" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email..."
               class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-blue-500 w-56">
        <select name="status" class="bg-gray-800 border border-gray-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none">
            <option value="">All Users</option>
            <option value="pending"  {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
            <option value="inactive" {{ request('status') === 'inactive'  ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm rounded-xl transition-colors">Filter</button>
    </form>
</div>

<div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-5 py-3 text-left">User</th>
                    <th class="px-5 py-3 text-left">Business</th>
                    <th class="px-4 py-3 text-center">Role</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-left">Last Login</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse($users as $user)
                @php
                    $roleColors = [
                        'owner'    => 'bg-purple-900/50 text-purple-400',
                        'admin'    => 'bg-blue-900/50 text-blue-400',
                        'manager'  => 'bg-indigo-900/50 text-indigo-400',
                        'employee' => 'bg-gray-700 text-gray-300',
                    ];
                    $roleColor = $roleColors[$user->role] ?? 'bg-gray-700 text-gray-300';
                @endphp
                <tr class="hover:bg-gray-800/30 transition-colors" x-data="{ resetOpen: false }">
                    <td class="px-5 py-3">
                        <p class="font-medium text-white">{{ $user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                    </td>
                    <td class="px-5 py-3">
                        <p class="text-gray-300 text-xs">{{ $user->business?->name ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $roleColor }}">{{ $user->role }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if(!$user->is_approved)
                            <span class="text-xs bg-orange-900/50 text-orange-400 px-2 py-0.5 rounded-full">Pending</span>
                        @elseif(!$user->is_active)
                            <span class="text-xs bg-red-900/50 text-red-400 px-2 py-0.5 rounded-full">Inactive</span>
                        @else
                            <span class="text-xs bg-green-900/50 text-green-400 px-2 py-0.5 rounded-full">Active</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-500">
                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @if(!$user->is_approved)
                            <form method="POST" action="{{ route('super-admin.users.approve', $user) }}">
                                @csrf
                                <button class="px-2 py-1 text-xs bg-green-700 hover:bg-green-600 text-white rounded-lg transition-colors">
                                    Approve
                                </button>
                            </form>
                            <form method="POST" action="{{ route('super-admin.users.reject', $user) }}"
                                  onsubmit="return confirm('Reject and delete {{ addslashes($user->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="px-2 py-1 text-xs bg-red-900/40 hover:bg-red-800 text-red-400 rounded-lg transition-colors">
                                    Reject
                                </button>
                            </form>
                            @endif

                            <button @click="resetOpen = !resetOpen"
                                    class="px-2 py-1 text-xs bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors">
                                Reset PW
                            </button>
                        </div>

                        {{-- Reset Password inline --}}
                        <div x-show="resetOpen" x-cloak class="mt-2">
                            <form method="POST" action="{{ route('super-admin.users.reset-password', $user) }}"
                                  class="flex gap-2">
                                @csrf
                                <input type="password" name="password" placeholder="New password" minlength="8" required
                                       class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1 text-xs text-white focus:outline-none focus:border-blue-500">
                                <button class="px-2 py-1 text-xs bg-blue-700 hover:bg-blue-600 text-white rounded-lg">Set</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-gray-600">No users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="px-5 py-4 border-t border-gray-800">
        {{ $users->links() }}
    </div>
    @endif
</div>

@endsection
