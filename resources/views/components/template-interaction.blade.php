@props(['request' => null, 'templates' => collect()])

@if($request && $templates->count() > 0)
<div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900">Available Templates</h3>
            </div>
            <div class="text-sm text-gray-600">
                {{ $templates->count() }} template(s) available
            </div>
        </div>
    </div>

    <div class="p-6">
        <div class="space-y-4">
            @foreach($templates as $template)
                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-3 flex-1">
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    @if($template->isPdf())
                                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.586 5L14 2.414A2 2 0 0012.586 2H9z"/>
                                        </svg>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900">{{ $template->name }}</h4>
                                @if($template->description)
                                    <p class="text-sm text-gray-600 mt-1">{{ $template->description }}</p>
                                @endif
                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                    <span>Uploaded by {{ $template->uploader->name }}</span>
                                    <span>{{ $template->getFormattedFileSize() }}</span>
                                    <span>{{ $template->download_count }} downloads</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2 ml-4">
                            <button type="button"
                                    onclick="downloadTemplate({{ $template->id }})"
                                    class="px-3 py-1 text-sm rounded-md border border-blue-300 text-blue-700 hover:bg-blue-50 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Download
                            </button>
                            
                            @if($template->isPdf())
                                <button type="button"
                                        onclick="fillTemplate({{ $template->id }})"
                                        class="px-3 py-1 text-sm rounded-md border border-green-300 text-green-700 hover:bg-green-50 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Fill Online
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($templates->isEmpty())
        <div class="text-center py-8">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Templates Available</h3>
            <p class="text-gray-600">Staff will upload templates for this request type when available.</p>
        </div>
    @endif
</div>

<!-- Template Fill Modal -->
<div id="templateFillModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Fill Template</h3>
                    <p class="text-sm text-gray-500 mt-1">Complete the form fields in the template</p>
                </div>
                
                <div id="templateContent" class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <!-- Template content will be loaded here -->
                    <div class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-8 h-8">
                            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">Loading template...</p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        <span id="templateInfo"></span>
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeTemplateModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="button" onclick="saveFilledTemplate()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Save & Attach
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentTemplateId = null;
let filledTemplateData = {};

function downloadTemplate(templateId) {
    window.open(`/documents/${templateId}/download`, '_blank');
}

function fillTemplate(templateId) {
    currentTemplateId = templateId;
    
    // Show modal
    document.getElementById('templateFillModal').classList.remove('hidden');
    
    // Load template content
    fetch(`/templates/${templateId}/fill`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('templateContent').innerHTML = data.html;
                document.getElementById('templateInfo').textContent = data.template_name;
                
                // Initialize form fields
                initializeTemplateFields();
            } else {
                console.error('Failed to load template:', data.error);
                closeTemplateModal();
            }
        })
        .catch(error => {
            console.error('Error loading template:', error);
            closeTemplateModal();
        });
}

function initializeTemplateFields() {
    const inputs = document.querySelectorAll('#templateContent input, #templateContent textarea, #templateContent select');
    
    inputs.forEach(input => {
        // Add change event listeners
        input.addEventListener('change', function(e) {
            filledTemplateData[input.name] = e.target.value;
        });
        
        // Pre-fill with user data if available
        if (input.dataset.prefill) {
            const prefillValue = getPrefillValue(input.dataset.prefill);
            if (prefillValue) {
                input.value = prefillValue;
                filledTemplateData[input.name] = prefillValue;
            }
        }
    });
}

function getPrefillValue(field) {
    // This would be implemented to fetch user/request data
    const user = @json(auth()->user());
    
    switch(field) {
        case 'user.name':
            return user.name;
        case 'user.email':
            return user.email;
        case 'user.staff_id':
            return user.staff_id;
        case 'user.designation':
            return user.designation;
        case 'user.department':
            return user.department;
        case 'user.phone':
            return user.phone;
        case 'user.employee_level':
            return user.employee_level;
        default:
            return null;
    }
}

function closeTemplateModal() {
    document.getElementById('templateFillModal').classList.add('hidden');
    currentTemplateId = null;
    filledTemplateData = {};
}

function saveFilledTemplate() {
    if (!currentTemplateId) return;
    
    // Collect all form data
    const form = document.querySelector('#templateContent form');
    if (form) {
        const formData = new FormData(form);
        for (let [key, value] of formData.entries()) {
            filledTemplateData[key] = value;
        }
    }
    
    // Submit the filled template
    fetch(`/templates/${currentTemplateId}/save`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            filled_data: filledTemplateData,
            request_id: {{ $request->id }}
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeTemplateModal();
            // Show success message or refresh page
            location.reload();
        } else {
            alert('Error saving template: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error saving template:', error);
        alert('Error saving template');
    });
}

// Auto-save functionality
let autoSaveTimer = null;
document.addEventListener('DOMContentLoaded', function() {
    const templateContent = document.getElementById('templateContent');
    
    if (templateContent) {
        templateContent.addEventListener('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                // Auto-save draft
                console.log('Auto-saving template draft...');
            }, 5000); // Auto-save after 5 seconds of inactivity
        });
    }
});
</script>
@endif
