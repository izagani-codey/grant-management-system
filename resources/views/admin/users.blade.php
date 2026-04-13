<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                <p class="mt-1 text-sm text-gray-600">Admins can create accounts and assign roles such as Staff 1, Staff 2, Dean, or Admin.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}"
               class="inline-flex items-center rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-900">
                Back to Admin
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                    <p class="font-semibold">Please fix the following issues:</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-5">
                <div class="rounded-2xl bg-slate-900 p-5 text-white shadow-lg">
                    <p class="text-sm text-slate-300">Total Users</p>
                    <p class="mt-2 text-3xl font-bold">{{ $users->total() }}</p>
                </div>
                @foreach($roleOptions as $role)
                    <div class="rounded-2xl bg-white p-5 shadow-lg ring-1 ring-gray-100">
                        <p class="text-sm text-gray-500">{{ \Illuminate\Support\Str::headline($role) }}</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $roleCounts[$role] ?? 0 }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.2fr_2fr]">
                <div class="rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-100">
                    <h2 class="text-xl font-bold text-gray-900">Create User</h2>
                    <p class="mt-1 text-sm text-gray-600">Create an account and assign its starting role.</p>

                    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        </div>

                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select id="role" name="role" required
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                @foreach($roleOptions as $role)
                                    <option value="{{ $role }}" @selected(old('role', 'admission') === $role)>{{ \Illuminate\Support\Str::headline($role) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <input id="password" name="password" type="password" required
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="staff_id" class="block text-sm font-medium text-gray-700">Staff ID</label>
                                <input id="staff_id" name="staff_id" type="text" value="{{ old('staff_id') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            </div>
                            <div>
                                <label for="employee_level" class="block text-sm font-medium text-gray-700">Employee Level</label>
                                <input id="employee_level" name="employee_level" type="text" value="{{ old('employee_level') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            </div>
                        </div>

                        <div>
                            <label for="designation" class="block text-sm font-medium text-gray-700">Designation</label>
                            <input id="designation" name="designation" type="text" value="{{ old('designation') }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
                                <input id="department" name="department" type="text" value="{{ old('department') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            </div>
                        </div>

                        <button type="submit"
                                class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-black">
                            Create User
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-100">
                    <div class="flex flex-col gap-4 border-b border-gray-100 pb-5 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Existing Users</h2>
                            <p class="mt-1 text-sm text-gray-600">Update profile details, reset roles, or set a new password.</p>
                        </div>

                        <form method="GET" action="{{ route('admin.users') }}" class="grid gap-3 md:grid-cols-[1fr_auto_auto]">
                            <input type="text"
                                   name="search"
                                   value="{{ $filters['search'] ?? '' }}"
                                   placeholder="Search name, email, or staff ID"
                                   class="rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">

                            <select name="role" class="rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                <option value="">All roles</option>
                                @foreach($roleOptions as $role)
                                    <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ \Illuminate\Support\Str::headline($role) }}</option>
                                @endforeach
                            </select>

                            <button type="submit"
                                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                                Filter
                            </button>
                        </form>
                    </div>

                    <div class="mt-6 space-y-4">
                        @forelse($users as $managedUser)
                            <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="rounded-2xl border border-gray-200 p-5">
                                @csrf
                                @method('PATCH')

                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-semibold text-gray-900">{{ $managedUser->name }}</h3>
                                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-700">
                                                {{ \Illuminate\Support\Str::headline($managedUser->role) }}
                                            </span>
                                            @if(auth()->id() === $managedUser->id)
                                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700">
                                                    You
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm text-gray-600">{{ $managedUser->email }}</p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Created {{ $managedUser->created_at->format('M j, Y') }}
                                            @if($managedUser->staff_id)
                                                | Staff ID: {{ $managedUser->staff_id }}
                                            @endif
                                        </p>
                                    </div>

                                    <button type="submit"
                                            class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-black">
                                        Save Changes
                                    </button>
                                </div>

                                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Name</label>
                                        <input name="name" type="text" value="{{ old('name', $managedUser->name) }}" required
                                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <input name="email" type="email" value="{{ old('email', $managedUser->email) }}" required
                                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Role</label>
                                        <select name="role" required
                                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                            @foreach($roleOptions as $role)
                                                <option value="{{ $role }}" @selected(old('role', $managedUser->role) === $role)>{{ \Illuminate\Support\Str::headline($role) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Staff ID</label>
                                        <input name="staff_id" type="text" value="{{ old('staff_id', $managedUser->staff_id) }}"
                                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Designation</label>
                                        <input name="designation" type="text" value="{{ old('designation', $managedUser->designation) }}"
                                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Department</label>
                                        <input name="department" type="text" value="{{ old('department', $managedUser->department) }}"
                                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                                        <input name="phone" type="text" value="{{ old('phone', $managedUser->phone) }}"
                                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Employee Level</label>
                                        <input name="employee_level" type="text" value="{{ old('employee_level', $managedUser->employee_level) }}"
                                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">New Password</label>
                                        <input name="password" type="password"
                                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                        <input name="password_confirmation" type="password"
                                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                    </div>
                                </div>
                            </form>
                        @empty
                            <div class="rounded-2xl border border-dashed border-gray-300 px-5 py-10 text-center text-sm text-gray-500">
                                No users matched the current filters.
                            </div>
                        @endforelse
                    </div>

                    @if($users->hasPages())
                        <div class="mt-6 border-t border-gray-100 pt-5">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
