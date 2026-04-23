<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Zone Designer</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $document->name ?: $document->original_name }}
                    &mdash; {{ $pageCount }} page{{ $pageCount !== 1 ? 's' : '' }}
                </p>
            </div>
            <a href="{{ route('admin.request-types') }}"
               class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                &larr; Back to Request Types
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="zoneDesigner()"
         x-init="loadPdf()">

        {{-- ── TOOLBAR ─────────────────────────────────────────────── --}}
        <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm mb-4 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex flex-wrap items-center gap-2">

                {{-- Tool buttons --}}
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider mr-1">Place:</span>

                <button type="button"
                        @click="activeTool = (activeTool === 'applicant_signature' ? null : 'applicant_signature')"
                        :class="activeTool === 'applicant_signature'
                            ? 'bg-blue-700 ring-2 ring-blue-400 text-white'
                            : 'bg-blue-100 text-blue-800 hover:bg-blue-200'"
                        class="text-sm px-3 py-1.5 rounded font-medium transition-all">
                    ✏ Applicant Signature
                </button>

                <button type="button"
                        @click="activeTool = (activeTool === 'staff2_signature' ? null : 'staff2_signature')"
                        :class="activeTool === 'staff2_signature'
                            ? 'bg-purple-700 ring-2 ring-purple-400 text-white'
                            : 'bg-purple-100 text-purple-800 hover:bg-purple-200'"
                        class="text-sm px-3 py-1.5 rounded font-medium transition-all">
                    ✏ Staff 2 Signature
                </button>

                @foreach($fieldSchema as $field)
                <button type="button"
                        @click="activeTool = (activeTool === 'field_{{ $field['name'] }}' ? null : 'field_{{ $field['name'] }}')"
                        :class="activeTool === 'field_{{ $field['name'] }}'
                            ? 'bg-green-700 ring-2 ring-green-400 text-white'
                            : 'bg-green-100 text-green-800 hover:bg-green-200'"
                        class="text-sm px-3 py-1.5 rounded font-medium transition-all">
                    ✏ {{ $field['label'] ?? $field['name'] }}
                </button>
                @endforeach

                <div class="flex-1"></div>

                {{-- Cancel --}}
                <button type="button"
                        x-show="activeTool !== null"
                        @click="activeTool = null"
                        class="text-sm px-3 py-1.5 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 transition-all">
                    Cancel
                </button>

                {{-- Save --}}
                <button type="button"
                        @click="saveZones()"
                        :disabled="saving"
                        class="text-sm px-4 py-1.5 rounded bg-gray-800 text-white hover:bg-gray-700 disabled:opacity-50 transition-all flex items-center gap-1.5">
                    <svg x-show="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="saving ? 'Saving…' : '💾 Save Zones'"></span>
                </button>

                <span x-show="saved"
                      x-transition
                      class="text-sm font-medium text-green-600">
                    ✓ Saved!
                </span>
            </div>

            {{-- Active tool hint --}}
            <p x-show="activeTool !== null"
               class="mt-1.5 text-xs text-amber-600 font-medium"
               x-text="'Click and drag on the PDF to place: ' + toolLabel(activeTool)">
            </p>
        </div>

        {{-- ── PAGE TABS ────────────────────────────────────────────── --}}
        <div class="flex gap-1 mb-3 flex-wrap" x-show="pageCount > 1">
            <template x-for="n in pageCount" :key="n">
                <button type="button"
                        @click="switchPage(n)"
                        :class="currentPage === n
                            ? 'bg-gray-800 text-white'
                            : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'"
                        class="text-sm px-3 py-1 rounded transition-all"
                        x-text="'Page ' + n">
                </button>
            </template>
        </div>

        {{-- ── CANVAS AREA ──────────────────────────────────────────── --}}
        <div class="flex gap-6">

            {{-- Canvas + overlay --}}
            <div class="flex-1 overflow-auto">
                {{-- Error state --}}
                <div x-show="pdfError"
                     class="rounded-lg border border-red-200 bg-red-50 p-6 text-red-700 text-sm mb-3">
                    <strong>PDF load error:</strong> <span x-text="pdfError"></span>
                </div>

                <div id="canvas-wrap"
                     x-show="!pdfError"
                     class="relative inline-block select-none shadow-lg"
                     :style="activeTool ? 'cursor:crosshair' : 'cursor:default'"
                     @mousedown="startDraw($event)"
                     @mousemove="whileDrawing($event)"
                     @mouseup="endDraw($event)"
                     @mouseleave="isDrawing && endDraw($event)">

                    <canvas id="pdf-canvas" class="block"></canvas>

                    {{-- Placed zones --}}
                    <template x-for="zone in currentZones()" :key="zone.id">
                        <div class="absolute border-2 pointer-events-auto"
                             :class="zoneColorClass(zone.tool)"
                             :style="zoneStyle(zone)">
                            <span class="text-xs font-medium px-1 leading-none truncate block"
                                  x-text="zone.label"></span>
                            <button type="button"
                                    @click.stop="removeZone(currentPage - 1, zone.id)"
                                    class="absolute -top-2.5 -right-2.5 w-5 h-5 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center shadow hover:bg-red-600">
                                &times;
                            </button>
                        </div>
                    </template>

                    {{-- Draw preview --}}
                    <div x-show="isDrawing && drawRect"
                         class="absolute border-2 border-dashed border-yellow-400 bg-yellow-50 bg-opacity-30 pointer-events-none"
                         :style="drawRect
                             ? `left:${drawRect.x}px;top:${drawRect.y}px;width:${drawRect.w}px;height:${drawRect.h}px`
                             : ''">
                    </div>
                </div>
            </div>

            {{-- Zone list sidebar --}}
            <div class="w-56 flex-shrink-0">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                    Zones on this page
                </h3>
                <div x-show="currentZones().length === 0"
                     class="text-sm text-gray-400 italic">
                    No zones placed yet.
                </div>
                <ul class="space-y-1">
                    <template x-for="zone in currentZones()" :key="zone.id">
                        <li class="flex items-center justify-between text-sm rounded px-2 py-1 border"
                            :class="zoneColorClass(zone.tool)">
                            <span class="truncate" x-text="zone.label"></span>
                            <button type="button"
                                    @click="removeZone(currentPage - 1, zone.id)"
                                    class="ml-1 text-red-500 hover:text-red-700 font-bold flex-shrink-0">
                                &times;
                            </button>
                        </li>
                    </template>
                </ul>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">All pages</h3>
                    <template x-for="(pageZones, idx) in allZones" :key="idx">
                        <div class="text-xs text-gray-500"
                             x-show="pageZones && pageZones.length > 0"
                             x-text="'Page ' + (idx + 1) + ': ' + (pageZones ? pageZones.length : 0) + ' zone(s)'">
                        </div>
                    </template>
                    <div class="text-xs text-gray-400 mt-1"
                         x-text="'Total: ' + totalZoneCount() + ' zone(s)'">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PDF.js must be loaded before Alpine initialises the component --}}
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.4.120/legacy/build/pdf.min.js"></script>

    <script>
    function zoneDesigner() {
        return {
            pdfDoc:    null,
            currentPage: 1,
            pageCount: {{ $pageCount }},
            activeTool: null,
            allZones: @json($existingZones),
            isDrawing: false,
            drawStart: null,
            drawRect:  null,
            canvasW:   1,
            canvasH:   1,
            saving:    false,
            saved:     false,
            pdfError:  null,
            pdfUrl:    "{{ route('staff2.zones.pdf', $document) }}",
            documentId: {{ $document->id }},
            fieldSchema: @json($fieldSchema),

            currentZones() {
                return this.allZones[this.currentPage - 1] || [];
            },

            totalZoneCount() {
                return Object.values(this.allZones).reduce((sum, z) => sum + (z ? z.length : 0), 0);
            },

            async loadPdf() {
                // The CDN build exposes window.pdfjsLib (NOT window['pdfjs-dist/build/pdf'])
                const pdfjsLib = window.pdfjsLib;
                if (!pdfjsLib) {
                    this.pdfError = 'PDF.js failed to load from CDN. Check your internet connection.';
                    console.error('[ZoneDesigner] window.pdfjsLib is undefined — pdf.min.js did not load');
                    return;
                }

                pdfjsLib.GlobalWorkerOptions.workerSrc =
                    'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.4.120/legacy/build/pdf.worker.min.js';

                try {
                    this.pdfDoc = await pdfjsLib.getDocument(this.pdfUrl).promise;
                    this.pageCount = this.pdfDoc.numPages;
                    await this.renderPage(this.currentPage);
                } catch (err) {
                    this.pdfError = 'Could not load PDF: ' + err.message;
                    console.error('[ZoneDesigner] getDocument failed:', err);
                }
            },

            async renderPage(num) {
                this.currentPage = num;
                const page      = await this.pdfDoc.getPage(num);
                const canvas    = document.getElementById('pdf-canvas');
                const container = document.getElementById('canvas-wrap');

                const viewport = page.getViewport({ scale: 1 });
                const scale    = Math.min(900, container.offsetWidth || 900) / viewport.width;
                const scaled   = page.getViewport({ scale });

                canvas.width  = scaled.width;
                canvas.height = scaled.height;
                this.canvasW  = scaled.width;
                this.canvasH  = scaled.height;

                await page.render({
                    canvasContext: canvas.getContext('2d'),
                    viewport: scaled,
                }).promise;
            },

            startDraw(e) {
                if (!this.activeTool) return;
                e.preventDefault();
                const rect = document.getElementById('canvas-wrap').getBoundingClientRect();
                this.isDrawing = true;
                this.drawStart = {
                    x: e.clientX - rect.left,
                    y: e.clientY - rect.top,
                };
            },

            whileDrawing(e) {
                if (!this.isDrawing) return;
                const rect = document.getElementById('canvas-wrap').getBoundingClientRect();
                const cx   = e.clientX - rect.left;
                const cy   = e.clientY - rect.top;
                this.drawRect = {
                    x: Math.min(this.drawStart.x, cx),
                    y: Math.min(this.drawStart.y, cy),
                    w: Math.abs(cx - this.drawStart.x),
                    h: Math.abs(cy - this.drawStart.y),
                };
            },

            endDraw(e) {
                if (!this.isDrawing) return;
                this.isDrawing = false;

                if (!this.drawRect || this.drawRect.w < 20 || this.drawRect.h < 10) {
                    this.drawRect = null;
                    return;
                }

                const pageIndex = this.currentPage - 1;
                if (!this.allZones[pageIndex]) {
                    this.allZones[pageIndex] = [];
                }

                this.allZones[pageIndex].push({
                    id:    Date.now(),
                    tool:  this.activeTool,
                    label: this.toolLabel(this.activeTool),
                    page:  pageIndex,
                    x:     this.drawRect.x,
                    y:     this.drawRect.y,
                    w:     this.drawRect.w,
                    h:     this.drawRect.h,
                    nx:    this.drawRect.x / this.canvasW,
                    ny:    this.drawRect.y / this.canvasH,
                    nw:    this.drawRect.w / this.canvasW,
                    nh:    this.drawRect.h / this.canvasH,
                });

                this.activeTool = null;
                this.drawRect   = null;
            },

            removeZone(pageIndex, zoneId) {
                if (!this.allZones[pageIndex]) return;
                this.allZones[pageIndex] = this.allZones[pageIndex].filter(z => z.id !== zoneId);
            },

            toolLabel(tool) {
                if (!tool) return '';
                if (tool === 'applicant_signature') return 'Applicant Sig';
                if (tool === 'staff2_signature')    return 'Staff 2 Sig';
                if (tool.startsWith('field_')) {
                    const name  = tool.slice(6);
                    const field = this.fieldSchema.find(f => f.name === name);
                    return field ? field.label : name;
                }
                return tool;
            },

            zoneColorClass(tool) {
                if (tool === 'applicant_signature')
                    return 'border-blue-500 bg-blue-100 bg-opacity-50 text-blue-800';
                if (tool === 'staff2_signature')
                    return 'border-purple-500 bg-purple-100 bg-opacity-50 text-purple-800';
                return 'border-green-500 bg-green-100 bg-opacity-50 text-green-800';
            },

            zoneStyle(zone) {
                return `left:${zone.x}px;top:${zone.y}px;width:${zone.w}px;height:${zone.h}px`;
            },

            async switchPage(num) {
                await this.renderPage(num);
            },

            async saveZones() {
                this.saving = true;
                try {
                    const res = await fetch(`/staff2/templates/${this.documentId}/zones`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ zones: this.allZones }),
                    });
                    if (res.ok) {
                        this.saved = true;
                        setTimeout(() => { this.saved = false; }, 3000);
                    }
                } catch (err) {
                    console.error('Save failed', err);
                } finally {
                    this.saving = false;
                }
            },
        };
    }
    </script>
</x-app-layout>
