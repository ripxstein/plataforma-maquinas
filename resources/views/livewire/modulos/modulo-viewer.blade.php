<div>
    <section class="hero">
        <h2>{{ $module->title }}</h2>

        <p>Avance total: <strong>{{ $totalProgress }}%</strong></p>

        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $totalProgress }}%;"></div>
        </div>

        <p>
            Lecturas: <strong>{{ $readingProgress }}%</strong> |
            Problemas: <strong>{{ $problemProgress }}%</strong>
        </p>
    </section>

    @foreach ($visibleItems as $item)
        <section class="content-section" id="lectura-{{ $item->id }}" style="counter-reset: mod-num {{ $module->order ?? 1 }} read-num {{ $item->order ?? 1 }} formula-idx 0;">
            <div class="section-header">
                <h3>
                    {{ $item->title }}

                    @if ($this->isReadingCompleted($item->id))
                        <span class="tag">Lectura completada</span>
                    @endif
                </h3>
            </div>

            <div class="section-body">
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

                                    <div class="accordion-item {{ !$isUnlocked ? 'disabled-problem' : '' }} {{ $isUnlocked && !$isCompleted ? 'open' : '' }}" style="border-left: 4px solid var(--azul-secundario);">
                                        <button
                                            class="accordion-btn"
                                            type="button"
                                            @disabled(!$isUnlocked)
                                        >
                                            <span style="font-weight: 700; color: var(--azul-oscuro);">
                                                Ejemplo {{ $exampleNumber }}: {{ $problem->title }}
                                            </span>

                                            <span>
                                                @if ($isCompleted)
                                                    <span class="tag" style="background: #eaf3fd; color: var(--azul-secundario); border: 1px solid #9fc4ec;">Revisado</span>
                                                @elseif (!$isUnlocked)
                                                    <span class="tag">Bloqueado</span>
                                                @else
                                                    <span class="tag" style="background: #fff8e6; color: #8a5b00; border: 1px solid #f0d182;">Disponible</span>
                                                @endif
                                                ▾
                                            </span>
                                        </button>

                                        @if ($isUnlocked)
                                            <div class="accordion-content">
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

                                    <div class="accordion-item {{ !$isUnlocked ? 'disabled-problem' : '' }} {{ $isUnlocked && !$isCompleted ? 'open' : '' }}">
                                        <button
                                            class="accordion-btn"
                                            type="button"
                                            @disabled(!$isUnlocked)
                                        >
                                            <span>
                                                Problema {{ $problemNumber }}: {{ $problem->title }}
                                            </span>

                                            <span>
                                                @if ($isCompleted)
                                                    <span class="tag">Completado</span>
                                                @elseif (!$isUnlocked)
                                                    <span class="tag">Bloqueado</span>
                                                @else
                                                    <span class="tag">Pendiente</span>
                                                @endif
                                                ▾
                                            </span>
                                        </button>

                                        @if ($isUnlocked)
                                            <div class="accordion-content">
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