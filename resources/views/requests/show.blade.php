<x-app-layout>
    @php
        $isFinalStatus  = $grantRequest->isFinal();
        $status         = $grantRequest->status_id;
        $RS             = \App\Enums\RequestStatus::class;
        $staff1Active   = $status === $RS::SUBMITTED->value;
        $staff1Complete = in_array($status, [$RS::STAFF1_REVIEWED->value, $RS::STAFF2_APPROVED->value, $RS::COMPLETED->value]);
        $staff1Completed = $staff1Complete;
        $staff2Active   = in_array($status, [$RS::STAFF1_REVIEWED->value, $RS::SUBMITTED->value]);
        $staff2Complete = in_array($status, [$RS::STAFF2_APPROVED->value, $RS::COMPLETED->value]);
        $staff2Completed = $staff2Complete;
        $finalDone      = $status === $RS::COMPLETED->value;
        $isReturned     = $status === $RS::RETURNED->value;
        $isDeclined     = $status === $RS::DECLINED->value;
        $isStaff        = in_array(auth()->user()->role, ['staff1', 'staff2', 'admin']);
        $userSubmissions = $grantRequest->documents->where('document_type', \App\Enums\DocumentType::UserSubmission);
        $signedDocument  = $grantRequest->signedDocument;
        $applicantSignature = $grantRequest->getSignatureImageForRole('applicant');
        $requiresVot    = $grantRequest->requestType?->requires_vot;
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

    {{-- Document Preview Modal --}}
    <div id="doc-preview-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60" onclick="if(event.target===this)closePreview()">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 flex flex-col" style="height:85vh;">
            <div class="flex items-center justify-between px-5 py-3 border-b">
                <span id="doc-preview-title" class="font-semibold text-gray-800 truncate max-w-lg"></span>
                <div class="flex items-center gap-3">
                    <a id="doc-preview-download" href="#" class="text-xs font-medium text-blue-600 hover:underline">Download</a>
                    <button onclick="closePreview()" class="text-gray-400 hover:text-gray-700 text-xl font-bold leading-none">&times;</button>
                </div>
            </div>
            <div class="flex-1 overflow-hidden">
                <iframe id="doc-preview-frame" src="" class="w-full h-full border-0"></iframe>
            </div>
        </div>
    </div>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 overflow-x-hidden">

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
                <div class="card-header-brand -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">Request Details</div>
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
                        {{ $grantRequest->description ?? $grantRequest->payload['description'] ?? 'No description provided.' }}
                    </div>
                </div>

                @if($requiresVot)
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
                @endif

                {{-- Applicant Signature --}}
                @if($applicantSignature)
                <div class="mt-4">
                    <p class="text-gray-500 text-sm mb-2">Applicant Signature</p>
                    <div class="inline-block border rounded p-2 bg-white">
                        <img src="{{ $applicantSignature }}" alt="Applicant Signature" class="max-h-24 max-w-xs">
                    </div>
                </div>
                @endif
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
                    <div class="absolute left-8 top-8 bottom-8 w-0.5 bg-gray-300"></div>

                    <div class="space-y-8">
                        <div class="flex items-center">
                            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900">Submitted</h4>
                                <p class="text-sm text-gray-600">{{ $grantRequest->user->name }} · {{ $grantRequest->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div class="w-16 h-16 {{ $staff1Completed ? 'bg-green-500' : ($staff1Active ? 'bg-blue-500' : 'bg-gray-300') }} rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900">Staff 1 Verification
                                    <span class="ml-2 text-xs font-normal {{ $staff1Active ? 'text-blue-600' : ($staff1Completed ? 'text-green-600' : 'text-gray-400') }}">
                                        {{ $staff1Active ? 'Pending' : ($staff1Completed ? 'Done' : 'Waiting') }}
                                    </span>
                                </h4>
                                @if($grantRequest->verifiedBy)
                                    <p class="text-sm text-gray-600">{{ $grantRequest->verifiedBy->name }}@if($grantRequest->verified_at) · {{ $grantRequest->verified_at->format('d M Y, h:i A') }}@endif</p>
                                @else
                                    <p class="text-sm text-gray-400">Not yet reviewed</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="w-16 h-16 shrink-0 {{ $staff2Completed ? 'bg-green-500' : ($staff2Active ? 'bg-blue-500' : 'bg-gray-300') }} rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900">Staff 2 Approval
                                    <span class="ml-2 text-xs font-normal {{ $staff2Active ? 'text-blue-600' : ($staff2Completed ? 'text-green-600' : 'text-gray-400') }}">
                                        {{ $staff2Active ? 'Pending' : ($staff2Completed ? 'Done' : 'Waiting') }}
                                    </span>
                                </h4>
                                @if($grantRequest->recommendedBy)
                                    <p class="text-sm text-gray-600">{{ $grantRequest->recommendedBy->name }}@if($grantRequest->recommended_at) · {{ $grantRequest->recommended_at->format('d M Y, h:i A') }}@endif</p>
                                @else
                                    <p class="text-sm text-gray-400">Not yet reviewed</p>
                                @endif
                                @if($isDeclined)
                                    <div class="mt-2 p-2 bg-red-50 rounded border border-red-200 text-sm text-red-800">
                                        <span class="font-medium">Declined:</span> {{ $grantRequest->decline_reason ?? 'No reason provided.' }}
                                    </div>
                                @endif
                                @if($isReturned)
                                    <div class="mt-2 p-2 bg-yellow-50 rounded border border-yellow-200 text-sm text-yellow-800">
                                        <span class="font-medium">Returned for revision:</span> {{ $grantRequest->return_reason ?? 'No reason provided.' }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div class="w-16 h-16 {{ $finalDone ? 'bg-teal-500' : 'bg-gray-300' }} rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900">Completed</h4>
                                <p class="text-sm text-gray-400">{{ $finalDone ? 'Request has been processed and completed.' : 'Awaiting completion by Staff 1.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Supporting Documents (templates from Staff 2) --}}
            @if($supportingDocuments->isNotEmpty())
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="card-header-brand -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">Supporting Documents</div>
                <p class="text-xs text-gray-500 mb-4">
                    Reference documents provided for <span class="font-medium">{{ $grantRequest->requestType?->name }}</span>.
                </p>
                <ul class="space-y-2 text-sm">
                    @foreach($supportingDocuments as $doc)
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span class="font-medium text-gray-800">{{ $doc->name ?: $doc->original_name }}</span>
                            <a href="{{ route('documents.preview', $doc->id) }}" target="_blank" class="text-blue-600 hover:underline text-xs">Preview</a>
                            <a href="{{ route('documents.download', $doc->id) }}" class="text-gray-500 hover:underline text-xs">Download</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Applicant-Uploaded Documents --}}
            @if($userSubmissions->isNotEmpty())
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="card-header-brand -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">Applicant Uploaded Documents</div>
                <p class="text-xs text-gray-500 mb-4">Files submitted by the applicant with this request.</p>
                <ul class="space-y-3">
                    @foreach($userSubmissions as $doc)
                        <li class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex items-center gap-2 min-w-0">
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span class="truncate font-medium text-gray-800" title="{{ $doc->original_name }}">{{ $doc->original_name }}</span>
                            </span>
                            <span class="flex items-center gap-2 shrink-0">
                                @if($isStaff)
                                    <button type="button"
                                        onclick="openPreview('{{ route('documents.preview', $doc->id) }}', '{{ addslashes($doc->original_name) }}', '{{ route('documents.download', $doc->id) }}')"
                                        class="text-xs text-blue-600 hover:underline font-medium">Preview</button>
                                @endif
                                <a href="{{ route('documents.download', $doc->id) }}" class="text-xs text-gray-600 hover:underline">Download</a>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Staff 2 uploaded documents (visible to admission) --}}
            @if(auth()->user()->role === 'admission' && $grantRequest->documents->where('uploader_role', 'staff2')->count() > 0)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-bold text-blue-800 mb-3">Documents from Staff 2</h4>
                <ul class="space-y-2">
                    @foreach($grantRequest->documents->where('uploader_role', 'staff2') as $doc)
                        <li class="flex items-center justify-between text-sm">
                            <span>{{ $doc->original_name }}</span>
                            <a href="{{ route('documents.download', $doc->id) }}" class="text-blue-600 hover:underline font-medium">Download</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Workflow Timeline (status events + internal comments) --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="card-header-brand -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">Activity Log</div>

                @php
                    $statusLabels = \App\Enums\RequestStatus::getAllCases();
                    $events = collect();

                    foreach (($grantRequest->auditLogs ?? collect()) as $log) {
                        $events->push([
                            'type'  => 'status',
                            'at'    => $log->created_at,
                            'actor' => $log->actor?->name ?? 'Unknown',
                            'from'  => $log->from_status,
                            'to'    => $log->to_status,
                            'note'  => $isStaff ? $log->note : null,
                        ]);
                    }

                    if ($isStaff) {
                        foreach (($grantRequest->comments ?? collect()) as $comment) {
                            $events->push([
                                'type'  => 'comment',
                                'at'    => $comment->created_at,
                                'actor' => $comment->user?->name ?? 'Unknown',
                                'note'  => $comment->content,
                            ]);
                        }
                    }

                    $events = $events->sortBy('at');
                @endphp

                @if($events->count() === 0)
                    <p class="text-gray-400 italic text-sm">No activity yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($events as $event)
                            <div class="flex gap-3">
                                <div class="mt-1.5 shrink-0">
                                    <div class="w-2.5 h-2.5 rounded-full {{ $event['type'] === 'status' ? 'bg-blue-500' : 'bg-gray-400' }}"></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline gap-2 text-xs text-gray-500 flex-wrap">
                                        <span>{{ \Carbon\Carbon::parse($event['at'])->format('d M Y, h:i A') }}</span>
                                        <span class="font-semibold text-gray-700">{{ $event['actor'] }}</span>
                                    </div>
                                    @if($event['type'] === 'status')
                                        <p class="mt-0.5 text-sm text-gray-800">
                                            {{ $statusLabels[$event['from']] ?? $event['from'] }}
                                            &rarr;
                                            <span class="font-semibold">{{ $statusLabels[$event['to']] ?? $event['to'] }}</span>
                                        </p>
                                        @if(!empty($event['note']))
                                            <p class="mt-0.5 text-sm text-gray-600 italic">{{ $event['note'] }}</p>
                                        @endif
                                    @else
                                        <p class="mt-0.5 text-sm text-gray-800"><span class="font-semibold">Comment:</span> {{ \Illuminate\Support\Str::limit($event['note'] ?? '', 200) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Document Review Panel (Staff only) --}}
            @if($isStaff && ($signedDocument || $userSubmissions->isNotEmpty() || $supportingDocuments->isNotEmpty()))
            <div class="bg-white shadow-sm rounded-lg overflow-hidden" id="doc-review-panel">
                <div class="card-header-brand px-6 py-3 flex items-center justify-between">
                    <span>Document Review</span>
                    <button type="button" onclick="toggleDocReview()"
                        class="text-xs text-white/80 hover:text-white font-medium flex items-center gap-1">
                        <span id="doc-review-toggle-label">Hide</span>
                        <svg id="doc-review-chevron" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                        </svg>
                    </button>
                </div>
                <div id="doc-review-body">
                    <div class="flex h-[70vh] min-h-[400px]">
                        {{-- Left: doc list --}}
                        <div class="w-64 shrink-0 border-r border-gray-200 overflow-y-auto bg-gray-50 p-3 space-y-1">
                            @if($signedDocument)
                                <p class="text-xs font-bold text-teal-700 uppercase mb-2">Signed Document</p>
                                <button type="button" data-signed="1"
                                    onclick="loadDocReview('{{ route('documents.preview', $signedDocument->id) }}', 'Signed — {{ addslashes($grantRequest->ref_number) }}', '{{ route('documents.download', $signedDocument->id) }}', true)"
                                    class="doc-review-btn w-full text-left px-3 py-2 rounded text-xs font-semibold text-teal-800 bg-teal-50 border border-teal-200 hover:bg-teal-100 flex items-center gap-2 mb-3">
                                    <svg class="w-3.5 h-3.5 shrink-0 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Signed Document</span>
                                </button>
                            @endif
                            @if($userSubmissions->isNotEmpty())
                                <p class="text-xs font-bold text-gray-500 uppercase mb-2">Applicant Documents</p>
                                @foreach($userSubmissions as $doc)
                                    @php $isPdf = strtolower(pathinfo($doc->original_name, PATHINFO_EXTENSION)) === 'pdf'; @endphp
                                    <button type="button"
                                        onclick="loadDocReview('{{ route('documents.preview', $doc->id) }}', '{{ addslashes($doc->original_name) }}', '{{ route('documents.download', $doc->id) }}', {{ $isPdf ? 'true' : 'false' }})"
                                        class="doc-review-btn w-full text-left px-3 py-2 rounded text-xs font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-700 flex items-start gap-2 group">
                                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="break-words leading-tight">{{ $doc->original_name }}</span>
                                    </button>
                                @endforeach
                            @endif

                            @if($supportingDocuments->isNotEmpty())
                                <p class="text-xs font-bold text-gray-500 uppercase mt-3 mb-2">Reference Templates</p>
                                @foreach($supportingDocuments as $doc)
                                    @php $isPdf = strtolower(pathinfo($doc->original_name, PATHINFO_EXTENSION)) === 'pdf'; @endphp
                                    <button type="button"
                                        onclick="loadDocReview('{{ route('documents.preview', $doc->id) }}', '{{ addslashes($doc->name ?: $doc->original_name) }}', '{{ route('documents.download', $doc->id) }}', {{ $isPdf ? 'true' : 'false' }})"
                                        class="doc-review-btn w-full text-left px-3 py-2 rounded text-xs font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-700 flex items-start gap-2 group">
                                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-indigo-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span class="break-words leading-tight">{{ $doc->name ?: $doc->original_name }}</span>
                                    </button>
                                @endforeach
                            @endif
                        </div>

                        {{-- Right: viewer --}}
                        <div class="flex-1 flex flex-col bg-gray-100">
                            <div id="doc-review-header" class="hidden px-4 py-2 bg-white border-b border-gray-200 flex items-center justify-between gap-3">
                                <span id="doc-review-name" class="text-sm font-semibold text-gray-800 truncate"></span>
                                <a id="doc-review-dl" href="#" class="shrink-0 text-xs text-blue-600 hover:underline font-medium">↓ Download</a>
                            </div>
                            <div id="doc-review-placeholder" class="flex-1 flex items-center justify-center text-gray-400 text-sm">
                                Select a document from the list to preview it here
                            </div>
                            <iframe id="doc-review-frame" class="flex-1 w-full border-0 hidden" src=""></iframe>
                            <div id="doc-review-nopreview" class="hidden flex-1 flex flex-col items-center justify-center gap-4 text-gray-500">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-sm">Preview not available for this file type.</p>
                                <a id="doc-review-dl2" href="#" class="px-4 py-2 bg-blue-600 text-white text-sm rounded font-semibold hover:bg-blue-700">↓ Download File</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- WORKFLOW ACTIONS --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="card-header-brand -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">Actions</div>

                {{-- Signed document download (Staff 1 & Staff 2 only) --}}
                @if($isStaff && $grantRequest->signed_document_id)
                    <div class="mb-5 p-4 bg-teal-50 border border-teal-300 rounded-lg flex items-center justify-between gap-4">
                        <div>
                            <p class="font-bold text-teal-800 text-sm">Signed document ready</p>
                            <p class="text-xs text-teal-600 mt-0.5">Both signatures have been embedded. Download to print and process offline.</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button"
                                onclick="loadDocReview('{{ route('documents.preview', $grantRequest->signed_document_id) }}', 'Signed Document', '{{ route('documents.download', $grantRequest->signed_document_id) }}', true); document.getElementById('doc-review-panel')?.scrollIntoView({behavior:'smooth'})"
                                class="bg-white border border-teal-400 text-teal-700 px-4 py-2 rounded font-bold text-sm hover:bg-teal-50">
                                Preview
                            </button>
                            <a href="{{ route('documents.download', $grantRequest->signed_document_id) }}"
                               class="bg-teal-600 text-white px-5 py-2 rounded font-bold text-sm hover:bg-teal-700">
                                ↓ Download
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Admission: returned --}}
                @if(auth()->user()->role === 'admission' && $isReturned)
                    <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded mb-4">
                        <p class="font-semibold text-yellow-800 mb-2">This request was returned for revision.</p>
                        @if($grantRequest->return_reason)
                            <p class="text-sm text-yellow-700 mb-3">Reason: {{ $grantRequest->return_reason }}</p>
                        @endif
                        <a href="{{ route('requests.edit', $grantRequest->id) }}"
                           class="inline-block bg-yellow-600 text-white px-5 py-2 rounded font-bold hover:bg-yellow-700">
                            Edit &amp; Resubmit
                        </a>
                    </div>
                @endif

                {{-- Staff1: checklist + review actions --}}
                @if(auth()->user()->isStaff1() && $staff1Active)
                    <x-checklist-review :request="$grantRequest" />

                    @can('changeStatus', $grantRequest)
                    <form id="staff1-action-form" action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3 mt-4" onsubmit="return handleFormSubmit(this, 'Submitting...')">
                        @csrf
                        @method('PATCH')
                        <textarea name="notes" rows="2" placeholder="Internal notes (optional)" class="w-full border rounded p-2 text-sm"></textarea>
                        <div id="s1-reason-field" class="hidden space-y-2">
                            <textarea name="return_reason" id="s1-return-reason" rows="2" placeholder="Reason for returning (required)" class="w-full border rounded p-2 text-sm"></textarea>
                            <textarea name="decline_reason" id="s1-decline-reason" rows="2" placeholder="Reason for declining (required)" class="w-full border rounded p-2 text-sm hidden"></textarea>
                        </div>
                        <input type="hidden" name="status_id" id="s1-status" value="{{ \App\Enums\RequestStatus::STAFF1_REVIEWED->value }}">
                        <div class="flex gap-3 flex-wrap">
                            <button type="submit" onclick="setS1Status('{{ \App\Enums\RequestStatus::STAFF1_REVIEWED->value }}', 'approve')"
                                class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700">
                                ✓ Verify &amp; Send to Staff 2
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
                    @endcan
                @endif

                {{-- Staff 1: mark complete after Staff2 approves --}}
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

                {{-- Staff 2: approve / return / decline --}}
                @can('changeStatus', $grantRequest)
                    @if(auth()->user()->role === 'staff2' && $staff2Active)
                        <form action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3" onsubmit="return handleFormSubmit(this, 'Submitting...', event)" data-signature-input="staff2-signature-data" data-role-action="staff2">
                            @csrf
                            @method('PATCH')
                            <textarea name="notes" rows="2" placeholder="Recommendation notes (optional)" class="w-full border rounded p-2 text-sm"></textarea>
                            <div id="s2-reason-field" class="hidden space-y-2">
                                <textarea name="return_reason" id="s2-return-reason" rows="2" placeholder="Reason for returning (required)" class="w-full border rounded p-2 text-sm"></textarea>
                                <textarea name="decline_reason" id="s2-decline-reason" rows="2" placeholder="Reason for declining (required)" class="w-full border rounded p-2 text-sm hidden"></textarea>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-green-700">Signature (required to approve):</label>
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

                {{-- Staff 2: upload document for applicant --}}
                @if(auth()->user()->role === 'staff2')
                    <div class="mt-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
                        <h4 class="font-bold text-green-800 mb-3">Upload Document for Applicant</h4>
                        <form action="{{ route('documents.store', $grantRequest->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="block text-sm text-gray-600" required>
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-green-700">Upload</button>
                        </form>
                        @if($grantRequest->documents->where('uploader_role', 'staff2')->count() > 0)
                            <div class="mt-4 space-y-2">
                                @foreach($grantRequest->documents->where('uploader_role', 'staff2') as $doc)
                                    <div class="flex items-center justify-between bg-white rounded p-2 border">
                                        <span class="text-sm text-gray-700 truncate max-w-xs" title="{{ $doc->original_name }}">{{ $doc->original_name }}</span>
                                        <div class="flex gap-2">
                                            <button type="button" onclick="openPreview('{{ route('documents.preview', $doc->id) }}', '{{ addslashes($doc->original_name) }}', '{{ route('documents.download', $doc->id) }}')" class="text-xs text-blue-600 hover:underline">Preview</button>
                                            <a href="{{ route('documents.download', $doc->id) }}" class="text-xs text-gray-600 hover:underline">Download</a>
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

                {{-- No actions --}}
                @if(
                    (auth()->user()->role === 'admission' && !$isReturned) ||
                    (auth()->user()->role === 'staff1' && !$staff1Active && $grantRequest->status_id !== \App\Enums\RequestStatus::STAFF2_APPROVED->value) ||
                    (auth()->user()->role === 'staff2' && !$staff2Active) ||
                    $isFinalStatus
                )
                    <p class="text-gray-400 italic text-sm">No actions available at this stage.</p>
                @endif
            </div>

            {{-- Staff Comments --}}
            @if($isStaff)
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="card-header-brand -mx-6 -mt-6 mb-5 rounded-t-lg px-6 py-3">Staff Comments</div>

                @php
                    $staff1Comments   = $grantRequest->comments->filter(fn($c) => $c->isStaff1Comment());
                    $staff2Comments   = $grantRequest->comments->filter(fn($c) => $c->isStaff2Comment());
                    $internalComments = $grantRequest->comments->filter(fn($c) => $c->isInternalComment());
                @endphp

                @if($staff1Comments->count() > 0)
                    <div class="mb-5">
                        <h4 class="font-semibold text-blue-700 mb-2 text-sm">Staff 1 Notes</h4>
                        @foreach($staff1Comments as $comment)
                            <div class="mb-2 p-3 bg-blue-50 rounded border border-blue-200">
                                <p class="text-xs text-blue-600 mb-1"><span class="font-bold">{{ $comment->user->name }}</span> · {{ \Carbon\Carbon::parse($comment->created_at)->format('d M Y, h:i A') }}</p>
                                <p class="text-sm text-gray-700">{{ $comment->content }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($staff2Comments->count() > 0)
                    <div class="mb-5">
                        <h4 class="font-semibold text-purple-700 mb-2 text-sm">Staff 2 Notes</h4>
                        @foreach($staff2Comments as $comment)
                            <div class="mb-2 p-3 bg-purple-50 rounded border border-purple-200">
                                <p class="text-xs text-purple-600 mb-1"><span class="font-bold">{{ $comment->user->name }}</span> · {{ \Carbon\Carbon::parse($comment->created_at)->format('d M Y, h:i A') }}</p>
                                <p class="text-sm text-gray-700">{{ $comment->content }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($internalComments->count() > 0 || ($staff1Comments->count() === 0 && $staff2Comments->count() === 0))
                    <div class="mb-5">
                        @if($internalComments->count() > 0)
                            <h4 class="font-semibold text-gray-700 mb-2 text-sm">Internal Comments</h4>
                            @foreach($internalComments as $comment)
                                <div class="mb-2 p-3 bg-gray-50 rounded border">
                                    <p class="text-xs text-gray-500 mb-1"><span class="font-bold">{{ $comment->user->name }}</span> · {{ \Carbon\Carbon::parse($comment->created_at)->format('d M Y, h:i A') }}</p>
                                    <p class="text-sm">{{ $comment->content }}</p>
                                </div>
                            @endforeach
                        @else
                            <p class="text-gray-400 italic text-sm">No comments yet.</p>
                        @endif
                    </div>
                @endif

                @can('addComment', $grantRequest)
                    <div class="border-t pt-4">
                        <form action="{{ route('requests.comment', $grantRequest->id) }}" method="POST" onsubmit="return handleFormSubmit(this, 'Posting...')">
                            @csrf
                            <textarea name="content" rows="2"
                                placeholder="{{ auth()->user()->role === 'staff1' ? 'Leave a Staff 1 note...' : (auth()->user()->role === 'staff2' ? 'Leave a Staff 2 note...' : 'Leave an internal comment...') }}"
                                class="w-full border rounded p-2 text-sm"></textarea>
                            <button type="submit"
                                class="mt-2 {{ auth()->user()->role === 'staff1' ? 'bg-blue-600 hover:bg-blue-700' : (auth()->user()->role === 'staff2' ? 'bg-purple-600 hover:bg-purple-700' : 'bg-gray-700 hover:bg-gray-800') }} text-white px-4 py-2 rounded text-sm font-bold">
                                Post {{ auth()->user()->role === 'staff1' ? 'Staff 1 Note' : (auth()->user()->role === 'staff2' ? 'Staff 2 Note' : 'Comment') }}
                            </button>
                        </form>
                    </div>
                @endcan
            </div>
            @endif

            {{-- Back --}}
            <div class="pb-6">
                <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 text-sm">&larr; Back to Dashboard</a>
            </div>

        </div>
    </div>

    <script>
        // Document Review Panel (inline, staff only)
        function loadDocReview(url, name, dlUrl, isPdf) {
            const frame       = document.getElementById('doc-review-frame');
            const placeholder = document.getElementById('doc-review-placeholder');
            const noPreview   = document.getElementById('doc-review-nopreview');
            const header      = document.getElementById('doc-review-header');
            const nameEl      = document.getElementById('doc-review-name');
            const dlEl        = document.getElementById('doc-review-dl');
            const dlEl2       = document.getElementById('doc-review-dl2');

            nameEl.textContent = name;
            if (dlEl)  dlEl.href  = dlUrl;
            if (dlEl2) dlEl2.href = dlUrl;
            header.classList.remove('hidden');
            placeholder.classList.add('hidden');

            // highlight active button
            document.querySelectorAll('.doc-review-btn').forEach(b => b.classList.remove('bg-blue-100','text-blue-800'));
            event?.currentTarget?.classList.add('bg-blue-100','text-blue-800');

            if (isPdf) {
                noPreview.classList.add('hidden');
                frame.classList.remove('hidden');
                frame.src = url;
            } else {
                frame.classList.add('hidden');
                frame.src = '';
                noPreview.classList.remove('hidden');
            }
        }

        function toggleDocReview() {
            const body    = document.getElementById('doc-review-body');
            const label   = document.getElementById('doc-review-toggle-label');
            const chevron = document.getElementById('doc-review-chevron');
            if (body.classList.contains('hidden')) {
                body.classList.remove('hidden');
                label.textContent = 'Hide';
                chevron.style.transform = '';
            } else {
                body.classList.add('hidden');
                label.textContent = 'Show';
                chevron.style.transform = 'rotate(180deg)';
            }
        }

        // Auto-load first document if panel is visible (prefer signed document)
        document.addEventListener('DOMContentLoaded', function() {
            const firstBtn = document.querySelector('.doc-review-btn[data-signed]')
                          ?? document.querySelector('.doc-review-btn');
            if (firstBtn) firstBtn.click();
        });

        // Preview modal
        function openPreview(url, title, downloadUrl) {
            document.getElementById('doc-preview-title').textContent = title;
            document.getElementById('doc-preview-download').href = downloadUrl;
            document.getElementById('doc-preview-frame').src = url;
            const modal = document.getElementById('doc-preview-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closePreview() {
            const modal = document.getElementById('doc-preview-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('doc-preview-frame').src = '';
        }
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closePreview(); });

        // Staff1 action helpers
        function setS1Status(statusVal, action) {
            document.getElementById('s1-status').value = statusVal;
            const reasonField   = document.getElementById('s1-reason-field');
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

        function isCanvasBlank(canvas) {
            const pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
            // Canvas is filled white on init — treat any non-white pixel as drawn content
            for (let i = 0; i < pixels.length; i += 4) {
                if (pixels[i] < 250 || pixels[i+1] < 250 || pixels[i+2] < 250) return false;
            }
            return true;
        }

        function handleFormSubmit(form, message, event) {
            if (form.dataset.roleAction === 'staff2') {
                const canvas = document.getElementById('staff2-signature-canvas');
                const input  = document.getElementById('staff2-signature-data');
                const currentStatus = document.getElementById('s2-status')?.value;
                const approveStatus = '{{ \App\Enums\RequestStatus::STAFF2_APPROVED->value }}';
                if (currentStatus === approveStatus) {
                    // Capture from canvas if not already captured by stopDrawing
                    if (!input?.value && canvas && !isCanvasBlank(canvas)) {
                        input.value = canvas.toDataURL('image/png');
                    }
                    if (!input?.value || isCanvasBlank(canvas)) {
                        alert('Please provide your signature before approving.');
                        return false;
                    }
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

        // Signature pad
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
                const rect = this.canvas.getBoundingClientRect();
                this.canvas.width = rect.width;
                this.canvas.height = rect.height;
                this.ctx.strokeStyle = '#000';
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
                this.ctx.fillStyle = '#fff';
                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
            }
            bindEvents() {
                this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
                this.canvas.addEventListener('mousemove', (e) => this.draw(e));
                this.canvas.addEventListener('mouseup', () => this.stopDrawing());
                this.canvas.addEventListener('mouseout', () => this.stopDrawing());
                this.canvas.addEventListener('touchstart', (e) => { e.preventDefault(); const t = e.touches[0]; this.canvas.dispatchEvent(new MouseEvent('mousedown', { clientX: t.clientX, clientY: t.clientY })); });
                this.canvas.addEventListener('touchmove', (e) => { e.preventDefault(); const t = e.touches[0]; this.canvas.dispatchEvent(new MouseEvent('mousemove', { clientX: t.clientX, clientY: t.clientY })); });
                this.canvas.addEventListener('touchend', (e) => { e.preventDefault(); this.canvas.dispatchEvent(new MouseEvent('mouseup', {})); });
            }
            startDrawing(e) {
                this.isDrawing = true;
                const rect = this.canvas.getBoundingClientRect();
                this.ctx.beginPath();
                this.ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
            }
            draw(e) {
                if (!this.isDrawing) return;
                const rect = this.canvas.getBoundingClientRect();
                this.ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
                this.ctx.stroke();
            }
            stopDrawing() {
                if (this.isDrawing) { this.isDrawing = false; this.saveSignature(); }
            }
            saveSignature() { this.hiddenInput.value = this.canvas.toDataURL('image/png'); }
            clear() {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                this.ctx.fillStyle = '#fff';
                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
                this.hiddenInput.value = '';
            }
        }

        let staff2SignaturePad;
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('staff2-signature-canvas')) {
                staff2SignaturePad = new SignaturePad('staff2-signature-canvas', 'staff2-signature-data');
            }
        });
        function clearStaff2Signature() { if (staff2SignaturePad) staff2SignaturePad.clear(); }
    </script>
</x-app-layout>
