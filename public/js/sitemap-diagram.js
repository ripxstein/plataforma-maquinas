/**
 * Mapa conceptual (sitemap) de módulos.
 * Componente Alpine compartido por:
 *  - el diseñador del administrador (mode: 'edit')
 *  - el visor del estudiante (mode: 'view')
 *
 * Los nodos se posicionan con coordenadas "mundo" (x, y = esquina superior izquierda)
 * dentro de una capa que se desplaza/escala (pan & zoom). Las conexiones son curvas
 * Bézier calculadas a partir del tamaño real medido de cada nodo.
 */
(function () {
    'use strict';

    const MIN_SCALE = 0.2;
    const MAX_SCALE = 2.5;
    const DEFAULT_SIZE = { w: 170, h: 56 };

    const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
    const uid = (prefix) => prefix + Math.random().toString(36).slice(2, 9);

    function register() {
        if (typeof Alpine === 'undefined' || window.__sitemapDiagramRegistered) return;
        window.__sitemapDiagramRegistered = true;

        Alpine.data('sitemapDiagram', (config = {}) => ({
            mode: config.mode || 'view',
            nodes: [],
            edges: [],
            markers: config.markers || [],
            readings: config.readings || {},
            problems: config.problems || {},
            unlockedItemIds: Array.isArray(config.unlockedItemIds) ? config.unlockedItemIds : null,
            completedItemIds: Array.isArray(config.completedItemIds) ? config.completedItemIds.map(Number) : [],
            completedProblemIds: Array.isArray(config.completedProblemIds) ? config.completedProblemIds.map(Number) : [],
            justUnlockedNodeIds: [],
            _celebrateHandler: null,
            _confettiAnimId: null,
            isActive: !!config.isActive,

            uid: uid('sm'),
            scale: 1,
            fitScale: 1,
            tx: 0,
            ty: 0,
            vpHeight: 420,
            sizes: {},

            selected: null, // { type: 'node' | 'edge', id }
            hoverId: null,
            linkFrom: null, // modo "conectar con..." (clic en el siguiente nodo)
            linkHover: null,
            tempLink: null, // { x1, y1, x2, y2 } mientras se arrastra desde el conector
            panning: false,
            userMoved: false,

            dirty: false,
            saving: false,
            filter: '',
            toast: '',
            toastKind: 'info',
            toastTimer: null,
            _resizeObserver: null,

            colors: ['blue', 'teal', 'green', 'amber', 'rose', 'violet', 'slate'],

            // ------------------------------------------------------------------
            // Ciclo de vida
            // ------------------------------------------------------------------
            init() {
                this.nodes = (config.nodes || []).map((n) => ({ ...n }));
                this.edges = (config.edges || []).map((e) => ({ ...e }));

                this.$nextTick(() => {
                    this.measure();
                    this.fit();
                    setTimeout(() => {
                        this.measure();
                        if (!this.userMoved) this.fit();
                    }, 250);
                });

                if (document.fonts && document.fonts.ready) {
                    document.fonts.ready.then(() => {
                        this.measure();
                        if (!this.userMoved) this.fit();
                    });
                }

                if (typeof ResizeObserver !== 'undefined' && this.$refs.viewport) {
                    this._resizeObserver = new ResizeObserver(() => {
                        if (!this.userMoved) this.fit();
                    });
                    this._resizeObserver.observe(this.$refs.viewport);
                }

                this._celebrateHandler = (e) => {
                    const nodeIds = e.detail?.nodeIds || [];
                    this.celebrateUnlock(nodeIds);
                };
                window.addEventListener('celebrate-sitemap-unlock', this._celebrateHandler);
            },

            destroy() {
                if (this._resizeObserver) this._resizeObserver.disconnect();
                if (this._celebrateHandler) window.removeEventListener('celebrate-sitemap-unlock', this._celebrateHandler);
                if (this._confettiAnimId) cancelAnimationFrame(this._confettiAnimId);
            },

            // ------------------------------------------------------------------
            // Utilidades de estado
            // ------------------------------------------------------------------
            get isEdit() {
                return this.mode === 'edit';
            },

            get zoomed() {
                return Math.abs(this.scale - this.fitScale) > 0.03;
            },

            get selectedNode() {
                if (!this.selected || this.selected.type !== 'node') return null;
                return this.nodes.find((n) => n.id === this.selected.id) || null;
            },

            get selectedEdge() {
                if (!this.selected || this.selected.type !== 'edge') return null;
                return this.edges.find((e) => e.id === this.selected.id) || null;
            },

            get usedMarkerIds() {
                return new Set(this.nodes.filter((n) => n.type === 'marker').map((n) => n.marker_id));
            },

            get groupedMarkers() {
                const q = (this.filter || '').trim().toLowerCase();
                const groups = {};
                this.markers.forEach((m) => {
                    if (q && !(m.title || '').toLowerCase().includes(q)) return;
                    if (!groups[m.item_id]) {
                        groups[m.item_id] = {
                            item_id: m.item_id,
                            item_title: m.item_title,
                            item_order: m.item_order,
                            markers: [],
                        };
                    }
                    groups[m.item_id].markers.push(m);
                });
                return Object.values(groups).sort((a, b) => (a.item_order || 0) - (b.item_order || 0));
            },

            isMarkerUsed(m) {
                return this.usedMarkerIds.has(m.marker_id);
            },

            isOrphan(n) {
                return this.isEdit && n.type === 'marker' && !this.markers.some((m) => m.marker_id === n.marker_id);
            },

            isNodeUnlocked(n, visited = new Set()) {
                if (this.isEdit) return true;
                if (!n) return false;
                if (visited.has(n.id)) return false;

                if (n.type === 'free') {
                    if (n.hide_if_disconnected) {
                        visited.add(n.id);
                        return this.edges.some((e) => {
                            let otherId = null;
                            if (e.from === n.id) otherId = e.to;
                            else if (e.to === n.id) otherId = e.from;
                            if (!otherId || visited.has(otherId)) return false;

                            const other = this.nodeById(otherId);
                            return other && this.isNodeUnlocked(other, visited);
                        });
                    }
                    return true;
                }
                if (n.type !== 'marker') return true;

                if (n.problem_id) {
                    return this.completedProblemIds.includes(Number(n.problem_id));
                }

                if (n.item_id) {
                    return this.completedItemIds.includes(Number(n.item_id));
                }

                return true;
            },

            isEdgeUnlocked(e) {
                if (this.isEdit) return true;
                if (!e) return false;
                const a = this.nodeById(e.from);
                const b = this.nodeById(e.to);
                if (!a || !b) return false;
                return this.isNodeUnlocked(a) && this.isNodeUnlocked(b);
            },

            isLocked(n) {
                return !this.isNodeUnlocked(n);
            },

            subtitle(n) {
                if (n.type !== 'marker') return '';
                if (n.problem_id && this.problems && this.problems[n.problem_id]) {
                    return this.problems[n.problem_id];
                }
                const t = this.readings[n.item_id];
                return t ? '📖 ' + t : '';
            },

            nodeClasses(n) {
                const classes = ['sm-c-' + (n.color || 'blue')];
                classes.push(n.type === 'free' ? 'sm-node-free' : 'sm-node-marker');
                if (this.selected && this.selected.type === 'node' && this.selected.id === n.id) classes.push('is-selected');
                if (this.isOrphan(n)) classes.push('is-orphan');
                if (!this.isEdit && n.type === 'marker') classes.push('is-clickable');
                if (this.justUnlockedNodeIds.includes(n.id)) classes.push('sm-node-just-unlocked');
                if (this.linkFrom === n.id) classes.push('is-link-source');
                if (this.linkHover === n.id) classes.push('is-link-target');
                if (this.isDimmed(n.id)) classes.push('is-dim');
                return classes;
            },

            isConnectedToHover(nodeId) {
                if (!this.hoverId) return true;
                if (nodeId === this.hoverId) return true;
                return this.edges.some(
                    (e) => (e.from === this.hoverId && e.to === nodeId) || (e.to === this.hoverId && e.from === nodeId)
                );
            },

            isDimmed(nodeId) {
                return !this.isEdit && this.hoverId && !this.isConnectedToHover(nodeId);
            },

            edgeState(e) {
                if (this.selected && this.selected.type === 'edge' && this.selected.id === e.id) return 'sel';
                if (this.hoverId && (e.from === this.hoverId || e.to === this.hoverId)) return 'act';
                return 'def';
            },

            edgeClasses(e) {
                const state = this.edgeState(e);
                const classes = ['sm-edge-' + state];
                if (this.hoverId && state === 'def' && !this.isEdit) classes.push('is-dim');
                return classes;
            },

            // ------------------------------------------------------------------
            // Medición y geometría
            // ------------------------------------------------------------------
            measure() {
                const world = this.$refs.world;
                if (!world) return;
                const next = {};
                world.querySelectorAll('[data-node-id]').forEach((el) => {
                    next[el.dataset.nodeId] = { w: el.offsetWidth, h: el.offsetHeight };
                });
                this.sizes = next;
            },

            measureSoon() {
                this.$nextTick(() => this.measure());
            },

            sizeOf(n) {
                return this.sizes[n.id] || DEFAULT_SIZE;
            },

            nodeById(id) {
                return this.nodes.find((n) => n.id === id);
            },

            anchor(n, side) {
                const s = this.sizeOf(n);
                switch (side) {
                    case 'r':
                        return { x: n.x + s.w, y: n.y + s.h / 2 };
                    case 'l':
                        return { x: n.x, y: n.y + s.h / 2 };
                    case 't':
                        return { x: n.x + s.w / 2, y: n.y };
                    default:
                        return { x: n.x + s.w / 2, y: n.y + s.h };
                }
            },

            offsetPoint(p, side, off) {
                switch (side) {
                    case 'r':
                        return { x: p.x + off, y: p.y };
                    case 'l':
                        return { x: p.x - off, y: p.y };
                    case 't':
                        return { x: p.x, y: p.y - off };
                    default:
                        return { x: p.x, y: p.y + off };
                }
            },

            geom(e) {
                const a = this.nodeById(e.from);
                const b = this.nodeById(e.to);
                if (!a || !b) return { d: '', lx: 0, ly: 0 };

                const sa = this.sizeOf(a);
                const sb = this.sizeOf(b);
                const dx = b.x + sb.w / 2 - (a.x + sa.w / 2);
                const dy = b.y + sb.h / 2 - (a.y + sa.h / 2);

                let sideA;
                let sideB;
                const horizontal = Math.abs(dx) / (sa.w / 2 + sb.w / 2) > Math.abs(dy) / (sa.h / 2 + sb.h / 2);
                if (horizontal) {
                    sideA = dx >= 0 ? 'r' : 'l';
                    sideB = dx >= 0 ? 'l' : 'r';
                } else {
                    sideA = dy >= 0 ? 'b' : 't';
                    sideB = dy >= 0 ? 't' : 'b';
                }

                const p1 = this.anchor(a, sideA);
                const p2 = this.anchor(b, sideB);
                const dist = Math.hypot(p2.x - p1.x, p2.y - p1.y);
                const off = clamp(dist * 0.45, 36, 170);
                const c1 = this.offsetPoint(p1, sideA, off);
                const c2 = this.offsetPoint(p2, sideB, off);

                return {
                    d: `M${p1.x},${p1.y} C${c1.x},${c1.y} ${c2.x},${c2.y} ${p2.x},${p2.y}`,
                    lx: (p1.x + 3 * c1.x + 3 * c2.x + p2.x) / 8,
                    ly: (p1.y + 3 * c1.y + 3 * c2.y + p2.y) / 8,
                };
            },

            tempLinkPath() {
                if (!this.tempLink) return '';
                const t = this.tempLink;
                return `M${t.x1},${t.y1} L${t.x2},${t.y2}`;
            },

            bounds() {
                if (!this.nodes.length) return { x: 0, y: 0, w: 1, h: 1 };
                let minX = Infinity;
                let minY = Infinity;
                let maxX = -Infinity;
                let maxY = -Infinity;
                this.nodes.forEach((n) => {
                    const s = this.sizeOf(n);
                    minX = Math.min(minX, n.x);
                    minY = Math.min(minY, n.y);
                    maxX = Math.max(maxX, n.x + s.w);
                    maxY = Math.max(maxY, n.y + s.h);
                });
                return { x: minX, y: minY, w: Math.max(1, maxX - minX), h: Math.max(1, maxY - minY) };
            },

            // ------------------------------------------------------------------
            // Pan & zoom
            // ------------------------------------------------------------------
            fit() {
                const vp = this.$refs.viewport;
                if (!vp) return;

                const pad = 48;
                const w = vp.clientWidth;
                if (!w) return;

                if (!this.nodes.length) {
                    this.scale = 1;
                    this.fitScale = 1;
                    this.tx = w / 2;
                    this.ty = (vp.clientHeight || 300) / 2;
                    return;
                }

                const b = this.bounds();

                if (!this.isEdit) {
                    // En la vista del estudiante la altura se adapta al diagrama.
                    const sw = Math.min((w - pad * 2) / b.w, 1.15);
                    this.vpHeight = clamp(Math.round(b.h * sw + pad * 2), 280, 580);
                }

                const h = this.isEdit ? vp.clientHeight : this.vpHeight;
                const s = clamp(Math.min((w - pad * 2) / b.w, (h - pad * 2) / b.h, this.isEdit ? 1.2 : 1.15), MIN_SCALE, MAX_SCALE);

                this.scale = s;
                this.fitScale = s;
                this.tx = (w - b.w * s) / 2 - b.x * s;
                this.ty = (h - b.h * s) / 2 - b.y * s;
            },

            resetView() {
                this.userMoved = false;
                this.measure();
                this.fit();
            },

            screenToWorld(clientX, clientY) {
                const r = this.$refs.viewport.getBoundingClientRect();
                return {
                    x: (clientX - r.left - this.tx) / this.scale,
                    y: (clientY - r.top - this.ty) / this.scale,
                };
            },

            zoomAt(factor, clientX, clientY) {
                const r = this.$refs.viewport.getBoundingClientRect();
                const px = clientX - r.left;
                const py = clientY - r.top;
                const next = clamp(this.scale * factor, MIN_SCALE, MAX_SCALE);
                const k = next / this.scale;
                this.tx = px - (px - this.tx) * k;
                this.ty = py - (py - this.ty) * k;
                this.scale = next;
                this.userMoved = true;
            },

            zoomBy(factor) {
                const r = this.$refs.viewport.getBoundingClientRect();
                this.zoomAt(factor, r.left + r.width / 2, r.top + r.height / 2);
            },

            onWheel(e) {
                if (!this.isEdit && !(e.ctrlKey || e.metaKey)) return;
                e.preventDefault();
                this.zoomAt(Math.exp(-e.deltaY * 0.0015), e.clientX, e.clientY);
            },

            // ------------------------------------------------------------------
            // Interacción con el puntero
            // ------------------------------------------------------------------
            onViewportDown(e) {
                if (e.button !== undefined && e.button !== 0) return;
                if (!this.isEdit && e.pointerType === 'touch' && !this.zoomed) return;

                const start = { x: e.clientX, y: e.clientY, tx: this.tx, ty: this.ty, moved: false };
                this.panning = true;

                const move = (ev) => {
                    const dx = ev.clientX - start.x;
                    const dy = ev.clientY - start.y;
                    if (!start.moved && Math.hypot(dx, dy) < 4) return;
                    start.moved = true;
                    this.tx = start.tx + dx;
                    this.ty = start.ty + dy;
                    this.userMoved = true;
                };
                const up = () => {
                    window.removeEventListener('pointermove', move);
                    window.removeEventListener('pointerup', up);
                    window.removeEventListener('pointercancel', up);
                    this.panning = false;
                    if (!start.moved && this.isEdit) {
                        this.selected = null;
                        this.linkFrom = null;
                    }
                };
                window.addEventListener('pointermove', move);
                window.addEventListener('pointerup', up);
                window.addEventListener('pointercancel', up);
            },

            onNodeDown(e, n) {
                e.stopPropagation();
                if (!this.isEdit) return;
                if (e.button !== undefined && e.button !== 0) return;

                // Modo "conectar con...": el siguiente nodo pulsado es el destino.
                if (this.linkFrom && this.linkFrom !== n.id) {
                    this.createEdge(this.linkFrom, n.id);
                    this.linkFrom = null;
                    return;
                }

                this.selected = { type: 'node', id: n.id };

                const start = { x: e.clientX, y: e.clientY, ox: n.x, oy: n.y, moved: false };
                const move = (ev) => {
                    const dx = (ev.clientX - start.x) / this.scale;
                    const dy = (ev.clientY - start.y) / this.scale;
                    if (!start.moved && Math.hypot(dx, dy) * this.scale < 3) return;
                    start.moved = true;
                    n.x = Math.round(start.ox + dx);
                    n.y = Math.round(start.oy + dy);
                };
                const up = () => {
                    window.removeEventListener('pointermove', move);
                    window.removeEventListener('pointerup', up);
                    window.removeEventListener('pointercancel', up);
                    if (start.moved) this.dirty = true;
                };
                window.addEventListener('pointermove', move);
                window.addEventListener('pointerup', up);
                window.addEventListener('pointercancel', up);
            },

            onHandleDown(e, n) {
                e.stopPropagation();
                e.preventDefault();
                if (!this.isEdit) return;

                this.selected = { type: 'node', id: n.id };
                const origin = this.anchor(n, 'r');
                this.tempLink = { x1: origin.x, y1: origin.y, x2: origin.x, y2: origin.y };

                const move = (ev) => {
                    const p = this.screenToWorld(ev.clientX, ev.clientY);
                    this.tempLink.x2 = p.x;
                    this.tempLink.y2 = p.y;
                    const el = document.elementFromPoint(ev.clientX, ev.clientY);
                    const target = el && el.closest ? el.closest('[data-node-id]') : null;
                    this.linkHover = target && target.dataset.nodeId !== n.id ? target.dataset.nodeId : null;
                };
                const up = (ev) => {
                    window.removeEventListener('pointermove', move);
                    window.removeEventListener('pointerup', up);
                    window.removeEventListener('pointercancel', up);
                    const el = document.elementFromPoint(ev.clientX, ev.clientY);
                    const target = el && el.closest ? el.closest('[data-node-id]') : null;
                    this.tempLink = null;
                    this.linkHover = null;
                    if (target && target.dataset.nodeId !== n.id) {
                        this.createEdge(n.id, target.dataset.nodeId);
                    }
                };
                window.addEventListener('pointermove', move);
                window.addEventListener('pointerup', up);
                window.addEventListener('pointercancel', up);
            },

            selectEdge(id) {
                if (!this.isEdit) return;
                this.selected = { type: 'edge', id };
                this.linkFrom = null;
            },

            startLinkMode() {
                if (!this.selectedNode) return;
                this.linkFrom = this.selectedNode.id;
                this.showToast('Haz clic en el nodo destino para conectarlos', 'info');
            },

            onKey(e) {
                if (!this.isEdit) return;
                const tag = (e.target && e.target.tagName) || '';
                const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || (e.target && e.target.isContentEditable);

                if (e.key === 'Escape') {
                    this.linkFrom = null;
                    this.tempLink = null;
                    if (!typing) this.selected = null;
                    return;
                }
                if (typing) return;
                if ((e.key === 'Delete' || e.key === 'Backspace') && this.selected) {
                    e.preventDefault();
                    this.removeSelected();
                }
            },

            // ------------------------------------------------------------------
            // Edición del diagrama (solo modo admin)
            // ------------------------------------------------------------------
            viewportCenterWorld() {
                const r = this.$refs.viewport.getBoundingClientRect();
                return this.screenToWorld(r.left + r.width / 2, r.top + r.height / 2);
            },

            nextFreeSpot() {
                const c = this.viewportCenterWorld();
                const k = this.nodes.length % 6;
                return { x: Math.round(c.x - 80 + k * 26), y: Math.round(c.y - 24 + k * 26) };
            },

            addMarkerNode(marker, x, y) {
                if (!marker) return;
                if (this.isMarkerUsed(marker)) {
                    const existing = this.nodes.find((n) => n.marker_id === marker.marker_id);
                    if (existing) this.selected = { type: 'node', id: existing.id };
                    this.showToast('Esa marca ya está en el diagrama', 'warn');
                    return;
                }
                const spot = x === undefined ? this.nextFreeSpot() : { x: Math.round(x), y: Math.round(y) };
                const node = {
                    id: uid('n'),
                    type: 'marker',
                    label: marker.title,
                    x: spot.x,
                    y: spot.y,
                    color: 'blue',
                    item_id: marker.item_id,
                    problem_id: marker.problem_id || null,
                    marker_id: marker.marker_id,
                };
                this.nodes.push(node);
                this.selected = { type: 'node', id: node.id };
                this.dirty = true;
                this.measureSoon();
            },

            addFreeNode() {
                const spot = this.nextFreeSpot();
                const node = {
                    id: uid('n'),
                    type: 'free',
                    label: 'Nuevo concepto',
                    x: spot.x,
                    y: spot.y,
                    color: 'teal',
                    item_id: null,
                    marker_id: null,
                    hide_if_disconnected: false,
                };
                this.nodes.push(node);
                this.selected = { type: 'node', id: node.id };
                this.dirty = true;
                this.measureSoon();
                this.$nextTick(() => {
                    const input = this.$refs.labelInput;
                    if (input) {
                        input.focus();
                        input.select();
                    }
                });
            },

            focusMarkerNode(marker) {
                const node = this.nodes.find((n) => n.marker_id === marker.marker_id);
                if (!node) return;
                this.selected = { type: 'node', id: node.id };
                const vp = this.$refs.viewport;
                const s = this.sizeOf(node);
                this.tx = vp.clientWidth / 2 - (node.x + s.w / 2) * this.scale;
                this.ty = vp.clientHeight / 2 - (node.y + s.h / 2) * this.scale;
                this.userMoved = true;
            },

            onMarkerDragStart(e, marker) {
                if (this.isMarkerUsed(marker)) {
                    e.preventDefault();
                    return;
                }
                e.dataTransfer.setData('application/x-sitemap-marker', JSON.stringify(marker));
                e.dataTransfer.effectAllowed = 'copy';
            },

            onDrop(e) {
                if (!this.isEdit) return;
                const raw = e.dataTransfer && e.dataTransfer.getData('application/x-sitemap-marker');
                if (!raw) return;
                try {
                    const marker = JSON.parse(raw);
                    const p = this.screenToWorld(e.clientX, e.clientY);
                    this.addMarkerNode(marker, p.x - 80, p.y - 24);
                } catch (err) {
                    /* drop inválido */
                }
            },

            createEdge(from, to) {
                if (!from || !to || from === to) return null;

                const existing = this.edges.find((e) => (e.from === from && e.to === to) || (e.from === to && e.to === from));
                if (existing) {
                    this.selected = { type: 'edge', id: existing.id };
                    this.showToast('Ya existe una conexión entre esos nodos', 'warn');
                    return existing;
                }

                const edge = { id: uid('e'), from, to, label: '' };
                this.edges.push(edge);
                this.selected = { type: 'edge', id: edge.id };
                this.dirty = true;
                return edge;
            },

            reverseSelectedEdge() {
                const e = this.selectedEdge;
                if (!e) return;
                const from = e.from;
                e.from = e.to;
                e.to = from;
                this.dirty = true;
            },

            removeSelected() {
                if (!this.selected) return;
                if (this.selected.type === 'node') {
                    const id = this.selected.id;
                    this.nodes = this.nodes.filter((n) => n.id !== id);
                    this.edges = this.edges.filter((e) => e.from !== id && e.to !== id);
                } else {
                    const id = this.selected.id;
                    this.edges = this.edges.filter((e) => e.id !== id);
                }
                this.selected = null;
                this.linkFrom = null;
                this.dirty = true;
            },

            setNodeColor(color) {
                const n = this.selectedNode;
                if (!n) return;
                n.color = color;
                this.dirty = true;
            },

            touch() {
                this.dirty = true;
                this.measureSoon();
            },

            autoLayout() {
                if (!this.nodes.length) return;
                this.measure();

                const count = this.nodes.length;
                const level = {};
                this.nodes.forEach((n) => (level[n.id] = 0));

                for (let i = 0; i < count; i++) {
                    let changed = false;
                    this.edges.forEach((e) => {
                        if (level[e.from] === undefined || level[e.to] === undefined) return;
                        if (level[e.to] < level[e.from] + 1 && level[e.from] + 1 < count) {
                            level[e.to] = level[e.from] + 1;
                            changed = true;
                        }
                    });
                    if (!changed) break;
                }

                const rows = {};
                this.nodes.forEach((n) => {
                    (rows[level[n.id]] = rows[level[n.id]] || []).push(n);
                });

                const gapX = 48;
                const gapY = 96;
                let y = 0;
                Object.keys(rows)
                    .map(Number)
                    .sort((a, b) => a - b)
                    .forEach((lv) => {
                        const row = rows[lv].sort((a, b) => a.x - b.x);
                        const total = row.reduce((sum, n) => sum + this.sizeOf(n).w, 0) + gapX * (row.length - 1);
                        let x = -total / 2;
                        let rowH = 0;
                        row.forEach((n) => {
                            n.x = Math.round(x);
                            n.y = Math.round(y);
                            x += this.sizeOf(n).w + gapX;
                            rowH = Math.max(rowH, this.sizeOf(n).h);
                        });
                        y += rowH + gapY;
                    });

                this.dirty = true;
                this.userMoved = false;
                this.$nextTick(() => this.fit());
            },

            async save() {
                if (!this.isEdit || this.saving || !this.$wire) return;
                this.saving = true;
                try {
                    const payload = JSON.parse(JSON.stringify({ nodes: this.nodes, edges: this.edges }));
                    const result = await this.$wire.saveSitemap(payload.nodes, payload.edges, !!this.isActive);
                    if (result && result.ok) {
                        this.dirty = false;
                        this.showToast('Mapa guardado correctamente', 'ok');
                    }
                } catch (err) {
                    this.showToast('No se pudo guardar el mapa. Intenta de nuevo.', 'error');
                } finally {
                    this.saving = false;
                }
            },

            requestClose() {
                if (this.dirty && !window.confirm('Tienes cambios sin guardar. ¿Cerrar el diseñador de todos modos?')) {
                    return;
                }
                this.dirty = false;
                if (this.$wire) this.$wire.dispatch('close-sitemap-designer');
            },

            // ------------------------------------------------------------------
            // Navegación del estudiante
            // ------------------------------------------------------------------
            goTo(n) {
                if (this.isEdit || n.type !== 'marker' || !n.marker_id) return;

                if (this.isLocked(n)) {
                    const msg = n.problem_id
                        ? '🔒 Este problema o ejemplo aún no ha sido completado.'
                        : '🔒 Esta lectura aún está bloqueada. Completa las lecturas anteriores para acceder.';
                    this.showToast(msg, 'warn');
                    return;
                }

                const targets = document.querySelectorAll('[data-concept-id="' + window.CSS.escape(n.marker_id) + '"]');
                if (!targets.length) {
                    this.showToast('Este concepto aún no está disponible en el contenido.', 'warn');
                    return;
                }

                // Si la sección de lectura está colapsada, abrirla
                const section = targets[0].closest('.content-section');
                if (section && window.Alpine) {
                    const data = window.Alpine.$data(section);
                    if (data && 'open' in data && !data.open) {
                        data.open = true;
                    }
                }
                window.dispatchEvent(new CustomEvent('expand-reading', { detail: { itemId: n.item_id } }));

                const accordion = targets[0].closest('.accordion-item');
                if (accordion && !accordion.classList.contains('open')) {
                    accordion.classList.add('open');
                }

                setTimeout(() => {
                    targets[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    targets.forEach((el) => {
                        el.classList.remove('edu-concept-flash');
                        void el.offsetWidth; // reinicia la animación
                        el.classList.add('edu-concept-flash');
                    });
                    setTimeout(() => targets.forEach((el) => el.classList.remove('edu-concept-flash')), 3600);
                }, 50);

                window.dispatchEvent(new CustomEvent('sitemap-navigated'));
            },

            showToast(message, kind = 'info') {
                this.toast = message;
                this.toastKind = kind;
                clearTimeout(this.toastTimer);
                this.toastTimer = setTimeout(() => (this.toast = ''), 3200);
            },

            celebrateUnlock(nodeIds) {
                if (nodeIds && nodeIds.length) {
                    this.justUnlockedNodeIds = [...new Set([...this.justUnlockedNodeIds, ...nodeIds])];
                    const firstNode = this.nodeById(nodeIds[0]);
                    if (firstNode) {
                        this.centerOnNode(firstNode);
                    }
                    setTimeout(() => {
                        this.justUnlockedNodeIds = this.justUnlockedNodeIds.filter(id => !nodeIds.includes(id));
                    }, 14000);
                }
                setTimeout(() => {
                    this.launchConfetti();
                }, 400);
            },

            centerOnNode(n) {
                const vp = this.$refs.viewport;
                if (!vp) return;
                const rect = vp.getBoundingClientRect();
                const s = this.sizeOf(n);
                const nodeCenterX = n.x + (s.w / 2);
                const nodeCenterY = n.y + (s.h / 2);
                this.tx = Math.round((rect.width / 2) - (nodeCenterX * this.scale));
                this.ty = Math.round((rect.height / 2) - (nodeCenterY * this.scale));
                this.userMoved = true;
            },

            launchConfetti() {
                const canvas = this.$refs.confettiCanvas;
                if (!canvas) return;
                const vp = this.$refs.viewport;
                if (!vp) return;

                const rect = vp.getBoundingClientRect();
                canvas.width = rect.width;
                canvas.height = rect.height;
                canvas.style.display = 'block';

                const ctx = canvas.getContext('2d');
                if (!ctx) return;

                const colors = ['#2563eb', '#0d9488', '#f59e0b', '#10b981', '#6366f1', '#ec4899', '#ffffff'];
                const particles = [];
                const count = 48;

                for (let i = 0; i < count; i++) {
                    particles.push({
                        x: rect.width / 2 + (Math.random() - 0.5) * 160,
                        y: rect.height / 2 + (Math.random() - 0.5) * 60,
                        w: Math.random() * 8 + 6,
                        h: Math.random() * 6 + 4,
                        color: colors[Math.floor(Math.random() * colors.length)],
                        vx: (Math.random() - 0.5) * 10,
                        vy: -Math.random() * 9 - 4,
                        rot: Math.random() * 360,
                        rotSpeed: (Math.random() - 0.5) * 12,
                        alpha: 1,
                    });
                }

                const startTime = performance.now();
                const duration = 2400;

                const frame = (now) => {
                    const elapsed = now - startTime;
                    if (elapsed > duration) {
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        canvas.style.display = 'none';
                        return;
                    }

                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    const progress = elapsed / duration;
                    const fade = progress > 0.65 ? (1 - progress) / 0.35 : 1;

                    particles.forEach((p) => {
                        p.x += p.vx;
                        p.y += p.vy;
                        p.vy += 0.35;
                        p.vx *= 0.98;
                        p.rot += p.rotSpeed;

                        ctx.save();
                        ctx.translate(p.x, p.y);
                        ctx.rotate((p.rot * Math.PI) / 180);
                        ctx.globalAlpha = Math.max(0, p.alpha * fade);
                        ctx.fillStyle = p.color;
                        ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                        ctx.restore();
                    });

                    this._confettiAnimId = requestAnimationFrame(frame);
                };

                if (this._confettiAnimId) cancelAnimationFrame(this._confettiAnimId);
                this._confettiAnimId = requestAnimationFrame(frame);
            },
        }));
    }

    document.addEventListener('alpine:init', register);
    if (window.Alpine) register();
})();
