<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">Staff 2 Admin Panel</h1>
                <p class="text-gray-600 mt-1">System management and analytics dashboard</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('staff2.admin.users') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-600 text-white text-sm font-semibold rounded-lg hover:from-blue-700 hover:to-blue-700 transition-all shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Manage Users
                </a>
                <a href="{{ route('staff2.admin.request-types') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-sm font-semibold rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Request Types
                </a>
                <a href="{{ route('form-templates.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-sm font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Blank Forms
                </a>
                <a href="{{ route('staff2.deployment-playbook') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-slate-700 to-slate-900 text-white text-sm font-semibold rounded-lg hover:from-slate-800 hover:to-black transition-all shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3v2h6v-2c0-1.657-1.343-3-3-3zm-7 5V9a7 7 0 1114 0v4m-1 0H6a2 2 0 00-2 2v3a2 2 0 002 2h12a2 2 0 002-2v-3a2 2 0 00-2-2z"/>
                    </svg>
                    Secure Deploy
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 text-red-800 px-6 py-4 rounded-xl shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <!-- System Overview Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Total Requests</p>
                            <p class="text-3xl font-bold mt-2">{{ $totalRequests }}</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                            <svg class="w-8 h-8 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Pending Verification</p>
                            <p class="text-3xl font-bold mt-2">{{ $submitted }}</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                            <svg class="w-8 h-8 text-purple-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Approved</p>
                            <p class="text-3xl font-bold mt-2">{{ $deanApproved }}</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                            <svg class="w-8 h-8 text-green-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-red-600 to-red-700 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-sm font-medium">Declined</p>
                            <p class="text-3xl font-bold mt-2">{{ $rejected }}</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                            <svg class="w-8 h-8 text-red-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Stats and Form Templates -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- User Statistics -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        User Statistics
                    </h3>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $totalUsers }}</p>
                            <p class="text-sm text-gray-600">Total Users</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-blue-600">{{ $admissionUsers }}</p>
                            <p class="text-sm text-gray-600">Admission</p>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-purple-600">{{ $staff1Users }}</p>
                            <p class="text-sm text-gray-600">Staff 1</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-green-600">{{ $staff2Users }}</p>
                            <p class="text-sm text-gray-600">Staff 2</p>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="{{ route('staff2.admin.users') }}" 
                           class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium">
                            View All Users →
                        </a>
                    </div>
                </div>

                <!-- Form Templates -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Form Templates
                    </h3>
                    
                    <div class="bg-purple-50 rounded-lg p-4 text-center mb-6">
                        <p class="text-2xl font-bold text-purple-600">{{ $totalTemplates }}</p>
                        <p class="text-sm text-gray-600">Total Templates</p>
                    </div>

                    @if($recentTemplates->count() > 0)
                        <div class="space-y-3">
                            @foreach($recentTemplates as $template)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $template->title }}</p>
                                        <p class="text-sm text-gray-600">by {{ $template->uploader->name }}</p>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ $template->created_at->diffForHumans() }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 text-gray-500">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p>No templates uploaded yet</p>
                        </div>
                    @endif

                    <div class="mt-6 text-center">
                        <a href="{{ route('form-templates.index') }}" 
                           class="inline-flex items-center text-purple-600 hover:text-purple-700 font-medium">
                            Manage Templates →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Request Types and High Priority Requests -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Request Types -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Top Request Types
                        </h3>
                        <a href="{{ route('staff2.admin.request-types') }}" 
                           class="text-green-600 hover:text-green-700 font-medium text-sm">
                            Manage →
                        </a>
                    </div>
                    
                    <div class="space-y-3">
                        @foreach($byType as $type)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $type->name }}</p>
                                    @if($type->description)
                                        <p class="text-sm text-gray-600">{{ $type->description }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-green-600">{{ $type->requests_count }}</p>
                                    <p class="text-xs text-gray-500">requests</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Recent High Priority Requests -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        Recent High Priority Requests
                    </h3>
                    
                    <div class="space-y-3">
                        @forelse($recentHighPriority as $req)
                            <a href="{{ route('requests.show', $req->id) }}" 
                               class="block p-4 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-red-900">{{ $req->ref_number }}</p>
                                        <p class="text-sm text-red-700">{{ $req->requestType->name ?? 'N/A' }}</p>
                                        <p class="text-sm text-red-600">{{ $req->user->name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-600 text-white">
                                            HIGH PRIORITY
                                        </span>
                                        <p class="text-xs text-red-500 mt-1">{{ $req->created_at->diffForHumans() }}</p>
                                        @if($req->deadline)
                                            <p class="text-xs text-red-500">Due: {{ $req->deadline->format('M j') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                                <p>No high priority requests</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
