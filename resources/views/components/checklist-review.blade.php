@props(['request' => null])

@if($request && $request->getChecklistItems()->count() > 0)
<div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900">Verification Checklist</h3>
            </div>
            <div class="flex items-center space-x-3">
                <div class="text-sm text-gray-600">
                    Progress: <span class="font-semibold">{{ $request->getChecklistProgress()['percentage'] }}%</span>
                </div>
                <div class="w-32 bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $request->getChecklistProgress()['percentage'] }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6">
        <div class="space-y-4" id="checklist-items">
            @php
                // Load checklist reviews once to avoid N+1 queries
                $reviews = $request->checklistReviews->keyBy('checklist_item_id');
            @endphp
            @foreach($request->getChecklistItems() as $item)
                @php
                    $review = $reviews->get($item->id);
                    $status = $review?->status;
                @endphp
                
                <div class="checklist-item border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors" data-item-id="{{ $item->id }}">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-3 flex-1">
                            <div class="flex-shrink-0 mt-1">
                                @if($status === 'checked')
                                    <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @elseif($status === 'flagged')
                                    <div class="w-5 h-5 bg-red-500 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-5 h-5 border-2 border-gray-300 rounded-full"></div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <h4 class="font-medium text-gray-900">{{ $item->label }}</h4>
                                    @if($item->is_required)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">Required</span>
                                    @endif
                                </div>
                                
                                @if($status === 'flagged' && $review?->note)
                                    <div class="mt-2 p-3 bg-red-50 border border-red-200 rounded-md">
                                        <p class="text-sm text-red-800">
                                            <strong>Flagged:</strong> {{ $review->note }}
                                        </p>
                                        <p class="text-xs text-red-600 mt-1">
                                            By {{ $review->reviewer->name }} on {{ $review->updated_at->format('M j, Y H:i') }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        @if(auth()->user()->isStaff1() && $request->canBeActionedByStaff1())
                            <div class="flex items-center space-x-2 ml-4">
                                <button type="button" 
                                        class="checklist-btn px-3 py-1 text-sm rounded-md border transition-colors"
                                        data-action="checked"
                                        data-item-id="{{ $item->id }}"
                                        @class($status === 'checked' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-white text-gray-700 border-gray-300 hover:border-green-300 hover:text-green-700')>
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    OK
                                </button>
                                
                                <button type="button"
                                        class="checklist-btn px-3 py-1 text-sm rounded-md border transition-colors"
                                        data-action="flagged"
                                        data-item-id="{{ $item->id }}"
                                        @class($status === 'flagged' ? 'bg-red-100 text-red-700 border-red-300' : 'bg-white text-gray-700 border-gray-300 hover:border-red-300 hover:text-red-700')>
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                    Flag
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    @if(auth()->user()->isStaff1() && $request->canBeActionedByStaff1() && $status === 'flagged')
                        <div class="mt-3 pl-8">
                            <textarea 
                                class="flag-note w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Add note for flagged item..."
                                data-item-id="{{ $item->id }}"
                                rows="2">{{ $review?->note }}</textarea>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if(auth()->user()->isStaff1() && $request->canBeActionedByStaff1())
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <span class="font-medium">{{ $request->getChecklistProgress()['checked'] }}</span> of 
                        <span class="font-medium">{{ $request->getChecklistProgress()['total'] }}</span> items checked
                        @if($request->getChecklistProgress()['flagged'] > 0)
                            <span class="text-red-600">({{ $request->getChecklistProgress()['flagged'] }} flagged)</span>
                        @endif
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <button type="button" 
                                id="save-checklist-btn"
                                class="px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                            Save Progress
                        </button>
                        
                        @if($request->canBeForwardedToStaff2())
                            <button type="button"
                                    id="forward-to-staff2-btn"
                                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                                Forward to Staff 2
                            </button>
                        @else
                            <button type="button" 
                                    disabled
                                    class="px-4 py-2 bg-gray-300 text-gray-500 text-sm font-medium rounded-lg cursor-not-allowed"
                                    title="Complete all required items and ensure none are flagged">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                                Forward to Staff 2
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@else
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center">
        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Checklist Defined</h3>
        <p class="text-gray-600">This request type doesn't have a verification checklist configured.</p>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checklistItems = document.querySelectorAll('.checklist-item');
    const saveBtn = document.getElementById('save-checklist-btn');
    const forwardBtn = document.getElementById('forward-to-staff2-btn');
    const requestId = {{ $request->id }};
    
    // Handle checklist item buttons
    checklistItems.forEach(item => {
        const itemId = item.dataset.itemId;
        const checkedBtn = item.querySelector('[data-action="checked"]');
        const flaggedBtn = item.querySelector('[data-action="flagged"]');
        const noteTextarea = item.querySelector('.flag-note');
        
        if (checkedBtn && flaggedBtn) {
            checkedBtn.addEventListener('click', () => updateChecklistItem(itemId, 'checked', noteTextarea?.value));
            flaggedBtn.addEventListener('click', () => updateChecklistItem(itemId, 'flagged', noteTextarea?.value));
        }
        
        if (noteTextarea) {
            noteTextarea.addEventListener('blur', () => {
                const currentStatus = item.querySelector('[data-action="flagged"]').classList.contains('bg-red-100') ? 'flagged' : 'checked';
                updateChecklistItem(itemId, currentStatus, noteTextarea.value);
            });
        }
    });
    
    function updateChecklistItem(itemId, status, note) {
        fetch(`/requests/${requestId}/checklist`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                checklist_item_id: itemId,
                status: status,
                note: note
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateUI(itemId, status, note);
                updateProgress(data.progress);
                updateForwardButton(data.can_forward);
            }
        })
        .catch(error => console.error('Error updating checklist:', error));
    }
    
    function updateUI(itemId, status, note) {
        const item = document.querySelector(`[data-item-id="${itemId}"]`);
        const checkedBtn = item.querySelector('[data-action="checked"]');
        const flaggedBtn = item.querySelector('[data-action="flagged"]');
        const noteTextarea = item.querySelector('.flag-note');
        const flaggedSection = item.querySelector('.bg-red-50');
        
        // Update button states
        if (status === 'checked') {
            checkedBtn.className = 'checklist-btn px-3 py-1 text-sm rounded-md border transition-colors bg-green-100 text-green-700 border-green-300';
            flaggedBtn.className = 'checklist-btn px-3 py-1 text-sm rounded-md border transition-colors bg-white text-gray-700 border-gray-300 hover:border-red-300 hover:text-red-700';
            
            // Hide flagged section
            if (flaggedSection) flaggedSection.style.display = 'none';
        } else {
            checkedBtn.className = 'checklist-btn px-3 py-1 text-sm rounded-md border transition-colors bg-white text-gray-700 border-gray-300 hover:border-green-300 hover:text-green-700';
            flaggedBtn.className = 'checklist-btn px-3 py-1 text-sm rounded-md border transition-colors bg-red-100 text-red-700 border-red-300';
            
            // Show flagged section
            if (flaggedSection && note) {
                flaggedSection.style.display = 'block';
                flaggedSection.querySelector('.text-red-800 strong').nextSibling.textContent = note;
            }
        }
        
        // Update note textarea
        if (noteTextarea) {
            noteTextarea.value = note || '';
        }
    }
    
    function updateProgress(progress) {
        const progressBar = document.querySelector('.bg-blue-600');
        const progressText = document.querySelector('.font-semibold');
        
        if (progressBar) progressBar.style.width = progress.percentage + '%';
        if (progressText) progressText.textContent = progress.percentage + '%';
        
        // Update summary text
        const summaryText = document.querySelector('.text-sm.text-gray-600');
        if (summaryText) {
            summaryText.innerHTML = `<span class="font-medium">${progress.checked}</span> of <span class="font-medium">${progress.total}</span> items checked${progress.flagged > 0 ? ` <span class="text-red-600">(${progress.flagged} flagged)</span>` : ''}`;
        }
    }
    
    function updateForwardButton(canForward) {
        if (forwardBtn) {
            if (canForward) {
                forwardBtn.disabled = false;
                forwardBtn.className = 'px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center';
                forwardBtn.title = '';
            } else {
                forwardBtn.disabled = true;
                forwardBtn.className = 'px-4 py-2 bg-gray-300 text-gray-500 text-sm font-medium rounded-lg cursor-not-allowed';
                forwardBtn.title = 'Complete all required items and ensure none are flagged';
            }
        }
    }
    
    // Handle save button
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            // Save is handled automatically on each update
            alert('Checklist progress saved automatically');
        });
    }
    
    // Handle forward button
    if (forwardBtn) {
        forwardBtn.addEventListener('click', () => {
            if (confirm('Are you ready to forward this request to Staff 2? This action cannot be undone.')) {
                // Forward to Staff 2 (this would be handled by the existing status change functionality)
                window.location.href = `/requests/${requestId}/status?status=staff1_reviewed`;
            }
        });
    }
});
</script>
@endif
