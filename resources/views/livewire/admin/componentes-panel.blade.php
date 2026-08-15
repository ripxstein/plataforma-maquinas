<div>
    <!-- ======================================================== -->
    <!-- VIEW MODE 1: COMPONENT LISTING                          -->
    <!-- ======================================================== -->
    @if($viewMode === 'list')
        <!-- Hero Section -->
        <section class="hero" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2>🧩 Constructor de Componentes y Problemas</h2>
                <p>Crea, edita, reordena y prueba visualmente problemas interactivos paso a paso para tus alumnos sin escribir código.</p>
            </div>
            <div>
                <button class="btn-primary" wire:click="createComponent" style="display: flex; align-items: center; gap: 8px; font-size: 1rem; padding: 12px 24px;">
                    <span style="font-size: 1.2rem;">➕</span> Crear Componente
                </button>
            </div>
        </section>

        <!-- Notification Message -->
        @if (session()->has('message'))
            <div class="footer-note" style="margin-bottom:22px; background:#e7f7ea; border-color:#7bc28a; color:#1e5aa8;">
                ✨ {{ session('message') }}
            </div>
        @endif

        <!-- Filters & Search Section -->
        <section class="content-section" style="margin-bottom: 20px;">
            <div class="section-body" style="padding: 18px 24px;">
                <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; flex: 1; max-width: 700px;">
                        <input 
                            type="text" 
                            class="admin-input" 
                            style="margin: 0; flex: 2; min-width: 200px;" 
                            placeholder="🔍 Buscar por nombre o slug..." 
                            wire:model.live.debounce.300ms="search"
                        >
                        <select class="admin-select" style="margin: 0; flex: 1.5; min-width: 180px;" wire:model.live="moduleFilter">
                            <option value="">-- Todos los Módulos --</option>
                            @foreach($modules as $m)
                                <option value="{{ $m->id }}">Módulo {{ $m->order }}: {{ $m->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="font-size: 0.9rem; color: var(--gris); font-weight: 600;">
                        Mostrando {{ $problemsList->count() }} componente(s)
                    </div>
                </div>
            </div>
        </section>

        <!-- Components List Table / Cards -->
        <section class="content-section">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Listado de Problemas Interactivos</h3>
                <span class="badge">{{ $problemsList->count() }} Registrados</span>
            </div>

            <div class="section-body">
                @if($problemsList->isEmpty())
                    <div class="footer-note" style="text-align: center; padding: 40px;">
                        <p style="font-size: 1.15rem; color: var(--gris); margin-bottom: 16px;">
                            No se encontraron componentes interactivos con los filtros seleccionados.
                        </p>
                        <button class="btn-primary" wire:click="createComponent">
                            ➕ Crear el Primer Componente
                        </button>
                    </div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Problema / Componente</th>
                                    <th>Lectura y Módulo</th>
                                    <th style="text-align: center;">Pasos</th>
                                    <th>Tipo</th>
                                    <th style="text-align: center;">Estado</th>
                                    <th>Creación</th>
                                    <th style="text-align: right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($problemsList as $prob)
                                    @php
                                        $stepsCount = $prob->steps->count();
                                        $isDynamic = empty($prob->component) || $prob->component === 'problemas.problema-dinamico';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: var(--azul-oscuro); font-size: 1.05rem;">
                                                {{ $prob->title }}
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--gris); margin-top: 2px;">
                                                slug: <code>{{ $prob->slug }}</code>
                                            </div>
                                        </td>
                                        <td>
                                            @if($prob->moduleItem)
                                                <div style="font-weight: 600; color: #1e40af; font-size: 0.92rem;">
                                                    📖 {{ $prob->moduleItem->title }}
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--gris);">
                                                    Módulo: {{ $prob->moduleItem->module->title ?? 'Sin módulo' }}
                                                </div>
                                            @else
                                                <span style="color: var(--gris); font-style: italic;">Sin lectura asignada</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            @if($isDynamic)
                                                <span class="tag" style="margin: 0; background: #e0f2fe; color: #0369a1; font-weight: 700;">
                                                    {{ $stepsCount }} {{ $stepsCount === 1 ? 'paso' : 'pasos' }}
                                                </span>
                                            @else
                                                <span class="tag" style="margin: 0; background: #f3f4f6; color: #4b5563;">
                                                    Código
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isDynamic)
                                                <span style="font-size: 0.84rem; color: #15803d; font-weight: 600;">
                                                    ✨ Constructor Visual
                                                </span>
                                            @else
                                                <span style="font-size: 0.84rem; color: #6b7280;">
                                                    ⚙️ <code>{{ $prob->component }}</code>
                                                </span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            @if($prob->is_active ?? true)
                                                <span class="tag" style="background: #dcfce7; color: #166534; margin: 0;">
                                                    ● Activo
                                                </span>
                                            @else
                                                <span class="tag" style="background: #fee2e2; color: #991b1b; margin: 0;">
                                                    ○ Inactivo
                                                </span>
                                            @endif
                                        </td>
                                        <td style="font-size: 0.86rem; color: var(--gris);">
                                            {{ $prob->created_at ? $prob->created_at->format('d/m/Y') : '-' }}
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: inline-flex; gap: 6px;">
                                                <button 
                                                    class="btn-secondary btn-sm" 
                                                    title="Editar componente y pasos"
                                                    wire:click="editComponent({{ $prob->id }})"
                                                    style="padding: 6px 12px; font-weight: 600;"
                                                >
                                                    ✏️ Editar
                                                </button>
                                                <button 
                                                    class="btn-secondary btn-sm" 
                                                    title="Duplicar problema y todos sus pasos"
                                                    wire:click="duplicateComponent({{ $prob->id }})"
                                                    style="padding: 6px 12px;"
                                                >
                                                    📋 Duplicar
                                                </button>
                                                <button 
                                                    class="btn-secondary btn-sm btn-danger" 
                                                    title="Eliminar este problema"
                                                    onclick="confirm('¿Estás seguro de eliminar el problema «{{ $prob->title }}»?') || event.stopImmediatePropagation()"
                                                    wire:click="deleteComponent({{ $prob->id }})"
                                                    style="padding: 6px 10px;"
                                                >
                                                    🗑️
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

    <!-- ======================================================== -->
    <!-- VIEW MODE 2: VISUAL PROBLEM BUILDER & LIVE PREVIEW      -->
    <!-- ======================================================== -->
    @elseif($viewMode === 'builder')
        <!-- Builder Top Bar -->
        <section class="hero" style="padding: 20px 26px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <button class="btn-secondary" wire:click="backToList" style="display: flex; align-items: center; gap: 6px; font-size: 0.95rem;">
                    ← Volver al Listado
                </button>
                <div>
                    <h3 style="margin: 0; color: var(--azul-oscuro); font-size: 1.45rem; font-weight: 700;">
                        {{ $problemId ? '✏️ Constructor: ' . $title : '✨ Nuevo Componente Interactivo' }}
                    </h3>
                    <p style="margin: 2px 0 0; font-size: 0.88rem; color: var(--gris);">
                        Diseña las preguntas, pasos, respuestas numéricas/opciones y visualiza los cambios al instante.
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 10px; align-items: center;">
                <button class="btn-primary" wire:click="saveComponent" style="display: flex; align-items: center; gap: 6px; font-size: 1rem; padding: 10px 22px;">
                    💾 Guardar Problema
                </button>
            </div>
        </section>

        <!-- Notification Message inside Builder -->
        @if (session()->has('message'))
            <div class="footer-note" style="margin-bottom:20px; background:#e7f7ea; border-color:#7bc28a; color:#1e5aa8;">
                ✨ {{ session('message') }}
            </div>
        @endif

        @if($errors->any())
            <div class="footer-note" style="margin-bottom:20px; background:#fff1f1; border-color:#e18b8b; color:#991b1b;">
                ⚠️ Por favor corrige los siguientes errores:
                <ul style="margin: 6px 0 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Split Screen Layout: Left Configuration / Right Student Simulator Preview -->
        <div style="display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 24px; align-items: start;">
            
            <!-- LEFT COLUMN: SETTINGS & STEP BUILDER -->
            <div style="display: flex; flex-direction: column; gap: 22px;">
                
                <!-- Card 1: General Info -->
                <div class="card" style="background: #ffffff; padding: 22px; border-radius: 18px; box-shadow: 0 4px 14px rgba(30,90,168,0.06);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--borde); padding-bottom: 12px;">
                        <h4 style="margin: 0; color: var(--azul-oscuro); font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                            ⚙️ Datos Generales del Problema
                        </h4>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" wire:model.live="isActive" style="width: 18px; height: 18px;">
                            <span style="color: {{ $isActive ? '#166534' : '#991b1b' }};">{{ $isActive ? 'Problema Activo' : 'Inactivo' }}</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Título del Problema</label>
                        <input class="admin-input" type="text" wire:model.live="title" placeholder="Ej: Problema 1: Placa con muescas">
                        @error('title') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Lectura Asociada</label>
                            <select class="admin-select" wire:model.live="moduleItemId">
                                <option value="">-- Selecciona la lectura --</option>
                                @foreach($modules as $m)
                                    <optgroup label="Módulo {{ $m->order }}: {{ $m->title }}">
                                        @foreach($m->items as $r)
                                            <option value="{{ $r->id }}">📖 {{ $r->title }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('moduleItemId') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Identificador (Slug)</label>
                            <input class="admin-input" type="text" wire:model="slug" placeholder="problema-1-placa-muescas">
                            @error('slug') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Orden en la Lectura</label>
                            <input class="admin-input" type="number" min="1" wire:model="order">
                            @error('order') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Peso en Progreso (%)</label>
                            <input class="admin-input" type="number" min="0" max="100" wire:model="percentage">
                            @error('percentage') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Enunciado / Descripción Teórica del Problema</label>
                        <div class="form-hint">Escribe el planteamiento general, datos numéricos o figuras que verá el estudiante antes de los pasos:</div>
                        <x-edu-wysiwyg wire:model="content" id="builder-problem-content" />
                        @error('content') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Card 2: Visual Step Builder -->
                <div class="card" style="background: #ffffff; padding: 22px; border-radius: 18px; box-shadow: 0 4px 14px rgba(30,90,168,0.06);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--borde); padding-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h4 style="margin: 0; color: var(--azul-oscuro); font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                                🪜 Pasos Interactivos del Problema
                            </h4>
                            <div style="font-size: 0.85rem; color: var(--gris); margin-top: 2px;">
                                Configura el orden progresivo y las validaciones de cada etapa.
                            </div>
                        </div>

                        <button 
                            type="button" 
                            class="btn-primary btn-sm" 
                            wire:click="openAddStepModal"
                            style="padding: 8px 16px; font-size: 0.92rem; display: flex; align-items: center; gap: 6px;"
                        >
                            ➕ Agregar Paso
                        </button>
                    </div>

                    @if(empty($stepsData))
                        <div class="footer-note" style="text-align: center; padding: 28px;">
                            <p style="margin: 0 0 12px; color: var(--gris);">
                                Este problema aún no tiene ningún paso interactivo.
                            </p>
                            <button type="button" class="btn-primary btn-sm" wire:click="openAddStepModal">
                                ➕ Agregar el Primer Paso
                            </button>
                        </div>
                    @else
                        <div style="display: grid; gap: 12px;">
                            @foreach($stepsData as $index => $step)
                                @php
                                    $stepTypeBadge = match($step['answer_type'] ?? 'numeric') {
                                        'numeric' => ['label' => '🔢 Numérico', 'color' => '#1e40af', 'bg' => '#dbeafe'],
                                        'multiple_choice' => ['label' => '🔘 Selección Múltiple', 'color' => '#6b21a8', 'bg' => '#f3e8ff'],
                                        'true_false' => ['label' => '⚖️ Verdadero / Falso', 'color' => '#854d0e', 'bg' => '#fef9c3'],
                                        'text' => ['label' => '📝 Texto', 'color' => '#374151', 'bg' => '#f3f4f6'],
                                        default => ['label' => 'Respuesta', 'color' => '#374151', 'bg' => '#f3f4f6'],
                                    };
                                @endphp
                                <div style="background: #fbfdff; border: 1px solid #c7d9ee; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; transition: all 0.2s ease;">
                                    
                                    <!-- Left Reordering and Step Info -->
                                    <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                                        <!-- Up/Down arrows -->
                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                            <button 
                                                type="button" 
                                                class="btn-secondary btn-sm" 
                                                style="padding: 2px 6px; font-size: 0.75rem;" 
                                                title="Mover arriba"
                                                @disabled($index === 0)
                                                wire:click="moveStepUp({{ $index }})"
                                            >
                                                ▲
                                            </button>
                                            <button 
                                                type="button" 
                                                class="btn-secondary btn-sm" 
                                                style="padding: 2px 6px; font-size: 0.75rem;" 
                                                title="Mover abajo"
                                                @disabled($index === count($stepsData) - 1)
                                                wire:click="moveStepDown({{ $index }})"
                                            >
                                                ▼
                                            </button>
                                        </div>

                                        <!-- Step Number Tag -->
                                        <span class="tag" style="margin: 0; background: #e0f2fe; color: #0369a1; font-weight: 700; font-size: 0.85rem;">
                                            Paso {{ $index + 1 }}
                                        </span>

                                        <!-- Step Title & Type -->
                                        <div style="flex: 1;">
                                            <div style="font-weight: 700; color: var(--azul-oscuro); font-size: 0.98rem;">
                                                {{ $step['title'] }}
                                            </div>
                                            <div style="display: flex; gap: 8px; align-items: center; margin-top: 4px; flex-wrap: wrap;">
                                                <span style="font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: 6px; background: {{ $stepTypeBadge['bg'] }}; color: {{ $stepTypeBadge['color'] }};">
                                                    {{ $stepTypeBadge['label'] }}
                                                </span>

                                                @if($step['answer_type'] === 'numeric')
                                                    <span style="font-size: 0.78rem; color: var(--gris);">
                                                        Esperado: <strong>{{ $step['correct_answer'] }}</strong> {{ $step['unit'] ?? '' }} (±{{ $step['tolerance'] ?? '0.01' }})
                                                    </span>
                                                @elseif($step['answer_type'] === 'multiple_choice')
                                                    <span style="font-size: 0.78rem; color: var(--gris);">
                                                        {{ count($step['options'] ?? []) }} opciones configuradas
                                                    </span>
                                                @endif

                                                @if(!empty($step['image_url']))
                                                    <span style="font-size: 0.75rem; color: #166534; background: #dcfce7; padding: 2px 6px; border-radius: 6px;">
                                                        🖼️ Imagen
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Step Actions -->
                                    <div style="display: flex; gap: 6px; align-items: center;">
                                        <button 
                                            type="button" 
                                            class="btn-secondary btn-sm" 
                                            style="padding: 6px 12px; font-weight: 600;" 
                                            wire:click="openEditStepModal({{ $index }})"
                                        >
                                            ✏️ Editar
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn-secondary btn-sm" 
                                            style="padding: 6px 10px;" 
                                            title="Duplicar este paso"
                                            wire:click="duplicateStep({{ $index }})"
                                        >
                                            📋
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn-secondary btn-sm btn-danger" 
                                            style="padding: 6px 10px;" 
                                            title="Eliminar paso"
                                            onclick="confirm('¿Eliminar el paso {{ $index + 1 }}?') || event.stopImmediatePropagation()"
                                            wire:click="deleteStep({{ $index }})"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- RIGHT COLUMN: REAL-TIME STUDENT PREVIEW SIMULATOR -->
            <div style="position: sticky; top: 20px;">
                <div class="card" style="background: #ffffff; padding: 20px; border-radius: 18px; border: 2px solid #bcdcff; box-shadow: 0 8px 24px rgba(30,90,168,0.1);">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--borde); padding-bottom: 10px;">
                        <div>
                            <span class="tag" style="background: #eef2ff; color: #3730a3; margin: 0; font-size: 0.8rem;">
                                👁️ Vista Previa del Alumno
                            </span>
                            <h4 style="margin: 4px 0 0; color: var(--azul-oscuro); font-size: 1.15rem;">
                                {{ $title ?: 'Título del Problema' }}
                            </h4>
                        </div>

                        <button 
                            type="button" 
                            class="btn-secondary btn-sm" 
                            wire:click="resetPreviewSimulator" 
                            title="Reiniciar prueba interactiva"
                            style="font-size: 0.8rem; padding: 4px 10px;"
                        >
                            🔄 Reiniciar Prueba
                        </button>
                    </div>

                    <!-- Problem Statement in Preview -->
                    @if($content)
                        <div class="problema-enunciado" style="margin-bottom: 16px; font-size: 0.95rem; line-height: 1.6; color: var(--texto); background: #fbfdff; padding: 12px 14px; border-radius: 12px; border: 1px solid #e2effd;">
                            {!! $content !!}
                        </div>
                    @endif

                    <!-- Interactive Steps Simulator Container -->
                    <div class="steps" style="margin: 0; background: #e7f7ea; border: 1px solid #7bc28a; border-radius: 16px; padding: 16px;">
                        <h5 style="margin: 0 0 10px; color: #25623b; font-size: 1.05rem;">
                            Resuelve paso a paso
                        </h5>

                        @if(empty($stepsData))
                            <p style="font-size: 0.9rem; color: var(--gris); font-style: italic; margin: 0;">
                                Agrega pasos en el panel izquierdo para visualizarlos aquí.
                            </p>
                        @endif

                        @foreach($stepsData as $index => $step)
                            @php
                                $stepNum = $index + 1;
                                $isUnlocked = $previewCurrentStep >= $stepNum;
                                $isStepDone = isset($previewMessages[$index]) && $previewMessages[$index]['ok'];
                                $showImg = (!empty($step['image_url']) && (($step['image_trigger'] ?? 'always') === 'always' || !empty($previewShowImages[$index])));
                            @endphp

                            @if($isUnlocked)
                                <div class="pstep" style="margin-top: {{ $index > 0 ? '14px' : '4px' }}; background: {{ $isStepDone ? '#fafffa' : '#ffffff' }}; border: 1px solid {{ $isStepDone ? '#b2e2bd' : '#c7d9ee' }}; border-radius: 12px; padding: 14px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                        <div class="tag" style="background: {{ $isStepDone ? '#e7f7ea' : '#dcecff' }}; color: {{ $isStepDone ? '#1e7e34' : '#103b73' }}; margin: 0; font-size: 0.78rem;">
                                            {{ $isStepDone ? '✔ ' : '' }}Paso {{ $stepNum }}: {{ $step['title'] }}
                                        </div>
                                        @if($isStepDone)
                                            <span style="font-size: 0.75rem; color: #25623b; font-weight: 700;">Completado</span>
                                        @endif
                                    </div>

                                    @if(!empty($step['instruction']))
                                        <div style="font-size: 0.92rem; line-height: 1.5; color: var(--texto); margin: 8px 0;">
                                            {!! $step['instruction'] !!}
                                        </div>
                                    @endif

                                    <!-- Step Image -->
                                    @if($showImg)
                                        <div class="edu-figure {{ $step['image_align'] ?? 'align-center' }}" style="margin: 10px 0;">
                                            <img 
                                                src="{{ $step['image_url'] }}" 
                                                alt="{{ $step['image_alt'] ?? '' }}"
                                                style="max-width: {{ $step['image_max_width'] ?? '100%' }}; height: auto; border: 1px solid var(--borde); border-radius: 10px; padding: 3px; background: #fff;"
                                            >
                                            @if(!empty($step['image_caption']))
                                                <figcaption style="font-size: 0.8rem; color: var(--gris); margin-top: 4px;">{{ $step['image_caption'] }}</figcaption>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Simulator Input Form -->
                                    <div style="margin-top: 10px;">
                                        @if(($step['answer_type'] ?? 'numeric') === 'numeric')
                                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                <label style="font-size: 0.88rem; font-weight: 600; color: var(--azul-oscuro);">
                                                    Tu respuesta:
                                                </label>
                                                <input 
                                                    type="number" 
                                                    step="any"
                                                    wire:model="previewAnswers.{{ $index }}"
                                                    placeholder="0.00"
                                                    style="padding: 6px 10px; border: 1px solid var(--borde); border-radius: 8px; font-size: 0.92rem; width: 120px;"
                                                    @disabled($isStepDone)
                                                    wire:keydown.enter="testPreviewStep({{ $index }})"
                                                >
                                                @if(!empty($step['unit']))
                                                    <span class="badge" style="font-size: 0.78rem; padding: 4px 8px;">{{ $step['unit'] }}</span>
                                                @endif

                                                @if(!$isStepDone)
                                                    <button 
                                                        type="button" 
                                                        class="badge" 
                                                        wire:click="testPreviewStep({{ $index }})"
                                                        style="cursor: pointer; background: var(--azul-secundario); color: white; border: none; padding: 6px 14px; font-size: 0.82rem;"
                                                    >
                                                        Revisar
                                                    </button>
                                                @endif
                                            </div>

                                        @elseif($step['answer_type'] === 'multiple_choice')
                                            <div style="display: grid; gap: 6px; margin-top: 6px;">
                                                @foreach($step['options'] ?? [] as $optIdx => $opt)
                                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.88rem; padding: 6px 10px; background: #f8fbff; border: 1px solid var(--borde); border-radius: 8px; cursor: pointer;">
                                                        <input 
                                                            type="radio" 
                                                            name="preview_opt_{{ $index }}" 
                                                            value="{{ $optIdx }}"
                                                            wire:model="previewAnswers.{{ $index }}"
                                                            @disabled($isStepDone)
                                                        >
                                                        <span>{{ $opt['option_text'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>

                                            @if(!$isStepDone)
                                                <div style="margin-top: 8px;">
                                                    <button 
                                                        type="button" 
                                                        class="badge" 
                                                        wire:click="testPreviewStep({{ $index }})"
                                                        style="cursor: pointer; background: var(--azul-secundario); color: white; border: none; padding: 6px 14px; font-size: 0.82rem;"
                                                    >
                                                        Revisar
                                                    </button>
                                                </div>
                                            @endif

                                        @elseif($step['answer_type'] === 'true_false')
                                            <div style="display: flex; gap: 10px; margin-top: 6px;">
                                                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; padding: 6px 12px; background: #f8fbff; border: 1px solid var(--borde); border-radius: 8px; cursor: pointer;">
                                                    <input 
                                                        type="radio" 
                                                        name="preview_tf_{{ $index }}" 
                                                        value="1"
                                                        wire:model="previewAnswers.{{ $index }}"
                                                        @disabled($isStepDone)
                                                    >
                                                    <span>Verdadero</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; padding: 6px 12px; background: #f8fbff; border: 1px solid var(--borde); border-radius: 8px; cursor: pointer;">
                                                    <input 
                                                        type="radio" 
                                                        name="preview_tf_{{ $index }}" 
                                                        value="0"
                                                        wire:model="previewAnswers.{{ $index }}"
                                                        @disabled($isStepDone)
                                                    >
                                                    <span>Falso</span>
                                                </label>
                                            </div>

                                            @if(!$isStepDone)
                                                <div style="margin-top: 8px;">
                                                    <button 
                                                        type="button" 
                                                        class="badge" 
                                                        wire:click="testPreviewStep({{ $index }})"
                                                        style="cursor: pointer; background: var(--azul-secundario); color: white; border: none; padding: 6px 14px; font-size: 0.82rem;"
                                                    >
                                                        Revisar
                                                    </button>
                                                </div>
                                            @endif

                                        @else {{-- Text --}}
                                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                <input 
                                                    type="text" 
                                                    wire:model="previewAnswers.{{ $index }}"
                                                    placeholder="Escribe tu respuesta..."
                                                    style="padding: 6px 10px; border: 1px solid var(--borde); border-radius: 8px; font-size: 0.88rem; min-width: 180px;"
                                                    @disabled($isStepDone)
                                                    wire:keydown.enter="testPreviewStep({{ $index }})"
                                                >

                                                @if(!$isStepDone)
                                                    <button 
                                                        type="button" 
                                                        class="badge" 
                                                        wire:click="testPreviewStep({{ $index }})"
                                                        style="cursor: pointer; background: var(--azul-secundario); color: white; border: none; padding: 6px 14px; font-size: 0.82rem;"
                                                    >
                                                        Revisar
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Feedback Message in Simulator -->
                                    @if(isset($previewMessages[$index]))
                                        <div style="margin-top: 10px;">
                                            @if($previewMessages[$index]['ok'])
                                                <div style="padding: 8px 12px; background: #e8fff0; border: 1px solid #67c587; border-radius: 8px; color: #166534; font-size: 0.88rem;">
                                                    ✔ {{ $previewMessages[$index]['text'] }}
                                                </div>
                                            @else
                                                <div style="padding: 8px 12px; background: #fff1f1; border: 1px solid #e18b8b; border-radius: 8px; color: #991b1b; font-size: 0.88rem;">
                                                    <div>✘ {{ $previewMessages[$index]['text'] }}</div>
                                                    @if(!empty($previewMessages[$index]['reminder']))
                                                        <div style="margin-top: 6px; padding-top: 6px; border-top: 1px dashed #fca5a5; font-size: 0.82rem; color: #7f1d1d;">
                                                            💡 <strong>Pista:</strong> {{ $previewMessages[$index]['reminder'] }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach

                        @if($previewCompleted)
                            <div style="margin-top: 14px; background: #ffffff; border: 1px solid #7bc28a; border-radius: 12px; padding: 14px; text-align: center;">
                                <div style="font-size: 1.4rem;">🎉</div>
                                <strong style="color: #1e5aa8; font-size: 0.95rem;">¡Problema Resuelto Correctamente!</strong>
                                <p style="margin: 4px 0 0; font-size: 0.82rem; color: #25623b;">
                                    El simulador completó todos los pasos con éxito.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ======================================================== -->
    <!-- MODAL: STEP EDITOR (CREAR / EDITAR PASO DETALLADO)       -->
    <!-- ======================================================== -->
    @if($showStepModal)
        <div class="admin-modal-backdrop" wire:click.self="closeStepModal">
            <div class="admin-modal" style="max-width: 780px;">
                <div class="admin-modal-header">
                    <h3>{{ $editingStepIndex !== null ? '✏️ Configurar Paso ' . ($editingStepIndex + 1) : '➕ Agregar Nuevo Paso' }}</h3>
                    <button class="modal-close-btn" wire:click="closeStepModal">&times;</button>
                </div>

                <div class="admin-modal-body">
                    <!-- Step Title -->
                    <div class="form-group">
                        <label class="form-label">Título del Paso</label>
                        <div class="form-hint">Escribe una descripción corta del procedimiento (ej: <em>Calcula la sección mínima</em> o <em>Obtén Kt de la gráfica</em>).</div>
                        <input class="admin-input" type="text" wire:model="stepTitle" placeholder="Ej: Calcula el área neta mínima">
                        @error('stepTitle') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Step Instruction / Rich Content -->
                    <div class="form-group">
                        <label class="form-label">Instrucción / Enunciado del Paso</label>
                        <div class="form-hint">Puedes incluir fórmulas matemáticas (ej: <code>\[d = w - 2r\]</code>) o texto explicativo:</div>
                        <x-edu-wysiwyg wire:model="stepInstruction" id="step-instruction-wysiwyg" />
                        @error('stepInstruction') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Step Answer Type -->
                    <div class="form-group">
                        <label class="form-label">Tipo de Respuesta</label>
                        <select class="admin-select" wire:model.live="stepAnswerType">
                            <option value="numeric">🔢 Respuesta Numérica (con tolerancia y unidad)</option>
                            <option value="multiple_choice">🔘 Selección Múltiple (opciones con única respuesta correcta)</option>
                            <option value="true_false">⚖️ Verdadero / Falso</option>
                            <option value="text">📝 Texto Libre (comparación exacta de texto/palabra)</option>
                        </select>
                    </div>

                    <!-- Conditional Config by Answer Type -->
                    @if($stepAnswerType === 'numeric')
                        <div style="background: #f8fbff; border: 1px solid var(--borde); border-radius: 14px; padding: 16px; margin-bottom: 16px;">
                            <h5 style="margin: 0 0 12px; color: var(--azul-oscuro); font-size: 1rem;">Configuración de Respuesta Numérica</h5>
                            
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Respuesta Correcta Esperada</label>
                                    <input class="admin-input" type="number" step="any" wire:model="stepCorrectAnswer" placeholder="Ej: 156.25">
                                    @error('stepCorrectAnswer') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Unidad de Medida (Opcional)</label>
                                    <input class="admin-input" type="text" wire:model="stepUnit" placeholder="Ej: MPa, psi, in², mm">
                                </div>
                            </div>

                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Tolerancia Aceptada</label>
                                    <input class="admin-input" type="number" step="any" wire:model="stepTolerance" placeholder="0.01">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Tipo de Tolerancia</label>
                                    <select class="admin-select" wire:model="stepToleranceType">
                                        <option value="absolute">Absoluta (ej: ±0.01 unidades)</option>
                                        <option value="percentage">Porcentual (ej: ±2%)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    @elseif($stepAnswerType === 'multiple_choice')
                        <div style="background: #f8fbff; border: 1px solid var(--borde); border-radius: 14px; padding: 16px; margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <h5 style="margin: 0; color: var(--azul-oscuro); font-size: 1rem;">Opciones de Selección Múltiple</h5>
                                <button type="button" class="btn-secondary btn-sm" wire:click="addOptionToActiveStep">
                                    ➕ Agregar Opción
                                </button>
                            </div>

                            <div class="form-hint" style="margin-bottom: 10px;">Marca el botón circular en la opción que sea la correcta:</div>

                            <div style="display: grid; gap: 10px;">
                                @foreach($stepOptions as $optIdx => $opt)
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <input 
                                            type="radio" 
                                            name="active_modal_correct_opt" 
                                            checked="{{ !empty($opt['is_correct']) }}"
                                            wire:click="setOptionAsCorrect({{ $optIdx }})"
                                            title="Marcar como respuesta correcta"
                                            style="width: 20px; height: 20px; cursor: pointer;"
                                        >
                                        <input 
                                            type="text" 
                                            class="admin-input" 
                                            style="margin: 0; flex: 1;" 
                                            wire:model="stepOptions.{{ $optIdx }}.option_text" 
                                            placeholder="Texto de la opción {{ $optIdx + 1 }}"
                                        >
                                        <button 
                                            type="button" 
                                            class="btn-secondary btn-sm btn-danger" 
                                            style="padding: 8px 10px;"
                                            @disabled(count($stepOptions) <= 2)
                                            wire:click="removeOptionFromActiveStep({{ $optIdx }})"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            @error('stepOptions') <span style="color:#b42318; font-size:0.85rem; display:block; margin-top:8px;">{{ $message }}</span> @enderror
                        </div>

                    @elseif($stepAnswerType === 'true_false')
                        <div style="background: #f8fbff; border: 1px solid var(--borde); border-radius: 14px; padding: 16px; margin-bottom: 16px;">
                            <h5 style="margin: 0 0 10px; color: var(--azul-oscuro); font-size: 1rem;">Opción Correcta</h5>
                            <div style="display: flex; gap: 16px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 1rem;">
                                    <input type="radio" value="1" wire:model="stepCorrectAnswer">
                                    <span>Verdadero</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 1rem;">
                                    <input type="radio" value="0" wire:model="stepCorrectAnswer">
                                    <span>Falso</span>
                                </label>
                            </div>
                        </div>

                    @else {{-- Text --}}
                        <div style="background: #f8fbff; border: 1px solid var(--borde); border-radius: 14px; padding: 16px; margin-bottom: 16px;">
                            <label class="form-label">Texto o Palabra Correcta Esperada</label>
                            <input class="admin-input" type="text" wire:model="stepCorrectAnswer" placeholder="Ej: concentración de esfuerzos">
                            @error('stepCorrectAnswer') <span style="color:#b42318; font-size:0.85rem;">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Feedback & Reminders Section -->
                    <div style="background: #fbfdff; border: 1px solid var(--borde); border-radius: 14px; padding: 16px; margin-bottom: 16px;">
                        <h5 style="margin: 0 0 12px; color: var(--azul-oscuro); font-size: 1rem;">💬 Mensajes y Retroalimentación</h5>

                        <div class="form-group">
                            <label class="form-label">Mensaje al Responder Correctamente</label>
                            <input class="admin-input" type="text" wire:model="stepSuccessMessage" placeholder="Ej: ¡Correcto! Ahora calcula el área mínima.">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Mensaje al Responder Incorrectamente</label>
                            <input class="admin-input" type="text" wire:model="stepErrorMessage" placeholder="Ej: Revisa nuevamente la operación.">
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Recordatorio / Pista de Ayuda (Opcional)</label>
                            <div class="form-hint">Se mostrará cuando el alumno se equivoque para orientarlo:</div>
                            <input class="admin-input" type="text" wire:model="stepReminderMessage" placeholder="Ej: Recuerda que d = w - 2r. Sustituye w = 1.5 in y r = 0.25 in.">
                        </div>
                    </div>

                    <!-- Step Image / Graphic Support Section -->
                    <div style="background: #fbfdff; border: 1px solid var(--borde); border-radius: 14px; padding: 16px;">
                        <h5 style="margin: 0 0 12px; color: var(--azul-oscuro); font-size: 1rem;">🖼️ Ilustración / Gráfica del Paso (Opcional)</h5>
                        
                        <div class="form-group">
                            <label class="form-label">URL de la Imagen</label>
                            <div class="form-hint">Puedes ingresar una ruta local (ej: <code>/images/graficas/figura-a-15-4.png</code>) o una URL:</div>
                            <input class="admin-input" type="text" wire:model="stepImageUrl" placeholder="/images/graficas/... o https://...">
                        </div>

                        @if($stepImageUrl)
                            <div style="text-align: center; margin-bottom: 14px; background: #fff; padding: 10px; border-radius: 10px; border: 1px solid var(--borde);">
                                <img src="{{ $stepImageUrl }}" style="max-height: 120px; max-width: 100%; border-radius: 8px;">
                            </div>
                        @endif

                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">¿Cuándo se muestra la imagen?</label>
                                <select class="admin-select" wire:model="stepImageTrigger">
                                    <option value="always">Mostrar Siempre</option>
                                    <option value="on_error">Mostrar si el alumno se equivoca (Ayuda visual / Gráfica)</option>
                                    <option value="on_success">Mostrar después de responder correctamente (Conclusión)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Alineación y Ancho</label>
                                <div style="display: flex; gap: 8px;">
                                    <select class="admin-select" style="flex: 1;" wire:model="stepImageAlign">
                                        <option value="align-center">Centrado</option>
                                        <option value="align-left">Izquierda</option>
                                        <option value="align-right">Derecha</option>
                                    </select>
                                    <select class="admin-select" style="flex: 1;" wire:model="stepImageMaxWidth">
                                        <option value="100%">100%</option>
                                        <option value="75%">75%</option>
                                        <option value="50%">50%</option>
                                        <option value="35%">35%</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Texto Alternativo (Alt)</label>
                                <input class="admin-input" type="text" wire:model="stepImageAlt" placeholder="Ej: Gráfica Kt para barra con muescas">
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Pie de Figura (Caption)</label>
                                <input class="admin-input" type="text" wire:model="stepImageCaption" placeholder="Ej: Figura A-15-4. Obtención de Kt">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="admin-modal-footer">
                    <button type="button" class="btn-secondary" wire:click="closeStepModal">Cancelar</button>
                    <button type="button" class="btn-primary" wire:click="saveStepModal">
                        {{ $editingStepIndex !== null ? 'Actualizar Paso' : 'Agregar Paso' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof renderAllMath === 'function') {
                renderAllMath();
            }
        });
        document.addEventListener('livewire:navigated', () => {
            if (typeof renderAllMath === 'function') {
                renderAllMath();
            }
        });
    </script>
</div>
