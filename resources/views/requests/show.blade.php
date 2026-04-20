<x-app-layout>
    @php
        $isFinalStatus  = $grantRequest->isFinal();
        $status         = $grantRequest->status_id;
        $RS             = \App\Enums\RequestStatus::class;
        $staff1Active   = $status === $RS::SUBMITTED->value;
        $staff1Complete = in_array($status, [$RS::STAFF1_REVIEWED->value, $RS::STAFF2_APPROVED->value, $RS::COMPLETED->value]);
        $staff2Active   = $status === $RS::STAFF1_REVIEWED->value;
        $staff2Complete = in_array($status, [$RS::STAFF2_APPROVED->value, $RS::COMPLETED->value]);
        $finalDone      = $status === $RS::COMPLETED->value;
        $isReturned     = $status === $RS::RETURNED->value;
        $isDeclined     = $status === $RS::DECLINED->value;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight min-w-0 break-words">
                Request: {{ $grantRequest->ref_number }}
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
                <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
    Request Details
</div>
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
                                
                                @if($isDeclined)
                                    <div class="mt-2 p-2 bg-red-50 rounded border border-red-200">
                                        <p class="text-sm text-red-800">
                                            <span class="font-medium">Declined:</span>
                                            {{ $grantRequest->decline_reason ?? 'No reason provided.' }}
                                        </p>
                                    </div>
                                @endif
                                @if($isReturned)
                                    <div class="mt-2 p-2 bg-yellow-50 rounded border border-yellow-200">
                                        <p class="text-sm text-yellow-800">
                                            <span class="font-medium">Returned for revision:</span>
                                            {{ $grantRequest->return_reason ?? 'No reason provided.' }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Step 4: Completed (Staff1 manual processing) -->
                        <div class="flex items-center">
                            <div class="w-16 h-16 {{ $finalDone ? 'bg-teal-500' : 'bg-gray-300' }} rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900">Completed</h4>
                                <p class="text-sm text-gray-600">Processed and completed by Staff 1</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 1: System-Generated Template --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
               <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
 Generate forms
</div>
                @if($systemTemplate)
                <p class="text-xs text-gray-500 mb-4">Template: <span class="italic">{{ $systemTemplate->title }}</span></p>
                @endif

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

            {{-- Section 2: Supporting Documents (admin-uploaded, linked to request type) --}}
            @if($supportingDocuments->isNotEmpty())
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
  Supporting Documents
</div>
                <p class="text-xs text-gray-500 mb-4">
                    Reference documents provided for <span class="font-medium">{{ $grantRequest->requestType?->name }}</span>.
                </p>
                <ul class="space-y-2 text-sm">
                    @foreach($supportingDocuments as $doc)
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <a href="{{ asset('storage/' . $doc->file_path) }}"
                               target="_blank"
                               class="text-blue-600 hover:underline font-medium">
                                {{ $doc->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Applicant-Uploaded Documents --}}
            @php
                $mainDocumentUrl = $grantRequest->file_path ? route('requests.document.main', $grantRequest->id) : null;
                $additionalDocuments = collect($grantRequest->payload['additional_documents'] ?? [])
                    ->filter(fn ($path) => is_string($path) && $path !== '')
                    ->values();
            @endphp
            @if($mainDocumentUrl || $additionalDocuments->isNotEmpty())
            <div class="bg-white shadow-sm rounded-lg p-6">
               <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
  Apllicant Uploaded Documents
</div>
                <p class="text-xs text-gray-500 mb-4">Files submitted by the applicant with this request.</p>

                @if($mainDocumentUrl)
                    @php $ext = pathinfo($grantRequest->file_path, PATHINFO_EXTENSION); @endphp
                    <div class="mb-3">
                        @if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png']))
                            <img src="{{ $mainDocumentUrl }}" class="max-w-full rounded border" alt="Uploaded document">
                        @elseif(strtolower($ext) === 'pdf')
                            <iframe src="{{ $mainDocumentUrl }}" class="w-full h-96 border rounded" title="PDF Viewer"></iframe>
                        @endif
                        <a href="{{ $mainDocumentUrl }}" target="_blank"
                           class="mt-2 inline-block text-blue-600 hover:underline text-sm font-semibold">
                            ↗ Open main document
                        </a>
                    </div>
                @endif

                @if($additionalDocuments->isNotEmpty())
                    <ul class="space-y-2 text-sm mt-2">
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
                @endif
            </div>
            @endif

            {{-- Rejection Reason (visible to admission) --}}
            @if($grantRequest->rejection_reason)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
               <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
  ⚠ Returned / Rejected — Reason:
</div>
                <p class="text-red-600 text-sm break-words">{{ $grantRequest->rejection_reason }}</p>
            </div>
            @endif

            {{-- Staff Notes (staff only) --}}
           @if($grantRequest->staff_notes && in_array(auth()->user()->role, ['staff1', 'staff2']))
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
    Internal Staff Notes
</div>
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
                       <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
    staff notes history
</div>
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
                <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
    verification trail
</div>
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
                <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
    Workflow Timeline
</div>

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
               <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
    Actions
</div>

                {{-- ADMISSION: Edit if returned --}}
                @can('revise', $grantRequest)
                    <a href="{{ route('requests.edit', $grantRequest->id) }}"
                       class="inline-block bg-yellow-500 text-white px-6 py-2 rounded font-bold hover:bg-yellow-600">
                        &#9999 Edit & Resubmit
                    </a>
                @endcan

                {{-- Staff1 Checklist Review --}}
                @if(auth()->user()->isStaff1() && $staff1Active)
                    <x-checklist-review :request="$grantRequest" />
                @endif

                {{-- STAFF 1: SUBMITTED &#8594; review/return/decline --}}
                @can('changeStatus', $grantRequest)
                    @if(auth()->user()->role === 'staff1' && $staff1Active)
                        <form action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3" onsubmit="return handleFormSubmit(this, 'Submitting...')">
                            @csrf
                            @method('PATCH')
                            <textarea name="notes" rows="2" placeholder="Internal notes (optional)" class="w-full border rounded p-2 text-sm"></textarea>
                            <div id="s1-reason-field" class="hidden">
                                <textarea name="return_reason" id="s1-return-reason" rows="2" placeholder="Reason for returning (required)" class="w-full border rounded p-2 text-sm"></textarea>
                                <textarea name="decline_reason" id="s1-decline-reason" rows="2" placeholder="Reason for declining (required)" class="w-full border rounded p-2 text-sm hidden"></textarea>
                            </div>
                            <input type="hidden" name="status_id" id="s1-status" value="{{ \App\Enums\RequestStatus::STAFF1_REVIEWED->value }}">
                            <div class="flex gap-3 flex-wrap">
                                <button type="submit" onclick="setS1Status('{{ \App\Enums\RequestStatus::STAFF1_REVIEWED->value }}', 'approve')"
                                    class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700">
                                    ✓ Verify & Send to Staff 2
                                </button>
                                <button type="submit" onclick="setS1Status('{{ \App\Enums\RequestStatus::RETURNED->value }}', 'return')"
                                    class="bg-yellow-500 text-white px-6 py-2 rounded font-bold hover:bg-yellow-600">
                                    ↩ Return for Revision
                                </button>
                                <button type="submit" onclick="setS1Status('{{ \App\Enums\RequestStatus::DECLINED->value }}', 'decline')"
                                    class="bg-red-600 text-white px-6 py-2 rounded font-bold hover:bg-red-700">
                                    ✕ Decline
                                </button>
                            </div>
                        </form>
                    @endif
                @endcan

                {{-- STAFF 1: STAFF2_APPROVED → mark complete --}}
                @can('changeStatus', $grantRequest)
                    @if(auth()->user()->role === 'staff1' && $grantRequest->status_id === \App\Enums\RequestStatus::STAFF2_APPROVED->value)
                        <form action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3" onsubmit="return handleFormSubmit(this, 'Marking complete...')">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status_id" value="{{ \App\Enums\RequestStatus::COMPLETED->value }}">
                            <textarea name="notes" rows="2" placeholder="Completion notes (optional)" class="w-full border rounded p-2 text-sm"></textarea>
                            <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded font-bold hover:bg-teal-700">
                                ✓ Mark as Completed
                            </button>
                        </form>
                    @endif
                @endcan

                {{-- STAFF 2: STAFF1_REVIEWED or SUBMITTED (override) → approve/return/decline --}}
                @can('changeStatus', $grantRequest)
                    @if(auth()->user()->role === 'staff2' && $staff2Active)
                        <form action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3" onsubmit="return handleFormSubmit(this, 'Submitting...', event)" data-signature-input="staff2-signature-data" data-role-action="staff2">
                            @csrf
                            @method('PATCH')
                            <textarea name="notes" rows="2" placeholder="Recommendation notes (optional)" class="w-full border rounded p-2 text-sm"></textarea>
                            <div id="s2-reason-field" class="hidden">
                                <textarea name="return_reason" id="s2-return-reason" rows="2" placeholder="Reason for returning (required)" class="w-full border rounded p-2 text-sm"></textarea>
                                <textarea name="decline_reason" id="s2-decline-reason" rows="2" placeholder="Reason for declining (required)" class="w-full border rounded p-2 text-sm hidden"></textarea>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-green-700">Staff 2 Signature (required to approve):</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-2 bg-gray-50">
                                    <canvas id="staff2-signature-canvas" width="400" height="150" class="w-full border border-gray-300 rounded bg-white cursor-crosshair"></canvas>
                                </div>
                                <button type="button" onclick="clearStaff2Signature()" class="text-xs bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">Clear</button>
                                <input type="hidden" name="staff2_signature_data" id="staff2-signature-data">
                            </div>
                            <input type="hidden" name="status_id" id="s2-status" value="{{ \App\Enums\RequestStatus::STAFF2_APPROVED->value }}">
                            <div class="flex gap-3 flex-wrap">
                                <button type="submit" onclick="setS2Status('{{ \App\Enums\RequestStatus::STAFF2_APPROVED->value }}', 'approve')"
                                    class="bg-green-600 text-white px-6 py-2 rounded font-bold hover:bg-green-700">
                                    ✓ Approve
                                </button>
                                <button type="submit" onclick="setS2Status('{{ \App\Enums\RequestStatus::RETURNED->value }}', 'return')"
                                    class="bg-yellow-500 text-white px-6 py-2 rounded font-bold hover:bg-yellow-600">
                                    ↩ Return for Revision
                                </button>
                                <button type="submit" onclick="setS2Status('{{ \App\Enums\RequestStatus::DECLINED->value }}', 'decline')"
                                    class="bg-red-600 text-white px-6 py-2 rounded font-bold hover:bg-red-700">
                                    ✕ Decline
                                </button>
                            </div>
                        </form>
                    @endif
                @endcan

                {{-- STAFF 2: Document upload for this request --}}
                @if(auth()->user()->role === 'staff2')
                    <div class="mt-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
                        <h4 class="font-bold text-green-800 mb-3">Upload Document for User</h4>
                        <form action="{{ route('documents.store', $grantRequest->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="block text-sm text-gray-600" required>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_template" value="1">
                                Mark as fillable template for user
                            </label>
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-green-700">Upload</button>
                        </form>
                        @if($grantRequest->documents->where('uploader_role', 'staff2')->count() > 0)
                            <div class="mt-4 space-y-2">
                                @foreach($grantRequest->documents->where('uploader_role', 'staff2') as $doc)
                                    <div class="flex items-center justify-between bg-white rounded p-2 border">
                                        <span class="text-sm text-gray-700 truncate max-w-xs" title="{{ $doc->original_name }}">
                                            {{ $doc->original_name }}
                                            @if($doc->is_template) <span class="text-xs bg-blue-100 text-blue-700 px-1 rounded ml-1">Template</span> @endif
                                        </span>
                                        <div class="flex gap-2">
                                            <a href="{{ route('documents.download', $doc->id) }}" class="text-xs text-blue-600 hover:underline">Download</a>
                                            <form action="{{ route('documents.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Delete this document?')">
                                                @csrf @method('DELETE')
                                                <button class="text-xs text-red-600 hover:underline">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Admission: show Staff2 uploaded documents for download --}}
                @if(auth()->user()->role === 'admission' && $grantRequest->documents->where('uploader_role', 'staff2')->count() > 0)
                    <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-400 rounded">
                        <h4 class="font-bold text-blue-800 mb-2">Documents from Staff 2</h4>
                        <ul class="space-y-2">
                            @foreach($grantRequest->documents->where('uploader_role', 'staff2') as $doc)
                                <li class="flex items-center justify-between text-sm">
                                    <span>{{ $doc->original_name }} @if($doc->is_template)<span class="text-xs bg-blue-200 text-blue-800 px-1 rounded ml-1">Fillable</span>@endif</span>
                                    <a href="{{ route('documents.download', $doc->id) }}" class="text-blue-600 hover:underline font-medium">Download</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Admission: returned request action --}}
                @if(auth()->user()->role === 'admission' && $isReturned)
                    <div class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                        <p class="font-semibold text-yellow-800 mb-2">This request has been returned for revision.</p>
                        @if($grantRequest->return_reason)
                            <p class="text-sm text-yellow-700 mb-3">Reason: {{ $grantRequest->return_reason }}</p>
                        @endif
                        <a href="{{ route('requests.edit', $grantRequest->id) }}"
                           class="inline-block bg-yellow-600 text-white px-5 py-2 rounded font-bold hover:bg-yellow-700">
                            Edit & Resubmit
                        </a>
                    </div>
                @endif

                {{-- No actions available --}}
                @if(
                    (auth()->user()->role === 'admission' && !$isReturned) ||
                    (auth()->user()->role === 'staff1' && !$staff1Active && $grantRequest->status_id !== \App\Enums\RequestStatus::STAFF2_APPROVED->value) ||
                    (auth()->user()->role === 'staff2' && !$staff2Active) ||
                    $isFinalStatus
                )
                    <p class="text-gray-400 italic text-sm">No actions available at this stage.</p>
                @endif
            </div>

            {{-- Comments (Staff only) --}}
            @if(auth()->user()->role !== 'admission')
            <div class="bg-white shadow-sm rounded-lg p-6">
               <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
    Staff Comments
</div>

                {{-- Staff Comments by Role --}}
                @php
                    $staff1Comments   = $grantRequest->comments->filter(fn($c) => $c->isStaff1Comment());
                    $staff2Comments   = $grantRequest->comments->filter(fn($c) => $c->isStaff2Comment());
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
                <div class="card-header-miit -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">
    Audit Trail
</div>
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
        // Staff1 action helpers
        function setS1Status(statusVal, action) {
            document.getElementById('s1-status').value = statusVal;
            const reasonField = document.getElementById('s1-reason-field');
            const returnReason  = document.getElementById('s1-return-reason');
            const declineReason = document.getElementById('s1-decline-reason');
            if (action === 'return') {
                reasonField.classList.remove('hidden');
                returnReason.classList.remove('hidden');
                declineReason.classList.add('hidden');
                returnReason.required = true;
                declineReason.required = false;
            } else if (action === 'decline') {
                reasonField.classList.remove('hidden');
                returnReason.classList.add('hidden');
                declineReason.classList.remove('hidden');
                returnReason.required = false;
                declineReason.required = true;
            } else {
                reasonField.classList.add('hidden');
                returnReason.required = false;
                declineReason.required = false;
            }
        }

        // Staff2 action helpers
        function setS2Status(statusVal, action) {
            document.getElementById('s2-status').value = statusVal;
            const reasonField   = document.getElementById('s2-reason-field');
            const returnReason  = document.getElementById('s2-return-reason');
            const declineReason = document.getElementById('s2-decline-reason');
            if (action === 'return') {
                reasonField.classList.remove('hidden');
                returnReason.classList.remove('hidden');
                declineReason.classList.add('hidden');
                returnReason.required = true;
                declineReason.required = false;
            } else if (action === 'decline') {
                reasonField.classList.remove('hidden');
                returnReason.classList.add('hidden');
                declineReason.classList.remove('hidden');
                returnReason.required = false;
                declineReason.required = true;
            } else {
                reasonField.classList.add('hidden');
                returnReason.required = false;
                declineReason.required = false;
            }
        }

        function captureSignatureIfNeeded(canvasId, inputId) {
            const canvas = document.getElementById(canvasId);
            const input  = document.getElementById(inputId);
            if (!canvas || !input || input.value) return;
            const ctx    = canvas.getContext('2d');
            const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
            const blank  = !Array.from(pixels).some((v, i) => i % 4 === 3 && v > 0);
            if (!blank) input.value = canvas.toDataURL('image/png');
        }

        function handleFormSubmit(form, message, event) {
            if (form.dataset.roleAction === 'staff2') {
                captureSignatureIfNeeded('staff2-signature-canvas', 'staff2-signature-data');
                const currentStatus = document.getElementById('s2-status')?.value;
                const approveStatus = '{{ \App\Enums\RequestStatus::STAFF2_APPROVED->value }}';
                if (currentStatus === approveStatus && !document.getElementById('staff2-signature-data')?.value) {
                    alert('Please provide your signature before approving.');
                    return false;
                }
            }

            const submitButtons = form.querySelectorAll('button[type="submit"]');
            submitButtons.forEach(btn => { btn.disabled = true; btn.classList.add('opacity-70', 'cursor-not-allowed'); });

            const active = document.activeElement;
            if (active?.tagName?.toLowerCase() === 'button' && active.form === form) {
                active.dataset.originalText = active.textContent.trim();
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

