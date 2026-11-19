(() => {
    const stackKey = 'edu-marker-return-stack';
    const pendingKey = 'edu-marker-return-pending';
    const read = (key, fallback) => {
        try { return JSON.parse(sessionStorage.getItem(key)) || fallback; } catch { return fallback; }
    };
    const write = (key, value) => {
        try { sessionStorage.setItem(key, JSON.stringify(value)); } catch { /* Disponible sin almacenamiento. */ }
    };
    const uid = () => 'edu-link-' + (crypto.randomUUID?.() || Date.now().toString(36) + Math.random().toString(36).slice(2));
    const pause = ms => new Promise(resolve => setTimeout(resolve, ms));

    // Bloquea la interacción del alumno, pero permite el desplazamiento programático.
    // Se activa desde el <head> al llegar con una marca, antes de iniciar Alpine.
    const navigationLock = {
        active: false, timer: null, controller: null, previousStyles: null, inertElements: [], onCancel: null,
        acquire(onCancel = null) {
            this.onCancel = onCancel;
            if (this.active) return;
            this.active = true;
            const style = document.documentElement.style;
            this.previousStyles = ['overflow', 'overscroll-behavior', 'scrollbar-gutter'].map(name =>
                [name, style.getPropertyValue(name), style.getPropertyPriority(name)]);
            style.setProperty('overflow', 'hidden', 'important');
            style.setProperty('overscroll-behavior', 'none', 'important');
            style.setProperty('scrollbar-gutter', 'stable');
            this.controller = new AbortController();
            const options = {capture: true, passive: false, signal: this.controller.signal};
            const prevent = event => event.preventDefault();
            document.addEventListener('wheel', prevent, options);
            document.addEventListener('touchmove', prevent, options);
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') { event.preventDefault(); this.cancel(); }
                else if (event.key === 'Tab') {
                    event.preventDefault();
                    document.querySelector('[data-marker-loading] button')?.focus({preventScroll: true});
                } else if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'PageUp', 'PageDown', 'Home', 'End', ' '].includes(event.key)
                    && !event.target.closest?.('[data-marker-loading] button')) event.preventDefault();
            }, options);
            this.timer = setTimeout(() => this.cancel(true), 30000);
        },
        isolate() {
            if (!this.active || !document.body) return;
            const navigation = document.querySelector('[data-marker-navigation]');
            for (const element of document.body.children) {
                if (element.contains(navigation) || ['SCRIPT', 'STYLE', 'LINK'].includes(element.tagName)) continue;
                this.inertElements.push([element, element.inert]);
                element.inert = true;
            }
        },
        cancel(timedOut = false) {
            if (this.onCancel) this.onCancel(timedOut);
            else this.release();
        },
        release() {
            clearTimeout(this.timer);
            this.controller?.abort();
            if (this.previousStyles) {
                for (const [name, value, priority] of this.previousStyles) {
                    if (value) document.documentElement.style.setProperty(name, value, priority);
                    else document.documentElement.style.removeProperty(name);
                }
            }
            this.inertElements.forEach(([element, inert]) => { element.inert = inert; });
            this.inertElements = []; this.previousStyles = null;
            this.active = false; this.onCancel = null;
        },
    };
    if (location.hash.startsWith('#marca=')) navigationLock.acquire();

    window.eduMarkerEditorMethods = () => ({
        linkPickerOpen: false, linkLoading: false, linkError: '', linkSearch: '', linkMarkers: [],
        linkRange: null, activeMarkerLink: null, linkPopoverX: 0, linkPopoverY: 0, linkPickerX: 8, linkPickerY: 8,
        filteredLinkMarkers() {
            const query = this.linkSearch.toLocaleLowerCase();
            return this.linkMarkers.filter(m => `${m.title} ${m.module_title} ${m.section_title}`.toLocaleLowerCase().includes(query));
        },
        async openLinkPicker(event) {
            const buttonRect = event.currentTarget.getBoundingClientRect();
            this.linkPickerX = Math.max(8, Math.min(buttonRect.left, window.innerWidth - 336));
            this.linkPickerY = Math.max(8, Math.min(buttonRect.bottom + 8, window.innerHeight - 400));
            this.saveSelection();
            const editable = document.getElementById(this.editorId + '-editable');
            this.linkRange = this.isRangeInEditor(this.savedSelection, editable) ? this.savedSelection.cloneRange() : null;
            this.linkError = ''; this.linkSearch = ''; this.activeMarkerLink = null;
            this.linkPickerOpen = true;
            this.$nextTick(() => this.$refs.linkSearch?.focus());
            if (!this.linkRange || this.linkRange.collapsed || !this.linkRange.toString().trim()) {
                this.linkError = 'Selecciona primero el texto que quieres convertir en hipervínculo.';
                return;
            }
            this.linkLoading = true;
            try {
                if (!window.eduMarkerCatalog) throw new Error();
                this.linkMarkers = await window.eduMarkerCatalog();
            } catch { this.linkError = 'No pudimos cargar las marcas. Cierra el selector y vuelve a intentarlo.'; }
            finally { this.linkLoading = false; }
        },
        insertMarkerLink(marker) {
            const editable = document.getElementById(this.editorId + '-editable');
            const range = this.linkRange;
            if (!this.isRangeInEditor(range, editable) || range.collapsed) {
                this.linkError = 'La selección cambió. Cierra el selector y vuelve a seleccionar el texto.';
                return;
            }
            const walker = document.createTreeWalker(editable, NodeFilter.SHOW_TEXT);
            const parts = [];
            while (walker.nextNode()) {
                const node = walker.currentNode;
                if (!range.intersectsNode(node)) continue;
                const start = node === range.startContainer ? range.startOffset : 0;
                const end = node === range.endContainer ? range.endOffset : node.length;
                if (start === end) continue;
                if (node.parentElement.closest('a, .katex, [contenteditable="false"]')) {
                    this.linkError = 'Selecciona texto sin otros enlaces ni fórmulas dentro de la selección.';
                    return;
                }
                parts.push({node, start, end});
            }
            if (!parts.length) return;
            const linkId = uid();
            const target = {source: marker.source, source_id: marker.source_id, marker_id: marker.marker_id};
            const href = marker.url + '#marca=' + encodeURIComponent(JSON.stringify(target));
            // Envolver por fragmentos conserva negritas, marcas y párrafos.
            parts.reverse().forEach(({node, start, end}, index) => {
                const part = document.createRange();
                part.setStart(node, start); part.setEnd(node, end);
                const anchor = document.createElement('a');
                anchor.href = href; anchor.className = 'edu-marker-link';
                anchor.dataset.markerTarget = JSON.stringify(target);
                anchor.dataset.linkId = linkId; anchor.title = marker.title;
                if (index === parts.length - 1) anchor.id = linkId;
                part.surroundContents(anchor);
            });
            this.linkPickerOpen = false; this.linkRange = null;
            this.onContentChange();
        },
        showMarkerLinkActions(event, anchor) {
            event.preventDefault(); event.stopPropagation(); this.closeMarkerPopover();
            this.activeMarkerLink = anchor;
            const rect = anchor.getBoundingClientRect();
            this.linkPopoverX = Math.max(8, Math.min(rect.left, window.innerWidth - 240));
            this.linkPopoverY = Math.max(8, Math.min(rect.bottom + 8, window.innerHeight - 70));
        },
        removeMarkerLink() {
            const editable = document.getElementById(this.editorId + '-editable');
            const active = this.activeMarkerLink;
            if (!active || !editable?.contains(active)) return;
            const links = active.dataset.linkId
                ? [...editable.querySelectorAll('a[data-link-id]')].filter(a => a.dataset.linkId === active.dataset.linkId) : [active];
            links.forEach(a => a.replaceWith(...a.childNodes));
            this.activeMarkerLink = null; this.onContentChange();
        },
    });

    const register = () => Alpine.data('markerNavigation', () => ({
        message: '', canReturn: false, busy: false, returnStack: [], controller: null, operation: null,
        init() {
            this.controller = new AbortController();
            window.eduMarkerCatalog = () => this.$wire.catalog();
            document.addEventListener('click', event => {
                const link = event.target.closest?.('a[data-marker-target]');
                if (!link || link.closest('.edu-wysiwyg-container')) return;
                event.preventDefault();
                let target;
                try { target = JSON.parse(link.dataset.markerTarget); } catch { this.message = 'El enlace no es válido.'; return; }
                this.navigate(target, link);
            }, {signal: this.controller.signal});
            window.addEventListener('hashchange', () => {
                this.cancelNavigation();
                this.followHash();
            }, {signal: this.controller.signal});
            window.addEventListener('pageshow', () => this.restorePending(), {signal: this.controller.signal});
            window.addEventListener('pagehide', () => this.finishNavigation(this.operation), {signal: this.controller.signal});
            document.addEventListener('livewire:navigating', () => this.cancelNavigation(), {signal: this.controller.signal});
            if (location.hash.startsWith('#marca=')) this.beginNavigation();
            const initialOperation = this.operation;
            this.$nextTick(async () => {
                this.refreshReturn();
                if (await this.restorePending()) this.finishNavigation(initialOperation);
                else if (!initialOperation || this.isNavigating(initialOperation)) this.followHash();
            });
        },
        destroy() { this.controller.abort(); this.finishNavigation(this.operation); },
        beginNavigation() {
            if (this.operation) return this.operation;
            const operation = {id: uid(), cancelled: false, handoff: false};
            this.operation = operation; this.busy = true; this.message = '';
            navigationLock.acquire(timedOut => this.cancelNavigation(timedOut));
            navigationLock.isolate();
            this.$nextTick(() => {
                if (this.isNavigating(operation)) document.querySelector('[data-marker-loading] button')?.focus({preventScroll: true});
            });
            return operation;
        },
        // Alpine envuelve el estado en proxies: comparar el ID, no la identidad del objeto.
        isNavigating(operation) { return !!operation && this.operation?.id === operation.id && !operation.cancelled; },
        finishNavigation(operation) {
            if (!operation || this.operation?.id !== operation.id) return;
            this.operation = null; this.busy = false;
            navigationLock.release();
        },
        cancelNavigation(timedOut = false) {
            const operation = this.operation;
            if (!operation) return;
            operation.cancelled = true;
            if (operation.handoff) {
                window.stop();
                const entries = read(stackKey, []);
                write(stackKey, entries.filter(entry => entry.navigationId !== operation.id));
            }
            this.finishNavigation(operation);
            this.message = timedOut
                ? 'La sección está tardando en abrirse. Puedes seguir navegando y volver a pulsar el enlace.' : '';
        },
        refreshReturn() {
            this.returnStack = read(stackKey, []);
            this.canReturn = this.returnStack.at(-1)?.destination === location.pathname;
        },
        async resolve(target, operation) {
            try {
                const result = await this.$wire.resolveMarker(target);
                if (!this.isNavigating(operation)) return {ok: false};
                if (!result.ok) this.message = result.message;
                return result;
            } catch {
                if (!this.isNavigating(operation)) return {ok: false};
                this.message = 'No pudimos comprobar el enlace. Revisa tu conexión y vuelve a pulsarlo.';
                return {ok: false};
            }
        },
        async navigate(target, link) {
            if (this.busy) return;
            const originY = window.scrollY;
            const operation = this.beginNavigation();
            try {
                const result = await this.resolve(target, operation);
                if (!result.ok) return;
                const url = new URL(result.url, location.href);
                if (url.origin !== location.origin) return;
                const origin = {
                    url: location.href, linkId: link.id || link.dataset.linkId,
                    itemId: Number(link.closest('section[id^="lectura-"]')?.id.replace('lectura-', '')),
                    problemId: link.closest('.accordion-item')?.id,
                    y: originY, destination: url.pathname, navigationId: operation.id,
                };
                if (url.pathname === location.pathname) {
                    if (!await this.reveal(result, operation)) return;
                    write(stackKey, [...read(stackKey, []), origin].slice(-30));
                    this.refreshReturn();
                } else {
                    const dynamicProblem = link.closest('.problem-dynamic-container');
                    if (dynamicProblem) {
                        // Guardar mediante el componente mantiene sus identificadores y paso protegidos.
                        const component = dynamicProblem.closest('[wire\\:id]');
                        try {
                            const token = await Livewire.find(component.getAttribute('wire:id')).rememberMarkerOrigin();
                            if (!this.isNavigating(operation)) return;
                            const sourceUrl = new URL(origin.url);
                            sourceUrl.searchParams.set('marker_return', token);
                            origin.url = sourceUrl.href;
                            history.replaceState(history.state, '', origin.url);
                        } catch {
                            if (!this.isNavigating(operation)) return;
                            this.message = 'No pudimos conservar tu lugar en el ejercicio. Revisa tu conexión y vuelve a pulsar el enlace.';
                            return;
                        }
                    }
                    write(stackKey, [...read(stackKey, []), origin].slice(-30));
                    url.hash = 'marca=' + encodeURIComponent(JSON.stringify(target));
                    operation.handoff = true;
                    location.assign(url.href);
                }
            } catch {
                if (this.isNavigating(operation)) this.message = 'No pudimos abrir la sección. Vuelve a pulsar el enlace para intentarlo otra vez.';
                operation.handoff = false;
            } finally {
                // El documento nuevo continúa el bloqueo desde su <head>.
                if (!operation.handoff) this.finishNavigation(operation);
            }
        },
        async reveal(result, operation) {
            const source = result.step_id ? 'step' : result.problem_id ? 'problem' : 'reading';
            const root = document.querySelector(`[data-marker-source="${source}"][data-marker-source-id="${result.step_id || result.problem_id || result.item_id}"]`);
            const target = root?.querySelector(`[data-concept-id="${CSS.escape(result.marker_id)}"]`);
            if (!target) {
                this.message = result.step_id ? 'Avanza en los pasos del ejercicio o ejemplo hasta que se muestre la sección de esta marca.'
                    : 'La marca no está disponible en esta vista. Recarga la página para actualizar el contenido.';
                return false;
            }
            await this.openParents(target, result.item_id);
            if (!await this.positionAtTarget(target, operation)) return false;
            target.classList.add('edu-concept-flash');
            setTimeout(() => target.classList.remove('edu-concept-flash'), 3600);
            return true;
        },
        async positionAtTarget(target, operation) {
            let stable = 0;
            let previousHeight = -1;
            while (this.isNavigating(operation)) {
                if (!target.isConnected) throw new Error('La sección cambió durante la navegación.');
                target.scrollIntoView({behavior: 'instant', block: 'center'});
                await pause(100);
                if (!this.isNavigating(operation)) return false;
                const rect = target.getBoundingClientRect();
                const height = document.documentElement.scrollHeight;
                // Esperar las imágenes visibles anteriores evita que desplacen la marca al cargar.
                const pendingImages = [...document.images].some(image => !image.complete
                    && image.getClientRects().length && (image.compareDocumentPosition(target) & Node.DOCUMENT_POSITION_FOLLOWING)
                    && (image.loading !== 'lazy' || image.getBoundingClientRect().bottom >= -window.innerHeight));
                const visible = rect.bottom > 0 && rect.top < window.innerHeight;
                stable = visible && height === previousHeight && !pendingImages && document.fonts?.status !== 'loading' ? stable + 1 : 0;
                if (stable >= 3) return true;
                previousHeight = height;
            }
            return false;
        },
        async openParents(target, itemId) {
            if (itemId) window.dispatchEvent(new CustomEvent('expand-reading', {detail: {itemId: Number(itemId)}}));
            const accordion = target.closest('.accordion-item');
            if (accordion && !accordion.classList.contains('disabled-problem')) {
                accordion.classList.add('open');
                accordion.querySelector('.accordion-btn')?.setAttribute('aria-expanded', 'true');
            }
            for (let i = 0; i < 20 && target.getClientRects().length === 0; i++) await pause(25);
            await pause(60);
        },
        async followHash() {
            if (!location.hash.startsWith('#marca=')) return;
            const operation = this.beginNavigation();
            try {
                const target = JSON.parse(decodeURIComponent(location.hash.slice(7)));
                const result = await this.resolve(target, operation);
                if (result.ok) await this.reveal(result, operation);
            } catch {
                if (this.isNavigating(operation)) this.message = 'No pudimos abrir esta marca. Comprueba el enlace e inténtalo otra vez.';
            } finally { this.finishNavigation(operation); }
        },
        async returnToSource() {
            if (this.busy) return;
            this.refreshReturn(); const origin = this.returnStack.at(-1);
            if (!origin) return;
            const url = new URL(origin.url, location.href);
            if (url.origin !== location.origin) return;
            this.returnStack.pop(); write(stackKey, this.returnStack);
            if (url.pathname === location.pathname) {
                await this.restoreOrigin(origin); history.replaceState(null, '', origin.url); this.refreshReturn();
            } else {
                write(pendingKey, origin);
                // El historial conserva respuestas y DOM del origen si hay caché de navegación.
                if (document.referrer && new URL(document.referrer).pathname === url.pathname) history.back();
                else location.assign(url.href);
            }
        },
        async restoreOrigin(origin) {
            const link = document.getElementById(origin.linkId);
            if (link) {
                await this.openParents(link, origin.itemId);
                link.scrollIntoView({behavior: 'smooth', block: 'center'});
            } else {
                const section = document.getElementById(origin.problemId || `lectura-${origin.itemId}`);
                if (section) await this.openParents(section, origin.itemId);
                window.scrollTo({top: origin.y, behavior: 'smooth'});
                this.message = 'Regresaste al origen. Si estabas en un paso del ejercicio, avanza hasta ese paso para volver a ver el enlace.';
            }
        },
        async restorePending() {
            const origin = read(pendingKey, null);
            if (origin && new URL(origin.url, location.href).pathname === location.pathname) {
                write(pendingKey, null); await this.restoreOrigin(origin);
                this.refreshReturn();
                return true;
            }
            this.refreshReturn();
            return false;
        },
    }));
    if (window.Alpine) register();
    else document.addEventListener('alpine:init', register, {once: true});
})();
