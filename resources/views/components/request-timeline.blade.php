<div class="bg-white rounded-2xl shadow-lg p-6">
    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
        <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Request Timeline
    </h3>

    @php $specialStatus = $request->getStatus() === \App\Enums\RequestStatus::RETURNED
        ? 'returned'
        : ($request->getStatus() === \App\Enums\RequestStatus::DECLINED ? 'declined' : null); @endphp

    @if($specialStatus === 'returned')
        <div class="mb-6 bg-yellow-50 border border-yellow-300 rounded-lg p-4 flex items-start space-x-3">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
            <div>
                <p class="font-semibold text-yellow-800">Returned for Revision</p>
                @if($request->return_reason)
                    <p class="text-sm text-yellow-700 mt-1">{{ $request->return_reason }}</p>
                @endif
            </div>
        </div>
    @elseif($specialStatus === 'declined')
        <div class="mb-6 bg-red-50 border border-red-300 rounded-lg p-4 flex items-start space-x-3">
            <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <div>
                <p class="font-semibold text-red-800">Declined</p>
                @if($request->decline_reason)
                    <p class="text-sm text-red-700 mt-1">{{ $request->decline_reason }}</p>
                @endif
            </div>
        </div>
    @endif

    <div class="relative">
        <div class="absolute left-8 top-8 bottom-8 w-0.5 bg-gray-300"></div>

        <div class="space-y-8">
            @foreach($timelineSteps as $index => $step)
                @php
                    if ($specialStatus) {
                        $stepStatus = $index < $currentStep ? 'completed' : ($index === $currentStep ? 'returned_here' : 'pending');
                    } else {
                        $stepStatus = $index < $currentStep ? 'completed' : ($index === $currentStep ? 'current' : 'pending');
                    }
                    $bgColor = match($stepStatus) {
                        'completed'     => 'bg-green-500',
                        'current'       => 'bg-blue-500',
                        'returned_here' => 'bg-yellow-400',
                        default         => 'bg-gray-300',
                    };
                    $borderColor = match($stepStatus) {
                        'completed'     => 'border-green-500',
                        'current'       => 'border-blue-500',
                        'returned_here' => 'border-yellow-400',
                        default         => 'border-gray-300',
                    };
                @endphp

                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 relative">
                        <div class="w-16 h-16 rounded-full {{ $bgColor }} flex items-center justify-center border-4 border-white shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/>
                            </svg>
                        </div>
                        @if($stepStatus === 'current')
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-blue-500 rounded-full animate-pulse border-2 border-white"></div>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0 pb-8">
                        <div class="bg-gray-50 rounded-lg p-4 border-l-4 {{ $borderColor }}">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-bold text-gray-900">{{ $step['label'] }}</h4>
                                @if($stepStatus === 'completed')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Done</span>
                                @elseif($stepStatus === 'current')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">In Progress</span>
                                @elseif($stepStatus === 'returned_here')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ $specialStatus === 'returned' ? 'Returned' : 'Declined' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Pending</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600">{{ $step['description'] }}</p>

                            @if($stepStatus === 'completed' && $step['id'] === 'submitted')
                                <div class="text-xs text-gray-500 mt-2">
                                    <span class="font-medium">Submitted by:</span> {{ $request->user->name }}
                                    <span class="ml-2">on {{ $request->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                            @elseif($stepStatus === 'completed' && $step['id'] === 'staff1_reviewed' && $request->verifiedBy)
                                <div class="text-xs text-gray-500 mt-2">
                                    <span class="font-medium">Checked by:</span> {{ $request->verifiedBy->name }}
                                </div>
                            @elseif(in_array($stepStatus, ['completed', 'current']) && $step['id'] === 'staff2_approved' && $request->recommendedBy)
                                <div class="text-xs text-gray-500 mt-2">
                                    <span class="font-medium">Approved by:</span> {{ $request->recommendedBy->name }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm text-blue-800"><span class="font-medium">Current Status:</span> {{ $request->statusLabel() }}</span>
        </div>
    </div>
</div>
