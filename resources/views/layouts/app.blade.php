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

        <!-- KaTeX for Math Formulas (Local / Offline) -->
        <link rel="stylesheet" href="{{ asset('vendor/katex/katex.min.css') }}">
        <script src="{{ asset('vendor/katex/katex.min.js') }}" data-navigate-once onload="if (typeof renderAllMath === 'function') renderAllMath();"></script>

        <!-- CodeMirror for HTML Source Code Editor (Local / Offline) -->
        <link rel="stylesheet" href="{{ asset('vendor/codemirror/codemirror.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/codemirror/addon/foldgutter.css') }}">
        <script data-navigate-once>
            // Carga secuencial: con wire:navigate los scripts se insertan async y podrían ejecutarse desordenados
            (function () {
                if (window.__cmLoader) return;
                const base = @js(asset('vendor/codemirror'));
                const files = [
                    'codemirror.js', 'mode/xml.js', 'mode/javascript.js', 'mode/css.js', 'mode/htmlmixed.js',
                    'addon/foldcode.js', 'addon/foldgutter.js', 'addon/xml-fold.js',
                ];
                window.__cmLoader = files.reduce((chain, file) => chain.then(() => new Promise((resolve) => {
                    if (file === 'codemirror.js' && window.CodeMirror) return resolve();
                    const s = document.createElement('script');
                    s.src = base + '/' + file;
                    s.async = false;
                    s.onload = resolve;
                    s.onerror = resolve;
                    document.head.appendChild(s);
                })), Promise.resolve());
            })();
        </script>

        <!-- Mapa conceptual de módulos (diseñador admin + visor del estudiante) -->
        <script src="{{ asset('js/sitemap-diagram.js') }}?v={{ @filemtime(public_path('js/sitemap-diagram.js')) }}" data-navigate-once></script>

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
            optionsCopy.ignoredClasses = optionsCopy.ignoredClasses || ['katex', 'katex-html', 'katex-mathml', 'admin-input', 'admin-textarea', 'edu-html-textarea'];
            optionsCopy.macros = optionsCopy.macros || {};

            renderElem(elem, optionsCopy);
        };
    })();

    // -----------------------------
    // UI: acordeones y KaTeX auto-render
    // -----------------------------
    let mathRenderPending = false;
    function scheduleMathRender(target = document.body) {
        if (!mathRenderPending) {
            mathRenderPending = true;
            requestAnimationFrame(() => {
                mathRenderPending = false;
                renderAllMath(target);
            });
        }
    }

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
                        ignoredClasses: ['katex', 'katex-html', 'katex-mathml', 'admin-input', 'admin-textarea', 'edu-html-textarea'],
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

    // Observer automático para contenido dinámico de Livewire y acordeones
    const mathObserver = new MutationObserver((mutations) => {
        let hasMath = false;
        for (const m of mutations) {
            // Ignorar mutaciones dentro de lo que KaTeX ya renderizó o dentro de editores de texto
            if (m.target && (m.target.closest?.('.katex') || m.target.closest?.('.edu-wysiwyg-editable') || m.target.closest?.('textarea'))) {
                continue;
            }
            for (const node of m.addedNodes) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.closest?.('.katex') || node.closest?.('.edu-wysiwyg-editable')) continue;
                    const txt = node.textContent || '';
                    if (txt.includes('\\(') || txt.includes('\\[') || txt.includes('$$')) {
                        hasMath = true;
                        break;
                    }
                } else if (node.nodeType === Node.TEXT_NODE) {
                    const txt = node.textContent || '';
                    if (txt.includes('\\(') || txt.includes('\\[') || txt.includes('$$')) {
                        hasMath = true;
                        break;
                    }
                }
            }
            if (hasMath) break;
        }
        if (hasMath) {
            scheduleMathRender();
        }
    });

    function setupMathListeners() {
        renderAllMath();

        document.querySelectorAll('.accordion-btn').forEach(btn => {
            if (!btn.dataset.accordionBound) {
                btn.dataset.accordionBound = 'true';
                btn.addEventListener('click', () => {
                    const parent = btn.parentElement;
                    parent.classList.toggle('open');
                    if (parent.classList.contains('open')) {
                        scheduleMathRender(parent);
                    }
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupMathListeners();
        if (document.body) {
            mathObserver.observe(document.body, { childList: true, subtree: true });
        }
    });

    document.addEventListener('livewire:navigated', () => {
        setupMathListeners();
        if (document.body) {
            mathObserver.disconnect();
            mathObserver.observe(document.body, { childList: true, subtree: true });
        }
    });

    document.addEventListener('livewire:init', () => {
        if (window.Livewire && typeof window.Livewire.hook === 'function') {
            window.Livewire.hook('morph.added', ({ el }) => {
                if (el) scheduleMathRender(el);
            });
            window.Livewire.hook('morph.updated', ({ el }) => {
                if (el) scheduleMathRender(el);
            });
            window.Livewire.hook('commit', ({ succeed }) => {
                succeed(() => {
                    scheduleMathRender();
                    setTimeout(() => renderAllMath(), 120);
                });
            });
        }
        window.addEventListener('render-math', () => scheduleMathRender());
    });

    // -----------------------------
    // Alpine Component: eduWysiwygEditor
    // -----------------------------
    function registerEduWysiwygEditor() {
        if (typeof Alpine === 'undefined') return;

        Alpine.data('eduWysiwygEditor', (config = {}) => ({
            value: config.value || '',
            modelName: config.modelName || '',
            editorId: config.editorId || 'edu-editor',
            hasUserEdited: false,
            activeTab: 'edit',
            showBlocks: false,
            currentBlockTag: 'p',
            currentFontFamily: '',
            currentFontSize: '',
            savedSelection: null,
            htmlCode: config.value || '',
            copiedHtml: false,

            // Context Menu State for Blocks
            blockContextMenu: {
                visible: false,
                x: 0,
                y: 0,
                targetEl: null,
                tagName: '',
            },
            
            codeMirrorInstance: null,
            
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

            // Marcas de concepto (mapa conceptual del módulo)
            showMarkerModal: false,
            markerMode: 'create',
            markerTitle: '',
            markerEditId: null,
            markerRange: null,
            markerError: '',

            showTableModal: false,
            tableRows: 3,
            tableCols: 3,

            init() {
                const editable = document.getElementById(this.editorId + '-editable');
                if (editable) {
                    editable.innerHTML = this.value || '';
                    this.hasUserEdited = false;

                    ['keyup', 'mouseup', 'touchend', 'input'].forEach(evt => {
                        editable.addEventListener(evt, () => {
                            this.saveSelection();
                            this.updateCurrentBlockTag();
                        });
                    });

                    editable.addEventListener('paste', (e) => {
                        const clipboardData = e.clipboardData || window.clipboardData;
                        if (!clipboardData) return;

                        const items = clipboardData.items;
                        let imageFile = null;

                        if (items) {
                            for (let i = 0; i < items.length; i++) {
                                if (items[i].type && items[i].type.startsWith('image/')) {
                                    imageFile = items[i].getAsFile();
                                    break;
                                }
                            }
                        }

                        if (imageFile) {
                            e.preventDefault();
                            this.uploadPastedImage(imageFile);
                            return;
                        }

                        const html = clipboardData.getData('text/html');
                        if (html && (html.includes('<!--[if') || html.includes('urn:schemas-microsoft-com') || html.includes('mso-') || html.includes('MsoNormal') || html.includes('<xml>'))) {
                            e.preventDefault();
                            const cleanedHtml = this.cleanWordHtml(html);
                            document.execCommand('insertHTML', false, cleanedHtml);
                            this.saveSelection();
                            this.onContentChange();
                        }
                    });
                }

                this.$watch('value', (newValue) => {
                    const el = document.getElementById(this.editorId + '-editable');
                    if (el && el !== document.activeElement) {
                        // Protección contra desincronización de Livewire:
                        // Si el usuario ya estuvo editando y Livewire devuelve un valor vacío o nulo
                        // durante una petición secundaria (ej: al escribir en el título o cambiar selects),
                        // no sobreescribir el contenido existente.
                        if (this.hasUserEdited && !newValue && el.innerHTML.trim() !== '') {
                            return;
                        }
                        if (el.innerHTML !== (newValue || '')) {
                            el.innerHTML = newValue || '';
                        }
                    }
                    if (this.activeTab !== 'html') {
                        this.htmlCode = newValue || '';
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
                    this.hasUserEdited = true;
                    this.value = el.innerHTML;
                    this.htmlCode = this.value;
                    if (this.$wire && this.modelName) {
                        try {
                            this.$wire.set(this.modelName, this.value, false);
                        } catch (e) {
                            // Silently ignore if $wire property binding is not found
                        }
                    }
                }
            },

            switchTab(tab) {
                // Sincronizar desde la pestaña saliente antes del cambio
                if (this.activeTab === 'edit') {
                    const el = document.getElementById(this.editorId + '-editable');
                    if (el) {
                        this.value = el.innerHTML;
                    }
                } else if (this.activeTab === 'html') {
                    if (this.codeMirrorInstance) {
                        this.value = this.codeMirrorInstance.getValue();
                    } else {
                        this.value = this.htmlCode || '';
                    }
                    const el = document.getElementById(this.editorId + '-editable');
                    if (el) {
                        el.innerHTML = this.value;
                    }
                }

                this.activeTab = tab;

                // Sincronizar hacia la pestaña entrante
                if (tab === 'html') {
                    this.htmlCode = this.formatHtml(this.value || '');
                    this.$nextTick(() => {
                        if (!this.codeMirrorInstance) {
                            this.initCodeMirror();
                        } else {
                            this.codeMirrorInstance.setValue(this.htmlCode);
                            this.codeMirrorInstance.refresh();
                        }
                        if (this.codeMirrorInstance) {
                            setTimeout(() => {
                                this.codeMirrorInstance.refresh();
                                this.codeMirrorInstance.focus();
                            }, 50);
                        }
                    });
                } else if (tab === 'edit') {
                    const el = document.getElementById(this.editorId + '-editable');
                    if (el && el.innerHTML !== (this.value || '')) {
                        el.innerHTML = this.value || '';
                    }
                } else if (tab === 'preview') {
                    this.$nextTick(() => this.triggerKaTeX());
                }
            },

            initCodeMirror() {
                if (this.codeMirrorInstance) return;
                const textarea = document.getElementById(this.editorId + '-html-textarea');
                if (!textarea || typeof CodeMirror === 'undefined') return;

                this.codeMirrorInstance = CodeMirror.fromTextArea(textarea, {
                    mode: 'htmlmixed',
                    lineNumbers: true,
                    lineWrapping: true,
                    foldGutter: true,
                    gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                    tabSize: 4,
                    indentUnit: 4,
                    indentWithTabs: false
                });

                this.codeMirrorInstance.setValue(this.htmlCode || '');

                this.codeMirrorInstance.on('change', (cm) => {
                    const newVal = cm.getValue();
                    if (this.htmlCode !== newVal) {
                        this.htmlCode = newVal;
                        this.onHtmlChange();
                    }
                });
            },

            onHtmlChange() {
                this.hasUserEdited = true;
                this.value = this.htmlCode || '';
                const el = document.getElementById(this.editorId + '-editable');
                if (el && el !== document.activeElement) {
                    el.innerHTML = this.value;
                }
                if (this.$wire && this.modelName) {
                    try {
                        this.$wire.set(this.modelName, this.value, false);
                    } catch (e) {
                        // Silently ignore
                    }
                }
            },

            formatHtmlCode() {
                const current = this.codeMirrorInstance ? this.codeMirrorInstance.getValue() : (this.htmlCode || this.value || '');
                this.htmlCode = this.formatHtml(current);
                if (this.codeMirrorInstance) {
                    this.codeMirrorInstance.setValue(this.htmlCode);
                }
                this.onHtmlChange();
            },

            cleanHtmlCode() {
                let clean = (this.htmlCode || this.value || '');
                clean = clean.replace(/<!--[\s\S]*?-->/gi, '');
                const parser = new DOMParser();
                const doc = parser.parseFromString(clean, 'text/html');
                
                doc.querySelectorAll('script, style').forEach(el => el.remove());

                doc.body.querySelectorAll('*').forEach(el => {
                    [...el.attributes].forEach(attr => {
                        if (attr.name.startsWith('data-sfc') || 
                            attr.name.startsWith('data-ep') || 
                            attr.name.startsWith('data-hveid') || 
                            attr.name.startsWith('data-processed') || 
                            attr.name.startsWith('data-complete') || 
                            attr.name.startsWith('data-subtree') || 
                            attr.name.startsWith('data-sae') || 
                            attr.name.startsWith('data-wiz') || 
                            attr.name.startsWith('jsaction') || 
                            attr.name.startsWith('jsc') || 
                            attr.name === 'jsuid') {
                            el.removeAttribute(attr.name);
                        }
                    });

                    if (el.className) {
                        const preserved = el.className.split(/\s+/).filter(cls => 
                            cls.startsWith('edu-') || 
                            cls.startsWith('formula') || 
                            cls.startsWith('tag') || 
                            cls.startsWith('card') || 
                            cls.startsWith('pstep') || 
                            cls.startsWith('katex') ||
                            cls.startsWith('accordion')
                        ).join(' ');
                        if (preserved) {
                            el.className = preserved;
                        } else {
                            el.removeAttribute('class');
                        }
                    }

                    if (el.hasAttribute('style')) {
                        const style = el.getAttribute('style') || '';
                        const cleaned = style.split(';')
                            .map(s => s.trim())
                            .filter(s => s && 
                                !s.startsWith('--tw-') && 
                                !s.includes('margin: -1px') && 
                                !s.includes('margin:-1px') && 
                                !s.startsWith('border-bottom: 0px rgb')
                            )
                            .join('; ');
                        if (cleaned) {
                            el.setAttribute('style', cleaned);
                        } else {
                            el.removeAttribute('style');
                        }
                    }
                });

                this.htmlCode = this.formatHtml(doc.body.innerHTML);
                if (this.codeMirrorInstance) {
                    this.codeMirrorInstance.setValue(this.htmlCode);
                }
                this.onHtmlChange();
            },

            copyHtmlToClipboard() {
                const textToCopy = this.codeMirrorInstance ? this.codeMirrorInstance.getValue() : (this.htmlCode || this.value || '');
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(textToCopy).then(() => {
                        this.copiedHtml = true;
                        setTimeout(() => { this.copiedHtml = false; }, 2000);
                    });
                } else {
                    const textarea = document.getElementById(this.editorId + '-html-textarea');
                    if (textarea) {
                        textarea.select();
                        document.execCommand('copy');
                        this.copiedHtml = true;
                        setTimeout(() => { this.copiedHtml = false; }, 2000);
                    }
                }
            },

            formatHtml(html) {
                if (!html) return '';
                let formatted = '';
                let indent = 0;
                const tab = '    ';
                
                const tokens = html.replace(/>\s*</g, '><').replace(/<(?:\/?)(?:[a-zA-Z0-9_\-]+)[^>]*>/g, function(match) {
                    return '\n' + match + '\n';
                }).split('\n').filter(Boolean);

                const selfClosingTags = ['img', 'br', 'hr', 'input', 'meta', 'link'];

                for (let i = 0; i < tokens.length; i++) {
                    let token = tokens[i].trim();
                    if (!token) continue;

                    const isClosing = /^<\//.test(token);
                    const tagNameMatch = token.match(/^<\/?([a-zA-Z0-9_\-]+)/);
                    const tagName = tagNameMatch ? tagNameMatch[1].toLowerCase() : '';
                    const isSelfClosing = selfClosingTags.includes(tagName) || /\/>$/.test(token);

                    if (isClosing) {
                        indent = Math.max(0, indent - 1);
                    }

                    formatted += tab.repeat(indent) + token + '\n';

                    if (!isClosing && !isSelfClosing && tagName) {
                        indent++;
                    }
                }

                return formatted.trim();
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

            applyFontFamily(font) {
                if (!font) return;
                const editable = document.getElementById(this.editorId + '-editable');
                if (!editable) return;

                this.restoreSelection();
                editable.focus();

                const sel = window.getSelection();
                if (!sel || !sel.rangeCount) return;

                const range = sel.getRangeAt(0);
                if (!editable.contains(range.commonAncestorContainer)) return;

                if (!sel.isCollapsed) {
                    document.execCommand('styleWithCSS', false, true);
                    document.execCommand('fontName', false, font);
                } else {
                    const span = document.createElement('span');
                    span.style.fontFamily = font;
                    span.innerHTML = '&#8203;';
                    range.insertNode(span);
                    const newRange = document.createRange();
                    newRange.setStart(span.firstChild, 1);
                    newRange.collapse(true);
                    sel.removeAllRanges();
                    sel.addRange(newRange);
                }

                this.saveSelection();
                this.onContentChange();
                this.$nextTick(() => {
                    this.currentFontFamily = '';
                });
            },

            applyFontSize(size) {
                if (!size) return;
                const editable = document.getElementById(this.editorId + '-editable');
                if (!editable) return;

                this.restoreSelection();
                editable.focus();

                const sel = window.getSelection();
                if (!sel || !sel.rangeCount) return;

                const range = sel.getRangeAt(0);
                if (!editable.contains(range.commonAncestorContainer)) return;

                if (!sel.isCollapsed) {
                    document.execCommand('styleWithCSS', false, false);
                    document.execCommand('fontSize', false, '7');

                    const fonts = editable.querySelectorAll('font[size="7"]');
                    fonts.forEach(fontEl => {
                        const span = document.createElement('span');
                        span.style.fontSize = size;
                        span.innerHTML = fontEl.innerHTML;
                        fontEl.parentNode.replaceChild(span, fontEl);
                    });

                    const spans = editable.querySelectorAll('span[style*="font-size: -webkit-xxx-large"], span[style*="font-size: xxx-large"]');
                    spans.forEach(spanEl => {
                        spanEl.style.fontSize = size;
                    });
                } else {
                    const span = document.createElement('span');
                    span.style.fontSize = size;
                    span.innerHTML = '&#8203;';
                    range.insertNode(span);
                    const newRange = document.createRange();
                    newRange.setStart(span.firstChild, 1);
                    newRange.collapse(true);
                    sel.removeAllRanges();
                    sel.addRange(newRange);
                }

                this.saveSelection();
                this.onContentChange();
                this.$nextTick(() => {
                    this.currentFontSize = '';
                });
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
                    if (response.status === 413) {
                        throw new Error('La imagen excede el tamaño máximo permitido por Nginx (413 Request Entity Too Large).');
                    }
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
                    this.isUploadingImage = false;
                    this.uploadError = err.message || 'Error al subir la imagen al servidor.';
                });
            },

            uploadPastedImage(file) {
                const formData = new FormData();
                formData.append('image', file);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                const placeholderId = 'paste-upload-' + Date.now();
                this.insertHtmlAtCursor(`<div id="${placeholderId}" class="footer-note" style="margin: 10px 0; background: #eef6ff; color: var(--azul-secundario); font-size: 0.85rem;">⏳ Subiendo imagen al servidor...</div>`);

                fetch('/admin/upload-image', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken || '' },
                    body: formData
                })
                .then(res => {
                    if (res.status === 413) throw new Error('La imagen pegada supera el tamaño permitido por Nginx (413).');
                    if (!res.ok) throw new Error('Error al subir la imagen pegada.');
                    return res.json();
                })
                .then(data => {
                    const el = document.getElementById(placeholderId);
                    if (data.url) {
                        const imgHtml = `<div class="edu-figure align-center" style="margin: 14px 0;"><img src="${data.url}" style="max-width: 75%; height: auto; border: 1px solid var(--borde); border-radius: 10px; padding: 3px; background: #fff;" alt="Figura pegada"><figcaption style="font-size: 0.8rem; color: var(--gris); margin-top: 4px;">Figura</figcaption></div><p><br></p>`;
                        if (el) {
                            el.outerHTML = imgHtml;
                        } else {
                            this.insertHtmlAtCursor(imgHtml);
                        }
                        this.onContentChange();
                    } else {
                        if (el) el.remove();
                        alert(data.error || 'No se pudo subir la imagen.');
                    }
                })
                .catch(err => {
                    const el = document.getElementById(placeholderId);
                    if (el) el.remove();
                    alert(err.message || 'Error de red al subir la imagen.');
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

            // Concept Markers (marcas para el mapa conceptual)
            getMarkerAncestor(node) {
                const editable = document.getElementById(this.editorId + '-editable');
                if (!node || !editable) return null;
                const el = node.nodeType === 1 ? node : node.parentElement;
                const mark = el ? el.closest('mark.edu-concept-marker') : null;
                return mark && editable.contains(mark) ? mark : null;
            },

            openMarkerModal() {
                this.saveSelection();
                const range = this.savedSelection;

                this.markerError = '';
                this.markerEditId = null;
                this.markerRange = null;
                this.markerTitle = '';

                if (!range) {
                    this.markerMode = 'create';
                    this.markerError = 'Selecciona primero el fragmento de texto que quieres marcar como concepto.';
                    this.showMarkerModal = true;
                    return;
                }

                const existing = this.getMarkerAncestor(range.startContainer) || this.getMarkerAncestor(range.endContainer);

                if (existing) {
                    this.markerMode = 'edit';
                    this.markerEditId = existing.dataset.conceptId;
                    this.markerTitle = existing.dataset.conceptTitle || '';
                } else if (!range.collapsed && range.toString().trim() !== '') {
                    this.markerMode = 'create';
                    this.markerRange = range.cloneRange();
                    this.markerTitle = range.toString().replace(/\s+/g, ' ').trim().slice(0, 80);
                } else {
                    this.markerMode = 'create';
                    this.markerError = 'Selecciona primero el fragmento de texto que quieres marcar como concepto.';
                }

                this.showMarkerModal = true;
                this.$nextTick(() => {
                    if (this.$refs.markerInput) {
                        this.$refs.markerInput.focus();
                        this.$refs.markerInput.select();
                    }
                });
            },

            saveMarker() {
                const title = (this.markerTitle || '').replace(/\s+/g, ' ').trim();
                if (!title) return;

                const editable = document.getElementById(this.editorId + '-editable');
                if (!editable) return;

                if (this.markerMode === 'edit' && this.markerEditId) {
                    editable.querySelectorAll('mark.edu-concept-marker').forEach((mark) => {
                        if (mark.dataset.conceptId === this.markerEditId) {
                            mark.setAttribute('data-concept-title', title);
                        }
                    });
                } else if (this.markerRange) {
                    const id = 'c' + Math.random().toString(36).slice(2, 10);
                    const wrapped = this.wrapRangeInMarkers(this.markerRange, id, title);
                    if (!wrapped) {
                        this.markerError = 'No se pudo marcar ese fragmento. Intenta seleccionar solo texto normal (sin fórmulas ni texto ya marcado).';
                        return;
                    }
                }

                this.showMarkerModal = false;
                this.markerRange = null;
                this.onContentChange();
            },

            removeMarker() {
                const editable = document.getElementById(this.editorId + '-editable');
                if (!editable || !this.markerEditId) return;

                editable.querySelectorAll('mark.edu-concept-marker').forEach((mark) => {
                    if (mark.dataset.conceptId !== this.markerEditId) return;
                    const parent = mark.parentNode;
                    while (mark.firstChild) parent.insertBefore(mark.firstChild, mark);
                    parent.removeChild(mark);
                    parent.normalize();
                });

                this.showMarkerModal = false;
                this.markerEditId = null;
                this.onContentChange();
            },

            /**
             * Envuelve el rango en uno o varios <mark> (uno por nodo de texto) con el mismo
             * data-concept-id, de modo que las selecciones que cruzan párrafos o negritas
             * siguen produciendo HTML válido.
             */
            wrapRangeInMarkers(range, conceptId, title) {
                const root = range.commonAncestorContainer.nodeType === 1
                    ? range.commonAncestorContainer
                    : range.commonAncestorContainer.parentNode;

                const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
                const slices = [];
                let textNode;

                while ((textNode = walker.nextNode())) {
                    if (!range.intersectsNode(textNode)) continue;
                    const parent = textNode.parentElement;
                    if (parent && (parent.closest('.katex') || parent.closest('mark.edu-concept-marker'))) continue;

                    const start = textNode === range.startContainer ? range.startOffset : 0;
                    const end = textNode === range.endContainer ? range.endOffset : textNode.length;
                    if (end <= start) continue;
                    if (textNode.data.slice(start, end).trim() === '') continue;

                    slices.push({ node: textNode, start, end });
                }

                slices.forEach((slice) => {
                    const r = document.createRange();
                    r.setStart(slice.node, slice.start);
                    r.setEnd(slice.node, slice.end);
                    const mark = document.createElement('mark');
                    mark.className = 'edu-concept-marker';
                    mark.setAttribute('data-concept-id', conceptId);
                    mark.setAttribute('data-concept-title', title);
                    r.surroundContents(mark);
                });

                if (slices.length) {
                    window.getSelection().removeAllRanges();
                    this.savedSelection = null;
                }

                return slices.length > 0;
            },

            // Block Context Menu Actions
            openBlockContextMenu(e) {
                if (this.activeTab !== 'edit' || !this.showBlocks) return;

                const editable = document.getElementById(this.editorId + '-editable');
                if (!editable) return;

                let target = e.target;
                if (!target || target === editable) return;

                const block = target.closest('p, h1, h2, h3, h4, h5, h6, div, section, article, blockquote, ul, ol, table, pre, .edu-block, .card, .formula');
                if (!block || block === editable || !editable.contains(block)) return;

                e.preventDefault();
                e.stopPropagation();

                this.blockContextMenu.targetEl = block;
                this.blockContextMenu.tagName = block.tagName ? block.tagName.toLowerCase() : 'bloque';

                const menuWidth = 240;
                const menuHeight = 310;
                let x = e.clientX;
                let y = e.clientY;

                if (x + menuWidth > window.innerWidth) {
                    x = Math.max(10, window.innerWidth - menuWidth - 15);
                }
                if (y + menuHeight > window.innerHeight) {
                    y = Math.max(10, window.innerHeight - menuHeight - 15);
                }

                this.blockContextMenu.x = x;
                this.blockContextMenu.y = y;
                this.blockContextMenu.visible = true;
            },

            closeBlockContextMenu() {
                this.blockContextMenu.visible = false;
                this.blockContextMenu.targetEl = null;
            },

            insertParagraphBeforeBlock() {
                const el = this.blockContextMenu.targetEl;
                if (!el || !el.parentNode) return;
                const p = document.createElement('p');
                p.innerHTML = '<br>';
                el.parentNode.insertBefore(p, el);
                this.closeBlockContextMenu();
                this.onContentChange();
                this.placeCursorIn(p);
            },

            insertParagraphAfterBlock() {
                const el = this.blockContextMenu.targetEl;
                if (!el || !el.parentNode) return;
                const p = document.createElement('p');
                p.innerHTML = '<br>';
                el.parentNode.insertBefore(p, el.nextSibling);
                this.closeBlockContextMenu();
                this.onContentChange();
                this.placeCursorIn(p);
            },

            unwrapBlockTag() {
                const el = this.blockContextMenu.targetEl;
                if (!el || !el.parentNode) return;
                const parent = el.parentNode;

                if (!el.hasChildNodes()) {
                    parent.removeChild(el);
                } else {
                    while (el.firstChild) {
                        parent.insertBefore(el.firstChild, el);
                    }
                    parent.removeChild(el);
                }

                this.closeBlockContextMenu();
                this.onContentChange();
            },

            wrapBlockInDiv() {
                const el = this.blockContextMenu.targetEl;
                if (!el || !el.parentNode) return;
                const div = document.createElement('div');
                el.parentNode.insertBefore(div, el);
                div.appendChild(el);
                this.closeBlockContextMenu();
                this.onContentChange();
                this.placeCursorIn(div);
            },

            changeBlockTag(newTag) {
                const el = this.blockContextMenu.targetEl;
                if (!el || !el.parentNode) return;
                const newEl = document.createElement(newTag);

                Array.from(el.attributes).forEach(attr => {
                    newEl.setAttribute(attr.name, attr.value);
                });

                while (el.firstChild) {
                    newEl.appendChild(el.firstChild);
                }

                el.parentNode.replaceChild(newEl, el);
                this.closeBlockContextMenu();
                this.onContentChange();
                this.placeCursorIn(newEl);
            },

            removeEntireBlock() {
                const el = this.blockContextMenu.targetEl;
                if (!el || !el.parentNode) return;
                el.parentNode.removeChild(el);
                this.closeBlockContextMenu();
                this.onContentChange();
            },

            placeCursorIn(el) {
                try {
                    const sel = window.getSelection();
                    const range = document.createRange();
                    range.selectNodeContents(el);
                    range.collapse(true);
                    sel.removeAllRanges();
                    sel.addRange(range);
                    const editable = document.getElementById(this.editorId + '-editable');
                    if (editable) editable.focus();
                } catch (e) {}
            },

            insertDivBlock() {
                const editable = document.getElementById(this.editorId + '-editable');
                if (!editable) return;

                this.restoreSelection();
                editable.focus();

                const sel = window.getSelection();
                if (!sel || !sel.rangeCount) {
                    const div = document.createElement('div');
                    div.innerHTML = '<p><br></p>';
                    editable.appendChild(div);
                    this.placeCursorIn(div.querySelector('p') || div);
                    this.saveSelection();
                    this.onContentChange();
                    return;
                }

                const range = sel.getRangeAt(0);
                if (!editable.contains(range.commonAncestorContainer)) {
                    const div = document.createElement('div');
                    div.innerHTML = '<p><br></p>';
                    editable.appendChild(div);
                    this.placeCursorIn(div.querySelector('p') || div);
                    this.saveSelection();
                    this.onContentChange();
                    return;
                }

                if (!sel.isCollapsed) {
                    try {
                        const div = document.createElement('div');
                        div.appendChild(range.extractContents());
                        range.insertNode(div);
                        this.placeCursorIn(div);
                    } catch (e) {
                        document.execCommand('formatBlock', false, '<div>');
                    }
                } else {
                    const div = document.createElement('div');
                    div.innerHTML = '<p><br></p>';
                    
                    range.deleteContents();
                    range.insertNode(div);

                    const trailingP = document.createElement('p');
                    trailingP.innerHTML = '<br>';
                    if (div.nextSibling) {
                        div.parentNode.insertBefore(trailingP, div.nextSibling);
                    } else {
                        div.parentNode.appendChild(trailingP);
                    }

                    this.placeCursorIn(div.querySelector('p') || div);
                }

                this.saveSelection();
                this.onContentChange();
            },



            triggerKaTeX() {
                const previewEl = document.getElementById(this.editorId + '-preview');
                if (previewEl) {
                    renderAllMath(previewEl);
                }
            },

            cleanWordHtml(html) {
                // 1. Eliminar condicionales de comentarios de Word (<!--[if ...]>...<![endif]-->)
                let clean = html.replace(/<!--\[if[\s\S]*?<!\[endif\]-->/gi, '');
                clean = clean.replace(/<!--[\s\S]*?-->/gi, '');

                // 2. Eliminar etiquetas específicas de Word/Office
                clean = clean.replace(/<xml[\s\S]*?<\/xml>/gi, '');
                clean = clean.replace(/<\/?\w+:[^>]*>/gi, '');

                // 3. Parsear el HTML resultante con DOMParser
                const parser = new DOMParser();
                const doc = parser.parseFromString(clean, 'text/html');

                // 4. Remover etiquetas que no pertenecen al contenido (style, meta, link, title)
                doc.querySelectorAll('style, meta, link, title').forEach(el => el.remove());

                // 5. Limpiar clases Mso y estilos mso- de cada elemento
                doc.body.querySelectorAll('*').forEach(el => {
                    if (el.className) {
                        const cleanedClass = el.className
                            .split(/\s+/)
                            .filter(cls => !cls.toLowerCase().startsWith('mso') && cls.trim() !== '')
                            .join(' ');
                        if (cleanedClass) {
                            el.className = cleanedClass;
                        } else {
                            el.removeAttribute('class');
                        }
                    }

                    if (el.hasAttribute('style')) {
                        const styleVal = el.getAttribute('style') || '';
                        const cleanedStyle = styleVal
                            .split(';')
                            .map(s => s.trim())
                            .filter(s => s && !s.toLowerCase().startsWith('mso-') && !s.toLowerCase().startsWith('tab-stops:'))
                            .join('; ');
                        if (cleanedStyle) {
                            el.setAttribute('style', cleanedStyle);
                        } else {
                            el.removeAttribute('style');
                        }
                    }
                });

                return doc.body.innerHTML;
            }
        }));
    }

    document.addEventListener('alpine:init', registerEduWysiwygEditor);
    if (window.Alpine) {
        registerEduWysiwygEditor();
    }
</script>
         @vite(['resources/css/app.css', 'resources/css/plataforma.css', 'resources/css/sitemap.css', 'resources/js/app.js'])
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
