<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printable Summary - {{ $grantRequest->ref_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-break { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-gray-50 print:bg-white">
    <div class="max-w-4xl mx-auto p-6 print:p-8">
        <!-- Print Button (Hidden when printing) -->
        <div class="mb-6 no-print">
            <button onclick="window.print()" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Summary
            </button>
        </div>

        <!-- Header Section -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden print-break">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">UniKL STRG Request Summary</h1>
                        <p class="text-blue-100">Student Travel Research Grant Application</p>
                    </div>
                    <div class="text-right">
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                            <p class="text-sm font-medium">Reference</p>
                            <p class="text-xl font-bold">{{ $grantRequest->ref_number }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Details -->
            <div class="p-8">
                <!-- Status and Date -->
                <div class="flex justify-between items-center mb-8 pb-6 border-b border-gray-200">
                    <div>
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold {{ $grantRequest->statusClass() }}">
                            {{ $grantRequest->statusLabel() }}
                        </span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Submitted on</p>
                        <p class="font-semibold">{{ $grantRequest->created_at->format('d F Y') }}</p>
                        @if($grantRequest->deadline)
                            <p class="text-sm text-gray-500 mt-1">Deadline</p>
                            <p class="font-semibold {{ $grantRequest->isUrgent() ? 'text-red-600' : 'text-gray-700' }}">
                                {{ $grantRequest->deadline->format('d F Y') }}
                                @if($grantRequest->isUrgent())
                                    <span class="text-xs ml-1">⚠️ URGENT</span>
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Applicant Information -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Applicant Information
                    </h3>
                    <div class="grid grid-cols-2 gap-4 bg-gray-50 rounded-lg p-4">
                        <div>
                            <p class="text-sm text-gray-600">Name</p>
                            <p class="font-semibold">{{ $grantRequest->user->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-semibold">{{ $grantRequest->payload['email'] ?? $grantRequest->user->email }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Request Type</p>
                            <p class="font-semibold">{{ $grantRequest->requestType->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Requested Amount</p>
                            <p class="font-semibold text-lg">RM {{ number_format((float) ($grantRequest->total_amount ?? 0), 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Staff ID</p>
                            <p class="font-semibold">{{ $grantRequest->submitter_staff_id ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Designation</p>
                            <p class="font-semibold">{{ $grantRequest->submitter_designation ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Phone Number</p>
                            <p class="font-semibold">{{ $grantRequest->submitter_phone ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Department</p>
                            <p class="font-semibold">{{ $grantRequest->submitter_department ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <!-- VOT Breakdown -->
                <div class="mb-8 print-break">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">VOT Breakdown</h3>
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="text-left px-4 py-2">VOT Code</th>
                                    <th class="text-left px-4 py-2">Description</th>
                                    <th class="text-right px-4 py-2">Amount (RM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($grantRequest->vot_items ?? []) as $item)
                                    <tr class="border-t border-gray-200">
                                        <td class="px-4 py-2 font-semibold">{{ $item['vot_code'] ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ $item['description'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-right">{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr class="border-t border-gray-200">
                                        <td colspan="3" class="px-4 py-3 text-center text-gray-500">No VOT items recorded</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-gray-50 border-t border-gray-200">
                                <tr>
                                    <td colspan="2" class="px-4 py-2 text-right font-semibold">Total</td>
                                    <td class="px-4 py-2 text-right font-bold">RM {{ number_format((float) ($grantRequest->total_amount ?? 0), 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Request Description -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Request Description
                    </h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $grantRequest->payload['description'] ?? 'No description provided' }}</p>
                    </div>
                </div>

                @if($grantRequest->file_path)
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Supporting Document</h3>
                        <p class="text-sm text-gray-700">
                            Attached file: <span class="font-semibold">{{ basename($grantRequest->file_path) }}</span>
                        </p>
                    </div>
                @endif

                <!-- Verification Trail -->
                <div class="print-break">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Verification Trail
                    </h3>
                    
                    @if($grantRequest->auditLogs->count() > 0)
                        <div class="space-y-3">
                            @foreach($grantRequest->auditLogs as $log)
                                <div class="border-l-4 border-blue-500 bg-blue-50 rounded-r-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <span class="font-semibold text-gray-900">{{ $log->actor?->name ?? 'System' }}</span>
                                            <span class="text-sm text-gray-600 ml-2">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A') }}</span>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ \App\Enums\RequestStatus::tryFrom($log->from_status)?->getLabel() ?? $log->from_status }}
                                            →
                                            {{ \App\Enums\RequestStatus::tryFrom($log->to_status)?->getLabel() ?? $log->to_status }}
                                        </span>
                                    </div>
                                    @if(!empty($log->note))
                                        <div class="bg-white rounded p-3 text-sm text-gray-700">
                                            <span class="font-semibold">Note:</span> {{ $log->note }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-gray-50 rounded-lg p-6 text-center">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-gray-600">No verification trail available</p>
                        </div>
                    @endif
                </div>

                <!-- Staff Verification -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Verified By</p>
                            <p class="font-semibold">{{ $grantRequest->verifiedBy?->name ?? 'Pending Verification' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Recommended By</p>
                            <p class="font-semibold">{{ $grantRequest->recommendedBy?->name ?? 'Pending Recommendation' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-gray-200 text-center text-sm text-gray-500">
                    <p>Generated on {{ now()->format('d F Y, h:i A') }}</p>
                    <p>UniKL Student Travel Research Grant System</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

