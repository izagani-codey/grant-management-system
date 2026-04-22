<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Verification Dashboard</h1>
            <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">Staff 1</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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

            {{-- Stats Row --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Pending Review</p>
                    <p class="text-3xl font-bold text-orange-600">{{ $dashboardStats['submitted'] ?? 0 }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Ready to Process</p>
                    <p class="text-3xl font-bold text-teal-600">{{ $dashboardStats['staff2_approved'] ?? 0 }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Sent to Staff 2</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $dashboardStats['staff1_reviewed'] ?? 0 }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Completed</p>
                    <p class="text-3xl font-bold text-green-600">{{ $dashboardStats['completed'] ?? 0 }}</p>
                </div>
            </div>

            {{-- Panel 1: Incoming Queue (SUBMITTED) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-orange-50">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Incoming Queue</h2>
                        <p class="text-xs text-gray-500 mt-0.5">New submissions awaiting your verification</p>
                    </div>
                    <span class="bg-orange-100 text-orange-700 text-xs font-bold px-3 py-1 rounded-full">
                        {{ $submittedQueue->count() }} request(s)
                    </span>
                </div>

                @if($submittedQueue->isEmpty())
                    <div class="px-6 py-10 text-center text-gray-500">
                        <p class="text-sm">No pending submissions. All caught up!</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Applicant</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Submitted</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($submittedQueue as $req)
                                    <tr class="hover:bg-orange-50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $req->ref_number }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $req->user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $req->user->email }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $req->requestType->name }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $req->created_at->format('d M Y') }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('requests.show', $req->id) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-orange-600 text-white text-xs font-semibold rounded hover:bg-orange-700 transition">
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Panel 2: Ready for Processing (STAFF2_APPROVED) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-teal-50">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Ready for Processing</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Approved by Staff 2 — print and mark as Completed</p>
                    </div>
                    <span class="bg-teal-100 text-teal-700 text-xs font-bold px-3 py-1 rounded-full">
                        {{ $approvedQueue->count() }} request(s)
                    </span>
                </div>

                @if($approvedQueue->isEmpty())
                    <div class="px-6 py-10 text-center text-gray-500">
                        <p class="text-sm">No requests ready for processing yet.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Applicant</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Approved</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($approvedQueue as $req)
                                    <tr class="hover:bg-teal-50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $req->ref_number }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $req->user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $req->user->email }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $req->requestType->name }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $req->updated_at->format('d M Y') }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('requests.show', $req->id) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-teal-600 text-white text-xs font-semibold rounded hover:bg-teal-700 transition">
                                                Process
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
