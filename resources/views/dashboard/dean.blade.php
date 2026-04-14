<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Dean Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Dean Welcome Section -->
            <div class="bg-gradient-to-r from-purple-600 via-purple-700 to-indigo-800 rounded-xl shadow-2xl p-8 mb-8 border border-purple-500/20">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">Dean Dashboard</h1>
                        <p class="text-purple-200 text-lg">Review and approve pending requests</p>
                    </div>
                    <div class="text-right bg-white/10 backdrop-blur-sm rounded-lg p-6">
                        <div class="text-4xl font-bold text-white">{{ $dashboardStats['staff2_approved'] ?? 0 }}</div>
                        <div class="text-purple-200 text-sm">Pending Dean Approval</div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50 border border-purple-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-3 shadow-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707L19.586 4H17a2 2 0 01-2-2V5a2 2 0 00-2-2H7a2 2 0 00-2-2v2a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-purple-600 truncate">Pending Approval</dt>
                                <dd class="mt-1 text-3xl font-bold text-purple-900">{{ $dashboardStats['staff2_approved'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-3 shadow-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-green-600 truncate">Approved Today</dt>
                                <dd class="mt-1 text-3xl font-bold text-green-900">{{ $dashboardStats['dean_approved'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl p-3 shadow-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-amber-600 truncate">Returned</dt>
                                <dd class="mt-1 text-3xl font-bold text-amber-900">{{ $dashboardStats['returned'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 border border-blue-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-xl p-3 shadow-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-blue-600 truncate">Total Requests</dt>
                                <dd class="mt-1 text-3xl font-bold text-blue-900">{{ $dashboardStats['total'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Dean Approvals -->
            <div class="bg-gradient-to-br from-white to-purple-50 border border-purple-200 rounded-xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white">Pending Dean Approval</h3>
                    <p class="text-purple-100 text-sm">Requests awaiting your final approval</p>
                </div>
                <div class="divide-y divide-purple-100">
                    @if($displayRequests->count() > 0)
                        @foreach($displayRequests as $request)
                            <div class="p-6 hover:bg-purple-50 transition-colors duration-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-shrink-0">
                                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-purple-100 text-purple-800 font-semibold text-sm">
                                                    {{ substr($request->ref_number, -3) }}
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ $request->ref_number }}
                                                </p>
                                                <div class="flex items-center mt-1 space-x-4">
                                                    <span class="text-sm text-gray-500">{{ $request->user->name }}</span>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        {{ $request->requestType->name }}
                                                    </span>
                                                    <span class="text-sm text-gray-500">{{ $request->created_at->format('M j, Y') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3 ml-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            {{ $request->statusLabel() }}
                                        </span>
                                        <a href="{{ route('requests.show', $request->id) }}" 
                                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 shadow-md hover:shadow-lg">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Review
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="p-12 text-center">
                            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">All Caught Up!</h3>
                            <p class="text-gray-500">No pending approvals at the moment. All requests have been processed.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Dev Switcher (Development Only) -->
            @if(app()->environment('local') && Route::has('dev.login'))
                <div class="mt-8">
                    @include('dashboard._dev-switcher')
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
