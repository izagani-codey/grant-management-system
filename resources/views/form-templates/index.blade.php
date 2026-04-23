<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent">Blank Forms</h1>
                <p class="text-gray-600 mt-1">Upload, preview, and manage reusable blank form templates</p>
            </div>
            @if(auth()->user()->role === 'admin')
                <button onclick="document.getElementById('upload-form').classList.toggle('hidden')" 
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Upload New Form
                </button>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Upload Form (Hidden by default) -->
            @if(auth()->user()->role === 'admin')
                <div id="upload-form" class="hidden">
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Upload Blank Form Template
                        </h3>
                        
                        <form action="{{ route('form-templates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            
                            @if($errors->any())
                                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                                    <div class="font-medium mb-2">Please fix the following errors:</div>
                                    @foreach($errors->all() as $error)
                                        <p class="text-sm">• {{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Form Title</label>
                                <input type="text" name="name" value="{{ old('name') }}" 
                                       placeholder="e.g., Travel Grant Application Form"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                       required>
                                <p class="mt-1 text-sm text-gray-500">Give this form a descriptive title for easy identification</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Request Type (Optional)</label>
                                <select name="request_type_id" id="request_type_id" 
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">General Template (Available for all request types)</option>
                                    @foreach(\App\Models\RequestType::where('is_active', true)->orderBy('name')->get() as $requestType)
                                        <option value="{{ $requestType->id }}" {{ old('request_type_id') == $requestType->id ? 'selected' : '' }}>
                                            {{ $requestType->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-sm text-gray-500">Select a specific request type to make this template available only for that type</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Document Purpose</label>
                                <select name="template_type"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                        id="template-type-select">
                                    <option value="request_type_form" {{ old('template_type') === 'request_type_form' ? 'selected' : '' }}>
                                        System Template (used to generate the form)
                                    </option>
                                    <option value="supporting_document" {{ old('template_type') === 'supporting_document' ? 'selected' : '' }}>
                                        Supporting Document (reference file for the request type)
                                    </option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500">
                                    System templates are auto-filled and signed. Supporting documents are static reference files shown alongside the request.
                                </p>
                            </div>

                            <div id="default-template-section" class="hidden">
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_default" value="1"
                                           {{ old('is_default') ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Set as default template for this request type</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-500">This template will be automatically selected when users create this type of request</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">PDF File</label>
                                <div id="file-upload-container" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="file-input">
                                    <label for="file-input" class="cursor-pointer" id="file-label">
                                        <span class="text-blue-600 font-medium hover:text-blue-700">Choose file</span>
                                        <span class="text-gray-600"> or drag and drop</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-2" id="file-help-text">PDF, JPG, or PNG (max 5MB)</p>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="document.getElementById('upload-form').classList.add('hidden')"
                                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all">
                                    Upload Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Success Messages -->
            @if(session('success'))
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 text-red-800 px-6 py-4 rounded-xl shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <!-- Templates Table -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
                    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Uploaded Blank Forms</h3>
                            <p class="text-sm text-slate-500">Manage all uploaded templates from your team.</p>
                        </div>
                        <span class="text-sm text-slate-500">Showing {{ $templates->count() }} result{{ $templates->count() === 1 ? '' : 's' }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
                        <thead class="bg-white text-slate-500 uppercase tracking-wide text-[11px]">
                            <tr>
                                <th class="px-6 py-4">Title</th>
                                <th class="px-6 py-4">Uploaded By</th>
                                <th class="px-6 py-4">Request Type</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse($templates as $template)
                                @php
                                    $ext     = strtolower(pathinfo($template->file_path, PATHINFO_EXTENSION));
                                    $isPdf   = $ext === 'pdf';
                                    $fileUrl = asset('storage/' . $template->file_path);
                                @endphp
                                <tr class="border-b border-slate-200 odd:bg-white even:bg-slate-50">
                                    <td class="px-6 py-4 font-medium text-slate-900 flex items-center gap-2">
                                        @if($isPdf)
                                            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                        {{ $template->name }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">{{ $template->uploader?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4">
                                        @if($template->requestType)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">{{ $template->requestType->name }}</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">General</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-slate-500">{{ $template->created_at?->format('d M Y') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if($isPdf && in_array(auth()->user()->role, ['staff2', 'admin']))
                                                @php
                                                    $zoneCount = collect($template->zones ?? [])->flatten(1)->count();
                                                @endphp
                                                <a href="{{ route('staff2.zones.edit', $template) }}"
                                                   class="rounded-full border border-gray-300 bg-white px-3 py-1 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition inline-flex items-center gap-1">
                                                    ⚙ Configure Zones
                                                </a>
                                                @if($zoneCount > 0)
                                                    <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">
                                                        {{ $zoneCount }} zone{{ $zoneCount !== 1 ? 's' : '' }}
                                                    </span>
                                                @else
                                                    <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 font-medium">
                                                        No zones
                                                    </span>
                                                @endif
                                            @endif
                                            <button type="button"
                                                onclick="openPreview('{{ addslashes($template->name) }}', '{{ $fileUrl }}', '{{ $isPdf ? 'pdf' : 'image' }}')"
                                                class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700 hover:bg-blue-100 transition inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Preview
                                            </button>
                                            <a href="{{ $fileUrl }}" target="_blank"
                                               class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                                                Download
                                            </a>
                                            @if(auth()->user()->role === 'admin')
                                                <form action="{{ route('form-templates.destroy', $template->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            onclick="return confirm('Delete &quot;{{ addslashes($template->name) }}&quot;? This cannot be undone.')"
                                                            class="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-sm font-semibold text-red-700 hover:bg-red-100 transition">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-slate-500">No templates uploaded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Preview Slide-over Modal -->
    <div id="preview-modal" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop -->
        <div id="preview-backdrop"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-300"
             onclick="closePreview()"></div>

        <!-- Panel -->
        <div id="preview-panel"
             class="fixed top-0 right-0 h-full w-full max-w-4xl bg-white shadow-2xl flex flex-col translate-x-full transition-transform duration-300 ease-out">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50 flex-shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <!-- File type icon -->
                    <div id="preview-icon-pdf" class="hidden flex-shrink-0 w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div id="preview-icon-img" class="hidden flex-shrink-0 w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 id="preview-title" class="text-base font-semibold text-slate-900 truncate"></h2>
                        <p id="preview-subtitle" class="text-xs text-slate-500"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0 ml-4">
                    <a id="preview-download-link" href="#" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </a>
                    <button type="button" onclick="closePreview()"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition text-lg font-semibold leading-none">
                        ×
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="flex-1 relative overflow-hidden bg-slate-100">
                <!-- Loading spinner -->
                <div id="preview-loading" class="absolute inset-0 flex items-center justify-center bg-slate-100 z-10">
                    <div class="text-center">
                        <svg class="animate-spin w-8 h-8 text-blue-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <p class="text-sm text-slate-500">Loading preview…</p>
                    </div>
                </div>

                <!-- PDF viewer -->
                <iframe id="preview-iframe"
                        class="w-full h-full border-0 hidden"
                        title="Form Preview"
                        src="about:blank"></iframe>

                <!-- Image viewer -->
                <div id="preview-image-wrap" class="hidden w-full h-full flex items-center justify-center p-6 overflow-auto">
                    <img id="preview-img" src="" alt="Form Preview"
                         class="max-w-full max-h-full object-contain rounded shadow-lg">
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const requestTypeSelect = document.getElementById('request_type_id');
    const defaultTemplateSection = document.getElementById('default-template-section');
    const fileInput = document.getElementById('file-input');
    const fileUploadContainer = document.getElementById('file-upload-container');
    const fileLabel = document.getElementById('file-label');
    const fileHelpText = document.getElementById('file-help-text');
    const submitButton = document.querySelector('button[type="submit"]');
    
    function updateFileRequirement() {
        const requestTypeId = requestTypeSelect.value;
        const isDefaultChecked = document.querySelector('input[name="is_default"]').checked;
        
        if (requestTypeId) {
            defaultTemplateSection.classList.remove('hidden');
        } else {
            defaultTemplateSection.classList.add('hidden');
            // Uncheck the default checkbox if no request type is selected
            const defaultCheckbox = document.querySelector('input[name="is_default"]');
            if (defaultCheckbox) {
                defaultCheckbox.checked = false;
            }
        }
        
        // Update file requirement based on default template selection
        if (requestTypeId && isDefaultChecked) {
            // File is optional when setting default template
            fileInput.removeAttribute('required');
            fileUploadContainer.classList.remove('border-red-300');
            fileUploadContainer.classList.add('border-gray-300');
            fileLabel.innerHTML = '<span class="text-blue-600 font-medium hover:text-blue-700">Choose file (optional)</span><span class="text-gray-600"> or drag and drop</span>';
            fileHelpText.textContent = 'PDF, JPG, or PNG (max 5MB) - Optional when setting default template';
            fileHelpText.classList.add('text-blue-600');
        } else {
            // File is required for custom templates
            fileInput.setAttribute('required', 'required');
            fileUploadContainer.classList.remove('border-gray-300');
            fileUploadContainer.classList.add('border-gray-300');
            fileLabel.innerHTML = '<span class="text-blue-600 font-medium hover:text-blue-700">Choose file</span><span class="text-gray-600"> or drag and drop</span>';
            fileHelpText.textContent = 'PDF, JPG, or PNG (max 5MB)';
            fileHelpText.classList.remove('text-blue-600');
        }
    }
    
    function validateForm() {
        const requestTypeId = requestTypeSelect.value;
        const isDefaultChecked = document.querySelector('input[name="is_default"]').checked;
        const hasFile = fileInput.files.length > 0;
        
        if (!requestTypeId && !hasFile) {
            alert('Please select a request type or upload a general template.');
            return false;
        }
        
        if (requestTypeId && !isDefaultChecked && !hasFile) {
            alert('Please upload a file for custom templates, or check "Set as default template" to use existing template.');
            return false;
        }
        
        return true;
    }
    
    requestTypeSelect.addEventListener('change', updateFileRequirement);
    
    // Listen for default checkbox changes
    document.querySelector('input[name="is_default"]').addEventListener('change', updateFileRequirement);
    
    // Validate form on submit
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
    });
    
    // Initialize on page load
    updateFileRequirement();
});

// ── Preview slide-over ──────────────────────────────────────────────────────

function openPreview(title, url, type) {
    const modal     = document.getElementById('preview-modal');
    const backdrop  = document.getElementById('preview-backdrop');
    const panel     = document.getElementById('preview-panel');
    const loading   = document.getElementById('preview-loading');
    const iframe    = document.getElementById('preview-iframe');
    const imgWrap   = document.getElementById('preview-image-wrap');
    const img       = document.getElementById('preview-img');
    const iconPdf   = document.getElementById('preview-icon-pdf');
    const iconImg   = document.getElementById('preview-icon-img');
    const dlLink    = document.getElementById('preview-download-link');

    // Populate header
    document.getElementById('preview-title').textContent = title;
    document.getElementById('preview-subtitle').textContent = type === 'pdf' ? 'PDF Document' : 'Image File';
    dlLink.href = url;

    // Show correct icon
    iconPdf.classList.toggle('hidden', type !== 'pdf');
    iconImg.classList.toggle('hidden', type !== 'image');

    // Reset body
    loading.classList.remove('hidden');
    iframe.classList.add('hidden');
    imgWrap.classList.add('hidden');
    iframe.src = 'about:blank';
    img.src = '';

    // Show modal and animate
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            backdrop.classList.add('opacity-100');
            panel.classList.remove('translate-x-full');
            panel.classList.add('translate-x-0');
        });
    });

    if (type === 'pdf') {
        iframe.classList.remove('hidden');
        let loaded = false;
        iframe.onload = function () {
            if (!loaded) { loaded = true; loading.classList.add('hidden'); }
        };
        // Fallback: hide spinner after 3s in case onload doesn't fire (some browsers)
        setTimeout(() => { if (!loaded) { loaded = true; loading.classList.add('hidden'); } }, 3000);
        iframe.src = url;
    } else {
        imgWrap.classList.remove('hidden');
        img.onload  = () => loading.classList.add('hidden');
        img.onerror = () => loading.classList.add('hidden');
        img.src = url;
    }
}

function closePreview() {
    const modal    = document.getElementById('preview-modal');
    const backdrop = document.getElementById('preview-backdrop');
    const panel    = document.getElementById('preview-panel');
    const iframe   = document.getElementById('preview-iframe');

    backdrop.classList.remove('opacity-100');
    backdrop.classList.add('opacity-0');
    panel.classList.remove('translate-x-0');
    panel.classList.add('translate-x-full');

    setTimeout(() => {
        modal.classList.add('hidden');
        iframe.src = 'about:blank'; // stop PDF stream
        document.body.style.overflow = '';
    }, 300);
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closePreview();
});
</script>
@endpush
