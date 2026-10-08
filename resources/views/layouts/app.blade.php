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
        <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js" data-navigate-once onload="if (typeof renderAllMath === 'function') renderAllMath();"></script>

        <!-- Scripts -->
        
<script data-navigate-once>
    // --------------------------------------------------------------------------
    // KaTeX Safe Auto-Render Engine (Directo a window.katex, sin fallas de UMD)
    // --------------------------------------------------------------------------
    (function() {
        const findEndOfMath = function (delimiter, text, startIndex) {
            let index = startIndex;
            let braceLevel = 0;
            const delimLength = delimiter.length;
            while (index < text.length) {
                const character = text[index];
                if (braceLevel <= 0 && text.slice(index, index + delimLength) === delimiter) {
                    return index;
                } else if (character === '\\') {
                    index++;
                } else if (character === '{') {
                    braceLevel++;
                } else if (character === '}') {
                    braceLevel--;
                }
                index++;
            }
            return -1;
        };

        const escapeRegex = function (string) {
            return string.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&');
        };

        const amsRegex = /^\\begin{/;

        const splitAtDelimiters = function (text, delimiters) {
            let index;
            const data = [];
            const regexLeft = new RegExp('(' + delimiters.map(x => escapeRegex(x.left)).join('|') + ')');

            while (true) {
                index = text.search(regexLeft);
                if (index === -1) break;

                if (index > 0) {
                    data.push({
                        type: 'text',
                        data: text.slice(0, index)
                    });
                    text = text.slice(index);
                }

                const i = delimiters.findIndex(delim => text.startsWith(delim.left));
                index = findEndOfMath(delimiters[i].right, text, delimiters[i].left.length);
                if (index === -1) break;

                const rawData = text.slice(0, index + delimiters[i].right.length);
                const math = amsRegex.test(rawData) ? rawData : text.slice(delimiters[i].left.length, index);
                data.push({
                    type: 'math',
                    data: math,
                    rawData: rawData,
                    display: delimiters[i].display
                });
                text = text.slice(index + delimiters[i].right.length);
            }

            if (text !== '') {
                data.push({ type: 'text', data: text });
            }
            return data;
        };

        const renderMathInText = function (text, optionsCopy) {
            const data = splitAtDelimiters(text, optionsCopy.delimiters);
            if (data.length === 1 && data[0].type === 'text') {
                return null;
            }

            const fragment = document.createDocumentFragment();
            for (let i = 0; i < data.length; i++) {
                if (data[i].type === 'text') {
                    fragment.appendChild(document.createTextNode(data[i].data));
                } else {
                    const span = document.createElement('span');
                    let math = data[i].data;
                    optionsCopy.displayMode = data[i].display;

                    try {
                        if (optionsCopy.preProcess) {
                            math = optionsCopy.preProcess(math);
                        }
                        if (window.katex && typeof window.katex.render === 'function') {
                            window.katex.render(math, span, optionsCopy);
                        } else {
                            fragment.appendChild(document.createTextNode(data[i].rawData));
                            continue;
                        }
                    } catch (e) {
                        if (window.katex && window.katex.ParseError && !(e instanceof window.katex.ParseError)) {
                            console.warn('KaTeX parse exception:', e);
                        }
                        fragment.appendChild(document.createTextNode(data[i].rawData));
                        continue;
                    }
                    fragment.appendChild(span);
                }
            }
            return fragment;
        };

        const renderElem = function (elem, optionsCopy) {
            for (let i = 0; i < elem.childNodes.length; i++) {
                const childNode = elem.childNodes[i];
                if (childNode.nodeType === 3) {
                    let textContentConcat = childNode.textContent;
                    let sibling = childNode.nextSibling;
                    let nSiblings = 0;

                    while (sibling && sibling.nodeType === Node.TEXT_NODE) {
                        textContentConcat += sibling.textContent;
                        sibling = sibling.nextSibling;
                        nSiblings++;
                    }

                    const frag = renderMathInText(textContentConcat, optionsCopy);
                    if (frag) {
                        for (let j = 0; j < nSiblings; j++) {
                            childNode.nextSibling.remove();
                        }
                        i += frag.childNodes.length - 1;
                        elem.replaceChild(frag, childNode);
                    } else {
                        i += nSiblings;
                    }
                } else if (childNode.nodeType === 1) {
                    const className = ' ' + (childNode.className || '') + ' ';
                    const shouldRender = optionsCopy.ignoredTags.indexOf(childNode.nodeName.toLowerCase()) === -1 &&
                        optionsCopy.ignoredClasses.every(x => className.indexOf(' ' + x + ' ') === -1);
                    if (shouldRender) {
                        renderElem(childNode, optionsCopy);
                    }
                }
            }
        };

        window.renderMathInElement = function (elem, options) {
            if (!elem) return;
            const optionsCopy = Object.assign({}, options);
            optionsCopy.delimiters = optionsCopy.delimiters || [
                { left: '$$', right: '$$', display: true },
                { left: '\\[', right: '\\]', display: true },
                { left: '\\(', right: '\\)', display: false },
                { left: '$', right: '$', display: false },
                { left: '\\begin{equation}', right: '\\end{equation}', display: true },
                { left: '\\begin{align}', right: '\\end{align}', display: true },
                { left: '\\begin{alignat}', right: '\\end{alignat}', display: true },
                { left: '\\begin{gather}', right: '\\end{gather}', display: true },
                { left: '\\begin{CD}', right: '\\end{CD}', display: true }
            ];
            optionsCopy.ignoredTags = optionsCopy.ignoredTags || ['script', 'noscript', 'style', 'textarea', 'pre', 'code', 'option'];
            optionsCopy.ignoredClasses = optionsCopy.ignoredClasses || [];
            optionsCopy.macros = optionsCopy.macros || {};

            renderElem(elem, optionsCopy);
        };
    })();

    // -----------------------------
    // UI: acordeones y KaTeX auto-render
    // -----------------------------
    function renderAllMath(target = document.body) {
        if (!target) return;

        const doRender = () => {
            if (typeof window.katex !== 'undefined' && typeof window.renderMathInElement === 'function') {
                try {
                    window.renderMathInElement(target, {
                        delimiters: [
                            {left: '$$', right: '$$', display: true},
                            {left: '\\[', right: '\\]', display: true},
                            {left: '\\(', right: '\\)', display: false},
                            {left: '$', right: '$', display: false}
                        ],
                        throwOnError: false
                    });
                } catch (err) {
                    console.warn('Error al renderizar KaTeX:', err);
                }
            }
        };

        if (typeof window.katex !== 'undefined' && typeof window.katex.render === 'function') {
            doRender();
        } else {
            let retries = 0;
            const timer = setInterval(() => {
                retries++;
                if (typeof window.katex !== 'undefined' && typeof window.katex.render === 'function') {
                    clearInterval(timer);
                    doRender();
                } else if (retries > 30) {
                    clearInterval(timer);
                }
            }, 100);
        }
    }

    document.addEventListener('DOMContentLoaded', () => renderAllMath());
    document.addEventListener('livewire:navigated', () => {
        renderAllMath();
        document.querySelectorAll('.accordion-btn').forEach(btn => {
            if (!btn.dataset.accordionBound) {
                btn.dataset.accordionBound = 'true';
                btn.addEventListener('click', () => {
                    btn.parentElement.classList.toggle('open');
                });
            }
        });
    });

    document.addEventListener('livewire:init', () => {
        if (window.Livewire && typeof window.Livewire.hook === 'function') {
            window.Livewire.hook('morph.updated', ({ el }) => {
                if (el) renderAllMath(el);
            });
            window.Livewire.hook('commit', ({ succeed }) => {
                succeed(() => {
                    setTimeout(() => renderAllMath(), 50);
                });
            });
        }
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
            formulaNumberMode: 'auto', // 'auto', 'manual', 'none'
            formulaCustomNumber: '',
            
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
            cursorRange: null,

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
                    if (el && el !== document.activeElement && el.innerHTML !== (newValue || '')) {
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
                this.formulaNumberMode = 'auto';
                this.formulaCustomNumber = '';
                this.showFormulaModal = true;
                this.$nextTick(() => this.updateFormulaPreview());
                this.cursorRange = this.getElementAtCursor();
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
                let html = '';
                if (this.formulaNumberMode === 'manual') {
                    const rawNum = this.formulaCustomNumber ? this.formulaCustomNumber.trim() : '';
                    const cleanNumber = rawNum.replace(/^\s*\(+/, '').replace(/\)+\s*$/, '').trim() || '1.1.1';
                    html = `<div class="formula" data-custom-number="${cleanNumber}">\\[${formulaText}\\]</div><p><br></p>`;
                } else if (this.formulaNumberMode === 'none') {
                    html = `<div class="formula no-number">\\[${formulaText}\\]</div><p><br></p>`;
                } else {
                    html = `<div class="formula">\\[${formulaText}\\]</div><p><br></p>`;
                }
                this.insertHtmlInElementAtCursor(html);
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
                this.cursorRange = this.getElementAtCursor();
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

                
                this.insertHtmlInElementAtCursor(html)
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

          
getElementAtCursor() {
    const editable = document.getElementById(this.editorId + '-editable');

    if (!editable) return null;

    editable.focus();

    const sel = window.getSelection();

    if (!sel || !sel.rangeCount) {
        return null;
    }

    let range = sel.getRangeAt(0);

    // Verificar que el cursor esté dentro del editor
    if (!editable.contains(range.commonAncestorContainer)) {
        range = document.createRange();
        range.selectNodeContents(editable);
        range.collapse(false);
    }

    return range;
},

insertHtmlInElementAtCursor(html) {
    const editable = document.getElementById(this.editorId + '-editable');

    if (!editable) return;

    this.cursorRange = this.cursorRange || this.getElementAtCursor();

    if (!this.cursorRange) return;

    const sel = window.getSelection();

    // Eliminar el contenido seleccionado
    this.cursorRange.deleteContents();

    // Crear los elementos HTML
    const el = document.createElement('div');
    el.innerHTML = html;

    const frag = document.createDocumentFragment();

    let node;
    let lastNode;

    while ((node = el.firstChild)) {
        lastNode = frag.appendChild(node);
    }

    // Insertar EXACTAMENTE en la posición del cursor
    this.cursorRange.insertNode(frag);

    // Colocar el cursor después del elemento insertado
    if (lastNode) {
        const newRange = this.cursorRange.cloneRange();

        newRange.setStartAfter(lastNode);
        newRange.collapse(true);

        sel.removeAllRanges();
        sel.addRange(newRange);
    }

    this.onContentChange();
},


toggleTagBlock() {
    const range = this.getElementAtCursor();

    if (!range) return;

    const editable = document.getElementById(
        this.editorId + '-editable'
    );

    if (!editable) return;

    let element = range.startContainer;

    if (element.nodeType === Node.TEXT_NODE) {
        element = element.parentElement;
    }

    // -------------------------------------------------
    // 1. Buscar si ya estamos dentro de un .tag
    // -------------------------------------------------

    const tagElement = element.closest('.tag');

    if (tagElement && editable.contains(tagElement)) {

        // Crear nuevamente un P
        const newElement = document.createElement('p');

        // Mantener el contenido
        while (tagElement.firstChild) {
            newElement.appendChild(tagElement.firstChild);
        }

        // Reemplazar el div.tag
        tagElement.replaceWith(newElement);

        // Restaurar cursor
        const newRange = document.createRange();

        newRange.selectNodeContents(newElement);
        newRange.collapse(false);

        const selection = window.getSelection();

        selection.removeAllRanges();
        selection.addRange(newRange);

        this.onContentChange();

        return;
    }

    // -------------------------------------------------
    // 2. Buscar el bloque actual
    // -------------------------------------------------

    const blockTags = [
        'P',
        'DIV',
        'H1',
        'H2',
        'H3',
        'H4',
        'H5',
        'H6',
        'LI',
        'BLOCKQUOTE'
    ];

    while (
        element &&
        element !== editable &&
        !blockTags.includes(element.tagName)
    ) {
        element = element.parentElement;
    }

    if (!element || element === editable) return;

    // -------------------------------------------------
    // 3. Convertir el bloque a div.tag
    // -------------------------------------------------

    const newElement = document.createElement('div');

    newElement.className = 'tag';

    while (element.firstChild) {
        newElement.appendChild(element.firstChild);
    }

    element.replaceWith(newElement);

    // -------------------------------------------------
    // 4. Restaurar cursor
    // -------------------------------------------------

    const newRange = document.createRange();

    newRange.selectNodeContents(newElement);
    newRange.collapse(false);

    const selection = window.getSelection();

    selection.removeAllRanges();
    selection.addRange(newRange);

    this.onContentChange();
},



            triggerKaTeX() {
                const previewEl = document.getElementById(this.editorId + '-preview');
                if (previewEl) {
                    renderAllMath(previewEl);
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
            <div class="layout"
                 @if(auth()->check() && auth()->user()->role !== 'admin')
                 x-data="{ 
                     sidebarCollapsed: localStorage.getItem('student_sidebar_collapsed') === 'true',
                     toggleSidebar() {
                         this.sidebarCollapsed = !this.sidebarCollapsed;
                         localStorage.setItem('student_sidebar_collapsed', this.sidebarCollapsed);
                     }
                 }"
                 :class="{ 'sidebar-collapsed': sidebarCollapsed }"
                 @endif>
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
