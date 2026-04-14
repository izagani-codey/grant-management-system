<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Verification Dashboard</h1>
                <p class="text-gray-600 mt-1">Review and verify incoming grant requests</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-sm font-semibold rounded-full shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    Staff 1
                </span>
            </div>
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

            {{-- Urgent Deadlines Alert --}}
            @if($urgentRequests->count() > 0)
                <div class="bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-2xl p-6 shadow-lg">
                    <div class="flex items-start">
                        <div class="bg-red-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-red-900 mb-2">⏰ Urgent: Deadlines within 3 days</h3>
                            <p class="text-red-700 text-sm mb-4">These requests require immediate attention:</p>
                            <div class="space-y-2">
                                @foreach($urgentRequests as $urgent)
                                    <a href="{{ route('requests.show', $urgent->id) }}"
                                       class="flex items-center justify-between p-3 bg-white rounded-lg border border-red-200 hover:bg-red-50 transition-colors">
                                        <div class="flex items-center space-x-3">
                                            <span class="font-semibold text-gray-900">{{ $urgent->ref_number }}</span>
                                            <span class="text-sm text-gray-600">{{ $urgent->requestType->name }}</span>
                                            <span class="text-sm text-gray-500">from {{ $urgent->user->name }}</span>
                                        </div>
                                        <span class="text-sm font-bold text-red-600 bg-red-100 px-2 py-1 rounded">
                                            Due {{ $urgent->deadline?->format('M j') }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Welcome Section with Stats --}}
            @php
                $myQueue = ($dashboardStats['submitted'] ?? 0) + ($dashboardStats['returned'] ?? 0);
            @endphp
            <div class="bg-gradient-to-br from-purple-600 via-pink-600 to-red-600 rounded-2xl p-8 text-white shadow-2xl">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="mb-6 md:mb-0">
                        <h2 class="text-3xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}! 👋</h2>
                        <p class="text-purple-100 text-lg">{{ auth()->user()->email }}</p>
                        <div class="mt-4 flex flex-wrap gap-4">
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $myQueue }}</div>
                                <div class="text-sm text-purple-100">Needs My Action</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $dashboardStats['staff1_approved'] }}</div>
                                <div class="text-sm text-orange-100">Staff 2 Approved</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $dashboardStats['dean_approved'] }}</div>
                                <div class="text-sm text-green-100">Approved</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-purple-100 mb-2">Queue Status</div>
                        @if($myQueue > 0)
                            <div class="bg-yellow-400 text-yellow-900 px-4 py-2 rounded-lg font-bold">
                                {{ $myQueue }} items pending review
                            </div>
                        @else
                            <div class="bg-green-400 text-green-900 px-6 py-4 rounded-lg font-bold">
                                No items pending review
                            </div>
                        @endif
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
                                <dt class="text-sm font-medium text-blue-600 truncate">Pending Verification</dt>
                                <dd class="mt-1 text-3xl font-bold text-blue-900">{{ $dashboardStats['submitted'] }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-50 to-pink-50 border border-purple-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-3 shadow-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-purple-600 truncate">Returned to Me</dt>
                                <dd class="mt-1 text-3xl font-bold text-purple-900">{{ $dashboardStats['returned'] }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-3 shadow-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-orange-600 truncate">Sent to Staff 2</dt>
                                <dd class="mt-1 text-3xl font-bold text-orange-900">{{ $dashboardStats['staff1_approved'] }}</dd>
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
                                <dt class="text-sm font-medium text-green-600 truncate">Approved</dt>
                                <dd class="mt-1 text-3xl font-bold text-green-900">{{ $dashboardStats['dean_approved'] }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Deadline Reminder Widget --}}
            @if($urgentRequests->count() > 0)
                <div class="bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-xl font-bold">⚠️ Urgent Deadlines</h3>
                        </div>
                        <span class="bg-white/20 backdrop-blur-sm rounded-full px-3 py-1 text-sm font-bold">
                            {{ $urgentRequests->count() }} requests
                        </span>
                    </div>
                    
                    <div class="space-y-3">
                        @foreach($urgentRequests->take(3) as $urgent)
                            @php
                                $daysLeft = $urgent->daysUntilDeadline();
                                $urgencyColor = $daysLeft <= 1 ? 'bg-white/30' : 'bg-white/20';
                            @endphp
                            <div class="{{ $urgencyColor }} backdrop-blur-sm rounded-lg p-3 border border-white/20">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold">{{ $urgent->ref_number }}</p>
                                        <p class="text-sm text-red-100">{{ $urgent->user->name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold">
                                            @if($daysLeft < 0)
                                                OVERDUE
                                            @elseif($daysLeft === 0)
                                                DUE TODAY
                                            @elseif($daysLeft === 1)
                                                TOMORROW
                                            @else
                                                {{ $daysLeft }} days
                                            @endif
                                        </p>
                                        <p class="text-xs text-red-100">{{ $urgent->deadline->format('M j') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($urgentRequests->count() > 3)
                        <div class="mt-3 text-center">
                            <a href="{{ route('dashboard') }}?urgent=1" class="text-white/90 hover:text-white text-sm font-medium underline">
                                View all {{ $urgentRequests->count() }} urgent requests →
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            <x-dashboard-filters 
                role="staff1" 
                :request-types="$requestTypes"
                title="Filter Verification Queue"
                description="Find requests to verify quickly"
                color-theme="indigo"
            />
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Reference Forms & Templates
                        </h3>
                        <p class="text-gray-600 text-sm mt-1">Download blank forms for verification reference</p>
                    </div>
                </div>
                
                @forelse($formTemplates as $template)
                    <div class="border border-gray-200 rounded-xl p-4 hover:border-purple-300 hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="bg-purple-100 rounded-lg p-3">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $template->title }}</h4>
                                    <p class="text-sm text-gray-500">Uploaded by {{ $template->uploader->name }}</p>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $template->file_path) }}" target="_blank"
                               class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Download
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200 rounded-xl p-8 text-center">
                        <div class="bg-gray-100 rounded-full p-4 w-16 h-16 mx-auto mb-4 border border-gray-300">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">No templates available yet</h3>
                        <p class="text-gray-600 max-w-md mx-auto">Templates will appear here once uploaded by administrators</p>
                    </div>
                @endforelse
            </div>

            {{-- Verification Queue Table --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h3 class="text-xl font-bold text-gray-900">Verification Queue</h3>
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                        <span>{{ $myQueue }} pending</span>
                    </div>
                </div>
                
                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($displayRequests as $request)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $request->ref_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $request->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $request->user->email }}</div>
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
                                        @if($request->deadline)
                                            <div class="flex items-center">
                                                {{ $request->deadline->format('M j, Y') }}
                                                @if($request->isUrgent())
                                                    <span class="ml-2 text-xs text-red-600 font-bold">⚠️</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-400">No deadline</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('requests.show', $request->id) }}" 
                                           class="text-purple-600 hover:text-purple-900 transition-colors">
                                            Review Request
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">No requests in queue</h3>
                                        <p class="text-gray-600">All caught up! New requests will appear here.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Card View -->
                <div class="lg:hidden p-4 space-y-3">
                    @forelse($displayRequests as $request)
                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $request->ref_number }}</h4>
                                    <p class="text-sm text-gray-600">{{ $request->user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $request->requestType->name }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $request->statusClass() }}">
                                        {{ $request->statusLabel() }}
                                    </span>
                                    @if($request->is_priority)
                                        <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            Priority
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-500">
                                    @if($request->deadline)
                                        <span class="inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            {{ $request->deadline->format('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">No deadline</span>
                                    @endif
                                </div>
                                <a href="{{ route('requests.show', $request->id) }}" 
                                   class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-purple-700 bg-purple-100 hover:bg-purple-200 transition-colors">
                                    Review
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No requests in queue</h3>
                            <p class="text-gray-600">All caught up! New requests will appear here.</p>
                        </div>
                    @endforelse
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
