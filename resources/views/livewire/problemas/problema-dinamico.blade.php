<div class="steps problem-dynamic-container" id="problem-dyn-{{ $problemId }}">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h5 style="margin: 0; color: #25623b; font-size: 1.15rem; font-weight: 700;">
            {{ $problem->title }}
        </h5>
        <span class="tag" style="background: #e7f7ea; color: #25623b; border: 1px solid #7bc28a; margin: 0;">
            {{ $steps->count() }} {{ $steps->count() === 1 ? 'Paso' : 'Pasos' }}
        </span>
    </div>

    @if($steps->isEmpty())
        <div class="footer-note" style="margin-top: 10px;">
            ⚠️ Este problema aún no tiene pasos configurados por el profesor.
        </div>
    @endif

    @foreach($steps as $index => $step)
        @php
            $stepNum = $index + 1;
            $isUnlocked = $currentStepIndex >= $stepNum;
            $isStepDone = isset($messages[$step->id]) && $messages[$step->id]['ok'];
            $isCurrent = $currentStepIndex === $stepNum;
            $showImg = ($step->image_url && ($step->image_trigger === 'always' || !empty($showTriggerImages[$step->id])));
        @endphp

        @if($isUnlocked)
            <div class="pstep" style="margin-top: {{ $index > 0 ? '16px' : '6px' }}; background: {{ $isStepDone ? '#fafffa' : '#ffffff' }}; border: 1px solid {{ $isStepDone ? '#b2e2bd' : '#c7d9ee' }}; border-radius: 14px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <div class="tag" style="background: {{ $isStepDone ? '#e7f7ea' : '#dcecff' }}; color: {{ $isStepDone ? '#1e7e34' : '#103b73' }}; margin: 0; font-weight: 700;">
                        {{ $isStepDone ? '✔ ' : '' }}Paso {{ $step->step_number ?? $stepNum }}: {{ $step->title }}
                    </div>
                    @if($isStepDone)
                        <span style="font-size: 0.8rem; color: #25623b; font-weight: 600;">Completado</span>
                    @endif
                </div>

                @if($step->instruction)
                    <div style="margin: 10px 0; font-size: 1rem; line-height: 1.6; color: var(--texto);">
                        {!! $step->instruction !!}
                    </div>
                @endif

                {{-- Step Image / Illustration if visible --}}
                @if($showImg)
                    <div class="edu-figure {{ $step->image_align ?? 'align-center' }}" style="margin: 14px 0;">
                        <img 
                            src="{{ $step->image_url }}" 
                            alt="{{ $step->image_alt ?? 'Ilustración del paso' }}"
                            style="max-width: {{ $step->image_max_width ?? '100%' }}; height: auto; border: 1px solid var(--borde); border-radius: 12px; padding: 4px; background: #fff;"
                        >
                        @if($step->image_caption || $step->image_source)
                            <figcaption style="margin-top: 6px; font-size: 0.86rem; color: var(--gris); font-style: italic;">
                                {{ $step->image_caption }}
                                @if($step->image_source)
                                    <small style="display: block; font-size: 0.78rem;">Fuente: {{ $step->image_source }}</small>
                                @endif
                            </figcaption>
                        @endif
                    </div>
                @endif

                {{-- Input Response Section --}}
                <div style="margin-top: 14px;">
                    @if($step->answer_type === 'numeric')
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
                            <label style="font-weight: 600; font-size: 0.95rem; color: var(--azul-oscuro);">
                                Tu respuesta:
                            </label>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <input 
                                    type="number" 
                                    step="any" 
                                    wire:model="answers.{{ $step->id }}"
                                    placeholder="0.00"
                                    style="padding: 8px 12px; border: 1px solid var(--borde); border-radius: 10px; font-size: 1rem; width: 160px;"
                                    @disabled($isStepDone)
                                    wire:keydown.enter="checkStep({{ $step->id }})"
                                >
                                @if($step->unit)
                                    <span class="badge" style="background: #f0f6ff; color: var(--azul-oscuro); font-size: 0.85rem;">
                                        {{ $step->unit }}
                                    </span>
                                @endif
                            </div>

                            @if(!$isStepDone)
                                <button 
                                    type="button" 
                                    class="badge" 
                                    wire:click="checkStep({{ $step->id }})"
                                    style="cursor: pointer; background: var(--azul-secundario); color: white; border: none; padding: 9px 18px;"
                                >
                                    Revisar
                                </button>
                            @endif
                        </div>

                    @elseif($step->answer_type === 'multiple_choice')
                        <div style="margin-top: 8px;">
                            <label style="font-weight: 600; font-size: 0.95rem; color: var(--azul-oscuro); display: block; margin-bottom: 8px;">
                                Selecciona la opción correcta:
                            </label>
                            <div style="display: grid; gap: 8px; max-width: 550px;">
                                @foreach($step->options as $opt)
                                    <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #f8fbff; border: 1px solid var(--borde); border-radius: 10px; cursor: {{ $isStepDone ? 'default' : 'pointer' }};">
                                        <input 
                                            type="radio" 
                                            name="step_opt_{{ $step->id }}" 
                                            value="{{ $opt->id }}"
                                            wire:model="answers.{{ $step->id }}"
                                            @disabled($isStepDone)
                                        >
                                        <span style="font-size: 0.95rem;">{{ $opt->option_text }}</span>
                                    </label>
                                @endforeach
                            </div>

                            @if(!$isStepDone)
                                <div style="margin-top: 10px;">
                                    <button 
                                        type="button" 
                                        class="badge" 
                                        wire:click="checkStep({{ $step->id }})"
                                        style="cursor: pointer; background: var(--azul-secundario); color: white; border: none; padding: 9px 18px;"
                                    >
                                        Revisar
                                    </button>
                                </div>
                            @endif
                        </div>

                    @elseif($step->answer_type === 'true_false')
                        <div style="margin-top: 8px;">
                            <label style="font-weight: 600; font-size: 0.95rem; color: var(--azul-oscuro); display: block; margin-bottom: 8px;">
                                Indica si la afirmación es Verdadera o Falsa:
                            </label>
                            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <label style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: #f8fbff; border: 1px solid var(--borde); border-radius: 10px; cursor: {{ $isStepDone ? 'default' : 'pointer' }};">
                                    <input 
                                        type="radio" 
                                        name="step_tf_{{ $step->id }}" 
                                        value="1"
                                        wire:model="answers.{{ $step->id }}"
                                        @disabled($isStepDone)
                                    >
                                    <span>Verdadero</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: #f8fbff; border: 1px solid var(--borde); border-radius: 10px; cursor: {{ $isStepDone ? 'default' : 'pointer' }};">
                                    <input 
                                        type="radio" 
                                        name="step_tf_{{ $step->id }}" 
                                        value="0"
                                        wire:model="answers.{{ $step->id }}"
                                        @disabled($isStepDone)
                                    >
                                    <span>Falso</span>
                                </label>
                            </div>

                            @if(!$isStepDone)
                                <div style="margin-top: 10px;">
                                    <button 
                                        type="button" 
                                        class="badge" 
                                        wire:click="checkStep({{ $step->id }})"
                                        style="cursor: pointer; background: var(--azul-secundario); color: white; border: none; padding: 9px 18px;"
                                    >
                                        Revisar
                                    </button>
                                </div>
                            @endif
                        </div>

                    @else {{-- Text type --}}
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
                            <label style="font-weight: 600; font-size: 0.95rem; color: var(--azul-oscuro);">
                                Tu respuesta:
                            </label>
                            <input 
                                type="text" 
                                wire:model="answers.{{ $step->id }}"
                                placeholder="Escribe tu respuesta..."
                                style="padding: 8px 12px; border: 1px solid var(--borde); border-radius: 10px; font-size: 0.95rem; min-width: 240px;"
                                @disabled($isStepDone)
                                wire:keydown.enter="checkStep({{ $step->id }})"
                            >

                            @if(!$isStepDone)
                                <button 
                                    type="button" 
                                    class="badge" 
                                    wire:click="checkStep({{ $step->id }})"
                                    style="cursor: pointer; background: var(--azul-secundario); color: white; border: none; padding: 9px 18px;"
                                >
                                    Revisar
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Feedback / Error / Reminder Messages --}}
                @if(isset($messages[$step->id]))
                    <div style="margin-top: 12px;">
                        @if($messages[$step->id]['ok'])
                            <div style="padding: 12px 14px; background: #e8fff0; border: 1px solid #67c587; border-radius: 10px; color: #166534; font-size: 0.95rem;">
                                ✔ {{ $messages[$step->id]['text'] }}
                            </div>
                        @else
                            <div style="padding: 12px 14px; background: #fff1f1; border: 1px solid #e18b8b; border-radius: 10px; color: #991b1b; font-size: 0.95rem;">
                                <div>✘ {{ $messages[$step->id]['text'] }}</div>
                                @if(!empty($messages[$step->id]['reminder']))
                                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #fca5a5; font-size: 0.9rem; color: #7f1d1d;">
                                        💡 <strong>Pista / Recordatorio:</strong> {{ $messages[$step->id]['reminder'] }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    @endforeach

    {{-- Completion celebration banner --}}
    @if($isCompleted || ($currentStepIndex > $steps->count() && $steps->isNotEmpty()))
        <div class="footer-note" style="margin-top: 20px; background: #e7f7ea; border: 1px solid #7bc28a; color: #1e5aa8; text-align: center; padding: 22px;">
            <div style="font-size: 2rem; margin-bottom: 6px;">🎉</div>
            <h4 style="margin: 0 0 6px; color: #1e5aa8; font-size: 1.25rem;">¡Problema Completado con Éxito!</h4>
            <p style="margin: 0; color: #25623b; font-size: 0.98rem;">
                Has resuelto satisfactoriamente todos los pasos interactivos de este problema. Tu progreso ha sido registrado.
            </p>
        </div>
    @endif
</div>
