<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                Admin
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if(app()->environment('local') && Route::has('dev.login'))
                @include('dashboard._dev-switcher')
            @endif

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header Banner --}}
            <div class="bg-gray-800 rounded-xl p-6 text-white">
                <h2 class="text-2xl font-bold mb-1">System Administration</h2>
                <p class="text-gray-300 text-sm">Manage users and monitor system activity. Configuration is handled by Staff 2.</p>
                <div class="mt-4 flex flex-wrap gap-4">
                    <div class="bg-white/10 rounded-lg px-4 py-2">
                        <div class="text-2xl font-bold">{{ $totalRequests }}</div>
                        <div class="text-xs text-gray-300">Total Requests</div>
                    </div>
                    <div class="bg-white/10 rounded-lg px-4 py-2">
                        <div class="text-2xl font-bold">{{ $totalUsers }}</div>
                        <div class="text-xs text-gray-300">Total Users</div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="{{ route('admin.users') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 border-l-4 border-l-blue-500 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">User Management</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalUsers }}</p>
                            <p class="text-xs text-gray-500 mt-1">Create, edit, and assign roles</p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                    </div>
                </a>

                <a href="{{ route('requests.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 border-l-4 border-l-gray-400 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">All Requests</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalRequests }}</p>
                            <p class="text-xs text-gray-500 mt-1">Read-only system view</p>
                        </div>
                        <div class="bg-gray-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Stats Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Request Statistics --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Request Statistics</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Submitted</span>
                            <span class="font-semibold text-orange-600">{{ $submitted }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Staff 1 Reviewed</span>
                            <span class="font-semibold text-blue-600">{{ $staff1Reviewed }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Staff 2 Approved</span>
                            <span class="font-semibold text-teal-600">{{ $staff2Approved }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Completed</span>
                            <span class="font-semibold text-green-600">{{ $completed }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600">Declined</span>
                            <span class="font-semibold text-red-600">{{ $declined }}</span>
                        </div>
                    </div>
                </div>

                {{-- User Statistics --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Users by Role</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Admission</span>
                            <span class="font-semibold text-blue-600">{{ $admissionUsers }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Staff 1</span>
                            <span class="font-semibold text-purple-600">{{ $staff1Users }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Staff 2</span>
                            <span class="font-semibold text-green-600">{{ $staff2Users }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600">Admin</span>
                            <span class="font-semibold text-gray-600">{{ $totalUsers - $admissionUsers - $staff1Users - $staff2Users }}</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <a href="{{ route('admin.users') }}" class="text-sm font-medium text-blue-600 hover:underline">
                            Manage Users →
                        </a>
                    </div>
                </div>
            </div>

            {{-- Recent Requests (read-only) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900">Recent Requests</h3>
                    <a href="{{ route('requests.index') }}" class="text-xs font-medium text-blue-600 hover:underline">View all →</a>
                </div>
                @if($recentRequests->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Applicant</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Submitted</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($recentRequests as $req)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            <a href="{{ route('requests.show', $req->id) }}" class="text-blue-600 hover:underline">
                                                {{ $req->ref_number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">{{ $req->user?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $req->requestType?->name ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $req->statusClass() }}">
                                                {{ $req->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $req->created_at->format('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-10 text-center text-gray-500">
                        <p class="text-sm">No requests in the system yet.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
