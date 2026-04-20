<script>
    let signatureState = { isDrawing: false, hasSignature: false };
    let isSubmitting = false;

    document.addEventListener('DOMContentLoaded', () => {
        initializeSignaturePad();
        initializeVotRows();
        initializeFormSubmission();

        const requestTypeSelect = document.getElementById('request-type-select');
        if (requestTypeSelect?.value) {
            loadTemplatePreview(requestTypeSelect.value);
        }
    });

    function initializeVotRows() {
        const rows = document.querySelectorAll('.vot-item-row');

        rows.forEach((row) => {
            const select = row.querySelector('.vot-code-select');
            if (select) {
                handleVotSelection(select);
            }
        });

        calculateTotal();
        updateVOTPreview();
    }

    function addVotItemRow(initialData = {}) {
        const container = document.getElementById('vot-items-container');
        const template = document.getElementById('vot-item-template');
        if (!container || !template) {
            return;
        }

        const rowCount = container.querySelectorAll('.vot-item-row').length;
        const clone = template.content.cloneNode(true);

        const select = clone.querySelector('.vot-code-select');
        const codeInput = clone.querySelector('.vot-code-input');
        const descInput = clone.querySelector('.vot-description-input');
        const amountInput = clone.querySelector('.vot-amount-input');

        if (codeInput) codeInput.name = `vot_items[${rowCount}][vot_code]`;
        if (descInput) descInput.name = `vot_items[${rowCount}][description]`;
        if (amountInput) amountInput.name = `vot_items[${rowCount}][amount]`;

        if (select && initialData.vot_code) {
            select.value = initialData.vot_code;
        }

        if (amountInput && initialData.amount !== undefined && initialData.amount !== null) {
            amountInput.value = initialData.amount;
        }

        container.appendChild(clone);

        const newRow = container.querySelectorAll('.vot-item-row')[rowCount];
        const newSelect = newRow?.querySelector('.vot-code-select');
        if (newSelect) {
            handleVotSelection(newSelect);
        }

        calculateTotal();
        updateVOTPreview();
    }

    function removeVotItemRow(button) {
        const rows = document.querySelectorAll('.vot-item-row');
        if (rows.length <= 1) {
            alert('At least one VOT item is required.');
            return;
        }

        button.closest('.vot-item-row')?.remove();
        reindexVotFields();
        calculateTotal();
        updateVOTPreview();
    }

    function reindexVotFields() {
        const rows = document.querySelectorAll('#vot-items-container .vot-item-row');
        rows.forEach((row, index) => {
            const codeInput = row.querySelector('.vot-code-input');
            const descInput = row.querySelector('.vot-description-input');
            const amountInput = row.querySelector('.vot-amount-input');

            if (codeInput) codeInput.name = `vot_items[${index}][vot_code]`;
            if (descInput) descInput.name = `vot_items[${index}][description]`;
            if (amountInput) amountInput.name = `vot_items[${index}][amount]`;
        });
    }

    function handleVotSelection(selectElement) {
        const row = selectElement.closest('.vot-item-row');
        if (!row) return;

        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const code = selectedOption?.value || '';
        const description = selectedOption?.dataset?.description || '';

        const codeInput = row.querySelector('.vot-code-input');
        const descInput = row.querySelector('.vot-description-input');
        const preview = row.querySelector('.vot-desc-preview');

        if (codeInput) codeInput.value = code;
        if (descInput) descInput.value = description;
        if (preview) preview.textContent = description ? `Description: ${description}` : '';

        updateVOTPreview();
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.vot-amount-input').forEach((input) => {
            total += parseFloat(input.value || '0') || 0;
        });

        const totalElement = document.getElementById('total-amount');
        const previewTotal = document.getElementById('preview-total');

        if (totalElement) totalElement.textContent = total.toFixed(2);
        if (previewTotal) previewTotal.textContent = `RM ${total.toFixed(2)}`;
    }

    function updateVOTPreview() {
        const rows = document.querySelectorAll('.vot-item-row');
        let validItems = 0;

        rows.forEach((row) => {
            const code = row.querySelector('.vot-code-select')?.value;
            const amount = row.querySelector('.vot-amount-input')?.value;
            if (code && amount && Number(amount) > 0) {
                validItems += 1;
            }
        });

        const previewVot = document.getElementById('preview-vot');
        if (previewVot) {
            previewVot.textContent = validItems > 0
                ? `${validItems} item${validItems > 1 ? 's' : ''} will be included`
                : 'Items you add below';
        }

        calculateTotal();
    }

    function initializeSignaturePad() {
        const canvas = document.getElementById('signature-canvas');
        const input = document.getElementById('signature_data');
        if (!canvas || !input) return;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;

        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.lineWidth = 2;
        ctx.strokeStyle = '#111827';
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, rect.width, rect.height);

        if (input.value) {
            signatureState.hasSignature = true;
        }

        const getPos = (event) => {
            const bounds = canvas.getBoundingClientRect();
            const source = event.touches ? event.touches[0] : event;
            return {
                x: source.clientX - bounds.left,
                y: source.clientY - bounds.top,
            };
        };

        const startDraw = (event) => {
            event.preventDefault();
            signatureState.isDrawing = true;
            const pos = getPos(event);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        };

        const draw = (event) => {
            if (!signatureState.isDrawing) return;
            event.preventDefault();
            const pos = getPos(event);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            signatureState.hasSignature = true;
        };

        const stopDraw = (event) => {
            event?.preventDefault();
            signatureState.isDrawing = false;
        };

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDraw);
        canvas.addEventListener('mouseleave', stopDraw);
        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDraw, { passive: false });
    }

    function clearSignature() {
        const canvas = document.getElementById('signature-canvas');
        const input = document.getElementById('signature_data');
        if (!canvas || !input) return;

        const ctx = canvas.getContext('2d');
        const rect = canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, rect.width, rect.height);

        input.value = '';
        signatureState.hasSignature = false;
    }

    function initializeFormSubmission() {
        const form = document.getElementById('request-form');
        const loadingButton = form?.querySelector('button[type="submit"]');
        if (!form) return;

        form.addEventListener('submit', (event) => {
            if (!signatureState.hasSignature) {
                event.preventDefault();
                alert('Please provide your digital signature before submitting.');
                return;
            }

            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            isSubmitting = true;
            const canvas = document.getElementById('signature-canvas');
            const input = document.getElementById('signature_data');
            if (canvas && input) {
                input.value = canvas.toDataURL('image/png');
            }

            if (loadingButton) {
                loadingButton.disabled = true;
            }
        });
    }

    async function loadTemplatePreview(requestTypeId) {
        const previewSection = document.getElementById('template-preview-section');
        const iframe = document.getElementById('template-preview-iframe');
        const errorSection = document.getElementById('template-error');
        const dynamicSection = document.getElementById('dynamic-fields-section');
        const dynamicContainer = document.getElementById('dynamic-fields-container');

        if (!requestTypeId) {
            previewSection?.classList.add('hidden');
            dynamicSection?.classList.add('hidden');
            errorSection?.classList.add('hidden');
            document.getElementById('required-docs-notice')?.classList.add('hidden');
            return;
        }

        if (dynamicContainer && dynamicSection) {
            try {
                const response = await fetch(`/api/request-types/${requestTypeId}/fields`);
                const data = response.ok ? await response.json() : null;

                if (data?.html) {
                    dynamicContainer.innerHTML = data.html;
                    dynamicSection.classList.remove('hidden');
                } else {
                    dynamicContainer.innerHTML = '';
                    dynamicSection.classList.add('hidden');
                }

                // Update required documents notice
                const docsNotice = document.getElementById('required-docs-notice');
                if (docsNotice) {
                    const docs = data?.required_documents ?? [];
                    if (docs.length > 0) {
                        docsNotice.innerHTML = `
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4">
                                <p class="text-sm font-semibold text-amber-800 mb-2">Required Supporting Documents</p>
                                <p class="text-xs text-amber-700 mb-3">Please ensure you have the following documents ready to attach before submitting:</p>
                                <ul class="space-y-1">
                                    ${docs.map(d => `<li class="flex items-center gap-2 text-sm text-amber-800"><span class="text-amber-500">□</span> ${d}</li>`).join('')}
                                </ul>
                            </div>`;
                        docsNotice.classList.remove('hidden');
                    } else {
                        docsNotice.innerHTML = '';
                        docsNotice.classList.add('hidden');
                    }
                }
            } catch (error) {
                dynamicSection.classList.add('hidden');
            }
        }

        if (iframe && previewSection) {
            iframe.src = `/request-types/${requestTypeId}/template`;
            iframe.onload = () => {
                previewSection.classList.remove('hidden');
                errorSection?.classList.add('hidden');
            };
            iframe.onerror = () => {
                previewSection.classList.add('hidden');
                errorSection?.classList.remove('hidden');
            };
        }

        const select = document.getElementById('request-type-select');
        const selectedOption = select?.options[select.selectedIndex];
        const previewRequestType = document.getElementById('preview-request-type');
        if (previewRequestType && selectedOption) {
            previewRequestType.textContent = selectedOption.text;
        }

        // Show/hide VOT section based on requires_vot field from API response
        try {
            const resp = await fetch(`/api/request-types/${requestTypeId}/fields`);
            if (resp.ok) {
                const data = await resp.json();
                const votSection = document.getElementById('vot-section');
                if (votSection) {
                    if (data.requires_vot) {
                        votSection.classList.remove('hidden');
                    } else {
                        votSection.classList.add('hidden');
                    }
                }
            }
        } catch {}
    }
</script>
