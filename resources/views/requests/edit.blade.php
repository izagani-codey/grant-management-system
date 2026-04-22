<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit &amp; Resubmit — {{ $grantRequest->ref_number }}
            </h2>
            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-bold rounded-full">
                Revision #{{ $grantRequest->revision_count + 1 }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Return reason --}}
            @if($grantRequest->return_reason)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="font-bold text-yellow-700 mb-1">Reason for Return</h3>
                    <p class="text-yellow-800 text-sm">{{ $grantRequest->return_reason }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                    <p class="text-sm font-semibold text-red-800 mb-2">Please fix the following:</p>
                    <ul class="list-disc pl-5 text-sm text-red-700 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('requests.update', $grantRequest->id) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  onsubmit="prepareSubmit()">
                @csrf
                @method('PATCH')
                <input type="hidden" name="request_type_id" value="{{ $grantRequest->request_type_id }}">

                {{-- Reference templates --}}
                @if($templates->isNotEmpty())
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-5">
                        <h3 class="text-sm font-bold text-blue-800 mb-2">Reference Templates</h3>
                        <ul class="space-y-2">
                            @foreach($templates as $template)
                                <li>
                                    <a href="{{ route('documents.download', $template->id) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-2 text-sm text-blue-700 hover:underline font-medium">
                                        ⬇ {{ $template->name ?: $template->original_name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="bg-white shadow-sm rounded-lg p-6 space-y-5">
                    <h3 class="text-base font-bold text-gray-800">Request Details</h3>

                    {{-- Request type (read-only) --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Request Type</label>
                        <input type="text" value="{{ $grantRequest->requestType->name }}"
                               class="w-full rounded border-gray-300 bg-gray-100 text-sm" readonly>
                    </div>

                    {{-- Staff2-defined fields --}}
                    @php
                        $fieldSchema  = $grantRequest->requestType->field_schema ?? [];
                        $fieldValues  = $grantRequest->field_values ?? [];
                    @endphp
                    @foreach($fieldSchema as $field)
                        @php
                            $fieldName = $field['name'];
                            $value     = old("field_values.{$fieldName}", $fieldValues[$fieldName] ?? ($field['default'] ?? ''));
                            $required  = $field['required'] ?? false;
                        @endphp
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $field['label'] ?? $fieldName }}
                                @if($required) <span class="text-red-500">*</span> @endif
                            </label>
                            @if(($field['type'] ?? 'text') === 'textarea')
                                <textarea name="field_values[{{ $fieldName }}]" rows="3"
                                          class="w-full rounded border-gray-300 text-sm"
                                          @if($required) required @endif>{{ $value }}</textarea>
                            @elseif(($field['type'] ?? 'text') === 'date')
                                <input type="date" name="field_values[{{ $fieldName }}]" value="{{ $value }}"
                                       class="w-full rounded border-gray-300 text-sm" @if($required) required @endif>
                            @elseif(($field['type'] ?? 'text') === 'number')
                                <input type="number" step="any" name="field_values[{{ $fieldName }}]" value="{{ $value }}"
                                       class="w-full rounded border-gray-300 text-sm" @if($required) required @endif>
                            @else
                                <input type="text" name="field_values[{{ $fieldName }}]" value="{{ $value }}"
                                       class="w-full rounded border-gray-300 text-sm" @if($required) required @endif>
                            @endif
                        </div>
                    @endforeach

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Justification / Description <span class="text-red-500">*</span></label>
                        <textarea name="description" rows="4" class="w-full rounded border-gray-300 text-sm" required>{{ old('description', $grantRequest->description ?? ($grantRequest->payload['description'] ?? '')) }}</textarea>
                        @error('description') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- VOT items --}}
                    @if($grantRequest->requestType->requires_vot)
                        @php
                            $existingItems = collect(old('vot_items', $grantRequest->vot_items ?? []))->values();
                            if ($existingItems->isEmpty()) $existingItems = collect([['vot_code'=>'','description'=>'','amount'=>0]]);
                            $votCodes = \App\Models\VotCode::active()->ordered()->get();
                            $lock = $grantRequest->shouldLockVotItems();
                        @endphp
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                VOT Breakdown
                                @if($lock) <span class="text-xs text-orange-600 ml-2">Locked after verification</span> @endif
                            </label>
                            @if($lock)
                                @foreach($existingItems as $i => $item)
                                    <div class="grid grid-cols-3 gap-2 p-2 bg-orange-50 border border-orange-200 rounded text-sm mb-2">
                                        <span class="font-medium">{{ $item['vot_code'] ?? '' }}</span>
                                        <span class="text-gray-600">{{ $item['description'] ?? '' }}</span>
                                        <span class="font-medium text-right">RM {{ number_format($item['amount'] ?? 0, 2) }}</span>
                                        <input type="hidden" name="vot_items[{{ $i }}][vot_code]" value="{{ $item['vot_code'] }}">
                                        <input type="hidden" name="vot_items[{{ $i }}][description]" value="{{ $item['description'] }}">
                                        <input type="hidden" name="vot_items[{{ $i }}][amount]" value="{{ $item['amount'] }}">
                                    </div>
                                @endforeach
                            @else
                                <div id="edit-vot-rows" class="space-y-3">
                                    @foreach($existingItems as $i => $item)
                                        <div class="vot-row grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-gray-50 border rounded">
                                            <div class="md:col-span-1">
                                                <select class="vot-code w-full rounded border-gray-300 text-sm" onchange="fillVotDesc(this)">
                                                    <option value="">Select...</option>
                                                    @foreach($votCodes as $vc)
                                                        <option value="{{ $vc->code }}" data-desc="{{ $vc->description }}"
                                                                @selected(($item['vot_code'] ?? '') === $vc->code)>
                                                            {{ $vc->code }} — {{ $vc->description }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="md:col-span-2">
                                                <input type="text" class="vot-desc w-full rounded border-gray-300 text-sm"
                                                       value="{{ $item['description'] ?? '' }}" placeholder="Description">
                                            </div>
                                            <div>
                                                <div class="flex gap-2">
                                                    <input type="number" step="0.01" min="0" class="vot-amount w-full rounded border-gray-300 text-sm"
                                                           value="{{ $item['amount'] ?? 0 }}" oninput="calcTotal()">
                                                    <button type="button" onclick="removeVotRow(this)"
                                                            class="px-2 rounded bg-red-100 text-red-700 text-sm font-bold">✕</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" onclick="addVotRow()"
                                        class="mt-3 px-4 py-2 rounded bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">+ Add VOT Item</button>
                                <div class="mt-3 flex justify-between bg-gray-100 p-3 rounded">
                                    <span class="text-sm font-medium text-gray-700">Total:</span>
                                    <span class="font-bold text-blue-700">RM <span id="vot-total">{{ number_format(collect($existingItems)->sum(fn($i) => $i['amount'] ?? 0), 2) }}</span></span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Existing user-submitted documents --}}
                    @if($userDocuments->isNotEmpty())
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Previously Submitted Documents</label>
                            <ul class="space-y-1">
                                @foreach($userDocuments as $doc)
                                    <li class="flex items-center gap-2 text-sm">
                                        <span class="text-gray-400">📄</span>
                                        <a href="{{ route('documents.download', $doc->id) }}"
                                           target="_blank"
                                           class="text-blue-700 hover:underline">
                                            {{ $doc->original_name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Upload new documents --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Upload New / Replacement Documents
                            <span class="text-gray-400 font-normal text-xs">(PDF, Word, Excel, JPG, PNG — max 5MB each)</span>
                        </label>
                        <input type="file" name="documents[]" multiple
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700">
                        @error('documents.*') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Signature (if required) --}}
                    @if($grantRequest->requestType->requires_signature)
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Signature <span class="text-gray-400 font-normal text-xs">(optional if already signed)</span></label>
                            <canvas id="sig-canvas" width="600" height="180"
                                    class="border-2 border-gray-300 rounded w-full cursor-crosshair bg-white"></canvas>
                            <input type="hidden" name="signature_data" id="signature_data">
                            <button type="button" onclick="clearSig()" class="mt-2 px-4 py-1 rounded border border-gray-300 text-sm text-gray-600 hover:bg-gray-50">Clear</button>
                        </div>
                    @endif

                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700 transition">
                        ↺ Resubmit for Verification
                    </button>
                    <a href="{{ route('dashboard') }}" class="px-6 py-3 border border-gray-300 rounded text-gray-600 hover:bg-gray-50 text-sm font-semibold flex items-center">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

<script>
function fillVotDesc(sel) {
    const row = sel.closest('.vot-row');
    const desc = sel.options[sel.selectedIndex]?.dataset.desc ?? '';
    const inp = row.querySelector('.vot-desc');
    if (inp && !inp.value) inp.value = desc;
}
function addVotRow() {
    const rows = document.getElementById('edit-vot-rows');
    if (!rows) return;
    const clone = rows.querySelector('.vot-row').cloneNode(true);
    clone.querySelectorAll('input,select').forEach(el => el.value = '');
    rows.appendChild(clone);
}
function removeVotRow(btn) {
    const rows = document.querySelectorAll('#edit-vot-rows .vot-row');
    if (rows.length <= 1) { alert('At least one VOT item is required.'); return; }
    btn.closest('.vot-row').remove();
    calcTotal();
}
function calcTotal() {
    let t = 0;
    document.querySelectorAll('#edit-vot-rows .vot-amount').forEach(i => t += parseFloat(i.value)||0);
    const el = document.getElementById('vot-total');
    if (el) el.textContent = t.toFixed(2);
}

// Signature
let sigCanvas, sigCtx, sigDrawing = false, sigLastX = 0, sigLastY = 0;
document.addEventListener('DOMContentLoaded', () => {
    sigCanvas = document.getElementById('sig-canvas');
    if (!sigCanvas) return;
    sigCtx = sigCanvas.getContext('2d');
    sigCtx.strokeStyle = '#1e293b';
    sigCtx.lineWidth = 2;
    sigCtx.lineCap = 'round';
    sigCanvas.addEventListener('mousedown',  e => { sigDrawing = true; [sigLastX, sigLastY] = pos(e); });
    sigCanvas.addEventListener('mousemove',  e => { if (!sigDrawing) return; draw(pos(e)); });
    sigCanvas.addEventListener('mouseup',    () => { sigDrawing = false; });
    sigCanvas.addEventListener('touchstart', e => { e.preventDefault(); sigDrawing = true; [sigLastX, sigLastY] = pos(e.touches[0]); }, { passive: false });
    sigCanvas.addEventListener('touchmove',  e => { e.preventDefault(); if (sigDrawing) draw(pos(e.touches[0])); }, { passive: false });
    sigCanvas.addEventListener('touchend',   () => { sigDrawing = false; });
});
function pos(e) { const r = sigCanvas.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; }
function draw([x,y]) {
    sigCtx.beginPath(); sigCtx.moveTo(sigLastX, sigLastY); sigCtx.lineTo(x,y); sigCtx.stroke();
    [sigLastX, sigLastY] = [x, y];
}
function clearSig() { sigCtx?.clearRect(0, 0, sigCanvas.width, sigCanvas.height); document.getElementById('signature_data').value = ''; }

function prepareSubmit() {
    // Rename VOT inputs
    const rows = document.querySelectorAll('#edit-vot-rows .vot-row');
    rows.forEach((row, i) => {
        const code = row.querySelector('.vot-code');
        const desc = row.querySelector('.vot-desc');
        const amt  = row.querySelector('.vot-amount');
        if (code) code.name = `vot_items[${i}][vot_code]`;
        if (desc) desc.name = `vot_items[${i}][description]`;
        if (amt)  amt.name  = `vot_items[${i}][amount]`;
    });
    // Save signature if canvas exists
    if (sigCanvas) document.getElementById('signature_data').value = sigCanvas.toDataURL('image/png');
}
</script>
</x-app-layout>
