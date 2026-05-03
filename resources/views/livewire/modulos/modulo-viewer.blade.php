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

    @if ($item->type === 'lectura')

    <section class="content-section" id="item-{{ $item->id }}">
        <div class="section-header">
            <h3>
                {{ $item->title }}

                @if ($this->isCompleted($item->id))
                <span class="tag">Completado</span>
                @endif
            </h3>
        </div>

        <div class="section-body">
            @if ($item->type === 'lectura')
            {!! $item->content !!}

            @if (!$this->isCompleted($item->id))
            <div style="margin-top:18px;">
                <button type="button" class="badge" wire:click="completeItem({{ $item->id }})">
                    Siguiente
                </button>
            </div>
            @endif
            @endif


            <div class="accordion">
                @foreach ($visibleItems as $item)
                @if ($item->type === 'problema')
                <div class="accordion-item @if (!$this->isCompleted($item->id)) open @endif" >
                    <button class="accordion-btn">{{ $item->title }} <span>@if ($this->isCompleted($item->id))
                <span class="tag">Completado</span>
                @endif▾</span></button>
                    <div class="accordion-content">
                        {!! $item->content !!}

                        <div class="card" style="margin-top:12px;">
                            <h4>Resuelve paso a paso</h4>
                            <p style="margin-top:0;color:var(--gris);">Ingresa tus resultados. Usa unidades
                                consistentes. La plataforma validará tus respuestas.</p>

                            @livewire($item->component, ['itemId' => $item->id], key('item-'.$item->id))
                        </div>
                    </div>
                </div>

                @endif
                @endforeach
            </div>
        </div>
    </section>

    @endif


    @endforeach
</div>