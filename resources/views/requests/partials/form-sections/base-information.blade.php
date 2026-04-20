@props(['requestTypes', 'user', 'grantRequest' => null])

@php
$selectedTypeId = old('request_type_id', $grantRequest?->request_type_id);
@endphp

<div class="mb-6 border-b border-gray-200 pb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Information</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 min-w-0">
        <div>
            <label class="block text-sm font-bold text-gray-700">Request Type *</label>
            <select name="request_type_id" id="request-type-select" class="w-full rounded border-gray-300" required onchange="loadTemplatePreview(this.value)">
                <option value="">Select Request Type</option>
                @foreach($requestTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) $selectedTypeId === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        
    </div>

    <!-- Template Preview Section -->
    <div id="template-preview-section" class="hidden mb-6 border-b border-gray-200 pb-6 overflow-x-hidden">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Template Preview</h3>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 min-w-0">
            <!-- PDF Preview -->
            <div class="min-w-0">
                <h4 class="text-md font-medium text-gray-800 mb-2">Blank Form Preview</h4>
                <div class="border border-gray-300 rounded-lg bg-gray-50" style="height: 400px;">
                    <iframe id="template-preview-iframe" src="" class="w-full h-full rounded" style="border: none;"></iframe>
                </div>
                <p class="text-xs text-gray-500 mt-2">This is blank form that will be filled with your information</p>
            </div>
            
            <!-- Auto-fill Preview -->
            <div>
                <h4 class="text-md font-medium text-gray-800 mb-2">Auto-fill Information</h4>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 min-w-0">
                    <h5 class="font-semibold text-blue-800 mb-3">Information that will be auto-filled:</h5>
                    <ul class="space-y-2 text-sm min-w-0">
                        <li class="flex items-start min-w-0">
                            <span class="text-blue-600 mr-2">✓</span>
                            <span class="min-w-0 break-words"><strong>Name:</strong> <span id="preview-name">{{ $user->name }}</span></span>
                        </li>
                        <li class="flex items-start min-w-0">
                            <span class="text-blue-600 mr-2">✓</span>
                            <span class="min-w-0 break-words"><strong>Staff ID:</strong> <span id="preview-staff-id">{{ $user->staff_id ?? 'Not set' }}</span></span>
                        </li>
                        <li class="flex items-start min-w-0">
                            <span class="text-blue-600 mr-2">✓</span>
                            <span class="min-w-0 break-words"><strong>Designation:</strong> <span id="preview-designation">{{ $user->designation ?? 'Not set' }}</span></span>
                        </li>
                        <li class="flex items-start min-w-0">
                            <span class="text-blue-600 mr-2">✓</span>
                            <span class="min-w-0 break-words"><strong>Department:</strong> <span id="preview-department">{{ $user->department ?? 'Not set' }}</span></span>
                        </li>
                        <li class="flex items-start min-w-0">
                            <span class="text-blue-600 mr-2">✓</span>
                            <span class="min-w-0 break-words"><strong>Phone:</strong> <span id="preview-phone">{{ $user->phone ?? 'Not set' }}</span></span>
                        </li>
                        <li class="flex items-start min-w-0">
                            <span class="text-blue-600 mr-2">✓</span>
                            <span class="min-w-0 break-words"><strong>Employee Level:</strong> <span id="preview-employee-level">{{ $user->employee_level ?? 'Not set' }}</span></span>
                        </li>
                        <li class="flex items-start min-w-0">
                            <span class="text-blue-600 mr-2">✓</span>
                            <span class="break-all"><strong>Email:</strong> <span id="preview-email">{{ $user->email }}</span></span>
                        </li>
                        <li class="flex items-start min-w-0">
                            <span class="text-blue-600 mr-2">✓</span>
                            <span class="min-w-0 break-words"><strong>Request Type:</strong> <span id="preview-request-type">Will be set based on selection</span></span>
                        </li>
                        <li class="flex items-start min-w-0">
                            <span class="text-blue-600 mr-2">✓</span>
                            <span class="min-w-0 break-words"><strong>VOT Items:</strong> <span id="preview-vot">Items you add below</span></span>
                        </li>
                        <li class="flex items-start min-w-0">
                            <span class="text-blue-600 mr-2">✓</span>
                            <span class="min-w-0 break-words"><strong>Total Amount:</strong> <span id="preview-total">Calculated from VOT items</span></span>
                        </li>
                    </ul>
                </div>
                <p class="text-xs text-gray-500 mt-2">Your digital signature will also be included</p>
            </div>
        </div>
        
        <!-- Error message for missing template -->
        <div id="template-error" class="hidden mt-4 rounded-md border border-yellow-200 bg-yellow-50 p-4">
            <h4 class="text-sm font-semibold text-yellow-800">No Template Available</h4>
            <p class="text-sm text-yellow-700 mt-1">This request type doesn't have a default template assigned. You can still submit your request, but no template preview is available.</p>
        </div>
    </div>

    <div class="mt-4">
        <label class="block text-sm font-bold text-gray-700">Justification / Description *</label>
        <textarea name="description" rows="4" class="w-full min-w-0 rounded border-gray-300 mt-1 break-words" placeholder="General description for this STRG request..." required>{{ old('description', $grantRequest?->payload['description'] ?? '') }}</textarea>
    </div>
</div>

