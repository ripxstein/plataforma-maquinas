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
        <section class="content-section" id="lectura-{{ $item->id }}">
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

                @if ($this->isReadingCompleted($item->id) && $item->problems->count())
                    <div class="accordion" style="margin-top:22px;">
                        @foreach ($item->problems as $problem)
        @php
            $unlockedProblemOrder = $this->getUnlockedProblemOrder($item->id);
            $isUnlocked = $problem->order <= $unlockedProblemOrder;
            $isCompleted = $this->isProblemCompleted($problem->id);
        @endphp

        <div class="accordion-item {{ !$isUnlocked ? 'disabled-problem' : '' }} {{ $isUnlocked && !$isCompleted ? 'open' : '' }}">
            <button
                class="accordion-btn"
                type="button"
                @disabled(!$isUnlocked)
            >
                {{ $problem->title }}

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

                        @livewire(
                            $problem->component,
                            ['problemId' => $problem->id],
                            key('problem-'.$problem->id)
                        )
                    </div>
                </div>
            @endif
        </div>
    @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endforeach
</div>