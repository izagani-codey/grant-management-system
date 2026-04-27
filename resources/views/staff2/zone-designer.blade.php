@php $isExcel = $document->isExcelDocument(); @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Zone Designer</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $document->name ?: $document->original_name }}
                    &mdash;
                    @if($isExcel)
                        Excel template
                    @else
                        {{ $pageCount }} page{{ $pageCount !== 1 ? 's' : '' }}
                    @endif
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
         x-init="init()">

        {{-- ── TOOLBAR ─────────────────────────────────────────────── --}}
        <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm mb-4 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex flex-wrap items-center gap-2">

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

                <button type="button"
                        x-show="activeTool !== null"
                        @click="activeTool = null"
                        class="text-sm px-3 py-1.5 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 transition-all">
                    Cancel
                </button>

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

                <span x-show="saved" x-transition class="text-sm font-medium text-green-600">✓ Saved!</span>
            </div>

            <p x-show="activeTool !== null"
               class="mt-1.5 text-xs text-amber-600 font-medium"
               x-text="'Click and drag on the ' + (isExcel ? 'Excel sheet' : 'PDF') + ' to place: ' + toolLabel(activeTool)">
            </p>
        </div>

        {{-- ── PAGE TABS (PDF only) ─────────────────────────────────── --}}
        <div class="flex gap-1 mb-3 flex-wrap" x-show="!isExcel && pageCount > 1">
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

            <div class="flex-1 overflow-auto">

                {{-- PDF error --}}
                <div x-show="!isExcel && pdfError"
                     class="rounded-lg border border-red-200 bg-red-50 p-6 text-red-700 text-sm mb-3">
                    <strong>PDF load error:</strong> <span x-text="pdfError"></span>
                </div>

                {{-- XLS error --}}
                <div x-show="isExcel && xlsError"
                     class="rounded-lg border border-red-200 bg-red-50 p-6 text-red-700 text-sm mb-3">
                    <strong>Excel load error:</strong> <span x-text="xlsError"></span>
                </div>

                <div id="canvas-wrap"
                     x-show="isExcel ? !xlsError : !pdfError"
                     class="relative inline-block select-none shadow-lg"
                     style="width:100%; max-width:900px;"
                     :style="activeTool ? 'cursor:crosshair' : 'cursor:default'"
                     @mousedown="startDraw($event)"
                     @mousemove="whileDrawing($event)"
                     @mouseup="endDraw($event)"
                     @mouseleave="isDrawing && endDraw($event)">

                    {{-- PDF canvas --}}
                    <canvas id="pdf-canvas" class="block" x-show="!isExcel"></canvas>

                    {{-- XLS: loading spinner --}}
                    <div x-show="isExcel && xlsLoading"
                         class="flex items-center justify-center h-64 bg-gray-50 border border-gray-200 rounded">
                        <div class="text-center text-gray-500">
                            <svg class="animate-spin h-8 w-8 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <p class="text-sm">Loading Excel template…</p>
                        </div>
                    </div>

                    {{-- XLS rendered table --}}
                    <div id="xls-content" x-show="isExcel && !xlsLoading" class="w-full overflow-auto"></div>

                    {{-- Placed zones (both PDF and XLS) --}}
                    <template x-for="zone in currentZones()" :key="zone.id">
                        <div class="absolute border-2 pointer-events-auto"
                             :class="zoneColorClass(zone.tool)"
                             :style="zoneStyle(zone)">
                            <span class="text-xs font-medium px-1 leading-none truncate block"
                                  x-text="zone.label + (zone.cell_start ? ' [' + zone.cell_start + ']' : '')">
                            </span>
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
                            <div class="truncate">
                                <span x-text="zone.label"></span>
                                <span x-show="zone.cell_start"
                                      class="text-xs opacity-60 ml-1"
                                      x-text="'[' + (zone.cell_start || '') + ']'"></span>
                            </div>
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

                @if($isExcel)
                <div class="mt-4 pt-4 border-t border-gray-200 text-xs text-gray-500 space-y-1">
                    <p class="font-semibold text-gray-600">Excel tips:</p>
                    <p>Cell references (e.g. <code class="bg-gray-100 px-1 rounded">B5</code>) are detected automatically when you draw a zone.</p>
                    <p>Field zones use the cell reference to insert the value directly into the cell.</p>
                    <p>Signature zones are placed as images at the drawn pixel position.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- PDF.js — only for PDF templates --}}
    @if(!$isExcel)
    <script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
    @endif

    {{-- SheetJS — only for Excel templates --}}
    @if($isExcel)
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
    @endif

    <style>
    #xls-content table {
        border-collapse: collapse;
        font-size: 12px;
        font-family: Calibri, Arial, sans-serif;
        background: white;
        min-width: 100%;
    }
    #xls-content table td,
    #xls-content table th {
        border: 1px solid #d1d5db;
        padding: 2px 6px;
        white-space: nowrap;
        overflow: hidden;
        max-width: 300px;
        text-overflow: ellipsis;
        vertical-align: middle;
        min-height: 20px;
    }
    #xls-content table th {
        background: #f3f4f6;
        font-weight: 600;
        text-align: center;
    }
    </style>

    <script>
    function zoneDesigner() {
        // Keep _pdfDoc outside Alpine reactive scope — PDF.js uses private class fields
        // that become inaccessible when proxied by Alpine's reactivity system.
        let _pdfDoc = null;

        return {
            isExcel:    {{ $isExcel ? 'true' : 'false' }},
            currentPage: 1,
            pageCount:  {{ $isExcel ? 1 : $pageCount }},
            activeTool: null,
            allZones:   @json($existingZones),
            isDrawing:  false,
            drawStart:  null,
            drawRect:   null,
            canvasW:    1,
            canvasH:    1,
            saving:     false,
            saved:      false,
            pdfError:   null,
            xlsError:   null,
            xlsLoading: false,
            templateUrl: "{{ route('staff2.zones.pdf', $document) }}",
            documentId:  {{ $document->id }},
            fieldSchema: @json($fieldSchema),

            // ── Shared ────────────────────────────────────────────────────

            init() {
                if (this.isExcel) {
                    this.loadXls();
                } else {
                    this.loadPdf();
                }
            },

            currentZones() {
                return this.allZones[this.currentPage - 1] || [];
            },

            totalZoneCount() {
                return Object.values(this.allZones).reduce(
                    (sum, z) => sum + (z ? z.length : 0), 0
                );
            },

            // ── PDF path ──────────────────────────────────────────────────

            async loadPdf() {
                const pdfjsLib = window['pdfjs-dist/build/pdf'];
                if (!pdfjsLib) {
                    this.pdfError = 'PDF.js failed to load. Check that /vendor/pdfjs/pdf.min.js is accessible.';
                    console.error('[ZoneDesigner] pdf.min.js did not load');
                    return;
                }

                pdfjsLib.GlobalWorkerOptions.workerSrc =
                    '{{ asset('vendor/pdfjs/pdf.worker.min.js') }}';

                try {
                    _pdfDoc = await pdfjsLib.getDocument(this.templateUrl).promise;
                    this.pageCount = _pdfDoc.numPages;
                    await this.renderPage(this.currentPage);
                } catch (err) {
                    this.pdfError = 'Could not load PDF: ' + err.message;
                    console.error('[ZoneDesigner] getDocument failed:', err);
                }
            },

            async renderPage(num) {
                this.currentPage = num;
                const page      = await _pdfDoc.getPage(num);
                const canvas    = document.getElementById('pdf-canvas');
                const container = document.getElementById('canvas-wrap');

                const baseViewport = page.getViewport({ scale: 1 });
                const desiredWidth = container.offsetWidth || 900;
                const scale        = desiredWidth / baseViewport.width;
                const viewport     = page.getViewport({ scale });

                canvas.width  = viewport.width;
                canvas.height = viewport.height;
                this.canvasW  = viewport.width;
                this.canvasH  = viewport.height;

                await page.render({
                    canvasContext: canvas.getContext('2d'),
                    viewport,
                }).promise;
            },

            async switchPage(num) {
                await this.renderPage(num);
            },

            // ── XLS path ──────────────────────────────────────────────────

            async loadXls() {
                this.xlsLoading = true;
                try {
                    const resp = await fetch(this.templateUrl);
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    const ab = await resp.arrayBuffer();

                    if (typeof XLSX === 'undefined') {
                        throw new Error('SheetJS (XLSX) library did not load.');
                    }

                    const wb     = XLSX.read(ab, { type: 'array' });
                    const wsName = wb.SheetNames[0];
                    const ws     = wb.Sheets[wsName];

                    // Render sheet as HTML table
                    const html = XLSX.utils.sheet_to_html(ws, {
                        id: 'xls-table',
                        editable: false,
                    });
                    document.getElementById('xls-content').innerHTML = html;

                    // Apply column widths from workbook metadata
                    const table    = document.getElementById('xls-table');
                    const allRows  = table.querySelectorAll('tr');
                    const colInfo  = ws['!cols'] || [];

                    if (allRows.length > 0) {
                        const firstRowCells = allRows[0].querySelectorAll('td, th');
                        colInfo.forEach((col, i) => {
                            if (col && col.wpx && firstRowCells[i]) {
                                firstRowCells[i].style.width    = col.wpx + 'px';
                                firstRowCells[i].style.minWidth = col.wpx + 'px';
                            }
                        });
                    }

                    // Apply row heights from workbook metadata
                    const rowInfo = ws['!rows'] || [];
                    allRows.forEach((row, i) => {
                        if (rowInfo[i] && rowInfo[i].hpx) {
                            row.style.height = rowInfo[i].hpx + 'px';
                        }
                    });

                    // Capture dimensions for zone normalisation
                    await this.$nextTick();
                    this.canvasW = table.offsetWidth  || 900;
                    this.canvasH = table.offsetHeight || 1200;

                } catch (err) {
                    this.xlsError = 'Could not load Excel file: ' + err.message;
                    console.error('[ZoneDesigner] XLS load failed:', err);
                } finally {
                    this.xlsLoading = false;
                }
            },

            // Return all <td> cells whose bounding rect overlaps the drawn rectangle.
            getCellsForRect(rect) {
                const container = document.getElementById('canvas-wrap');
                if (!container) return [];
                const cRect  = container.getBoundingClientRect();
                const covered = [];

                document.querySelectorAll('#xls-content table td').forEach(td => {
                    const r  = td.getBoundingClientRect();
                    const x1 = r.left - cRect.left;
                    const y1 = r.top  - cRect.top;
                    const x2 = x1 + r.width;
                    const y2 = y1 + r.height;

                    if (rect.x < x2 && rect.x + rect.w > x1 &&
                        rect.y < y2 && rect.y + rect.h > y1) {
                        covered.push({
                            row: td.parentElement.rowIndex,
                            col: td.cellIndex,
                        });
                    }
                });

                return covered;
            },

            // Convert zero-based row/col to Excel cell reference (e.g. row=4, col=2 → "C5").
            encodeCellRef(row, col) {
                let colStr = '';
                if (col < 26) {
                    colStr = String.fromCharCode(65 + col);
                } else {
                    colStr = String.fromCharCode(64 + Math.floor(col / 26)) +
                             String.fromCharCode(65 + (col % 26));
                }
                return colStr + (row + 1);
            },

            // ── Zone drawing (shared) ─────────────────────────────────────

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
                if (!this.allZones[pageIndex]) this.allZones[pageIndex] = [];

                const zone = {
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
                };

                // For Excel: detect which cells are covered and store the top-left reference.
                // Field zones use cell_start for direct setCellValue() during signing.
                if (this.isExcel) {
                    const covered = this.getCellsForRect(this.drawRect);
                    if (covered.length > 0) {
                        covered.sort((a, b) => a.row - b.row || a.col - b.col);
                        zone.cell_start = this.encodeCellRef(covered[0].row, covered[0].col);
                        zone.cell_end   = this.encodeCellRef(
                            covered[covered.length - 1].row,
                            covered[covered.length - 1].col
                        );
                    }
                }

                this.allZones[pageIndex].push(zone);
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

            // ── Save ──────────────────────────────────────────────────────

            async saveZones() {
                this.saving = true;
                try {
                    const res = await fetch(`/staff2/templates/${this.documentId}/zones`, {
                        method:  'POST',
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
