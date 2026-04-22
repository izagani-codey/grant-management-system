<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Submit New STRG Request</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <form action="{{ route('requests.store') }}" method="POST" enctype="multipart/form-data" id="create-request-form">
                @csrf

                {{-- Step 1: Request Type --}}
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-4">1. Select Request Type</h3>
                    <select name="request_type_id" id="request_type_id"
                            class="w-full rounded border-gray-300 text-sm"
                            onchange="onRequestTypeChange(this)" required>
                        <option value="">— Choose a request type —</option>
                        @foreach ($requestTypes as $type)
                            <option value="{{ $type->id }}"
                                    data-requires-vot="{{ $type->requires_vot ? '1' : '0' }}"
                                    data-requires-sig="{{ $type->requires_signature ? '1' : '0' }}"
                                    data-fields="{{ json_encode($type->field_schema ?? []) }}"
                                    data-templates="{{ json_encode($type->activeTemplates->map(fn($t) => ['id' => $t->id, 'name' => $t->name ?: $t->original_name, 'url' => route('documents.download', $t->id)])->values()) }}"
                                    @selected(old('request_type_id') == $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('request_type_id')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Step 2: Templates for Download --}}
                <div id="templates-section" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-base font-bold text-blue-800 mb-2">2. Reference Templates</h3>
                    <p class="text-sm text-blue-700 mb-3">Download and complete the following templates before uploading your documents.</p>
                    <ul id="templates-list" class="space-y-2"></ul>
                </div>

                {{-- Step 3: Staff2-defined fields --}}
                <div id="fields-section" class="hidden bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-4">3. Request Details</h3>
                    <div id="dynamic-fields-container" class="space-y-4"></div>
                </div>

                {{-- VOT Items --}}
                <div id="vot-section" class="hidden bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-4">Budget Breakdown (VOT)</h3>
                    <div id="vot-rows" class="space-y-3">
                        <div class="vot-row grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-gray-50 border rounded">
                            <div class="md:col-span-1">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">VOT Code</label>
                                <select class="vot-code w-full rounded border-gray-300 text-sm" onchange="fillVotDesc(this)">
                                    <option value="">Select...</option>
                                    @foreach ($votCodes as $vc)
                                        <option value="{{ $vc->code }}" data-desc="{{ $vc->description }}">{{ $vc->code }} — {{ $vc->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                                <input type="text" class="vot-desc w-full rounded border-gray-300 text-sm" placeholder="Description">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Amount (RM)</label>
                                <div class="flex gap-2">
                                    <input type="number" step="0.01" min="0" class="vot-amount w-full rounded border-gray-300 text-sm" placeholder="0.00" oninput="calcTotal()">
                                    <button type="button" onclick="removeVotRow(this)" class="px-2 rounded bg-red-100 text-red-700 text-sm font-bold hover:bg-red-200">✕</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addVotRow()" class="mt-3 px-4 py-2 rounded bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">+ Add VOT Item</button>
                    <div class="mt-3 flex justify-between items-center bg-gray-100 p-3 rounded">
                        <span class="text-sm font-medium text-gray-700">Total:</span>
                        <span class="font-bold text-blue-700">RM <span id="vot-total">0.00</span></span>
                    </div>
                    @error('vot_items') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Step 4: Description --}}
                <div id="main-form-section" class="hidden bg-white shadow-sm rounded-lg p-6 space-y-4">
                    <h3 class="text-base font-bold text-gray-800 mb-2">4. Description &amp; Documents</h3>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Justification / Description <span class="text-red-500">*</span></label>
                        <textarea name="description" rows="4" class="w-full rounded border-gray-300 text-sm" required>{{ old('description') }}</textarea>
                        @error('description') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Upload Documents <span class="text-gray-400 font-normal text-xs">(PDF, Word, Excel, JPG, PNG — max 5MB each)</span></label>
                        <input type="file" name="documents[]" multiple
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                        @error('documents.*') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Step 5: Signature (conditional) --}}
                <div id="signature-section" class="hidden bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-3">5. Applicant Signature</h3>
                    <p class="text-sm text-gray-600 mb-3">Draw your signature in the box below.</p>
                    <canvas id="sig-canvas" width="600" height="180"
                            class="border-2 border-gray-300 rounded w-full cursor-crosshair bg-white"></canvas>
                    <input type="hidden" name="signature_data" id="signature_data" value="{{ old('signature_data') }}">
                    <div class="flex gap-3 mt-2">
                        <button type="button" onclick="clearSig()" class="px-4 py-2 rounded border border-gray-300 text-sm text-gray-600 hover:bg-gray-50">Clear</button>
                    </div>
                    @error('signature_data') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Submit --}}
                <div id="submit-section" class="hidden">
                    <button type="submit" onclick="prepareSubmit()" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded hover:bg-blue-700 transition">
                        Submit Request for Verification
                    </button>
                </div>

            </form>
        </div>
    </div>

<script>
const votCodesData = @json($votCodes->map(fn($v) => ['code' => $v->code, 'description' => $v->description])->values());

function onRequestTypeChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) {
        hide(['templates-section','fields-section','vot-section','main-form-section','signature-section','submit-section']);
        return;
    }
    const requiresVot = opt.dataset.requiresVot === '1';
    const requiresSig = opt.dataset.requiresSig === '1';
    const fields      = JSON.parse(opt.dataset.fields || '[]');
    const templates   = JSON.parse(opt.dataset.templates || '[]');

    // Templates
    if (templates.length) {
        const list = document.getElementById('templates-list');
        list.innerHTML = templates.map(t =>
            `<li><a href="${t.url}" target="_blank" class="inline-flex items-center gap-2 text-sm text-blue-700 hover:underline font-medium">⬇ ${t.name}</a></li>`
        ).join('');
        show('templates-section');
    } else {
        hide('templates-section');
    }

    // Dynamic fields
    buildFields(fields);
    if (fields.length) show('fields-section'); else hide('fields-section');

    // VOT
    if (requiresVot) show('vot-section'); else hide('vot-section');

    // Main form + signature + submit always shown once type selected
    show('main-form-section');
    if (requiresSig) show('signature-section'); else hide('signature-section');
    show('submit-section');
}

function buildFields(fields) {
    const container = document.getElementById('dynamic-fields-container');
    container.innerHTML = '';
    const old = @json(old('field_values', []));
    fields.forEach(f => {
        const name = `field_values[${f.name}]`;
        const val  = old[f.name] ?? (f.default ?? '');
        const req  = f.required ? 'required' : '';
        const star = f.required ? '<span class="text-red-500">*</span>' : '';
        let input = '';
        if (f.type === 'textarea') {
            input = `<textarea name="${name}" rows="3" class="w-full rounded border-gray-300 text-sm" ${req}>${val}</textarea>`;
        } else if (f.type === 'date') {
            input = `<input type="date" name="${name}" value="${val}" class="w-full rounded border-gray-300 text-sm" ${req}>`;
        } else if (f.type === 'number') {
            input = `<input type="number" name="${name}" value="${val}" step="any" class="w-full rounded border-gray-300 text-sm" ${req}>`;
        } else {
            input = `<input type="text" name="${name}" value="${val}" class="w-full rounded border-gray-300 text-sm" ${req}>`;
        }
        container.insertAdjacentHTML('beforeend',
            `<div><label class="block text-sm font-medium text-gray-700 mb-1">${f.label ?? f.name} ${star}</label>${input}</div>`
        );
    });
}

function show(id) { document.getElementById(id)?.classList.remove('hidden'); }
function hide(ids) { [].concat(ids).forEach(id => document.getElementById(id)?.classList.add('hidden')); }

// VOT helpers
function addVotRow() {
    const template = document.querySelector('#vot-rows .vot-row').cloneNode(true);
    template.querySelectorAll('input, select').forEach(el => { el.value = ''; });
    document.getElementById('vot-rows').appendChild(template);
}
function removeVotRow(btn) {
    const rows = document.querySelectorAll('#vot-rows .vot-row');
    if (rows.length <= 1) { alert('At least one VOT item is required.'); return; }
    btn.closest('.vot-row').remove();
    calcTotal();
}
function fillVotDesc(sel) {
    const row  = sel.closest('.vot-row');
    const desc = sel.options[sel.selectedIndex]?.dataset.desc ?? '';
    const inp  = row.querySelector('.vot-desc');
    if (inp && !inp.value) inp.value = desc;
}
function calcTotal() {
    let t = 0;
    document.querySelectorAll('#vot-rows .vot-amount').forEach(i => t += parseFloat(i.value) || 0);
    document.getElementById('vot-total').textContent = t.toFixed(2);
}

// Signature
let sigCanvas, sigCtx, sigDrawing = false, sigLastX = 0, sigLastY = 0;
document.addEventListener('DOMContentLoaded', () => {
    sigCanvas = document.getElementById('sig-canvas');
    if (!sigCanvas) return;
    sigCtx = sigCanvas.getContext('2d');
    sigCtx.strokeStyle = '#1e293b';
    sigCtx.lineWidth   = 2;
    sigCtx.lineCap     = 'round';
    sigCanvas.addEventListener('mousedown',  e => { sigDrawing = true; [sigLastX, sigLastY] = pos(e, sigCanvas); });
    sigCanvas.addEventListener('mousemove',  e => { if (!sigDrawing) return; draw(pos(e, sigCanvas)); });
    sigCanvas.addEventListener('mouseup',    () => { sigDrawing = false; saveSignature(); });
    sigCanvas.addEventListener('mouseleave', () => { sigDrawing = false; });
    sigCanvas.addEventListener('touchstart', e => { e.preventDefault(); sigDrawing = true; [sigLastX, sigLastY] = pos(e.touches[0], sigCanvas); }, { passive: false });
    sigCanvas.addEventListener('touchmove',  e => { e.preventDefault(); if (sigDrawing) draw(pos(e.touches[0], sigCanvas)); }, { passive: false });
    sigCanvas.addEventListener('touchend',   () => { sigDrawing = false; saveSignature(); });

    // Restore from old() if validation failed
    const existing = document.getElementById('signature_data').value;
    if (existing && sigCanvas) {
        const img = new Image();
        img.onload = () => sigCtx.drawImage(img, 0, 0);
        img.src = existing;
    }

    // Trigger change if old request_type_id present
    const sel = document.getElementById('request_type_id');
    if (sel && sel.value) onRequestTypeChange(sel);
});
function pos(e, canvas) {
    const r = canvas.getBoundingClientRect();
    return [e.clientX - r.left, e.clientY - r.top];
}
function draw([x, y]) {
    sigCtx.beginPath();
    sigCtx.moveTo(sigLastX, sigLastY);
    sigCtx.lineTo(x, y);
    sigCtx.stroke();
    [sigLastX, sigLastY] = [x, y];
}
function clearSig() {
    sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
    document.getElementById('signature_data').value = '';
}
function saveSignature() {
    document.getElementById('signature_data').value = sigCanvas.toDataURL('image/png');
}

function prepareSubmit() {
    // Rename VOT inputs
    const rows = document.querySelectorAll('#vot-rows .vot-row');
    rows.forEach((row, i) => {
        const code = row.querySelector('.vot-code');
        const desc = row.querySelector('.vot-desc');
        const amt  = row.querySelector('.vot-amount');
        if (code) code.name = `vot_items[${i}][vot_code]`;
        if (desc) desc.name = `vot_items[${i}][description]`;
        if (amt)  amt.name  = `vot_items[${i}][amount]`;
    });
    if (document.getElementById('signature-section') && !document.getElementById('signature-section').classList.contains('hidden')) {
        saveSignature();
    }
}
</script>
</x-app-layout>
