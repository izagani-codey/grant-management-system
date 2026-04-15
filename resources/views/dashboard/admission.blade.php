<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent">Request Dashboard</h1>
                <p class="text-gray-600 mt-1">Manage and track your grant applications</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-500 text-white text-sm font-semibold rounded-full shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    Admission
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Dev Quick-Switch (local only) --}}
            @if(app()->environment('local') && Route::has('dev.login'))
                @include('dashboard._dev-switcher')
            @endif

            {{-- Success Message --}}
            @if(session('success'))
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Welcome Section with Stats --}}
            <div class="bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 rounded-2xl p-8 text-white shadow-2xl">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="mb-6 md:mb-0">
                        <h2 class="text-3xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}! 👋</h2>
                        <p class="text-blue-100 text-lg">{{ auth()->user()->email }}</p>
                        <div class="mt-4 flex flex-wrap gap-4">
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $dashboardStats['submitted'] ?? 0 }}</div>
                                <div class="text-sm text-blue-100">Submitted</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $dashboardStats['staff1_approved'] ?? 0 }}</div>
                                <div class="text-sm text-yellow-100">Staff 1 Approved</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $dashboardStats['dean_approved'] ?? 0 }}</div>
                                <div class="text-sm text-green-100">Dean Approved</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('requests.create') }}"
                           class="inline-flex items-center bg-white text-blue-900 font-bold px-6 py-3 rounded-xl hover:bg-gray-50 transition-all transform hover:scale-105 shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Submit New Request
                        </a>
                    </div>
                </div>
            </div>

            {{-- Status Overview Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-gradient-to-br from-orange-50 to-amber-50 border border-orange-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-3 shadow-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-blue-600 truncate">Submitted</dt>
                                <dd class="mt-1 text-3xl font-bold text-blue-900">{{ $dashboardStats['submitted'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-blue-50 border border-blue-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-3 shadow-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-yellow-600 truncate">Staff 1 Approved</dt>
                                <dd class="mt-1 text-3xl font-bold text-yellow-900">{{ $dashboardStats['staff1_approved'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-yellow-50 to-amber-50 border border-yellow-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gradient-to-r from-yellow-500 to-amber-600 rounded-xl p-3 shadow-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-purple-600 truncate">Returned</dt>
                                <dd class="mt-1 text-3xl font-bold text-purple-900">{{ $dashboardStats['returned'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-3 shadow-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-green-600 truncate">Dean Approved</dt>
                                <dd class="mt-1 text-3xl font-bold text-green-900">{{ $dashboardStats['dean_approved'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <x-dashboard-filters 
                role="admission" 
                :request-types="$requestTypes"
                title="Filter Requests"
                description="Find specific requests quickly"
                color-theme="indigo"
            />
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Blank Forms & Templates
                        </h3>
                        <p class="text-gray-600 text-sm mt-1">Download and fill these forms for your applications</p>
                    </div>
                </div>
                
                {{-- Request Type Specific Templates --}}
                @forelse($requestTypeTemplates as $requestType)
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            {{ $requestType->name }}
                        </h4>
                        <div class="grid grid-cols-1 gap-3">
                            @forelse($requestType->templates as $template)
                                <div class="border border-gray-200 rounded-lg p-3 hover:border-blue-300 hover:shadow-md transition-all">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="bg-blue-100 rounded-lg p-2">
                                                <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h5 class="font-medium text-gray-900 text-sm">{{ $template->title }}</h5>
                                                <p class="text-xs text-gray-500">by {{ $template->uploader->name }}</p>
                                                @if($template->pivot->is_default)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mt-1">
                                                        Default
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <a href="{{ asset('storage/' . $template->file_path) }}" target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 bg-blue-900 text-white text-xs font-medium rounded-lg hover:bg-blue-950 transition-colors">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Download
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <p class="text-gray-500">No templates available for this request type</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <p class="text-gray-500">No templates available for this request type</p>
                    </div>
                @endforelse

                {{-- General Templates --}}
                @if($formTemplates->count() > 0)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            General Templates
                        </h4>
                        <div class="grid grid-cols-1 gap-3">
                            @foreach($formTemplates as $template)
                                <div class="border border-gray-200 rounded-lg p-3 hover:border-blue-300 hover:shadow-md transition-all">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="bg-gray-100 rounded-lg p-2">
                                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h5 class="font-medium text-gray-900 text-sm">{{ $template->title }}</h5>
                                                <p class="text-xs text-gray-500">by {{ $template->uploader->name }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ asset('storage/' . $template->file_path) }}" target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition-colors">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Download
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Recent Requests Table --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900">Recent Requests</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($displayRequests as $request)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $request->ref_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $request->requestType->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $request->statusClass() }}">
                                            {{ $request->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $request->priorityBadgeClass() }}">
                                            {{ $request->priorityLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $request->deadline?->format('M j, Y') ?? 'No deadline' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('requests.show', $request->id) }}" 
                                           class="text-blue-900 hover:text-blue-950 transition-colors">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">No requests yet</h3>
                                        <p class="text-gray-600 mb-4">Get started by submitting your first request.</p>
                                        <a href="{{ route('requests.create') }}" 
                                           class="inline-flex items-center px-4 py-2 bg-blue-900 text-white text-sm font-medium rounded-lg hover:bg-blue-950 transition-colors">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Submit Request
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($displayRequests->hasPages())
                    <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            {{ $displayRequests->links() }}
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ $displayRequests->firstItem() }}</span>
                                    to
                                    <span class="font-medium">{{ $displayRequests->lastItem() }}</span>
                                    of
                                    <span class="font-medium">{{ $displayRequests->total() }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                {{ $displayRequests->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>