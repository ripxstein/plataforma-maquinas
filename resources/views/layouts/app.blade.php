<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Maquinas') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- KaTeX for Math Formulas -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
        <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>

        <!-- Scripts -->
        
<script>
    // -----------------------------
    // UI: acordeones y KaTeX auto-render
    // -----------------------------
    function renderAllMath() {
        if (typeof renderMathInElement === 'function') {
            renderMathInElement(document.body, {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '\\[', right: '\\]', display: true},
                    {left: '\\(', right: '\\)', display: false},
                    {left: '$', right: '$', display: false}
                ],
                throwOnError: false
            });
        }
    }

    document.addEventListener('DOMContentLoaded', renderAllMath);
    document.addEventListener('livewire:navigated', () => {
        renderAllMath();
        document.querySelectorAll('.accordion-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.parentElement.classList.toggle('open');
            });
        });
    });

    // -----------------------------
    // Alpine Component: eduWysiwygEditor
    // -----------------------------
    function registerEduWysiwygEditor() {
        if (typeof Alpine === 'undefined') return;

        Alpine.data('eduWysiwygEditor', (config = {}) => ({
            value: config.value || '',
            editorId: config.editorId || 'edu-editor',
            activeTab: 'edit',
            currentBlockTag: 'p',
            savedSelection: null,
            
            // Modals state
            showFormulaModal: false,
            formulaInput: '',
            
            showImageModal: false,
            imageUrl: '',
            imageAlign: 'align-center',
            imageWidth: '75%',
            imageAlt: '',
            imageCaption: '',
            imageSource: '',
            imageSourceType: 'file',
            isUploadingImage: false,
            uploadError: '',

            showCardModal: false,

            showTableModal: false,
            tableRows: 3,
            tableCols: 3,

            init() {
                const editable = document.getElementById(this.editorId + '-editable');
                if (editable) {
                    editable.innerHTML = this.value || '';

                    ['keyup', 'mouseup', 'touchend', 'input'].forEach(evt => {
                        editable.addEventListener(evt, () => {
                            this.saveSelection();
                            this.updateCurrentBlockTag();
                        });
                    });
                }

                this.$watch('value', (newValue) => {
                    const el = document.getElementById(this.editorId + '-editable');
                    if (el && el.innerHTML !== (newValue || '')) {
                        el.innerHTML = newValue || '';
                    }
                    if (this.activeTab === 'preview') {
                        this.$nextTick(() => this.triggerKaTeX());
                    }
                });
            },

            saveSelection() {
                const editable = document.getElementById(this.editorId + '-editable');
                const sel = window.getSelection();
                if (sel && sel.rangeCount > 0 && editable) {
                    const range = sel.getRangeAt(0);
                    if (editable.contains(range.commonAncestorContainer)) {
                        this.savedSelection = range.cloneRange();
                    }
                }
            },

            restoreSelection() {
                const editable = document.getElementById(this.editorId + '-editable');
                if (!editable) return;

                const sel = window.getSelection();
                if (this.savedSelection && editable.contains(this.savedSelection.commonAncestorContainer)) {
                    sel.removeAllRanges();
                    sel.addRange(this.savedSelection);
                } else {
                    const range = document.createRange();
                    range.selectNodeContents(editable);
                    range.collapse(false);
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            },

            updateCurrentBlockTag() {
                const sel = window.getSelection();
                if (!sel || !sel.rangeCount) return;
                let node = sel.anchorNode;
                const editable = document.getElementById(this.editorId + '-editable');
                if (!node || !editable || !editable.contains(node)) return;

                while (node && node !== editable) {
                    const tag = node.nodeName ? node.nodeName.toLowerCase() : '';
                    if (['h1', 'h2', 'h3', 'h4', 'h5', 'p'].includes(tag)) {
                        this.currentBlockTag = tag;
                        return;
                    }
                    node = node.parentNode;
                }
                this.currentBlockTag = 'p';
            },

            onContentChange() {
                const el = document.getElementById(this.editorId + '-editable');
                if (el) {
                    this.value = el.innerHTML;
                }
            },

            switchTab(tab) {
                this.activeTab = tab;
                if (tab === 'preview') {
                    this.$nextTick(() => this.triggerKaTeX());
                }
            },

            format(command, value = null) {
                const editable = document.getElementById(this.editorId + '-editable');
                if (editable) {
                    this.restoreSelection();
                    editable.focus();
                    document.execCommand(command, false, value);
                    this.saveSelection();
                    this.onContentChange();
                }
            },

            applyHeading(tag) {
                const editable = document.getElementById(this.editorId + '-editable');
                if (!editable) return;

                this.restoreSelection();
                editable.focus();

                const normalizedTag = (tag || 'p').toLowerCase();
                let success = false;

                try {
                    success = document.execCommand('formatBlock', false, '<' + normalizedTag + '>');
                } catch (e) {
                    success = false;
                }

                if (!success) {
                    try {
                        success = document.execCommand('formatBlock', false, normalizedTag);
                    } catch (e) {
                        success = false;
                    }
                }

                if (!success) {
                    try {
                        document.execCommand('formatBlock', false, normalizedTag.toUpperCase());
                    } catch (e) {
                        console.warn('formatBlock error:', e);
                    }
                }

                this.saveSelection();
                this.currentBlockTag = normalizedTag;
                this.onContentChange();
            },

            // Insert Educational Callout Blocks
            insertEducationalBlock(type) {
                const config = {
                    definition: { label: '🏷️ Definición', placeholder: 'Escribe aquí la definición...' },
                    important: { label: '💡 Importante', placeholder: 'Escribe aquí la información importante...' },
                    warning: { label: '⚠️ Advertencia', placeholder: 'Escribe aquí la advertencia...' },
                    example: { label: '📐 Ejemplo', placeholder: 'Escribe aquí el ejemplo práctico...' },
                    note: { label: '📝 Nota', placeholder: 'Escribe aquí la nota explicativa...' }
                    
                };

                const item = config[type] || config.definition;
                const html = `
                    <div class="edu-block edu-block-${type}">
                        <div class="edu-block-header">${item.label}</div>
                        <div class="edu-block-content">
                            <p>${item.placeholder}</p>
                        </div>
                    </div>
                    <p><br></p>
                `;

                this.insertHtmlAtCursor(html);
            },

            // Insert Educational Callout BlocksNoteFoot
            insertEducationalBlockNote(type) {
                const config = {
                    footnote: { label: '📝 Nota al pie', placeholder: 'Escribe aquí la nota al pie...' }
                };

                const item = config[type] || config.footnote;
                const html = `
                    <div class="footer-note" style="margin-top:18px;">
                        <strong>${item.label}:</strong> ${item.placeholder}.
                    </div>
                `;

                this.insertHtmlAtCursor(html);
            },

            // Insert Educational Callout Card
            insertEducationalBlockCard(type) {
                const config = {
                    card: { label: '📝 Tarjeta', placeholder: 'Escribe el contenido de la tarjeta...' }
                };

                const item = config[type] || config.card;
                const html = `
                    <div class="ilustracion-header">
                        <h4>${item.label}</h4>
                        <p>${item.placeholder}</p>
                    </div>
                    
                `;

                this.insertHtmlAtCursor(html);
            },

            // Insert Educational Callout Header
            insertEducationalBlockHeader(type) {
                const config = {
                    header: { label: 'Encabezado'}
                };

                const item = config[type] || config.header;
                const html = `
                    <div class="tag">${item.label}</div>
                    
                `;

                this.insertHtmlAtCursor(html);
            },
            

            // Formula Modal Logic
            openFormulaModal() {
                this.formulaInput = '\\sigma_{max} = K_t \\cdot \\sigma_{nom}';
                this.showFormulaModal = true;
                this.$nextTick(() => this.updateFormulaPreview());
            },

            addSymbol(symbol) {
                this.formulaInput += ' ' + symbol;
                this.updateFormulaPreview();
            },

            updateFormulaPreview() {
                const container = document.getElementById(this.editorId + '-formula-preview');
                if (container && typeof katex !== 'undefined') {
                    try {
                        katex.render(this.formulaInput || '\\text{Formula...}', container, {
                            displayMode: true,
                            throwOnError: false
                        });
                    } catch (e) {
                        container.innerHTML = this.formulaInput;
                    }
                }
            },

            insertFormula() {
                if (!this.formulaInput) return;
                const formulaText = this.formulaInput.trim();
                const html = `<div class="formula formula-display">\\[${formulaText}\\]</div><p><br></p>`;
                this.insertHtmlAtCursor(html);
                this.showFormulaModal = false;
            },

            // Image Modal Logic
            openImageModal() {
                this.imageUrl = '';
                this.imageAlt = '';
                this.imageCaption = '';
                this.imageSource = '';
                this.imageAlign = 'align-center';
                this.imageWidth = '75%';
                this.imageSourceType = 'file';
                this.isUploadingImage = false;
                this.uploadError = '';
                this.showImageModal = true;
            },

            uploadImageFile(event) {
                const file = event.target.files ? event.target.files[0] : null;
                if (!file) return;

                this.isUploadingImage = true;
                this.uploadError = '';

                const formData = new FormData();
                formData.append('image', file);

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                fetch('/admin/upload-image', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Error al subir imagen al servidor');
                    return response.json();
                })
                .then(data => {
                    this.isUploadingImage = false;
                    if (data.url) {
                        this.imageUrl = data.url;
                        if (!this.imageAlt) {
                            const nameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
                            this.imageAlt = nameWithoutExt.replace(/[-_]/g, ' ');
                        }
                    } else if (data.error) {
                        this.uploadError = data.error;
                    }
                })
                .catch(err => {
                    console.warn('Network upload failed, reading locally:', err);
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.isUploadingImage = false;
                        this.imageUrl = e.target.result;
                        if (!this.imageAlt) {
                            const nameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
                            this.imageAlt = nameWithoutExt.replace(/[-_]/g, ' ');
                        }
                    };
                    reader.onerror = () => {
                        this.isUploadingImage = false;
                        this.uploadError = 'No se pudo procesar el archivo seleccionado.';
                    };
                    reader.readAsDataURL(file);
                });
            },

            insertImage() {
                if (!this.imageUrl) {
                    alert('Por favor selecciona una imagen primero.');
                    return;
                }

                let captionHtml = '';
                if (this.imageCaption || this.imageSource) {
                    const sourcePart = this.imageSource ? `<small>Fuente: ${this.imageSource}</small>` : '';
                    captionHtml = `<figcaption>${this.imageCaption} ${sourcePart}</figcaption>`;
                }

                const html = `
                    <figure class="edu-figure ${this.imageAlign}">
                        <img src="${this.imageUrl}" alt="${this.imageAlt}" style="max-width:${this.imageWidth};">
                        ${captionHtml}
                    </figure>
                    <p><br></p>
                `;

                this.insertHtmlAtCursor(html);
                this.showImageModal = false;
            },

            // Table Modal Logic
            openTableModal() {
                this.tableRows = 3;
                this.tableCols = 3;
                this.showTableModal = true;
            },

             // Card Modal Logic
            openCardModal() {
                this.showCardModal = true;
                this.tableCols = 2;
            },

            insertCard() {
                let cardHtml = `<div class="grid-${this.tableCols} graficas-grid">`;
                for (let c = 1; c <= this.tableCols; c++) {
                    cardHtml += `<div class="card grafica-card"><div class="figura-descripcion">
                                <h4>Card ${c}</h4>
                                <p>
                                   Escribe el contenido de la tarjeta
                                </p>
                            </div></div>`;
                }
                cardHtml += '</div><p><br></p>';
                this.insertHtmlAtCursor(cardHtml);
                this.showCardModal = false;
            },

            insertTable() {
                let tableHtml = '<div class="table-wrap"><table><thead><tr>';
                for (let c = 1; c <= this.tableCols; c++) {
                    tableHtml += `<th>Encabezado ${c}</th>`;
                }
                tableHtml += '</tr></thead><tbody>';

                for (let r = 1; r <= this.tableRows; r++) {
                    tableHtml += '<tr>';
                    for (let c = 1; c <= this.tableCols; c++) {
                        tableHtml += `<td>Dato ${r}-${c}</td>`;
                    }
                    tableHtml += '</tr>';
                }

                tableHtml += '</tbody></table></div><p><br></p>';
                this.insertHtmlAtCursor(tableHtml);
                this.showTableModal = false;
            },

            insertHtmlAtCursor(html) {
                const editable = document.getElementById(this.editorId + '-editable');
                if (!editable) return;
                editable.focus();

                const sel = window.getSelection();
                if (sel.getRangeAt && sel.rangeCount) {
                    let range = sel.getRangeAt(0);
                    if (!editable.contains(range.commonAncestorContainer)) {
                        range = document.createRange();
                        range.selectNodeContents(editable);
                        range.collapse(false);
                    }

                    range.deleteContents();
                    const el = document.createElement('div');
                    el.innerHTML = html;
                    let frag = document.createDocumentFragment(), node, lastNode;
                    while ((node = el.firstChild)) {
                        lastNode = frag.appendChild(node);
                    }
                    range.insertNode(frag);

                    if (lastNode) {
                        range = range.cloneRange();
                        range.setStartAfter(lastNode);
                        range.collapse(true);
                        sel.removeAllRanges();
                        sel.addRange(range);
                    }
                } else {
                    editable.innerHTML += html;
                }

                this.onContentChange();
            },

            triggerKaTeX() {
                const previewEl = document.getElementById(this.editorId + '-preview');
                if (previewEl && typeof renderMathInElement === 'function') {
                    renderMathInElement(previewEl, {
                        delimiters: [
                            {left: '$$', right: '$$', display: true},
                            {left: '\\[', right: '\\]', display: true},
                            {left: '\\(', right: '\\)', display: false},
                            {left: '$', right: '$', display: false}
                        ],
                        throwOnError: false
                    });
                }
            }
        }));
    }

    document.addEventListener('alpine:init', registerEduWysiwygEditor);
    if (window.Alpine) {
        registerEduWysiwygEditor();
    }
</script>
         @vite(['resources/css/app.css', 'resources/css/plataforma.css', 'resources/js/app.js'])
    </head>
    <body class="body-app">
        <div class="min-h-screen body-app">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            <div class="layout">
        @auth
    @if(auth()->user()->role === 'admin')
        @include('partials.sidebar-admin')
    @else
        @include('partials.sidebar-user')
    @endif
@endauth

        <main>
           
            {{ $slot }}
        </main>
    </div>
        </div>
    </body>
</html>
