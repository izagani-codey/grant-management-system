<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Admin Dashboard
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
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
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Admin Overview -->
            <div class="bg-gradient-to-br from-gray-600 via-gray-700 to-gray-800 rounded-2xl p-8 text-white shadow-2xl">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="mb-6 md:mb-0">
                        <h2 class="text-3xl font-bold mb-2">Admin Dashboard</h2>
                        <p class="text-gray-300 text-lg">System administration and management</p>
                        <div class="mt-4 flex flex-wrap gap-4">
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $totalRequests }}</div>
                                <div class="text-sm text-gray-300">Total Requests</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $totalUsers }}</div>
                                <div class="text-sm text-gray-300">Total Users</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $totalTemplates }}</div>
                                <div class="text-sm text-gray-300">Templates</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="{{ route('admin.users') }}" class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">User Management</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalUsers }}</p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.request-types') }}" class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Request Types</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $byType->count() }}</p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                </a>

                <a href="{{ route('form-templates.index') }}" class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Templates</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalTemplates }}</p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.deployment-playbook') }}" class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-500 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Deployment</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">Info</p>
                        </div>
                        <div class="bg-orange-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Statistics Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Request Statistics -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Request Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Submitted</span>
                            <span class="font-semibold text-blue-600">{{ $submitted }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Staff 1 Checked</span>
                            <span class="font-semibold text-purple-600">{{ $staff1Approved }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Staff 2 Approved</span>
                            <span class="font-semibold text-green-600">{{ $staff2Approved }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Completed</span>
                            <span class="font-semibold text-teal-600">{{ $completed ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Declined</span>
                            <span class="font-semibold text-red-600">{{ $declined ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- User Statistics -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">User Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Admission Users</span>
                            <span class="font-semibold text-blue-600">{{ $admissionUsers }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Staff 1</span>
                            <span class="font-semibold text-purple-600">{{ $staff1Users }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Staff 2</span>
                            <span class="font-semibold text-green-600">{{ $staff2Users }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Admin Users</span>
                            <span class="font-semibold text-gray-600">{{ $totalUsers - $admissionUsers - $staff1Users - $staff2Users }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent High Priority Requests -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Requests</h3>
                    @if($recentHighPriority->count() > 0)
                        <div class="space-y-3">
                            @foreach($recentHighPriority as $request)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $request->ref_number }}</p>
                                        <p class="text-sm text-gray-600">{{ $request->user?->name ?? 'Unknown' }}</p>
                                    </div>
                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Priority</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">No requests yet</p>
                    @endif
                </div>

                <!-- Recent Templates -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Templates</h3>
                    @if($recentTemplates->count() > 0)
                        <div class="space-y-3">
                            @foreach($recentTemplates as $template)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $template->name }}</p>
                                        <p class="text-sm text-gray-600">{{ $template->uploader->name ?? 'Unknown' }}</p>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $template->created_at->format('M j') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">No templates uploaded</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

