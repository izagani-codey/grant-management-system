<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Recommendation Dashboard</h1>
            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">Staff 2</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(app()->environment('local') && Route::has('dev.login'))
                @include('dashboard._dev-switcher')
            @endif

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Stats Row --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Awaiting Review</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $dashboardStats['staff1_reviewed'] ?? 0 }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Approved</p>
                    <p class="text-3xl font-bold text-green-600">{{ $dashboardStats['staff2_approved'] ?? 0 }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Declined</p>
                    <p class="text-3xl font-bold text-red-600">{{ $dashboardStats['declined'] ?? 0 }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Completed</p>
                    <p class="text-3xl font-bold text-gray-700">{{ $dashboardStats['completed'] ?? 0 }}</p>
                </div>
            </div>

            {{-- Panel 1: My Queue (STAFF1_REVIEWED) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-blue-50">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">My Queue</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Requests verified by Staff 1, awaiting your recommendation</p>
                    </div>
                    <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full">
                        {{ $myQueue->count() }} request(s)
                    </span>
                </div>

                @if($myQueue->isEmpty())
                    <div class="px-6 py-10 text-center text-gray-500">
                        <p class="text-sm">No requests awaiting review. All caught up!</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Applicant</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Submitted</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($myQueue as $req)
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $req->ref_number }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $req->user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $req->user->email }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $req->requestType->name }}</td>
                                        <td class="px-4 py-3 text-gray-700 font-medium">
                                            RM {{ number_format($req->total_amount ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $req->created_at->format('d M Y') }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('requests.show', $req->id) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded hover:bg-blue-700 transition">
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Panel 2: Override Queue (SUBMITTED) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-orange-50">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            Override Queue
                            <span class="text-xs bg-orange-500 text-white px-2 py-0.5 rounded-full font-bold">STAFF2</span>
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Documented Staff 2 override: act directly on submitted requests</p>
                    </div>
                </div>

                @if($overrideQueue->isEmpty())
                    <div class="px-6 py-10 text-center text-gray-500">
                        <p class="text-sm">No submitted requests to override right now.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-orange-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Applicant</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Submitted</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($overrideQueue as $req)
                                    <tr class="hover:bg-orange-50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $req->ref_number }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $req->user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $req->user->email }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $req->requestType->name }}</td>
                                        <td class="px-4 py-3 text-gray-700 font-medium">
                                            RM {{ number_format($req->total_amount ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $req->created_at->format('d M Y') }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('requests.show', $req->id) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-orange-600 text-white text-xs font-semibold rounded hover:bg-orange-700 transition">
                                                Override Review
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Panel 3: Configuration (tabbed) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-base font-bold text-gray-900">Configuration</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Manage request types, templates, checklists, and user fields</p>
                </div>

                {{-- Tab navigation --}}
                <div x-data="{ tab: 'types' }" class="p-6 space-y-6">
                    <div class="flex gap-1 border-b border-gray-200 mb-6">
                        <button @click="tab = 'types'"
                            :class="tab === 'types' ? 'border-b-2 border-blue-600 text-blue-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 text-sm transition">
                            Request Types
                        </button>
                        <button @click="tab = 'templates'"
                            :class="tab === 'templates' ? 'border-b-2 border-blue-600 text-blue-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 text-sm transition">
                            Templates
                        </button>
                        <button @click="tab = 'checklist'"
                            :class="tab === 'checklist' ? 'border-b-2 border-blue-600 text-blue-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 text-sm transition">
                            Checklist Builder
                        </button>
                        <button @click="tab = 'fields'"
                            :class="tab === 'fields' ? 'border-b-2 border-blue-600 text-blue-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 text-sm transition">
                            User Fields
                        </button>
                    </div>

                    {{-- Tab: Request Types --}}
                    <div x-show="tab === 'types'" x-cloak>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm mb-6">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">VOT</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Signature</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Active</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Requests</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($configRequestTypes as $rt)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $rt->name }}</td>
                                            <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $rt->description ?: '—' }}</td>
                                            <td class="px-4 py-3 text-center">
                                                @if($rt->requires_vot)
                                                    <span class="text-green-600 font-bold text-xs">Yes</span>
                                                @else
                                                    <span class="text-gray-400 text-xs">No</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if($rt->requires_signature)
                                                    <span class="text-green-600 font-bold text-xs">Yes</span>
                                                @else
                                                    <span class="text-gray-400 text-xs">No</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if($rt->is_active)
                                                    <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                                                @else
                                                    <span class="inline-block w-2 h-2 rounded-full bg-gray-300"></span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">{{ $rt->requests_count ?? $rt->requests()->count() }}</td>
                                            <td class="px-4 py-3">
                                                <button type="button"
                                                    onclick="openEditTypeModal({{ $rt->id }}, @js($rt->name), @js($rt->description), {{ $rt->requires_vot ? 'true' : 'false' }}, {{ $rt->requires_signature ? 'true' : 'false' }}, {{ $rt->is_active ? 'true' : 'false' }})"
                                                    class="text-xs text-blue-600 hover:underline font-medium">
                                                    Edit
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Add new request type --}}
                        <div class="border border-dashed border-gray-300 rounded-lg p-4">
                            <h4 class="text-sm font-bold text-gray-700 mb-3">Add New Request Type</h4>
                            <form action="{{ route('admin.request-types.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                @csrf
                                <input type="text" name="name" placeholder="Type name *" required
                                       class="rounded border-gray-300 text-sm">
                                <input type="text" name="description" placeholder="Description (optional)"
                                       class="rounded border-gray-300 text-sm">
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-1 text-sm text-gray-600">
                                        <input type="checkbox" name="requires_vot" value="1"> VOT required
                                    </label>
                                    <label class="flex items-center gap-1 text-sm text-gray-600">
                                        <input type="checkbox" name="requires_signature" value="1"> Signature required
                                    </label>
                                    <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white text-xs font-bold rounded hover:bg-blue-700">
                                        Add
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Tab: Templates --}}
                    <div x-show="tab === 'templates'" x-cloak>
                        @foreach($configRequestTypes as $rt)
                            @if($rt->activeTemplates->isNotEmpty())
                                <div class="mb-4">
                                    <h4 class="text-sm font-bold text-gray-700 mb-2">{{ $rt->name }}</h4>
                                    <ul class="space-y-1 mb-3">
                                        @foreach($rt->activeTemplates as $tpl)
                                            <li class="flex items-center justify-between text-sm bg-gray-50 rounded px-3 py-2">
                                                <a href="{{ route('documents.download', $tpl->id) }}" target="_blank"
                                                   class="text-blue-600 hover:underline">
                                                    {{ $tpl->name ?: $tpl->original_name }}
                                                </a>
                                                <form action="{{ route('documents.destroy', $tpl->id) }}" method="POST"
                                                      onsubmit="return confirm('Remove this template?')">
                                                    @csrf @method('DELETE')
                                                    <button class="text-xs text-red-500 hover:underline">Remove</button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endforeach

                        <div class="border border-dashed border-gray-300 rounded-lg p-4 mt-4">
                            <h4 class="text-sm font-bold text-gray-700 mb-3">Upload New Template</h4>
                            <form action="{{ route('form-templates.store') }}" method="POST" enctype="multipart/form-data"
                                  class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                                @csrf
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Request Type</label>
                                    <select name="request_type_id" class="w-full rounded border-gray-300 text-sm">
                                        <option value="">— General —</option>
                                        @foreach($configRequestTypes as $rt)
                                            <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Display Name (optional)</label>
                                    <input type="text" name="name" placeholder="e.g. Application Form"
                                           class="w-full rounded border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">File *</label>
                                    <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                           class="w-full text-sm text-gray-500">
                                </div>
                                <div>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded hover:bg-blue-700 w-full">
                                        Upload
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Tab: Checklist Builder --}}
                    <div x-show="tab === 'checklist'" x-cloak class="space-y-4">
                        @foreach($configRequestTypes as $rt)
                            <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                                <button @click="open = !open"
                                    class="w-full px-4 py-3 text-left flex items-center justify-between bg-gray-50 hover:bg-gray-100 transition">
                                    <span class="text-sm font-semibold text-gray-800">{{ $rt->name }}</span>
                                    <span class="text-xs text-gray-500">{{ $rt->checklistItems->count() }} item(s)</span>
                                </button>
                                <div x-show="open" x-cloak class="p-4 space-y-2">
                                    @forelse($rt->checklistItems as $item)
                                        <div class="flex items-center justify-between bg-gray-50 rounded px-3 py-2 text-sm">
                                            <div>
                                                <span class="{{ $item->trashed() ? 'line-through text-gray-400' : 'text-gray-800' }}">
                                                    {{ $item->label }}
                                                </span>
                                                @if($item->is_required)
                                                    <span class="text-red-500 text-xs ml-1">*required</span>
                                                @endif
                                            </div>
                                            @if(!$item->trashed())
                                                <form action="{{ route('admin.checklists.destroy', $item->id) }}" method="POST"
                                                      onsubmit="return confirm('Remove this checklist item?')">
                                                    @csrf @method('DELETE')
                                                    <button class="text-xs text-red-500 hover:underline">Remove</button>
                                                </form>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-xs text-gray-400">No checklist items yet.</p>
                                    @endforelse

                                    <form action="{{ route('admin.checklists.store') }}" method="POST"
                                          class="flex gap-2 mt-3 items-center">
                                        @csrf
                                        <input type="hidden" name="request_type_id" value="{{ $rt->id }}">
                                        <input type="text" name="label" placeholder="New checklist item..." required
                                               class="flex-1 rounded border-gray-300 text-sm">
                                        <label class="flex items-center gap-1 text-xs text-gray-600 whitespace-nowrap">
                                            <input type="checkbox" name="is_required" value="1" checked> Required
                                        </label>
                                        <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded hover:bg-blue-700">
                                            Add
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Tab: User Fields --}}
                    <div x-show="tab === 'fields'" x-cloak class="space-y-4">
                        <p class="text-xs text-gray-500">Define dynamic fields that users fill in when submitting each request type.</p>
                        @foreach($configRequestTypes as $rt)
                            <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                                <button @click="open = !open"
                                    class="w-full px-4 py-3 text-left flex items-center justify-between bg-gray-50 hover:bg-gray-100 transition">
                                    <span class="text-sm font-semibold text-gray-800">{{ $rt->name }}</span>
                                    <span class="text-xs text-gray-500">{{ count($rt->field_schema ?? []) }} field(s)</span>
                                </button>
                                <div x-show="open" x-cloak class="p-4 space-y-2">
                                    @php $schema = $rt->field_schema ?? []; @endphp
                                    @forelse($schema as $fi => $field)
                                        <div class="flex items-center gap-3 bg-gray-50 rounded px-3 py-2 text-sm">
                                            <span class="font-medium text-gray-800 w-32 truncate" title="{{ $field['label'] ?? $field['name'] }}">
                                                {{ $field['label'] ?? $field['name'] }}
                                            </span>
                                            <span class="text-xs text-gray-400 w-16">{{ $field['type'] ?? 'text' }}</span>
                                            <span class="text-xs {{ ($field['required'] ?? false) ? 'text-red-500' : 'text-gray-400' }}">
                                                {{ ($field['required'] ?? false) ? 'required' : 'optional' }}
                                            </span>
                                            <span class="flex-1 text-xs text-gray-400 truncate">name: {{ $field['name'] }}</span>
                                            <form action="{{ route('admin.request-types.update', $rt->id) }}" method="POST">
                                                @csrf @method('PUT')
                                                @php
                                                    $newSchema = array_values(array_filter($schema, fn($f, $k) => $k !== $fi, ARRAY_FILTER_USE_BOTH));
                                                @endphp
                                                <input type="hidden" name="name" value="{{ $rt->name }}">
                                                <input type="hidden" name="field_schema" value="{{ json_encode($newSchema) }}">
                                                <button type="submit" onclick="return confirm('Remove this field?')"
                                                        class="text-xs text-red-500 hover:underline">
                                                    Remove
                                                </button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="text-xs text-gray-400">No custom fields defined.</p>
                                    @endforelse

                                    {{-- Add field form --}}
                                    <form action="{{ route('admin.request-types.update', $rt->id) }}" method="POST"
                                          class="mt-3 grid grid-cols-2 md:grid-cols-5 gap-2 items-end"
                                          x-data="addFieldForm({{ json_encode($schema) }})">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="name" value="{{ $rt->name }}">
                                        <input type="hidden" name="field_schema" x-bind:value="newSchema">

                                        <input type="text" x-model="newField.label" placeholder="Label *" required
                                               class="rounded border-gray-300 text-sm">
                                        <input type="text" x-model="newField.name" placeholder="Field key (e.g. project_title) *" required
                                               class="rounded border-gray-300 text-sm">
                                        <select x-model="newField.type" class="rounded border-gray-300 text-sm">
                                            <option value="text">text</option>
                                            <option value="textarea">textarea</option>
                                            <option value="number">number</option>
                                            <option value="date">date</option>
                                        </select>
                                        <label class="flex items-center gap-1 text-xs text-gray-600">
                                            <input type="checkbox" x-model="newField.required"> Required
                                        </label>
                                        <button type="submit" @click="prepareSchema()"
                                                class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded hover:bg-blue-700">
                                            Add Field
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>

{{-- Edit Request Type Modal --}}
<div id="edit-type-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-base font-bold text-gray-900 mb-4">Edit Request Type</h3>
        <form id="edit-type-form" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Name *</label>
                <input type="text" name="name" id="edit-type-name" required class="w-full rounded border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                <textarea name="description" id="edit-type-desc" rows="2" class="w-full rounded border-gray-300 text-sm"></textarea>
            </div>
            <div class="flex gap-6">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="requires_vot" id="edit-type-vot" value="1"> VOT required
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="requires_signature" id="edit-type-sig" value="1"> Signature required
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" id="edit-type-active" value="1"> Active
                </label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white text-sm font-bold py-2 rounded hover:bg-blue-700">Save</button>
                <button type="button" onclick="closeEditTypeModal()" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditTypeModal(id, name, desc, vot, sig, active) {
    document.getElementById('edit-type-form').action = `/admin/request-types/${id}`;
    document.getElementById('edit-type-name').value = name;
    document.getElementById('edit-type-desc').value = desc || '';
    document.getElementById('edit-type-vot').checked = vot;
    document.getElementById('edit-type-sig').checked = sig;
    document.getElementById('edit-type-active').checked = active;
    document.getElementById('edit-type-modal').classList.remove('hidden');
}
function closeEditTypeModal() {
    document.getElementById('edit-type-modal').classList.add('hidden');
}

function addFieldForm(existingSchema) {
    return {
        newField: { label: '', name: '', type: 'text', required: false },
        newSchema: JSON.stringify(existingSchema),
        prepareSchema() {
            const updated = [...existingSchema, {
                label: this.newField.label,
                name: this.newField.name.replace(/\s+/g, '_').toLowerCase(),
                type: this.newField.type,
                required: this.newField.required,
            }];
            this.newSchema = JSON.stringify(updated);
        }
    };
}
</script>
</x-app-layout>
