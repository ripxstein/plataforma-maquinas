<div>
    {{-- El lienzo corre completamente en Alpine: wire:ignore evita que Livewire lo re-renderice al guardar. --}}
    <div
        class="sm-designer"
        wire:ignore
        x-data="sitemapDiagram(@js($config))"
        @keydown.window="onKey($event)"
    >
        {{-- Barra superior --}}
        <header class="sm-designer-header">
            <div class="sm-designer-title">
                <span class="sm-designer-badge">🗺️ Mapa conceptual</span>
                <h3>{{ $module->title }}</h3>
                <span class="sm-dirty-pill" x-show="dirty" x-cloak>● Cambios sin guardar</span>
            </div>

            <div class="sm-designer-actions">
                <label class="sm-switch" title="Si está activo, el mapa aparece al inicio del módulo para los estudiantes">
                    <input type="checkbox" x-model="isActive" @change="dirty = true">
                    <span class="sm-switch-track"><span class="sm-switch-thumb"></span></span>
                    <span class="sm-switch-label" x-text="isActive ? 'Visible para estudiantes' : 'Oculto (borrador)'"></span>
                </label>

                <button type="button" class="btn-secondary btn-sm" @click="autoLayout()" title="Ordena los nodos automáticamente según sus conexiones">
                    ✨ Reorganizar
                </button>
                <button type="button" class="btn-primary btn-sm" @click="save()" :disabled="saving || !dirty">
                    <span x-text="saving ? 'Guardando…' : '💾 Guardar mapa'"></span>
                </button>
                <button type="button" class="btn-secondary btn-sm" @click="requestClose()">✕ Cerrar</button>
            </div>
        </header>

        <div class="sm-designer-body">
            {{-- Panel izquierdo: marcas disponibles --}}
            <aside class="sm-palette">
                <div class="sm-panel-title">Marcas de las lecturas</div>

                <button type="button" class="sm-free-btn" @click="addFreeNode()">
                    <span>◆</span> Añadir nodo libre
                    <small>Tema central, categoría o conector</small>
                </button>

                <input type="search" class="admin-input sm-search" placeholder="Buscar marca…" x-model="filter">

                <div class="sm-orphan-warning" x-show="nodes.some(n => isOrphan(n))" x-cloak>
                    ⚠️ Hay nodos cuya marca ya no existe en la lectura (aparecen con borde rojo). Elimínalos o vuelve a crear la marca.
                </div>

                <div class="sm-palette-scroll">
                    <template x-if="!markers.length">
                        <div class="sm-palette-empty">
                            <strong>Aún no hay marcas</strong>
                            <p>Abre una lectura, selecciona un fragmento de texto y pulsa <b>🔖 Marca</b> en el editor. Luego vuelve aquí.</p>
                        </div>
                    </template>

                    <template x-for="group in groupedMarkers" :key="group.item_id">
                        <div class="sm-group">
                            <div class="sm-group-title">📖 <span x-text="group.item_title"></span></div>
                            <template x-for="m in group.markers" :key="m.marker_id">
                                <div
                                    class="sm-marker-item"
                                    :class="{ 'is-used': isMarkerUsed(m) }"
                                    :draggable="!isMarkerUsed(m)"
                                    @dragstart="onMarkerDragStart($event, m)"
                                    @click="isMarkerUsed(m) ? focusMarkerNode(m) : addMarkerNode(m)"
                                    :title="isMarkerUsed(m) ? 'Ya está en el mapa — clic para ubicarla' : 'Arrastra al lienzo o haz clic para añadir'"
                                >
                                    <span class="sm-marker-dot">🔖</span>
                                    <span class="sm-marker-title" x-text="m.title"></span>
                                    <span class="sm-marker-state" x-text="isMarkerUsed(m) ? '✓' : '＋'"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </aside>

            {{-- Lienzo --}}
            <main class="sm-canvas-wrap">
                @include('partials.sitemap-viewport')
            </main>

            {{-- Panel derecho: inspector --}}
            <aside class="sm-inspector">
                <div class="sm-panel-title">Propiedades</div>

                <template x-if="!selected">
                    <div class="sm-help">
                        <p><strong>Cómo construir el mapa</strong></p>
                        <ol>
                            <li>Arrastra marcas desde la izquierda al lienzo.</li>
                            <li>Une dos nodos arrastrando desde el punto <span class="sm-help-dot"></span> del borde derecho hacia otro nodo.</li>
                            <li>Haz clic en un nodo o conexión para cambiar su texto, color o dirección.</li>
                            <li>Rueda del ratón = zoom. Arrastra el fondo para moverte.</li>
                            <li>Pulsa <kbd>Supr</kbd> para borrar lo seleccionado.</li>
                        </ol>
                    </div>
                </template>

                <template x-if="selectedNode">
                    <div class="sm-form">
                        <div class="sm-type-badge" :class="selectedNode?.type === 'free' ? 'is-free' : 'is-marker'"
                             x-text="selectedNode?.type === 'free' ? '◆ Nodo libre' : '🔖 Concepto de lectura'"></div>

                        <label class="form-label">Texto del nodo</label>
                        <textarea class="admin-input" rows="2" maxlength="200" x-ref="labelInput"
                                  :value="selectedNode?.label ?? ''" @input="if (selectedNode) { selectedNode.label = $event.target.value; touch(); }"></textarea>

                        <template x-if="selectedNode?.type === 'marker'">
                            <div class="sm-linked-info">
                                <div><small>Lectura enlazada</small></div>
                                <div x-text="readings[selectedNode?.item_id] || 'Lectura no encontrada'"></div>
                                <div class="sm-linked-warn" x-show="selectedNode && isOrphan(selectedNode)">
                                    ⚠️ La marca ya no existe en la lectura. Este nodo no llevará a ningún lugar.
                                </div>
                            </div>
                        </template>

                        <label class="form-label">Color</label>
                        <div class="sm-swatches">
                            <template x-for="c in colors" :key="c">
                                <button type="button" class="sm-swatch" :class="['sm-c-' + c, selectedNode?.color === c ? 'is-active' : '']"
                                        @click="setNodeColor(c)" :title="c"></button>
                            </template>
                        </div>

                        <button type="button" class="btn-secondary btn-sm sm-block-btn" @click="startLinkMode()">
                            🔗 Conectar con otro nodo
                        </button>
                        <button type="button" class="btn-secondary btn-sm btn-danger sm-block-btn" @click="removeSelected()">
                            🗑️ Eliminar nodo
                        </button>
                    </div>
                </template>

                <template x-if="selectedEdge">
                    <div class="sm-form">
                        <div class="sm-type-badge is-edge">↗ Conexión</div>
                        <div class="sm-edge-summary">
                            <span x-text="nodeById(selectedEdge?.from)?.label"></span>
                            <b>→</b>
                            <span x-text="nodeById(selectedEdge?.to)?.label"></span>
                        </div>

                        <label class="form-label">Etiqueta de la relación (opcional)</label>
                        <input type="text" class="admin-input" maxlength="100" placeholder="Ej.: se divide en, provoca, depende de…"
                               :value="selectedEdge?.label ?? ''" @input="if (selectedEdge) { selectedEdge.label = $event.target.value; dirty = true; }">

                        <button type="button" class="btn-secondary btn-sm sm-block-btn" @click="reverseSelectedEdge()">
                            ⇄ Invertir dirección
                        </button>
                        <button type="button" class="btn-secondary btn-sm btn-danger sm-block-btn" @click="removeSelected()">
                            🗑️ Eliminar conexión
                        </button>
                    </div>
                </template>
            </aside>
        </div>
    </div>
</div>
