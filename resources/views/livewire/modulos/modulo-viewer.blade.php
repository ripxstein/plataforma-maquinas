<div>
    <section class="hero module-overview" aria-labelledby="module-overview-title">
        <div class="module-overview-heading">
            <h2 id="module-overview-title">{{ $module->title }}</h2>
            <p class="module-overview-percent"><strong>{{ $totalProgress }}%</strong><span>de avance</span></p>
        </div>
        <div class="progress-bar" role="progressbar" aria-label="Avance del módulo" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $totalProgress }}">
            <div class="progress-fill" style="width: {{ $totalProgress }}%;"></div>
        </div>
        <div class="module-overview-meta">
            <span><strong>{{ $activitySummary['readings_completed'] }}/{{ $activitySummary['readings_total'] }}</strong> lecturas visibles completadas</span>
            @if ($activitySummary['examples_pending'] > 0)
                <span><strong>{{ $activitySummary['examples_pending'] }}</strong> {{ $activitySummary['examples_pending'] === 1 ? 'ejemplo por revisar' : 'ejemplos por revisar' }}</span>
            @endif
            @if ($activitySummary['exercises_pending'] > 0)
                <span><strong>{{ $activitySummary['exercises_pending'] }}</strong> {{ $activitySummary['exercises_pending'] === 1 ? 'ejercicio pendiente' : 'ejercicios pendientes' }}</span>
            @endif
        </div>
        @if ($activitySummary['next'])
            <a class="module-overview-next" href="#{{ $activitySummary['next']['type'] === 'lectura' ? 'lectura-'.$activitySummary['next']['item_id'] : 'problema-'.$activitySummary['next']['problem_id'] }}" title="{{ ucfirst($activitySummary['next']['type']) }}: {{ $activitySummary['next']['title'] }}">
                <span>Continuar con {{ $activitySummary['next']['type'] === 'lectura' ? 'la lectura' : ($activitySummary['next']['type'] === 'ejemplo' ? 'el ejemplo' : 'el ejercicio') }}</span>
                <strong>{{ $activitySummary['next']['title'] }}</strong>
                <span class="module-overview-next-arrow" aria-hidden="true">→</span>
            </a>
        @endif
    </section>

    {{-- MAPA CONCEPTUAL DEL MÓDULO (antes de la primera lectura) --}}
    @if ($this->sitemap)
        @php
            $problemsMap = [];
            foreach ($items as $item) {
                foreach ($item->problems as $p) {
                    $prefix = $p->is_example ? '📘 Ejemplo: ' : '✏️ Problema: ';
                    $problemsMap[$p->id] = $prefix . ($p->title ?: '#' . $p->id);
                }
            }

            $sitemapConfig = [
                'mode' => 'view',
                'nodes' => $this->sitemap['nodes'],
                'edges' => $this->sitemap['edges'],
                'readings' => (object) $items->pluck('title', 'id')->all(),
                'problems' => (object) $problemsMap,
                'unlockedItemIds' => $visibleItems->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'completedItemIds' => array_values($completedItemIds),
                'completedProblemIds' => array_values($completedProblemIds),
            ];
        @endphp

        <section
            class="content-section sitemap-section"
            id="mapa-conceptual"
            wire:key="sitemap-{{ $module->id }}-{{ count($completedItemIds) }}-{{ count($completedProblemIds) }}"
            x-data="{ open: true }"
        >
            <div class="section-header" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                <h3>🗺️ Mapa conceptual del módulo</h3>
                <button type="button" class="badge" style="cursor:pointer;" @click="open = !open" x-text="open ? 'Ocultar mapa' : 'Mostrar mapa'"></button>
            </div>

            <div class="section-body" x-show="open" x-transition.opacity>
                <p class="sm-view-hint">
                    Explora cómo se relacionan los conceptos de este módulo.
                    <strong>Haz clic en un concepto</strong> para ir directamente al texto que lo explica.
                </p>

                <div class="sm-root" x-data="sitemapDiagram(@js($sitemapConfig))">
                    @include('partials.sitemap-viewport')
                </div>
            </div>
        </section>

        {{-- Banner de celebración de nuevo concepto desbloqueado --}}
        <div
            class="sm-unlocked-banner"
            x-data="{
                show: false,
                conceptNames: '',
                nodeIds: [],
                init() {
                    window.addEventListener('concept-unlocked', (e) => {
                        const d = e.detail || {};
                        const names = d.concepts || [];
                        this.conceptNames = names.join(', ');
                        this.nodeIds = d.nodeIds || [];
                        this.show = true;
                    });
                },
                viewInMap() {
                    this.show = false;
                    const mapEl = document.getElementById('mapa-conceptual');
                    if (mapEl) {
                        mapEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    window.dispatchEvent(new CustomEvent('celebrate-sitemap-unlock', {
                        detail: { nodeIds: this.nodeIds }
                    }));
                }
            }"
            x-show="show"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-8 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-8 scale-95"
        >
            <div class="sm-unlocked-banner-icon">🎉</div>
            <div class="sm-unlocked-banner-body">
                <span class="sm-unlocked-banner-title">¡Nuevo concepto desbloqueado!</span>
                <span class="sm-unlocked-banner-concept" x-text="conceptNames"></span>
            </div>
            <button type="button" class="sm-unlocked-banner-btn" @click="viewInMap()">
                🗺️ Ver en el mapa
            </button>
            <button type="button" class="sm-unlocked-banner-close" @click="show = false" title="Cerrar">&times;</button>
        </div>

        {{-- Botón flotante para regresar al mapa después de navegar a una lectura --}}
        <div
            class="sm-back-fab"
            x-data="{ show: false }"
            @sitemap-navigated.window="show = true"
            x-show="show"
            x-cloak
            x-transition.opacity
        >
            <button type="button" @click="document.getElementById('mapa-conceptual').scrollIntoView({ behavior: 'smooth', block: 'start' }); show = false;">
                🗺️ Volver al mapa
            </button>
        </div>
    @endif

    @foreach ($visibleItems as $item)
        <section
            class="content-section"
            id="lectura-{{ $item->id }}"
            wire:key="lectura-section-{{ $item->id }}"
            data-module-order="{{ $module->order ?? 1 }}"
            data-reading-order="{{ $item->order ?? 1 }}"
            x-data="{ open: true }"
            @expand-reading.window="if (!$event.detail || $event.detail.itemId === {{ $item->id }}) open = true"
        >
            <div class="section-header flex items-center justify-between gap-3">
                <h3 class="flex min-w-0 flex-1 flex-col items-start gap-2.5" style="margin: 0;">
                    <span class="w-full">{{ $item->title }}</span>

                    @if ($this->isReadingCompleted($item->id))
                        <span class="tag" style="margin-bottom: 0;">Lectura completada</span>
                    @else
                        <span class="tag student-status student-status-pending" style="margin-bottom: 0;">Lectura pendiente</span>
                    @endif
                </h3>

                <button
                    type="button"
                    class="badge shrink-0 whitespace-nowrap !px-2 !py-1 !text-xs sm:!px-3.5 sm:!py-2 sm:!text-[.92rem]"
                    style="cursor:pointer;"
                    @click="open = !open"
                    :aria-label="open ? 'Ocultar lectura' : 'Mostrar lectura'"
                    :aria-expanded="open.toString()"
                    aria-controls="contenido-lectura-{{ $item->id }}"
                >
                    <span class="sm:hidden" x-text="open ? 'Ocultar' : 'Mostrar'"></span>
                    <span class="hidden sm:inline" x-text="open ? 'Ocultar lectura' : 'Mostrar lectura'"></span>
                </button>
            </div>

            <div class="section-body" id="contenido-lectura-{{ $item->id }}" x-show="open" x-transition.opacity>
                {!! $item->content !!}

                @if (!$this->isReadingCompleted($item->id))
                    <div style="margin-top:18px;">
                        <button type="button" class="badge" wire:click="completeReading({{ $item->id }})">
                            Siguiente
                        </button>
                    </div>
                @endif

                @php
                    $activeProblems = $item->problems->where('is_active', true)->sortBy('order');
                    $exampleProblems = $activeProblems->where('is_example', true)->values();
                    $exerciseProblems = $activeProblems->where('is_example', false)->values();
                @endphp

                @if ($this->isReadingCompleted($item->id) && $activeProblems->count())
                    {{-- BLOQUE 1: EJEMPLOS RESUELTOS (PASO A PASO) --}}
                    @if ($exampleProblems->isNotEmpty())
                        <div style="margin-top: 26px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 2px solid #dcecff; padding-bottom: 8px;">
                                <h4 style="margin: 0; color: var(--azul-oscuro); font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
                                    <span>📘</span>
                                    <span>Ejemplos Resueltos (Paso a paso)</span>
                                </h4>
                                <span class="tag" style="background: #eaf3fd; color: var(--azul-secundario); border: 1px solid #9fc4ec; font-size: 0.8rem; margin: 0;">
                                    Demostrativo / Consulta libre
                                </span>
                            </div>

                            <div class="accordion">
                                @foreach ($exampleProblems as $index => $problem)
                                    @php
                                        $isUnlocked = $this->isProblemUnlocked($problem, $item->id);
                                        $isCompleted = $this->isProblemCompleted($problem->id);
                                        $exampleNumber = $index + 1;
                                    @endphp

                                    <div id="problema-{{ $problem->id }}" class="accordion-item {{ !$isUnlocked ? 'disabled-problem' : '' }} {{ $isUnlocked && !$isCompleted ? 'open' : '' }}" style="border-left: 4px solid var(--azul-secundario);">
                                        <button
                                            class="accordion-btn"
                                            type="button"
                                            @disabled(!$isUnlocked)
                                            @if ($isUnlocked) aria-controls="contenido-problema-{{ $problem->id }}" @endif
                                            aria-expanded="{{ $isUnlocked && !$isCompleted ? 'true' : 'false' }}"
                                        >
                                            <span class="accordion-btn-title" style="font-weight: 700; color: var(--azul-oscuro);">
                                                <span class="sr-only">Ejemplo. </span>Ejemplo {{ $exampleNumber }}: {{ $problem->title }}
                                            </span>

                                            <span class="accordion-btn-meta">
                                                <span class="tag student-status student-status-example">Ejemplo</span>
                                                @if ($isCompleted)
                                                    <span class="tag student-status student-status-completed">Completado</span>
                                                @elseif (!$isUnlocked)
                                                    <span class="tag student-status student-status-locked">Bloqueado</span>
                                                @else
                                                    <span class="tag student-status student-status-pending">Pendiente</span>
                                                @endif
                                                <span class="accordion-chevron" aria-hidden="true">▾</span>
                                            </span>
                                        </button>

                                        @if (!$isUnlocked)
                                            <p class="student-lock-reason">{{ $this->problemLockReason($problem, $item->id) }}</p>
                                        @endif

                                        @if ($isUnlocked)
                                            <div class="accordion-content" id="contenido-problema-{{ $problem->id }}">
                                                @if ($problem->content)
                                                    <div class="problema-enunciado">
                                                        {!! $problem->content !!}
                                                    </div>
                                                @endif

                                                <div class="card" style="margin-top:12px; background: #fbfdff; border: 1px solid #cfe0f5;">
                                                    <h4 style="color: var(--azul-secundario);">Demostración guiada paso a paso</h4>
                                                    <p style="margin-top:0; color: var(--gris); font-size: 0.95rem;">
                                                        Revisa el procedimiento paso a paso para comprender la metodología antes de resolver los problemas de práctica.
                                                    </p>

                                                    @php
                                                        $comp = $problem->component ?: 'problemas.problema-dinamico';
                                                    @endphp

                                                    @livewire(
                                                        $comp,
                                                        ['problemId' => $problem->id],
                                                        key('example-'.$problem->id)
                                                    )
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- BLOQUE 2: PROBLEMAS A RESOLVER (EVALUADOS) --}}
                    @if ($exerciseProblems->isNotEmpty())
                        <div style="margin-top: 28px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 2px solid #e2ebd8; padding-bottom: 8px;">
                                <h4 style="margin: 0; color: #1e5a32; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
                                    <span>✏️</span>
                                    <span>Problemas a Resolver</span>
                                </h4>
                                @if (!$this->areExamplesCompleted($item->id) && $exampleProblems->isNotEmpty())
                                    <span class="tag" style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba; font-size: 0.8rem; margin: 0;">
                                        🔒 Requiere revisar los ejemplos primero
                                    </span>
                                @endif
                            </div>

                            <div class="accordion">
                                @foreach ($exerciseProblems as $index => $problem)
                                    @php
                                        $isUnlocked = $this->isProblemUnlocked($problem, $item->id);
                                        $isCompleted = $this->isProblemCompleted($problem->id);
                                        $problemNumber = $index + 1;
                                    @endphp

                                    <div id="problema-{{ $problem->id }}" class="accordion-item {{ !$isUnlocked ? 'disabled-problem' : '' }} {{ $isUnlocked && !$isCompleted ? 'open' : '' }}">
                                        <button
                                            class="accordion-btn"
                                            type="button"
                                            @disabled(!$isUnlocked)
                                            @if ($isUnlocked) aria-controls="contenido-problema-{{ $problem->id }}" @endif
                                            aria-expanded="{{ $isUnlocked && !$isCompleted ? 'true' : 'false' }}"
                                        >
                                            <span class="accordion-btn-title">
                                                Problema {{ $problemNumber }}: {{ $problem->title }}
                                            </span>

                                            <span class="accordion-btn-meta">
                                                @if ($isCompleted)
                                                    <span class="tag student-status student-status-completed">Completado</span>
                                                @elseif (!$isUnlocked)
                                                    <span class="tag student-status student-status-locked">Bloqueado</span>
                                                @else
                                                    <span class="tag student-status student-status-pending">Pendiente</span>
                                                @endif
                                                <span class="accordion-chevron" aria-hidden="true">▾</span>
                                            </span>
                                        </button>

                                        @if (!$isUnlocked)
                                            <p class="student-lock-reason">{{ $this->problemLockReason($problem, $item->id) }}</p>
                                        @endif

                                        @if ($isUnlocked)
                                            <div class="accordion-content" id="contenido-problema-{{ $problem->id }}">
                                                @if ($problem->content)
                                                    <div class="problema-enunciado">
                                                        {!! $problem->content !!}
                                                    </div>
                                                @endif

                                                <div class="card" style="margin-top:12px;">
                                                    <h4>Resuelve paso a paso</h4>
                                                    <p style="margin-top:0;color:var(--gris);">
                                                        Ingresa tus resultados. Usa unidades consistentes.
                                                        La plataforma validará tus respuestas.
                                                    </p>

                                                    @php
                                                        $comp = $problem->component ?: 'problemas.problema-dinamico';
                                                    @endphp

                                                    @livewire(
                                                        $comp,
                                                        ['problemId' => $problem->id],
                                                        key('exercise-'.$problem->id)
                                                    )
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </section>
    @endforeach
</div>
