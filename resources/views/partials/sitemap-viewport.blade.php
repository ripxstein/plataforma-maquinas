{{--
    Lienzo del mapa conceptual (compartido entre el diseñador del admin y la vista del estudiante).
    Debe incluirse dentro de un elemento con x-data="sitemapDiagram(...)".
    Las conexiones se dibujan con un <svg> por arista para poder usar <template x-for>
    (Alpine no puede iterar <template> dentro de un <svg>).
--}}
<div
    class="sm-viewport"
    :class="{ 'is-edit': isEdit, 'is-panning': panning }"
    x-ref="viewport"
    :style="`${isEdit ? '' : 'height:' + vpHeight + 'px;'} touch-action:${isEdit || zoomed ? 'none' : 'pan-y'}; ${isEdit ? 'background-size:' + (24 * scale) + 'px ' + (24 * scale) + 'px; background-position:' + tx + 'px ' + ty + 'px;' : ''}`"
    @pointerdown="onViewportDown($event)"
    @wheel="onWheel($event)"
    @dragover.prevent
    @drop.prevent="onDrop($event)"
>
    {{-- Definición de puntas de flecha --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true">
        <defs>
            <marker :id="uid + '-arr-def'" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto">
                <path d="M0,0 L10,5 L0,10 z" fill="#8aa0bb" />
            </marker>
            <marker :id="uid + '-arr-act'" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto">
                <path d="M0,0 L10,5 L0,10 z" fill="#1e5aa8" />
            </marker>
            <marker :id="uid + '-arr-sel'" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto">
                <path d="M0,0 L10,5 L0,10 z" fill="#e8890c" />
            </marker>
        </defs>
    </svg>

    {{-- Canvas de confeti para celebración de desbloqueo --}}
    <canvas class="sm-confetti-canvas" x-ref="confettiCanvas" style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:40; display:none;"></canvas>

    <div class="sm-world" x-ref="world" :style="`transform: translate(${tx}px, ${ty}px) scale(${scale});`">
        {{-- Conexiones --}}
        <template x-for="e in edges" :key="e.id">
            <div class="sm-edge" x-show="isEdgeUnlocked(e)">
                <svg class="sm-edge-svg" aria-hidden="true">
                    <path class="sm-edge-hit" :d="geom(e).d" @pointerdown.stop="selectEdge(e.id)" />
                    <path
                        class="sm-edge-path"
                        :class="edgeClasses(e)"
                        :d="geom(e).d"
                        :marker-end="`url(#${uid}-arr-${edgeState(e)})`"
                    />
                </svg>
                <div
                    class="sm-edge-label"
                    :class="{ 'is-selected': selected && selected.type === 'edge' && selected.id === e.id, 'is-dim': hoverId && edgeState(e) === 'def' && !isEdit }"
                    x-show="(e.label && e.label.trim().length > 0) || (isEdit && selected && selected.type === 'edge' && selected.id === e.id)"
                    :style="`left:${geom(e).lx}px; top:${geom(e).ly}px;`"
                    @pointerdown.stop="selectEdge(e.id)"
                    x-text="(e.label && e.label.trim()) ? e.label : (isEdit ? 'sin etiqueta' : '')"
                ></div>
            </div>
        </template>

        {{-- Conexión temporal mientras se arrastra desde el conector --}}
        <svg class="sm-edge-svg" x-show="tempLink" aria-hidden="true">
            <path class="sm-edge-temp" :d="tempLinkPath()" />
        </svg>

        {{-- Nodos --}}
        <template x-for="n in nodes" :key="n.id">
            <div
                class="sm-node"
                :class="nodeClasses(n)"
                :data-node-id="n.id"
                x-show="isNodeUnlocked(n)"
                :style="`left:${n.x}px; top:${n.y}px;`"
                :title="isOrphan(n) ? 'La marca de esta lectura ya no existe' : ''"
                @pointerdown="onNodeDown($event, n)"
                @click="goTo(n)"
                @mouseenter="hoverId = n.id"
                @mouseleave="hoverId = null"
            >
                <div class="sm-node-body">
                    <span class="sm-node-icon" x-show="n.type === 'marker'" x-text="isOrphan(n) ? '⚠️' : '🔖'"></span>
                    <span class="sm-node-icon sm-free-vis-icon" x-show="isEdit && n.type === 'free' && n.hide_if_disconnected" title="Se oculta para el alumno si sus conexiones están bloqueadas">🔒</span>
                    <div class="sm-node-text">
                        <div class="sm-node-label" x-text="n.label"></div>
                        <div class="sm-node-sub" x-show="subtitle(n)" x-text="subtitle(n)"></div>
                    </div>
                    <span class="sm-node-go" x-show="!isEdit && n.type === 'marker'" aria-hidden="true">→</span>
                </div>
                <span
                    class="sm-handle"
                    x-show="isEdit"
                    title="Arrastra hasta otro nodo para conectarlos"
                    @pointerdown="onHandleDown($event, n)"
                ></span>
            </div>
        </template>
    </div>

    {{-- Estado vacío (solo diseñador) --}}
    <div class="sm-empty" x-show="isEdit && !nodes.length" x-cloak>
        <div class="sm-empty-icon">🗺️</div>
        <strong>Tu mapa está vacío</strong>
        <p>Arrastra una marca desde el panel izquierdo hasta aquí, o crea un nodo libre para el tema central.</p>
    </div>

    {{-- Controles de zoom --}}
    <div class="sm-controls" @pointerdown.stop>
        <button type="button" @click="zoomBy(1.2)" title="Acercar" aria-label="Acercar">＋</button>
        <button type="button" @click="zoomBy(1 / 1.2)" title="Alejar" aria-label="Alejar">－</button>
        <button type="button" @click="resetView()" title="Ajustar a la pantalla" aria-label="Ajustar a la pantalla">⤢</button>
    </div>

    <div class="sm-toast" :class="'is-' + toastKind" x-show="toast" x-transition.opacity x-cloak x-text="toast"></div>
</div>
