<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Configure Preset Fields — {{ $document->name ?: $document->original_name }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Select which applicant and signatory fields will be auto-filled on this template. 
                    Set this once — it applies to every document generated from this template.
                </p>
            </div>
            <a href="{{ route('admin.request-types') }}"
               class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                &larr; Back to Request Types
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-lg flex items-center gap-2 mb-6">
                    <svg class="w-5 h-5 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('staff2.preset.save', $document->id) }}" method="POST" class="space-y-8">
                @csrf

                {{-- Section 1: APPLICANT INFORMATION --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                        <h2 class="text-base font-bold text-gray-900">APPLICANT INFORMATION</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Pulled automatically from applicant's profile</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="applicant_name" value="1" {{ !empty($config['applicant_name']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Full Name</span>
                            </label>
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="applicant_staff_id" value="1" {{ !empty($config['applicant_staff_id']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Staff ID</span>
                            </label>
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="applicant_designation" value="1" {{ !empty($config['applicant_designation']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Designation</span>
                            </label>
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="applicant_department" value="1" {{ !empty($config['applicant_department']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Department</span>
                            </label>
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="applicant_phone" value="1" {{ !empty($config['applicant_phone']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Phone</span>
                            </label>
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="applicant_employee_level" value="1" {{ !empty($config['applicant_employee_level']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Employee Level</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Section 2: DOCUMENT METADATA --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-base font-bold text-gray-900">DOCUMENT METADATA</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Generated automatically by the system</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="submission_date" value="1" {{ !empty($config['submission_date']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Submission Date</span>
                            </label>
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="reference_number" value="1" {{ !empty($config['reference_number']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Reference Number</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Section 3: SIGNATORY FIELDS --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                        <h2 class="text-base font-bold text-gray-900">SIGNATORY FIELDS</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Filled when Staff 2 selects a signatory at approval</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="final_signatory_name" value="1" {{ !empty($config['final_signatory_name']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Final Signatory Name</span>
                            </label>
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="final_signatory_designation" value="1" {{ !empty($config['final_signatory_designation']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Final Signatory Designation</span>
                            </label>
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="second_signatory_name" value="1" {{ !empty($config['second_signatory_name']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Second Signatory Name</span>
                            </label>
                            <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="second_signatory_designation" value="1" {{ !empty($config['second_signatory_designation']) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>Second Signatory Designation</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Note Section --}}
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-amber-800">
                            Only checked fields will appear as placeable zones in the zone designer.
                        </p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-4 pt-4">
                    <button type="submit" name="redirect_to_zones" value="1"
                            class="px-6 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                        Save & Open Zone Designer
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 bg-gray-600 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition-colors">
                        Save Configuration
                    </button>
                    <a href="{{ route('staff2.zones.edit', $document->id) }}"
                       class="px-6 py-2.5 text-gray-600 text-sm font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
