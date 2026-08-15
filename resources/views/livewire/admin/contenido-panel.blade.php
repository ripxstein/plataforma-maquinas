<div>
    <!-- Hero Section -->
    <section class="hero" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2>📚 Administrar Contenido del Curso</h2>
            <p>Gestiona fácilmente Módulos, Lecturas y Problemas interactivos de forma jerárquica y sin necesidad de escribir código.</p>
        </div>
        <div>
            <button class="btn-primary" wire:click="openModuleModal">
                ➕ Crear Nuevo Módulo
            </button>
        </div>
    </section>

    <!-- Notification Message -->
    @if (session()->has('message'))
        <div class="footer-note" style="margin-bottom:22px; background:#e7f7ea; border-color:#7bc28a; color:#1e5aa8;">
            ✨ {{ session('message') }}
        </div>
    @endif

    <!-- Content Tree Section -->
    <section class="content-section">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Estructura del Contenido del Curso</h3>
            <span class="badge">{{ $modules->count() }} Módulos en total</span>
        </div>

        <div class="section-body">
            @if($modules->isEmpty())
                <div class="footer-note" style="text-align: center; padding: 36px;">
                    <p style="font-size: 1.1rem; color: var(--gris); margin-bottom: 14px;">
                        No hay módulos registrados en la plataforma.
                    </p>
                    <button class="btn-primary" wire:click="openModuleModal">
                        ➕ Crear el Primer Módulo
                    </button>
                </div>
            @endif

            @foreach($modules as $module)
                <div class="tree-module-card">
                    <!-- Module Header -->
                    <div class="tree-module-header">
                        <div class="tree-module-title">
                            <span class="tag" style="margin: 0; font-size: 0.9rem;">Módulo {{ $module->order }}</span>
                            <h4>{{ $module->title }}</h4>
                            <span style="font-size: 0.85rem; color: var(--gris); font-weight: normal;">(slug: <code>{{ $module->slug }}</code>)</span>
                        </div>

                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button class="btn-secondary btn-sm" wire:click="openReadingModal(null, {{ $module->id }})">
                                ➕ Lectura
                            </button>
                            <button class="btn-secondary btn-sm" wire:click="openModuleModal({{ $module->id }})">
                                ✏️ Editar
                            </button>
                            <button
                                class="btn-secondary btn-sm btn-danger"
                                onclick="confirm('¿Estás seguro de eliminar el módulo «{{ $module->title }}» y todo su contenido?') || event.stopImmediatePropagation()"
                                wire:click="deleteModule({{ $module->id }})"
                            >
                                🗑️ Eliminar
                            </button>
                        </div>
                    </div>

                    <!-- Readings List -->
                    <div class="tree-readings-list">
                        @if($module->items->isEmpty())
                            <div style="font-size: 0.92rem; color: var(--gris); font-style: italic; padding: 6px 0;">
                                ℹ️ Este módulo aún no tiene lecturas. Haz clic en <strong>"+ Lectura"</strong> para agregar una.
                            </div>
                        @endif

                        @foreach($module->items as $reading)
                            <div class="tree-reading-card">
                                <div class="tree-reading-header">
                                    <div>
                                        <span class="tag" style="background: #e1effe; color: #1e40af; margin-right: 6px;">
                                            📖 Lectura {{ $reading->order }}
                                        </span>
                                        <strong style="font-size: 1.05rem; color: var(--azul-oscuro);">{{ $reading->title }}</strong>
                                        <span style="font-size: 0.82rem; color: var(--gris); margin-left: 6px;">({{ $reading->percentage }}% del progreso)</span>
                                    </div>

                                    <div style="display: flex; gap: 6px;">
                                        <button class="btn-secondary btn-sm" style="font-size: 0.8rem; padding: 4px 10px;" wire:click="openProblemModal(null, {{ $reading->id }})">
                                            ⚡ Agregar Problema
                                        </button>
                                        <button class="btn-secondary btn-sm" style="font-size: 0.8rem; padding: 4px 10px;" wire:click="openReadingModal({{ $reading->id }})">
                                            ✏️ Editar
                                        </button>
                                        <button
                                            class="btn-secondary btn-sm btn-danger"
                                            style="font-size: 0.8rem; padding: 4px 10px;"
                                            onclick="confirm('¿Eliminar la lectura «{{ $reading->title }}» y sus problemas asociados?') || event.stopImmediatePropagation()"
                                            wire:click="deleteReading({{ $reading->id }})"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </div>

                                <!-- Problems Nested List -->
                                @if($reading->problems->isNotEmpty())
                                    <div class="tree-problems-list">
                                        @foreach($reading->problems as $problem)
                                            <div class="tree-problem-card">
                                                <div>
                                                    <span class="tag" style="background: #eef2ff; color: #3730a3; font-size: 0.75rem; margin-right: 6px;">
                                                        🧮 Problema {{ $problem->order }}
                                                    </span>
                                                    <strong style="font-size: 0.95rem; color: #1f2d3d;">{{ $problem->title }}</strong>
                                                    <span style="font-size: 0.8rem; color: var(--gris); margin-left: 8px;">
                                                        • Componente: <code>{{ $problem->component }}</code>
                                                    </span>
                                                </div>

                                                <div style="display: flex; gap: 6px;">
                                                    <button class="btn-secondary btn-sm" style="font-size: 0.78rem; padding: 3px 8px;" wire:click="openProblemModal({{ $problem->id }})">
                                                        ✏️ Editar
                                                    </button>
                                                    <button
                                                        class="btn-secondary btn-sm btn-danger"
                                                        style="font-size: 0.78rem; padding: 3px 8px;"
                                                        onclick="confirm('¿Eliminar este problema?') || event.stopImmediatePropagation()"
                                                        wire:click="deleteProblem({{ $problem->id }})"
                                                    >
                                                        🗑️
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- MODAL: MODULE -->
    @if($showModuleModal)
        <div class="admin-modal-backdrop" wire:click.self="closeModuleModal">
            <div class="admin-modal">
                <div class="admin-modal-header">
                    <h3>{{ $moduleId ? '✏️ Editar Módulo' : '➕ Nuevo Módulo' }}</h3>
                    <button class="modal-close-btn" wire:click="closeModuleModal">&times;</button>
                </div>

                <div class="admin-modal-body">
                    <div class="form-group">
                        <label class="form-label">Título del Módulo</label>
                        <div class="form-hint">Escribe el nombre principal del módulo educativo (ej: <em>Concentración de esfuerzos</em>).</div>
                        <input class="admin-input" type="text" wire:model.live="moduleTitle" placeholder="Ej: Concentración de esfuerzos">
                        @error('moduleTitle') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Orden de Aparición</label>
                        <div class="form-hint">Posición numérica del módulo en el programa.</div>
                        <input class="admin-input" type="number" wire:model="moduleOrder" min="1">
                        @error('moduleOrder') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="admin-modal-footer">
                    <button class="btn-secondary" wire:click="closeModuleModal">Cancelar</button>
                    <button class="btn-primary" wire:click="saveModule">Guardar Módulo</button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL: READING -->
    @if($showReadingModal)
        <div class="admin-modal-backdrop" wire:click.self="closeReadingModal">
            <div class="admin-modal">
                <div class="admin-modal-header">
                    <h3>{{ $readingId ? '✏️ Editar Lectura' : '📖 Nueva Lectura' }}</h3>
                    <button class="modal-close-btn" wire:click="closeReadingModal">&times;</button>
                </div>

                <div class="admin-modal-body">
                    <div class="form-group">
                        <label class="form-label">Módulo Perteneciente</label>
                        <select class="admin-select" wire:model="moduleIdForReading">
                            <option value="">-- Selecciona el módulo --</option>
                            @foreach($modules as $m)
                                <option value="{{ $m->id }}">{{ $m->order }}. {{ $m->title }}</option>
                            @endforeach
                        </select>
                        @error('moduleIdForReading') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Título de la Lectura</label>
                        <input class="admin-input" type="text" wire:model.live="readingTitle" placeholder="Ej: Introducción a concentradores de esfuerzo">
                        @error('readingTitle') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Orden de Aparición</label>
                            <input class="admin-input" type="number" wire:model="readingOrder" min="1">
                            @error('readingOrder') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Peso en Progreso (%)</label>
                            <input class="admin-input" type="number" min="0" max="100" wire:model="readingPercentage">
                            @error('readingPercentage') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contenido Teórico de la Lectura</label>
                        <div class="form-hint">Construye la lección visualmente usando encabezados, negritas, listas, fórmulas matemáticas, imágenes y bloques educativos:</div>

                        <x-edu-wysiwyg wire:model="readingContent" id="reading-editor" />
                        @error('readingContent') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="admin-modal-footer">
                    <button class="btn-secondary" wire:click="closeReadingModal">Cancelar</button>
                    <button class="btn-primary" wire:click="saveReading">Guardar Lectura</button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL: PROBLEM -->
    @if($showProblemModal)
        <div class="admin-modal-backdrop" wire:click.self="closeProblemModal">
            <div class="admin-modal">
                <div class="admin-modal-header">
                    <h3>{{ $problemId ? '✏️ Editar Problema Interactivo' : '⚡ Nuevo Problema Interactivo' }}</h3>
                    <button class="modal-close-btn" wire:click="closeProblemModal">&times;</button>
                </div>

                <div class="admin-modal-body">
                    <div class="form-group">
                        <label class="form-label">Lectura Asociada</label>
                        <select class="admin-select" wire:model="readingIdForProblem">
                            <option value="">-- Selecciona la lectura --</option>
                            @foreach($modules as $m)
                                <optgroup label="Módulo {{ $m->order }}: {{ $m->title }}">
                                    @foreach($m->items as $r)
                                        <option value="{{ $r->id }}">📖 {{ $r->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('readingIdForProblem') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Título del Problema</label>
                        <input class="admin-input" type="text" wire:model.live="problemTitle" placeholder="Ej: Problema 1: Placa con muescas">
                        @error('problemTitle') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Componente Interactivo Paso a Paso</label>
                        <div class="form-hint">Selecciona el problema interactivo que responderá el estudiante:</div>
                        <select class="admin-select" wire:model.live="problemComponent">
                            @foreach($availableComponents as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('problemComponent') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>

                    @if($problemComponent === 'custom')
                        <div class="form-group">
                            <label class="form-label">Nombre de Componente Personalizado</label>
                            <input class="admin-input" type="text" wire:model="customComponent" placeholder="ej: problemas.problema3">
                            @error('customComponent') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Orden de Aparición</label>
                            <input class="admin-input" type="number" wire:model="problemOrder" min="1">
                            @error('problemOrder') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Peso en Progreso (%)</label>
                            <input class="admin-input" type="number" min="0" max="100" wire:model="problemPercentage">
                            @error('problemPercentage') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: var(--azul-oscuro); cursor: pointer;">
                            <input type="checkbox" wire:model="problemIsActive" style="width: 18px; height: 18px;">
                            <span>Problema Activo (visible para alumnos)</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Enunciado / Descripción del Problema</label>
                        <x-edu-wysiwyg wire:model="problemContent" id="problem-editor" />
                        @error('problemContent') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="admin-modal-footer">
                    <button class="btn-secondary" wire:click="closeProblemModal">Cancelar</button>
                    <button class="btn-primary" wire:click="saveProblem">Guardar Problema</button>
                </div>
            </div>
        </div>
    @endif
</div>