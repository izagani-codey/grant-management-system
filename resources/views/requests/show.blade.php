<x-app-layout>
    @php
        $isFinalStatus = $grantRequest->isFinal();
        $staff1Active = in_array($grantRequest->status_id, [\App\Enums\RequestStatus::SUBMITTED->value, \App\Enums\RequestStatus::RETURNED->value], true);
        $staff1Completed = in_array($grantRequest->status_id, [
            \App\Enums\RequestStatus::STAFF1_APPROVED->value,
            \App\Enums\RequestStatus::STAFF2_APPROVED->value,
            \App\Enums\RequestStatus::RETURNED->value,
            \App\Enums\RequestStatus::DEAN_APPROVED->value,
            \App\Enums\RequestStatus::REJECTED->value,
        ], true);
        $staff2Active = in_array($grantRequest->status_id, [\App\Enums\RequestStatus::STAFF1_APPROVED->value, \App\Enums\RequestStatus::RETURNED->value], true);
        $staff2Completed = in_array($grantRequest->status_id, [
            \App\Enums\RequestStatus::STAFF2_APPROVED->value,
            \App\Enums\RequestStatus::DEAN_APPROVED->value,
            \App\Enums\RequestStatus::REJECTED->value,
        ], true);
        $completedStepDone = $grantRequest->isTrulyComplete() || $grantRequest->status_id === \App\Enums\RequestStatus::REJECTED->value;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight min-w-0 break-words">
                Request: {{ $grantRequest->ref_number }}
                @if($grantRequest->is_priority)
                    <span class="ml-2 px-2 py-1 bg-red-500 text-white text-xs rounded-full">⚠ PRIORITY</span>
                @endif
            </h2>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('requests.print', $grantRequest->id) }}" target="_blank"
                   class="px-3 py-1 text-xs font-semibold rounded bg-slate-100 text-slate-700 hover:bg-slate-200">
                    Printable Summary
                </a>
                <span class="px-3 py-1 text-xs font-bold rounded-full {{ $grantRequest->statusClass() }}">
                    {{ $grantRequest->statusLabel() }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 overflow-x-hidden">

            {{-- Success Message --}}
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Request Details --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Request Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="min-w-0">
                        <p class="text-gray-500">Submitted By</p>
                        <p class="font-semibold break-words">{{ $grantRequest->user->name }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-gray-500">Email</p>
                        <p class="font-semibold break-all">{{ $grantRequest->payload['email'] ?? $grantRequest->user->email }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-gray-500">Request Type</p>
                        <p class="font-semibold break-words">{{ $grantRequest->requestType->name }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-gray-500">Staff ID</p>
                        <p class="font-semibold break-words">{{ $grantRequest->submitter_staff_id ?? '-' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-gray-500">Designation</p>
                        <p class="font-semibold break-words">{{ $grantRequest->submitter_designation ?? '-' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-gray-500">Phone Number</p>
                        <p class="font-semibold break-words">{{ $grantRequest->submitter_phone ?? '-' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-gray-500">Amount Requested</p>
                        <p class="font-bold text-lg">RM {{ number_format((float) ($grantRequest->total_amount ?? 0), 2) }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-gray-500">Date Submitted</p>
                        <p class="font-semibold">{{ $grantRequest->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-gray-500">Deadline</p>
                        <p class="font-semibold {{ $grantRequest->is_priority ? 'text-red-600 font-bold' : '' }}">
                            {{ $grantRequest->deadline ? $grantRequest->deadline->format('d M Y') : 'None' }}
                        </p>
                    </div>
                    @if($grantRequest->revision_count > 0)
                        <div class="min-w-0">
                            <p class="text-gray-500">Revisions</p>
                            <p class="font-semibold text-yellow-600">{{ $grantRequest->revision_count }} revision(s)</p>
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <p class="text-gray-500 text-sm">Justification / Description</p>
                    <div class="mt-1 p-3 bg-gray-50 rounded border text-sm whitespace-pre-wrap break-all [overflow-wrap:anywhere]">
                        {{ $grantRequest->payload['description'] ?? 'No description provided.' }}
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-gray-500 text-sm mb-2">VOT Breakdown</p>
                    <div class="overflow-x-auto rounded border">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="text-left px-3 py-2">VOT Code</th>
                                    <th class="text-left px-3 py-2">Description</th>
                                    <th class="text-right px-3 py-2">Amount (RM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($grantRequest->vot_items ?? []) as $item)
                                    <tr class="border-t">
                                        <td class="px-3 py-2 font-semibold">{{ $item['vot_code'] ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $item['description'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right">{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr class="border-t">
                                        <td colspan="3" class="px-3 py-2 text-center text-gray-500">No VOT items provided</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Request Timeline --}}
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    Request Timeline
                </h3>
                
                <div class="relative">
                    <!-- Timeline Line -->
                    <div class="absolute left-8 top-8 bottom-8 w-0.5 bg-gray-300"></div>
                    
                    <!-- Timeline Steps -->
                    <div class="space-y-8">
                        <!-- Step 1: Submitted -->
                        <div class="flex items-center">
                            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900">Submitted</h4>
                                <p class="text-sm text-gray-600">Request submitted by applicant</p>
                                <div class="text-xs text-gray-500 mt-2">
                                    <span class="font-medium">Submitted by:</span> {{ $grantRequest->user->name }}
                                    <span class="ml-2">on {{ $grantRequest->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 2: Staff 1 Verification -->
                        <div class="flex items-center">
                            <div class="w-16 h-16 {{ $staff1Completed ? 'bg-green-500' : ($staff1Active ? 'bg-blue-500' : 'bg-gray-300') }} rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900">Staff 1 Verification</h4>
                                <p class="text-sm text-gray-600">Request verified by Staff 1</p>
                                @if($grantRequest->verifiedBy)
                                    <div class="text-xs text-gray-500 mt-2">
                                        <span class="font-medium">Verified by:</span> {{ $grantRequest->verifiedBy->name }}
                                        @if($grantRequest->verified_at)
                                            <span class="ml-2">on {{ $grantRequest->verified_at->format('d M Y, h:i A') }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Step 3: Staff 2 Recommendation -->
                        <div class="flex items-center">
                            <div class="w-16 h-16 {{ $staff2Completed ? 'bg-green-500' : ($staff2Active ? 'bg-blue-500' : 'bg-gray-300') }} rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900">Staff 2 Recommendation</h4>
                                <p class="text-sm text-gray-600">Request reviewed and recommended by Staff 2</p>
                                @if($grantRequest->recommendedBy)
                                    <div class="text-xs text-gray-500 mt-2">
                                        <span class="font-medium">Recommended by:</span> {{ $grantRequest->recommendedBy->name }}
                                        @if($grantRequest->recommended_at)
                                            <span class="ml-2">on {{ $grantRequest->recommended_at->format('d M Y, h:i A') }}</span>
                                        @endif
                                    </div>
                                @endif
                                
                                <!-- Show rejection info if rejected -->
                                @if($grantRequest->status_id === \App\Enums\RequestStatus::REJECTED->value)
                                    <div class="mt-2 p-2 bg-red-50 rounded border border-red-200">
                                        <p class="text-sm text-red-800">
                                            <span class="font-medium">Rejected:</span> 
                                            @if($grantRequest->rejection_reason)
                                                {{ $grantRequest->rejection_reason }}
                                            @else
                                                No reason provided
                                            @endif
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Step 4: Completed -->
                        <div class="flex items-center">
                            <div class="w-16 h-16 {{ $completedStepDone ? 'bg-green-500' : 'bg-gray-300' }} rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900">Completed</h4>
                                <p class="text-sm text-gray-600">Request approved and completed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- General Form (Generated PDF) --}}
           {{-- General Form (Generated PDF) --}}
@php
    $latestTemplateUsage = $grantRequest->templateUsages->first();
@endphp
<div class="bg-white shadow-sm rounded-lg p-6">
    <h3 class="font-bold text-lg mb-4 border-b pb-2">General Form</h3>
    <p class="text-sm text-gray-600 mb-3">
        System-generated form based on the selected template and latest request data.
    </p>

    {{-- Inline PDF viewer --}}
    <div class="border border-gray-200 rounded-lg overflow-hidden mb-3" style="height: 500px;">
        <iframe 
            src="{{ route('requests.pdf.inline', $grantRequest->id) }}"
            class="w-full h-full"
            title="Generated form preview">
        </iframe>
    </div>

    <a href="{{ route('requests.downloadPdf', $grantRequest->id) }}"
       class="inline-flex items-center text-blue-600 hover:underline text-sm font-semibold">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Download PDF
    </a>
</div>

            {{-- Main Uploaded Document --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Uploaded Document</h3>
                @if($grantRequest->file_path)
                    @php
                        $ext = pathinfo($grantRequest->file_path, PATHINFO_EXTENSION);
                        $mainDocumentUrl = route('requests.document.main', $grantRequest->id);
                    @endphp

                    @if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png']))
                        <img src="{{ $mainDocumentUrl }}"
                             class="max-w-full rounded border" alt="Uploaded document">
                    @elseif(strtolower($ext) === 'pdf')
                        <iframe src="{{ $mainDocumentUrl }}"
                                class="w-full h-96 border rounded" title="PDF Viewer"></iframe>
                    @else
                        <p class="text-sm text-gray-600">Preview is not available for this file type.</p>
                    @endif

                    <a href="{{ $mainDocumentUrl }}"
                       target="_blank"
                       class="mt-3 inline-block text-blue-600 hover:underline text-sm font-semibold break-all">
                        Open in new tab
                    </a>
                @else
                    <p class="text-sm text-gray-500">No supporting document was uploaded for this request.</p>
                @endif
            </div>

            @php
                $additionalDocuments = collect($grantRequest->payload['additional_documents'] ?? [])
                    ->filter(fn ($path) => is_string($path) && $path !== '')
                    ->values();
            @endphp
            @if($additionalDocuments->isNotEmpty())
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Additional Supporting Documents</h3>
                <ul class="space-y-2 text-sm">
                    @foreach($additionalDocuments as $documentPath)
                        <li>
                            <a href="{{ route('requests.document.additional', ['id' => $grantRequest->id, 'index' => $loop->index]) }}"
                               target="_blank"
                               class="text-blue-600 hover:underline font-semibold break-all">
                                ↗ {{ basename($documentPath) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Rejection Reason (visible to admission) --}}
            @if($grantRequest->rejection_reason)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="font-bold text-red-700 mb-1">⚠ Returned / Rejected — Reason:</h3>
                <p class="text-red-600 text-sm break-words">{{ $grantRequest->rejection_reason }}</p>
            </div>
            @endif

            {{-- Staff Notes (staff only) --}}
           @if($grantRequest->staff_notes && in_array(auth()->user()->role, ['staff1', 'staff2']))
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="font-bold text-yellow-700 mb-1">Internal Staff Notes</h3>
                <p class="text-yellow-800 text-sm break-words">{{ $grantRequest->staff_notes }}</p>
            </div>
            @endif

            {{-- Staff Notes History (from audit logs) --}}
            @if(in_array(auth()->user()->role, ['staff1', 'staff2']))
                @php
                    $staffNoteLogs = ($grantRequest->auditLogs ?? collect())
                        ->filter(fn ($log) => (int) $log->from_status !== 0 && !empty($log->note))
                        ->values();
                @endphp

                @if($staffNoteLogs->count() > 0)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="font-bold text-yellow-700 mb-3">Staff Notes History</h3>
                        <div class="space-y-3">
                            @foreach($staffNoteLogs as $log)
                                <div class="text-sm">
                                    <div class="text-xs text-yellow-800">
                                        {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A') }}
                                        , {{ $log->actor?->name ?? 'Unknown' }}
                                    </div>
                                    <div class="mt-1 text-yellow-900">
                                        <span class="font-semibold">Status:</span> {{ $log->from_status }} -> {{ $log->to_status }}
                                    </div>
                                    <div class="mt-1 text-yellow-800">
                                        {{ $log->note }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            {{-- Verified / Recommended By (staff only) --}}
            @if(auth()->user()->role !== 'admission')
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Verification Trail</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Verified By (Staff 1)</p>
                        <p class="font-semibold">{{ $grantRequest->verifiedBy?->name ?? 'Pending' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Recommended By (Staff 2)</p>
                        <p class="font-semibold">{{ $grantRequest->recommendedBy?->name ?? 'Pending' }}</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Workflow Timeline (Audit events + internal comments) --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Workflow Timeline</h3>

                @php
                    $isStaff = auth()->user()->role !== 'admission';
                    $statusLabels = \App\Enums\RequestStatus::getAllCases();

                    $events = collect();

                    foreach (($grantRequest->auditLogs ?? collect()) as $log) {
                        $events->push([
                            'type' => 'status',
                            'at' => $log->created_at,
                            'actor' => $log->actor?->name ?? 'Unknown',
                            'from' => $log->from_status,
                            'to' => $log->to_status,
                            // Keep staff notes internal; admissions should only see status transitions.
                            'note' => $isStaff ? $log->note : null,
                        ]);
                    }

                    if ($isStaff) {
                        foreach (($grantRequest->comments ?? collect()) as $comment) {
                            // Comments are internal and staff-facing; show them in the timeline as "comment" events.
                            $events->push([
                                'type' => 'comment',
                                'at' => $comment->created_at,
                                'actor' => $comment->user?->name ?? 'Unknown',
                                'note' => $comment->content,
                            ]);
                        }
                    }

                    $events = $events->sortBy('at');
                @endphp

                @if($events->count() === 0)
                    <p class="text-gray-400 italic text-sm">No timeline events yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($events as $event)
                            <div class="flex gap-3">
                                <div class="mt-1">
                                    <div class="w-3 h-3 rounded-full {{ $event['type'] === 'status' ? 'bg-blue-600' : 'bg-gray-500' }}"></div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between gap-3 text-xs text-gray-600">
                                        <span>
                                            {{ \Carbon\Carbon::parse($event['at'])->format('d M Y, h:i A') }}
                                        </span>
                                        <span class="font-semibold">{{ $event['actor'] }}</span>
                                    </div>

                                    @if($event['type'] === 'status')
                                        <div class="mt-1 text-sm text-gray-800">
                                            <span class="font-semibold">Status:</span>
                                            {{ $statusLabels[$event['from']] ?? $event['from'] }}
                                            ->
                                            {{ $statusLabels[$event['to']] ?? $event['to'] }}
                                        </div>
                                        @if(!empty($event['note']))
                                            <div class="mt-1 text-sm text-gray-700">
                                                <span class="font-semibold">Note:</span> {{ $event['note'] }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="mt-1 text-sm text-gray-800">
                                            <span class="font-semibold">Comment:</span> {{ \Illuminate\Support\Str::limit($event['note'] ?? '', 180) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- WORKFLOW ACTION BUTTONS --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Actions</h3>

                {{-- ADMISSION: Edit if returned --}}
                @can('revise', $grantRequest)
                    <a href="{{ route('requests.edit', $grantRequest->id) }}"
                       class="inline-block bg-yellow-500 text-white px-6 py-2 rounded font-bold hover:bg-yellow-600">
                        ✏ Edit & Resubmit
                    </a>
                @endcan

                {{-- STAFF 1: Verify or Return to Admission --}}
                @can('changeStatus', $grantRequest)
                    @if(auth()->user()->role === 'staff1')
                        <form action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3" onsubmit="return handleFormSubmit(this, 'Submitting...')">
                            @csrf
                            @method('PATCH')
                            <textarea name="notes" rows="2"
                                placeholder="Internal notes (optional)"
                                class="w-full border rounded p-2 text-sm"></textarea>
                            <textarea name="rejection_reason" rows="2"
                                placeholder="Reason for returning or rejecting (visible to admission)"
                                class="w-full border rounded p-2 text-sm"></textarea>
                            
                            <input type="hidden" name="status_id" value="{{ \App\Enums\RequestStatus::STAFF1_APPROVED->value }}" id="s1-status">
                            <div class="flex gap-3 flex-wrap">
                                <x-loading-button type="primary" onclick="document.getElementById('s1-status').value='{{ \App\Enums\RequestStatus::STAFF1_APPROVED->value }}'">
                                    ✓ Verify & Send to Staff 2
                                </x-loading-button>
                                <x-loading-button type="secondary" onclick="document.getElementById('s1-status').value='{{ \App\Enums\RequestStatus::RETURNED->value }}'">
                                    ↩ Return to Admission
                                </x-loading-button>
                                <x-loading-button type="danger" onclick="document.getElementById('s1-status').value='{{ \App\Enums\RequestStatus::REJECTED->value }}'">
                                    ✕ Reject
                                </x-loading-button>
                            </div>
                        </form>
                    @endif
                @endcan

                {{-- STAFF 2: Approve, Return to Staff 1, or Decline --}}
                @can('changeStatus', $grantRequest)
                    @if(auth()->user()->role === 'staff2')
                        <form action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3" onsubmit="return handleFormSubmit(this, 'Submitting...', event)" data-signature-input="staff2-signature-data" data-role-action="staff2">
                            @csrf
                            @method('PATCH')
                            <input type="file" name="staff2_supporting_documents[]" multiple
                                class="w-full border rounded p-2 text-sm"
                                accept=".pdf,.jpg,.jpeg,.png">
                            <p class="text-xs text-gray-500 -mt-1">Optional: add revised supporting documents (old files remain available).</p>
                            <textarea name="notes" rows="2" placeholder="Recommendation notes (optional)"
                                class="w-full border rounded p-2 text-sm"></textarea>
                            <textarea name="rejection_reason" rows="2"
                                placeholder="Reason (required for Decline or Return)"
                                class="w-full border rounded p-2 text-sm"></textarea>
                            
                            <!-- Staff 2 Signature -->
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-green-700">Staff 2 Signature:</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-2 bg-gray-50">
                                    <canvas id="staff2-signature-canvas" width="400" height="150" class="w-full border border-gray-300 rounded bg-white cursor-crosshair"></canvas>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" onclick="clearStaff2Signature()" class="text-xs bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">Clear Signature</button>
                                    <span class="text-xs text-gray-500">Sign above before submitting</span>
                                </div>
                                <input type="hidden" name="staff2_signature_data" id="staff2-signature-data">
                            </div>
                            
                            <input type="hidden" name="status_id" value="{{ \App\Enums\RequestStatus::STAFF2_APPROVED->value }}" id="status2-input">
                            <div class="flex gap-3 flex-wrap">
                                <button type="submit"
                                    data-status="{{ \App\Enums\RequestStatus::STAFF2_APPROVED->value }}"
                                    class="bg-purple-600 text-white px-6 py-2 rounded font-bold hover:bg-purple-700">
                                    ✓ Send to Dean
                                </button>
                                
                                @if($grantRequest->status_id === \App\Enums\RequestStatus::STAFF1_APPROVED->value)
                                    <button type="submit"
                                        data-status="{{ \App\Enums\RequestStatus::RETURNED->value }}"
                                        class="bg-yellow-500 text-white px-6 py-2 rounded font-bold hover:bg-yellow-600">
                                        ↩ Return to Staff 1
                                    </button>
                                @endif
                                
                                <button type="submit"
                                    data-status="{{ \App\Enums\RequestStatus::REJECTED->value }}"
                                    class="bg-red-600 text-white px-6 py-2 rounded font-bold hover:bg-red-700">
                                    ✕ Reject
                                </button>
                            </div>
                        </form>
                        <p class="text-xs text-gray-400 mt-3 italic">Primary flow: Admission → Staff 1 → Staff 2 → Dean.</p>
                    @endif
                @endcan

                {{-- DEAN ACTIONS --}}
                @if(auth()->user()->role === 'dean' && $grantRequest->canBeActionedByDean())
                    <div class="mt-6 p-4 bg-purple-50 border-l-4 border-purple-500 rounded">
                        <h4 class="font-bold text-purple-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Dean Approval Actions
                        </h4>
                        
                        <form action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3" onsubmit="return handleFormSubmit(this, 'Submitting...', event)" data-signature-input="dean-signature-data" data-role-action="dean">
                            @csrf
                            @method('PATCH')
                            
                            <!-- Dean Signature -->
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-purple-700">Dean Signature:</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-2 bg-gray-50">
                                    <canvas id="dean-signature-canvas" width="400" height="150" class="w-full border border-gray-300 rounded bg-white cursor-crosshair"></canvas>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" onclick="clearDeanSignature()" class="text-xs bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">Clear Signature</button>
                                    <span class="text-xs text-gray-500">Sign above before submitting</span>
                                </div>
                                <input type="hidden" name="dean_signature_data" id="dean-signature-data">
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-purple-700">Dean Decision:</label>
                                <div class="flex gap-3 flex-wrap">
                                    <button type="submit"
                                        data-status="{{ \App\Enums\RequestStatus::DEAN_APPROVED->value }}"
                                        data-dean-action="approve"
                                        class="bg-green-600 text-white px-6 py-2 rounded font-bold hover:bg-green-700">
                                        ✓ Approve Request
                                    </button>
                                    
                                    <button type="submit"
                                        data-status="{{ \App\Enums\RequestStatus::REJECTED->value }}"
                                        data-dean-action="reject"
                                        class="bg-red-600 text-white px-6 py-2 rounded font-bold hover:bg-red-700">
                                        ✗ Reject Request
                                    </button>
                                    
                                    <button type="submit"
                                        data-status="{{ \App\Enums\RequestStatus::RETURNED->value }}"
                                        data-dean-action="return_staff1"
                                        class="bg-orange-600 text-white px-6 py-2 rounded font-bold hover:bg-orange-700">
                                        ↩ Return to Staff 1
                                    </button>
                                    
                                    <button type="submit"
                                        data-status="{{ \App\Enums\RequestStatus::RETURNED->value }}"
                                        data-dean-action="return_staff2"
                                        class="bg-yellow-600 text-white px-6 py-2 rounded font-bold hover:bg-yellow-700">
                                        ↩ Return to Staff 2
                                    </button>
                                </div>
                                <input type="hidden" name="status_id" value="{{ \App\Enums\RequestStatus::DEAN_APPROVED->value }}" id="dean-status-input">
                                <input type="hidden" name="dean_action" value="approve" id="dean-action-input">
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-purple-700">Comments/Reason:</label>
                                <textarea name="rejection_reason" rows="2"
                                    placeholder="Reason (required for Reject or Return)"
                                    class="w-full border rounded p-2 text-sm"></textarea>
                            </div>
                        </form>
                        
                        <p class="text-xs text-gray-400 mt-3 italic">Final approval stage. Dean decision is final.</p>
                    </div>
                @endif

                {{-- MANUAL PRIORITY CONTROLS --}}
                @if(in_array(auth()->user()->role, ['staff1', 'staff2']) && !$isFinalStatus)
                    <div class="mt-6 p-4 bg-orange-50 border-l-4 border-orange-500 rounded">
                        <h4 class="font-bold text-orange-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Priority Management
                        </h4>
                        
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-orange-700">Current Priority:</p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $grantRequest->priorityBadgeClass() }}">
                                        {{ $grantRequest->priorityLabel() }}
                                    </span>
                                </div>
                                
                                @if($grantRequest->deadline)
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">Days until deadline:</span> 
                                        <span class="{{ $grantRequest->daysUntilDeadline() <= 3 ? 'text-red-600 font-bold' : ($grantRequest->daysUntilDeadline() <= 5 ? 'text-orange-600 font-semibold' : '') }}">
                                            {{ $grantRequest->daysUntilDeadline() }} days
                                        </span>
                                    </div>
                                @endif
                            </div>
                            
                            <form method="POST" action="{{ route('requests.updatePriority', $grantRequest->id) }}" class="flex items-center gap-3">
                                @csrf
                                @method('PATCH')
                                
                                <input type="hidden" name="is_priority" value="{{ $grantRequest->is_priority ? '0' : '1' }}">
                                
                                <button type="submit" 
                                    class="{{ $grantRequest->is_priority ? 'bg-gray-600 hover:bg-gray-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white px-4 py-2 rounded text-sm font-medium transition-colors"
                                    onclick="return confirm('Are you sure you want to {{ $grantRequest->is_priority ? 'remove' : 'set' }} high priority for this request?')">
                                    {{ $grantRequest->is_priority ? '🔻 Remove Priority' : '🔺 Set High Priority' }}
                                </button>
                                
                                <span class="text-xs text-gray-500">
                                    {{ $grantRequest->deadline ? 'Auto: ≤5 days = HIGH' : 'Manual control only' }}
                                </span>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- No actions available --}}
                @if(
                    (auth()->user()->role === 'admission' && $grantRequest->status_id != \App\Enums\RequestStatus::RETURNED->value) ||
                    (auth()->user()->role === 'staff1' && !in_array($grantRequest->status_id, [\App\Enums\RequestStatus::SUBMITTED->value, \App\Enums\RequestStatus::RETURNED->value], true)) ||
                    (auth()->user()->role === 'staff2' && $isFinalStatus)
                )
                    <p class="text-gray-400 italic text-sm">No actions available at this stage.</p>
                @endif
            </div>

            {{-- Comments (Staff only) --}}
            @if(auth()->user()->role !== 'admission')
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Staff Comments</h3>

                {{-- Staff Comments by Role --}}
                @php
                    $staff1Comments = $grantRequest->comments->filter(fn($c) => $c->isStaff1Comment());
                    $staff2Comments = $grantRequest->comments->filter(fn($c) => $c->isStaff2Comment());
                    $deanComments = $grantRequest->comments->filter(fn($c) => $c->isDeanComment());
                    $internalComments = $grantRequest->comments->filter(fn($c) => $c->isInternalComment());
                @endphp

                {{-- Staff 1 Comments --}}
                @if($staff1Comments->count() > 0)
                    <div class="mb-6">
                        <h4 class="font-semibold text-blue-700 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            Staff 1 Notes
                        </h4>
                        @foreach($staff1Comments as $comment)
                            <div class="mb-3 p-3 bg-blue-50 rounded border border-blue-200">
                                <p class="text-xs text-blue-600 mb-1">
                                    <span class="font-bold">{{ $comment->user->name }}</span>
                                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded">Staff 1</span>
                                    · {{ \Carbon\Carbon::parse($comment->created_at)->format('d M Y, h:i A') }}
                                </p>
                                <p class="text-sm text-gray-700">{{ $comment->content }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Staff 2 Comments --}}
                @if($staff2Comments->count() > 0)
                    <div class="mb-6">
                        <h4 class="font-semibold text-purple-700 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            Staff 2 Notes
                        </h4>
                        @foreach($staff2Comments as $comment)
                            <div class="mb-3 p-3 bg-purple-50 rounded border border-purple-200">
                                <p class="text-xs text-purple-600 mb-1">
                                    <span class="font-bold">{{ $comment->user->name }}</span>
                                    <span class="ml-2 px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded">Staff 2</span>
                                    · {{ \Carbon\Carbon::parse($comment->created_at)->format('d M Y, h:i A') }}
                                </p>
                                <p class="text-sm text-gray-700">{{ $comment->content }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Dean Comments --}}
                @if($deanComments->count() > 0)
                    <div class="mb-6">
                        <h4 class="font-semibold text-green-700 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            Dean Notes
                        </h4>
                        @foreach($deanComments as $comment)
                            <div class="mb-3 p-3 bg-green-50 rounded border border-green-200">
                                <p class="text-xs text-green-600 mb-1">
                                    <span class="font-bold">{{ $comment->user->name }}</span>
                                    <span class="ml-2 px-2 py-1 bg-green-100 text-green-700 text-xs rounded">Dean</span>
                                    · {{ \Carbon\Carbon::parse($comment->created_at)->format('d M Y, h:i A') }}
                                </p>
                                <p class="text-sm text-gray-700">{{ $comment->content }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Internal Comments --}}
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        Internal Comments
                    </h4>
                    @forelse($internalComments as $comment)
                        <div class="mb-3 p-3 bg-gray-50 rounded border">
                            <p class="text-xs text-gray-500 mb-1">
                                <span class="font-bold">{{ $comment->user->name }}</span>
                                <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">{{ $comment->user->role }}</span>
                                · {{ \Carbon\Carbon::parse($comment->created_at)->format('d M Y, h:i A') }}
                            </p>
                            <p class="text-sm">{{ $comment->content }}</p>
                        </div>
                    @empty
                        @if($staff1Comments->count() === 0 && $staff2Comments->count() === 0 && $deanComments->count() === 0)
                            <p class="text-gray-400 italic text-sm">No comments yet.</p>
                        @endif
                    @endforelse
                </div>

                @can('addComment', $grantRequest)
                    <div class="border-t pt-4">
                        @if(auth()->user()->role === 'staff1')
                            <p class="text-sm text-blue-600 mb-2">You're posting a Staff 1 Note (visible to all staff)</p>
                        @elseif(auth()->user()->role === 'staff2')
                            <p class="text-sm text-purple-600 mb-2">You're posting a Staff 2 Note (visible to all staff)</p>
                        @elseif(auth()->user()->role === 'dean')
                            <p class="text-sm text-green-600 mb-2">You're posting a Dean Note (visible to all staff)</p>
                        @else
                            <p class="text-sm text-gray-600 mb-2">You're posting an Internal Comment (visible to all staff)</p>
                        @endif
                        <form action="{{ route('requests.comment', $grantRequest->id) }}" method="POST" class="mt-4" onsubmit="return handleFormSubmit(this, 'Posting comment...')">
                            @csrf
                            <textarea name="content" rows="2" 
                                placeholder="{{ auth()->user()->role === 'staff1' ? 'Leave a Staff 1 note for the review team...' : (auth()->user()->role === 'staff2' ? 'Leave a Staff 2 note for the review team...' : (auth()->user()->role === 'dean' ? 'Leave a Dean note for the review team...' : 'Leave an internal comment for the review team...')) }}"
                                class="w-full border rounded p-2 text-sm"></textarea>
                            <button type="submit"
                                class="mt-2 {{ auth()->user()->role === 'staff1' ? 'bg-blue-600 hover:bg-blue-700' : (auth()->user()->role === 'staff2' ? 'bg-purple-600 hover:bg-purple-700' : (auth()->user()->role === 'dean' ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-700 hover:bg-gray-800')) }} text-white px-4 py-2 rounded text-sm font-bold transition-colors">
                                {{ auth()->user()->role === 'staff1' ? 'Post Staff 1 Note' : (auth()->user()->role === 'staff2' ? 'Post Staff 2 Note' : (auth()->user()->role === 'dean' ? 'Post Dean Note' : 'Post Comment')) }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>
            @endif

            {{-- Audit Log (staff only) --}}
            @if(auth()->user()->role !== 'admission')
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Audit Trail</h3>
                @forelse($grantRequest->auditLogs as $log)
                    <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-3 mb-3 text-sm min-w-0">
                        <span class="text-gray-400 sm:w-32 sm:shrink-0">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y') }}
                        </span>
                        <span class="font-semibold sm:w-32 sm:shrink-0 break-words">{{ $log->actor->name }}</span>
                        <span class="text-gray-600 break-words min-w-0">
                            Status
                            {{ \App\Enums\RequestStatus::tryFrom((int) $log->from_status)?->getLabel() ?? $log->from_status }}
                            ->
                            {{ \App\Enums\RequestStatus::tryFrom((int) $log->to_status)?->getLabel() ?? $log->to_status }}
                            @if($log->note) · {{ $log->note }} @endif
                        </span>
                    </div>
                @empty
                    <p class="text-gray-400 italic text-sm">No audit trail yet.</p>
                @endforelse
            </div>
            @endif

            {{-- Back button --}}
            <div class="pb-6">
                <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 text-sm">&lt;- Back to Dashboard</a>
            </div>

        </div>
    </div>

    <script>
        const overrideActionSelect = document.getElementById('override-action-type');
        const reinstateDoubleConfirmation = document.getElementById('reinstate-double-confirmation');

        if (overrideActionSelect && reinstateDoubleConfirmation) {
            const toggleDoubleConfirmation = () => {
                if (overrideActionSelect.value === 'reject_reverse') {
                    reinstateDoubleConfirmation.classList.remove('hidden');
                } else {
                    reinstateDoubleConfirmation.classList.add('hidden');
                }
            };

            overrideActionSelect.addEventListener('change', toggleDoubleConfirmation);
            toggleDoubleConfirmation();
        }

        function isCanvasBlank(canvas) {
            const ctx = canvas?.getContext?.('2d');
            if (!ctx || !canvas) return true;

            const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
            for (let i = 3; i < pixels.length; i += 4) {
                // Any non-white pixel indicates user ink.
                if (pixels[i] !== 0 && !(pixels[i - 3] === 255 && pixels[i - 2] === 255 && pixels[i - 1] === 255)) {
                    return false;
                }
            }
            return true;
        }

        function captureSignatureIfNeeded(canvasId, inputId) {
            const canvas = document.getElementById(canvasId);
            const input = document.getElementById(inputId);
            if (!canvas || !input || input.value) {
                return;
            }

            if (!isCanvasBlank(canvas)) {
                input.value = canvas.toDataURL('image/png');
            }
        }

        function handleFormSubmit(form, message, event) {
            const submitter = event?.submitter;
            const statusFromButton = submitter?.dataset?.status;
            if (statusFromButton) {
                if (form.dataset.roleAction === 'dean') {
                    const deanStatusInput = document.getElementById('dean-status-input');
                    if (deanStatusInput) deanStatusInput.value = statusFromButton;
                }
                if (form.dataset.roleAction === 'staff2') {
                    const staff2StatusInput = document.getElementById('status2-input');
                    if (staff2StatusInput) staff2StatusInput.value = statusFromButton;
                }
            }

            const isDeanActionForm = form.dataset.roleAction === 'dean';
            if (isDeanActionForm) {
                const deanAction = submitter?.dataset?.deanAction;
                if (deanAction) {
                    const deanActionInput = document.getElementById('dean-action-input');
                    if (deanActionInput) deanActionInput.value = deanAction;
                }
            }

            if (form.dataset.roleAction === 'dean') {
                captureSignatureIfNeeded('dean-signature-canvas', 'dean-signature-data');
            } else if (form.dataset.roleAction === 'staff2') {
                captureSignatureIfNeeded('staff2-signature-canvas', 'staff2-signature-data');
            }

            if (isDeanActionForm) {
                const deanAction = document.getElementById('dean-action-input')?.value || 'approve';
                const reasonField = form.querySelector('textarea[name="rejection_reason"]');
                const reasonValue = reasonField?.value?.trim() || '';

                // For reject/return, enforce reason. For return, do not enforce signature.
                if (['reject', 'return_staff1', 'return_staff2'].includes(deanAction) && reasonValue === '') {
                    alert('Please provide a reason for reject/return action.');
                    reasonField?.focus();
                    return false;
                }

                if (['approve', 'reject'].includes(deanAction)) {
                    const signatureValue = document.getElementById('dean-signature-data')?.value;
                    if (!signatureValue) {
                        alert('Please provide your signature before approving or rejecting.');
                        return false;
                    }
                }
            }

            const requiredSignatureInput = form.dataset.signatureInput;
            if (requiredSignatureInput) {
                // Dean return actions intentionally do not require signature.
                if (isDeanActionForm) {
                    const deanAction = document.getElementById('dean-action-input')?.value || 'approve';
                    if (['return_staff1', 'return_staff2'].includes(deanAction)) {
                        // Skip generic signature validation for dean return actions.
                    } else {
                        const signatureValue = document.getElementById(requiredSignatureInput)?.value;
                        if (!signatureValue) {
                            alert('Please provide your signature before submitting.');
                            return false;
                        }
                    }
                } else {
                    const signatureValue = document.getElementById(requiredSignatureInput)?.value;
                    if (!signatureValue) {
                        alert('Please provide your signature before submitting.');
                        return false;
                    }
                }
            }

            const submitButtons = form.querySelectorAll('button[type="submit"]');
            submitButtons.forEach((btn) => {
                btn.disabled = true;
                btn.classList.add('opacity-70', 'cursor-not-allowed');
            });

            const active = document.activeElement;
            if (active && active.tagName && active.tagName.toLowerCase() === 'button' && active.form === form) {
                if (!active.dataset.originalText) {
                    active.dataset.originalText = active.textContent.trim();
                }
                active.textContent = message;
            }

            return true;
        }

        // Signature handling
        class SignaturePad {
            constructor(canvasId, hiddenInputId) {
                this.canvas = document.getElementById(canvasId);
                this.hiddenInput = document.getElementById(hiddenInputId);
                this.isDrawing = false;
                this.ctx = this.canvas.getContext('2d');
                
                this.setupCanvas();
                this.bindEvents();
            }

            setupCanvas() {
                // Set canvas size
                const rect = this.canvas.getBoundingClientRect();
                this.canvas.width = rect.width;
                this.canvas.height = rect.height;
                
                // Set drawing styles
                this.ctx.strokeStyle = '#000';
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
                
                // Fill with white background
                this.ctx.fillStyle = '#fff';
                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
            }

            bindEvents() {
                // Mouse events
                this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
                this.canvas.addEventListener('mousemove', (e) => this.draw(e));
                this.canvas.addEventListener('mouseup', () => this.stopDrawing());
                this.canvas.addEventListener('mouseout', () => this.stopDrawing());
                
                // Touch events
                this.canvas.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    const touch = e.touches[0];
                    const mouseEvent = new MouseEvent('mousedown', {
                        clientX: touch.clientX,
                        clientY: touch.clientY
                    });
                    this.canvas.dispatchEvent(mouseEvent);
                });
                
                this.canvas.addEventListener('touchmove', (e) => {
                    e.preventDefault();
                    const touch = e.touches[0];
                    const mouseEvent = new MouseEvent('mousemove', {
                        clientX: touch.clientX,
                        clientY: touch.clientY
                    });
                    this.canvas.dispatchEvent(mouseEvent);
                });
                
                this.canvas.addEventListener('touchend', (e) => {
                    e.preventDefault();
                    const mouseEvent = new MouseEvent('mouseup', {});
                    this.canvas.dispatchEvent(mouseEvent);
                });
            }

            startDrawing(e) {
                this.isDrawing = true;
                const rect = this.canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                this.ctx.beginPath();
                this.ctx.moveTo(x, y);
            }

            draw(e) {
                if (!this.isDrawing) return;
                
                const rect = this.canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                this.ctx.lineTo(x, y);
                this.ctx.stroke();
            }

            stopDrawing() {
                if (this.isDrawing) {
                    this.isDrawing = false;
                    this.saveSignature();
                }
            }

            saveSignature() {
                const dataURL = this.canvas.toDataURL('image/png');
                this.hiddenInput.value = dataURL;
            }

            clear() {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                this.ctx.fillStyle = '#fff';
                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
                this.hiddenInput.value = '';
            }
        }

        // Initialize signature pads
        let staff2SignaturePad, deanSignaturePad;

        document.addEventListener('DOMContentLoaded', function() {
            // Staff 2 signature
            if (document.getElementById('staff2-signature-canvas')) {
                staff2SignaturePad = new SignaturePad('staff2-signature-canvas', 'staff2-signature-data');
            }

            // Dean signature
            if (document.getElementById('dean-signature-canvas')) {
                deanSignaturePad = new SignaturePad('dean-signature-canvas', 'dean-signature-data');
            }
        });

        function clearStaff2Signature() {
            if (staff2SignaturePad) staff2SignaturePad.clear();
        }

        function clearDeanSignature() {
            if (deanSignaturePad) deanSignaturePad.clear();
        }
    </script>
</x-app-layout>

