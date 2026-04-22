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
                                <div class="mb-6">
                                    <h4 class="text-sm font-bold text-gray-700 mb-2">{{ $rt->name }}</h4>
                                    <div class="space-y-3">
                                        @foreach($rt->activeTemplates as $tpl)
                                            <div x-data="zeRow('{{ $tpl->id }}', '{{ route('documents.preview', $tpl->id) }}', {{ $tpl->signature_zones ? json_encode($tpl->signature_zones) : 'null' }}, {{ json_encode($tpl->requestType?->field_schema ?? []) }}, {{ $tpl->field_zones ? json_encode($tpl->field_zones) : 'null' }})"
                                                 class="border border-gray-200 rounded-lg overflow-hidden">
                                                {{-- Template row --}}
                                                <div class="flex items-center justify-between text-sm bg-gray-50 px-3 py-2">
                                                    <div class="flex items-center gap-3 min-w-0">
                                                        <a href="{{ route('documents.download', $tpl->id) }}" target="_blank"
                                                           class="text-blue-600 hover:underline truncate">
                                                            {{ $tpl->name ?: $tpl->original_name }}
                                                        </a>
                                                        @if($tpl->signature_zones)
                                                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium shrink-0">Zones set</span>
                                                        @else
                                                            <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full shrink-0">No zones</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center gap-3 shrink-0">
                                                        <button type="button" @click="toggle()"
                                                            class="text-xs text-indigo-600 hover:underline font-medium">
                                                            <span x-text="zonesOpen ? 'Hide zones' : 'Set signature zones'"></span>
                                                        </button>
                                                        <form action="{{ route('documents.destroy', $tpl->id) }}" method="POST"
                                                              onsubmit="return confirm('Remove this template?')">
                                                            @csrf @method('DELETE')
                                                            <button class="text-xs text-red-500 hover:underline">Remove</button>
                                                        </form>
                                                    </div>
                                                </div>

                                                {{-- Signature zones panel (visual editor) --}}
                                                <div x-show="zonesOpen" x-cloak
                                                     class="p-4 bg-indigo-50 border-t border-indigo-100">

                                                    @if($tpl->isPdf())
                                                        {{-- Visual editor container --}}
                                                        <div id="ze-{{ $tpl->id }}">
                                                            {{-- Page navigation --}}
                                                            <div class="flex items-center gap-3 mb-3">
                                                                <button type="button" onclick="zeChangePage('{{ $tpl->id }}', -1)"
                                                                    class="w-6 h-6 flex items-center justify-center rounded bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 text-xs font-bold">‹</button>
                                                                <span class="text-xs text-gray-700">
                                                                    Page <span id="ze-page-{{ $tpl->id }}" class="font-bold">1</span>
                                                                    / <span id="ze-total-{{ $tpl->id }}" class="font-bold">…</span>
                                                                </span>
                                                                <button type="button" onclick="zeChangePage('{{ $tpl->id }}', 1)"
                                                                    class="w-6 h-6 flex items-center justify-center rounded bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 text-xs font-bold">›</button>
                                                                <span class="ml-auto text-xs text-gray-400">See legend below</span>
                                                            </div>

                                                            {{-- Canvas wrapper --}}
                                                            <div class="relative inline-block max-w-full overflow-auto border border-gray-300 rounded bg-white shadow-sm"
                                                                 id="ze-wrap-{{ $tpl->id }}" style="cursor:default;">
                                                                <canvas id="ze-canvas-{{ $tpl->id }}" class="block"></canvas>
                                                                {{-- Overlay for draggable boxes --}}
                                                                <div id="ze-overlay-{{ $tpl->id }}"
                                                                     class="absolute inset-0"
                                                                     style="pointer-events:none;">
                                                                    {{-- Applicant box --}}
                                                                    <div id="ze-box-applicant-{{ $tpl->id }}"
                                                                         class="ze-drag-box absolute hidden select-none"
                                                                         data-role="applicant"
                                                                         data-tpl="{{ $tpl->id }}"
                                                                         style="border:2px solid #3b82f6;background:rgba(59,130,246,0.12);pointer-events:auto;cursor:move;min-width:40px;min-height:20px;">
                                                                        <span class="absolute top-0.5 left-1 text-blue-700 font-bold pointer-events-none"
                                                                              style="font-size:9px;white-space:nowrap;line-height:1;">Applicant</span>
                                                                        <div class="ze-resize-handle"
                                                                             style="position:absolute;right:0;bottom:0;width:12px;height:12px;background:#3b82f6;cursor:se-resize;opacity:0.8;border-radius:2px 0 0 0;"></div>
                                                                    </div>
                                                                    {{-- Staff 2 box --}}
                                                                    <div id="ze-box-staff2-{{ $tpl->id }}"
                                                                         class="ze-drag-box absolute hidden select-none"
                                                                         data-role="staff2"
                                                                         data-tpl="{{ $tpl->id }}"
                                                                         style="border:2px solid #22c55e;background:rgba(34,197,94,0.12);pointer-events:auto;cursor:move;min-width:40px;min-height:20px;">
                                                                        <span class="absolute top-0.5 left-1 text-green-700 font-bold pointer-events-none"
                                                                              style="font-size:9px;white-space:nowrap;line-height:1;">Staff 2</span>
                                                                        <div class="ze-resize-handle"
                                                                             style="position:absolute;right:0;bottom:0;width:12px;height:12px;background:#22c55e;cursor:se-resize;opacity:0.8;border-radius:2px 0 0 0;"></div>
                                                                    </div>
                                                                    {{-- Field value boxes --}}
                                                                    @php $fColours = ['#6366f1','#f59e0b','#f43f5e','#14b8a6','#a855f7','#f97316','#0ea5e9','#84cc16']; @endphp
                                                                    @foreach(($tpl->requestType?->field_schema ?? []) as $fi => $field)
                                                                    @php $fc = $fColours[$fi % count($fColours)]; @endphp
                                                                    <div id="ze-field-{{ $field['name'] }}-{{ $tpl->id }}"
                                                                         class="ze-drag-box absolute hidden select-none"
                                                                         data-role="field" data-field="{{ $field['name'] }}" data-tpl="{{ $tpl->id }}"
                                                                         style="border:2px solid {{ $fc }};background:{{ $fc }}1e;pointer-events:auto;cursor:move;min-width:40px;min-height:16px;">
                                                                        <span class="absolute top-0.5 left-1 font-bold pointer-events-none"
                                                                              style="font-size:8px;white-space:nowrap;line-height:1;color:{{ $fc }};">{{ $field['label'] }}</span>
                                                                        <div class="ze-resize-handle"
                                                                             style="position:absolute;right:0;bottom:0;width:10px;height:10px;background:{{ $fc }};cursor:se-resize;opacity:0.8;border-radius:2px 0 0 0;"></div>
                                                                    </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>

                                                            {{-- Legend --}}
                                                            <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2 text-xs">
                                                                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-blue-500 opacity-70"></span><span class="text-gray-600">Applicant sig</span></span>
                                                                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-green-500 opacity-70"></span><span class="text-gray-600">Staff 2 sig</span></span>
                                                                <div id="ze-field-legend-{{ $tpl->id }}" class="contents"></div>
                                                            </div>
                                                            <p class="text-xs text-gray-400 mt-1">
                                                                Drag a box to position it · Drag the <strong>corner handle</strong> to resize · Navigate pages to place on a different page
                                                            </p>
                                                        </div>
                                                    @else
                                                        <p class="text-xs text-yellow-700 bg-yellow-50 border border-yellow-200 rounded p-3">
                                                            Visual editor is only available for PDF templates. This template is a {{ strtoupper($tpl->getFileExtension()) }} file — enter coordinates manually below.
                                                        </p>
                                                    @endif

                                                    {{-- Save form (works for both PDF and non-PDF) --}}
                                                    <form id="ze-form-{{ $tpl->id }}"
                                                          action="{{ route('admin.templates.zones', $tpl->id) }}" method="POST"
                                                          class="mt-4 pt-4 border-t border-indigo-200"
                                                          onsubmit="zePopulateForm('{{ $tpl->id }}')">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="applicant_page"   id="ze-ap-page-{{ $tpl->id }}">
                                                        <input type="hidden" name="applicant_x"      id="ze-ap-x-{{ $tpl->id }}">
                                                        <input type="hidden" name="applicant_y"      id="ze-ap-y-{{ $tpl->id }}">
                                                        <input type="hidden" name="applicant_width"  id="ze-ap-w-{{ $tpl->id }}">
                                                        <input type="hidden" name="applicant_height" id="ze-ap-h-{{ $tpl->id }}">
                                                        <input type="hidden" name="staff2_page"      id="ze-s2-page-{{ $tpl->id }}">
                                                        <input type="hidden" name="staff2_x"         id="ze-s2-x-{{ $tpl->id }}">
                                                        <input type="hidden" name="staff2_y"         id="ze-s2-y-{{ $tpl->id }}">
                                                        <input type="hidden" name="staff2_width"     id="ze-s2-w-{{ $tpl->id }}">
                                                        <input type="hidden" name="staff2_height"    id="ze-s2-h-{{ $tpl->id }}">

                                                        @if(!$tpl->isPdf())
                                                            {{-- Manual fallback for non-PDF --}}
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                                                @foreach(['applicant' => 'blue', 'staff2' => 'green'] as $role => $colour)
                                                                <div class="bg-white rounded border border-indigo-200 p-3">
                                                                    <p class="text-xs font-bold text-{{ $colour }}-700 mb-2">{{ $role === 'applicant' ? 'Applicant' : 'Staff 2' }} Signature</p>
                                                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                                                        @foreach(['page' => '1', 'x' => '10', 'y' => '240', 'width' => '70', 'height' => '25'] as $field => $ph)
                                                                        <div>
                                                                            <label class="block text-gray-500 mb-1">{{ ucfirst($field) }}{{ in_array($field, ['x','y','width','height']) ? ' (mm)' : '' }}</label>
                                                                            <input type="number" name="{{ $role }}_{{ $field }}"
                                                                                   value="{{ $tpl->signature_zones[$role][$field] ?? '' }}"
                                                                                   placeholder="{{ $ph }}" min="{{ $field === 'page' ? 1 : 0 }}" step="0.5"
                                                                                   class="w-full rounded border-gray-300 text-xs py-1">
                                                                        </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        <div class="flex gap-2 items-center flex-wrap">
                                                            <button type="submit"
                                                                class="px-4 py-1.5 bg-indigo-600 text-white text-xs font-bold rounded hover:bg-indigo-700">
                                                                Save Zones
                                                            </button>
                                                            @if($tpl->isPdf())
                                                                <button type="button" onclick="zeClearAll('{{ $tpl->id }}')"
                                                                    class="px-3 py-1.5 bg-white border border-gray-300 text-xs text-gray-600 rounded hover:bg-gray-50">
                                                                    Clear All
                                                                </button>
                                                                <span class="text-xs text-gray-400">Position boxes on the PDF above, then save.</span>
                                                            @else
                                                                <span class="text-xs text-gray-400">Leave fields blank to skip that signature.</span>
                                                            @endif
                                                        </div>
                                                    </form>

                                                    {{-- Field Zones save form --}}
                                                    @if($tpl->requestType?->field_schema)
                                                    <form id="ze-field-form-{{ $tpl->id }}"
                                                          action="{{ route('admin.templates.field-zones', $tpl->id) }}" method="POST"
                                                          class="mt-3 pt-3 border-t border-indigo-200"
                                                          onsubmit="zePopulateFieldForm('{{ $tpl->id }}')">
                                                        @csrf @method('PATCH')
                                                        <div id="ze-field-inputs-{{ $tpl->id }}"></div>
                                                        <div class="flex items-center gap-3 flex-wrap">
                                                            <button type="submit"
                                                                class="px-3 py-1.5 bg-teal-600 text-white text-xs font-bold rounded hover:bg-teal-700">
                                                                Save Field Zones
                                                            </button>
                                                            <button type="button" onclick="zeClearAllFields('{{ $tpl->id }}')"
                                                                class="px-3 py-1.5 bg-white border border-gray-300 text-xs text-gray-600 rounded hover:bg-gray-50">
                                                                Clear All Fields
                                                            </button>
                                                            @if($tpl->field_zones)
                                                                <span class="text-xs text-teal-700 font-medium">✓ {{ count($tpl->field_zones) }} field(s) configured</span>
                                                            @else
                                                                <span class="text-xs text-gray-400">Drag the coloured boxes to position each field value.</span>
                                                            @endif
                                                        </div>
                                                    </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
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

// ─── Alpine component factory for each template row ─────────────────────────
window.zeRow = function(tplId, previewUrl, existingZones, fieldSchema, existingFieldZones) {
    return {
        zonesOpen: false,
        toggle() {
            this.zonesOpen = !this.zonesOpen;
            if (!this.zonesOpen) return;
            setTimeout(() => {
                window.initZoneEditor(tplId, previewUrl, existingZones);
                if (fieldSchema && fieldSchema.length) {
                    window.zeInitFieldBoxes(tplId, fieldSchema, existingFieldZones);
                }
            }, 50);
        }
    };
};

// ─── Zone Editor ────────────────────────────────────────────────────────────
// keyed by tplId → { pdfDoc, currentPage, pageWidthMm, pageHeightMm, zones }
const _zeState = {};

function zeShowStatus(tplId, html) {
    const canvas = document.getElementById('ze-canvas-' + tplId);
    if (canvas) { canvas.style.display = 'none'; }
    let statusEl = document.getElementById('ze-status-' + tplId);
    if (!statusEl) {
        statusEl = document.createElement('div');
        statusEl.id = 'ze-status-' + tplId;
        const wrap = document.getElementById('ze-wrap-' + tplId);
        if (wrap) wrap.appendChild(statusEl);
    }
    statusEl.innerHTML = html;
}

async function initZoneEditor(tplId, previewUrl, existingZones) {
    if (_zeState[tplId]) return; // already initialised

    // Immediately show something so we know the function ran
    zeShowStatus(tplId, '<p class="text-xs text-blue-600 p-3 text-center">⏳ Initialising PDF viewer…</p>');

    if (typeof pdfjsLib === 'undefined') {
        zeShowStatus(tplId, '<p class="text-xs text-red-600 p-3">❌ PDF.js not loaded — check internet connection and hard-refresh (Ctrl+Shift+R).</p>');
        return;
    }

    zeShowStatus(tplId, '<p class="text-xs text-gray-500 p-4 text-center">⏳ Fetching PDF…</p>');

    const state = {
        pdfDoc: null, currentPage: 1,
        pageWidthMm: 210, pageHeightMm: 297,
        zones: {
            applicant: existingZones?.applicant
                ? { page: existingZones.applicant.page, x: existingZones.applicant.x, y: existingZones.applicant.y, w: existingZones.applicant.width, h: existingZones.applicant.height }
                : null,
            staff2: existingZones?.staff2
                ? { page: existingZones.staff2.page, x: existingZones.staff2.x, y: existingZones.staff2.y, w: existingZones.staff2.width, h: existingZones.staff2.height }
                : null,
        },
    };
    _zeState[tplId] = state;

    zeShowStatus(tplId, '<p class="text-xs text-gray-500 p-3 text-center">⏳ Fetching: ' + previewUrl + '</p>');
    try {
        const resp = await fetch(previewUrl, { credentials: 'same-origin' });
        if (!resp.ok) throw new Error('HTTP ' + resp.status + ' for: ' + previewUrl);
        const contentType = resp.headers.get('content-type') || '';
        if (!contentType.includes('pdf')) {
            throw new Error('Expected PDF but got: ' + contentType);
        }
        const data = await resp.arrayBuffer();
        zeShowStatus(tplId, '<p class="text-xs text-gray-500 p-4 text-center">⏳ Parsing PDF…</p>');
        state.pdfDoc = await pdfjsLib.getDocument({ data }).promise;
    } catch (e) {
        console.error('ZoneEditor fetch error for tpl ' + tplId + ':', e);
        zeShowStatus(tplId, '<p class="text-xs text-red-600 p-3 font-medium">❌ ' + e.message + '</p>');
        delete _zeState[tplId];
        return;
    }

    // Success — remove status, reveal canvas
    const statusEl = document.getElementById('ze-status-' + tplId);
    if (statusEl) statusEl.remove();
    const canvas = document.getElementById('ze-canvas-' + tplId);
    if (canvas) canvas.style.display = 'block';

    document.getElementById('ze-total-' + tplId).textContent = state.pdfDoc.numPages;
    await zeRenderPage(tplId);
    zeMakeDraggable(tplId);
}

async function zeRenderPage(tplId) {
    const state = _zeState[tplId];
    if (!state?.pdfDoc) return;

    try {
        const page = await state.pdfDoc.getPage(state.currentPage);
        const vp1  = page.getViewport({ scale: 1 });
        // Convert PDF points → mm  (1pt = 25.4/72 mm)
        state.pageWidthMm  = vp1.width  * 25.4 / 72;
        state.pageHeightMm = vp1.height * 25.4 / 72;

        // Scale to fit within the panel (max 700px)
        const wrap    = document.getElementById('ze-wrap-' + tplId);
        const maxPx   = Math.min(700, (wrap?.parentElement?.offsetWidth || 700) - 32);
        const scale   = Math.min(maxPx / vp1.width, 2.0);
        const viewport = page.getViewport({ scale });

        const canvas = document.getElementById('ze-canvas-' + tplId);
        const ctx    = canvas.getContext('2d');
        canvas.width  = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);
        canvas.style.display = 'block';

        await page.render({ canvasContext: ctx, viewport }).promise;

        document.getElementById('ze-page-' + tplId).textContent  = state.currentPage;
        document.getElementById('ze-total-' + tplId).textContent = state.pdfDoc.numPages;

        // Reposition existing boxes for this page
        Object.keys(state.zones).forEach(role => zeUpdateBoxPositionAny(tplId, role));
    } catch (e) {
        zeShowStatus(tplId, '<p class="text-xs text-red-600 p-3">Page render error: ' + e.message + '</p>');
    }
}

async function zeChangePage(tplId, delta) {
    const state = _zeState[tplId];
    if (!state?.pdfDoc) return;
    const next = state.currentPage + delta;
    if (next < 1 || next > state.pdfDoc.numPages) return;
    state.currentPage = next;
    await zeRenderPage(tplId);
}

function mmToPx(tplId, mmX, mmY) {
    const state  = _zeState[tplId];
    const canvas = document.getElementById('ze-canvas-' + tplId);
    const px = canvas.width  / state.pageWidthMm;
    const py = canvas.height / state.pageHeightMm;
    return { x: mmX * px, y: mmY * py };
}

function pxToMm(tplId, pxX, pxY) {
    const state  = _zeState[tplId];
    const canvas = document.getElementById('ze-canvas-' + tplId);
    const mx = state.pageWidthMm  / canvas.width;
    const my = state.pageHeightMm / canvas.height;
    return { x: pxX * mx, y: pxY * my };
}

// Generic box position updater — works for signature roles AND field_* keys
function zeUpdateBoxPositionAny(tplId, roleKey) {
    const state  = _zeState[tplId];
    const zone   = state.zones[roleKey];
    // Determine element ID: signatures use 'ze-box-{role}-{tplId}', fields use 'ze-field-{name}-{tplId}'
    let box;
    if (roleKey.startsWith('field_')) {
        box = document.getElementById('ze-field-' + roleKey.slice(6) + '-' + tplId);
    } else {
        box = document.getElementById('ze-box-' + roleKey + '-' + tplId);
    }
    if (!box) return;

    if (!zone || zone.page !== state.currentPage) {
        box.classList.add('hidden');
        return;
    }
    box.classList.remove('hidden');

    const canvas = document.getElementById('ze-canvas-' + tplId);
    const px = canvas.width  / state.pageWidthMm;
    const py = canvas.height / state.pageHeightMm;

    box.style.left   = (zone.x * px) + 'px';
    box.style.top    = (zone.y * py) + 'px';
    box.style.width  = (zone.w * px) + 'px';
    box.style.height = (zone.h * py) + 'px';
}

// Legacy alias for compatibility
function zeUpdateBoxPosition(tplId, role) { zeUpdateBoxPositionAny(tplId, role); }

// Generic drag+resize for any box element with a given zone key
function zeMakeDraggableBox(tplId, box, roleKey, defaultZone) {
    const handle = box.querySelector('.ze-resize-handle');
    const canvas = document.getElementById('ze-canvas-' + tplId);
    const state  = _zeState[tplId];

    if (!state.zones[roleKey]) {
        state.zones[roleKey] = defaultZone ?? { page: state.currentPage, x: 15, y: 30, w: 60, h: 8 };
        zeUpdateBoxPositionAny(tplId, roleKey);
    }

    let dragging = false, resizing = false, startX, startY, startL, startT, startW, startH;

    if (handle) {
        handle.addEventListener('mousedown', e => {
            e.stopPropagation();
            resizing = true;
            startX = e.clientX; startY = e.clientY;
            startW = box.offsetWidth; startH = box.offsetHeight;
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    }

    box.addEventListener('mousedown', e => {
        if (handle && e.target === handle) return;
        dragging = true;
        startX = e.clientX; startY = e.clientY;
        startL = box.offsetLeft; startT = box.offsetTop;
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    });

    function onMouseMove(e) {
        const dx = e.clientX - startX, dy = e.clientY - startY;
        if (dragging) {
            const newL = Math.max(0, Math.min(startL + dx, canvas.width  - box.offsetWidth));
            const newT = Math.max(0, Math.min(startT + dy, canvas.height - box.offsetHeight));
            box.style.left = newL + 'px';
            box.style.top  = newT + 'px';
        } else if (resizing) {
            box.style.width  = Math.max(40, startW + dx) + 'px';
            box.style.height = Math.max(16, startH + dy) + 'px';
        }
    }

    function onMouseUp() {
        if (dragging || resizing) {
            const mm  = pxToMm(tplId, box.offsetLeft,  box.offsetTop);
            const mmW = pxToMm(tplId, box.offsetWidth, box.offsetHeight);
            state.zones[roleKey] = {
                page: state.currentPage,
                x: parseFloat(mm.x.toFixed(2)),
                y: parseFloat(mm.y.toFixed(2)),
                w: parseFloat(mmW.x.toFixed(2)),
                h: parseFloat(mmW.y.toFixed(2)),
                font_size: state.zones[roleKey]?.font_size ?? 10,
            };
        }
        dragging = resizing = false;
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    }
}

function zeMakeDraggable(tplId) {
    const state = _zeState[tplId];
    ['applicant', 'staff2'].forEach((role, idx) => {
        const box = document.getElementById('ze-box-' + role + '-' + tplId);
        if (!box) return;
        const defaultMm = role === 'applicant'
            ? { page: state.currentPage, x: 15,  y: state.pageHeightMm - 45, w: 75, h: 25 }
            : { page: state.currentPage, x: 110, y: state.pageHeightMm - 45, w: 75, h: 25 };
        zeMakeDraggableBox(tplId, box, role, state.zones[role] ?? defaultMm);
    });
}

// ─── Field Zone Functions ─────────────────────────────────────────────────────
const ZE_FIELD_COLOURS = ['#6366f1','#f59e0b','#f43f5e','#14b8a6','#a855f7','#f97316','#0ea5e9','#84cc16'];

window.zeInitFieldBoxes = function(tplId, fieldSchema, existingFieldZones) {
    const state = _zeState[tplId];
    if (!state) { setTimeout(() => window.zeInitFieldBoxes(tplId, fieldSchema, existingFieldZones), 100); return; }

    const legend = document.getElementById('ze-field-legend-' + tplId);
    if (legend) legend.innerHTML = '';

    fieldSchema.forEach((field, idx) => {
        const colour  = ZE_FIELD_COLOURS[idx % ZE_FIELD_COLOURS.length];
        const zoneKey = 'field_' + field.name;
        const box     = document.getElementById('ze-field-' + field.name + '-' + tplId);
        if (!box) return;

        const existing = existingFieldZones?.[field.name];
        const defaultZone = existing
            ? { page: existing.page, x: existing.x, y: existing.y, w: existing.width, h: existing.height, font_size: existing.font_size ?? 10 }
            : { page: 1, x: 15, y: 20 + (idx * 12), w: 130, h: 8, font_size: 10 };
        state.zones[zoneKey] = defaultZone;

        zeMakeDraggableBox(tplId, box, zoneKey, defaultZone);
        zeUpdateBoxPositionAny(tplId, zoneKey);

        if (legend) {
            legend.insertAdjacentHTML('beforeend',
                '<span class="flex items-center gap-1 whitespace-nowrap">'
                + '<span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:' + colour + ';flex-shrink:0;"></span>'
                + '<span class="text-gray-600">' + field.label + '</span></span>');
        }
    });
};

window.zePopulateFieldForm = function(tplId) {
    const state     = _zeState[tplId];
    const container = document.getElementById('ze-field-inputs-' + tplId);
    if (!container || !state) return;
    container.innerHTML = '';
    for (const [key, z] of Object.entries(state.zones)) {
        if (!key.startsWith('field_')) continue;
        const fieldName = key.slice(6);
        [['page', z.page], ['x', z.x], ['y', z.y], ['width', z.w], ['height', z.h], ['font_size', z.font_size ?? 10]].forEach(([attr, val]) => {
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = 'field_zones[' + fieldName + '][' + attr + ']';
            inp.value = val ?? '';
            container.appendChild(inp);
        });
    }
};

window.zeClearAllFields = function(tplId) {
    const state = _zeState[tplId];
    if (!state) return;
    for (const key of Object.keys(state.zones)) {
        if (!key.startsWith('field_')) continue;
        const box = document.getElementById('ze-field-' + key.slice(6) + '-' + tplId);
        if (box) box.classList.add('hidden');
        delete state.zones[key];
    }
};

function zePopulateForm(tplId) {
    const state = _zeState[tplId];
    if (!state) return;

    ['applicant', 'staff2'].forEach(role => {
        const z   = state.zones[role];
        const pfx = role === 'applicant' ? 'ze-ap' : 'ze-s2';
        const set = (suffix, val) => {
            const el = document.getElementById(pfx + '-' + suffix + '-' + tplId);
            if (el) el.value = val ?? '';
        };
        if (z) {
            set('page', z.page);
            set('x',    z.x);
            set('y',    z.y);
            set('w',    z.w);
            set('h',    z.h);
        }
    });
}

function zeClearAll(tplId) {
    const state = _zeState[tplId];
    if (!state) return;
    state.zones.applicant = null;
    state.zones.staff2    = null;
    ['applicant', 'staff2'].forEach(role => {
        document.getElementById('ze-box-' + role + '-' + tplId)?.classList.add('hidden');
    });
}
// ─── End Zone Editor ─────────────────────────────────────────────────────────

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
    }
</script>
</x-app-layout>
