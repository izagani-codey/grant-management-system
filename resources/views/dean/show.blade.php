<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-900 to-blue-700 bg-clip-text text-transparent">Request Review</h1>
                <p class="text-gray-600 mt-1">Reference: {{ $request->ref_number }}</p>
            </div>
            <a href="{{ route('dashboard') }}" 
               class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Dashboard
            </a>
        </div>
    </x-slot>
<div class="min-h-screen bg-gradient-to-br from-purple-50 to-indigo-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Request Review</h1>
                <p class="text-gray-600 mt-1">Reference: {{ $request->ref_number }}</p>
            </div>
            <a href="{{ route('dashboard') }}" 
               class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Dashboard
            </a>
        </div>

        <!-- Request Details Card -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-blue-900 to-blue-700 text-white p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold">{{ $request->requestType->name }}</h2>
                        <p class="text-purple-100 mt-1">Submitted by {{ $request->user->name }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/20 text-white">
                            {{ $request->statusLabel() }}
                        </span>
                        <p class="text-purple-100 text-sm mt-1">{{ $request->created_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <!-- Applicant Information -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Applicant Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Name</p>
                            <p class="text-gray-900">{{ $request->user->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Staff ID</p>
                            <p class="text-gray-900">{{ $request->submitter_staff_id }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Designation</p>
                            <p class="text-gray-900">{{ $request->submitter_designation }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Department</p>
                            <p class="text-gray-900">{{ $request->submitter_department }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Phone</p>
                            <p class="text-gray-900">{{ $request->submitter_phone }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Employee Level</p>
                            <p class="text-gray-900">{{ $request->submitter_employee_level }}</p>
                        </div>
                    </div>
                </div>

                <!-- Request Description -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Description</h3>
                    <p class="text-gray-700 bg-gray-50 p-4 rounded-lg">
                        {{ $request->payload['description'] ?? 'No description provided' }}
                    </p>
                </div>

                <!-- VOT Breakdown -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Budget Breakdown (VOT Items)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">VOT Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (RM)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($request->getVotItems() as $item)
    <tr>
        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item['vot_code'] ?? '—' }}</td>
        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['description'] ?? 'N/A' }}</td>
        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($item['amount'] ?? 0, 2) }}</td>
    </tr>
@endforeach
                                <tr class="bg-gray-50 font-semibold">
                                    <td colspan="2" class="px-4 py-3 text-sm text-gray-900">Total Amount</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">RM{{ number_format($request->total_amount, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Verification Trail -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Verification Trail</h3>
                    <div class="space-y-3">
                        <div class="flex items-center p-3 bg-green-50 rounded-lg">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Verified by Staff 1</p>
                                <p class="text-xs text-gray-600">{{ $request->verifiedBy->name }} - {{ $request->updated_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                        
                        @if($request->recommendedBy)
                            <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Recommended by Staff 2</p>
                                    <p class="text-xs text-gray-600">{{ $request->recommendedBy->name }} - {{ $request->updated_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Dean Actions -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Dean Actions</h3>
                    @if(empty(auth()->user()->signature_data))
                        <div class="mb-4 rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
                            Your profile signature is missing. Please save your signature in profile settings before approving/rejecting.
                        </div>
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Approve Form -->
                        <form action="{{ route('dean.requests.approve', $request->id) }}" method="POST" class="space-y-4 dean-action-form" data-requires-signature="true">
                            @csrf
                            <input type="hidden" name="dean_signature_data" value="{{ auth()->user()->signature_data }}">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Approval Notes (Optional)</label>
                                <textarea name="notes" rows="3" class="w-full rounded border-gray-300" placeholder="Add any approval notes..."></textarea>
                            </div>
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                Approve Request
                            </button>
                        </form>

                        <!-- Reject Form -->
                        <form action="{{ route('dean.requests.reject', $request->id) }}" method="POST" class="space-y-4 dean-action-form" data-requires-signature="true">
                            @csrf
                            <input type="hidden" name="dean_signature_data" value="{{ auth()->user()->signature_data }}">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason *</label>
                                <textarea name="reason" rows="3" class="w-full rounded border-gray-300" placeholder="Provide reason for rejection..." required></textarea>
                            </div>
                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                Reject Request
                            </button>
                        </form>
                    </div>

                    <!-- Return Actions -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <form action="{{ route('dean.requests.return-staff1', $request->id) }}" method="POST" class="space-y-4 dean-action-form">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Return Reason *</label>
                                <textarea name="reason" rows="2" class="w-full rounded border-gray-300" placeholder="Reason for returning to Staff 1..." required></textarea>
                            </div>
                            <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                                Return to Staff 1
                            </button>
                        </form>

                        <form action="{{ route('dean.requests.return-staff2', $request->id) }}" method="POST" class="space-y-4 dean-action-form">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Return Reason *</label>
                                <textarea name="reason" rows="2" class="w-full rounded border-gray-300" placeholder="Reason for returning to Staff 2..." required></textarea>
                            </div>
                            <button type="submit" class="w-full bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                                Return to Staff 2
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.dean-action-form');
    forms.forEach((form) => {
        form.addEventListener('submit', function (event) {
            const requiresSignature = form.dataset.requiresSignature === 'true';
            if (requiresSignature) {
                const signature = form.querySelector('input[name="dean_signature_data"]')?.value?.trim();
                if (!signature) {
                    event.preventDefault();
                    alert('Dean signature is required. Please save your profile signature first.');
                    return false;
                }
            }

            const reasonField = form.querySelector('textarea[name="reason"]');
            if (reasonField && reasonField.value.trim() === '') {
                event.preventDefault();
                reasonField.focus();
                alert('Please provide a reason before submitting.');
                return false;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-70', 'cursor-not-allowed');
            }

            return true;
        });
    });
});
</script>

