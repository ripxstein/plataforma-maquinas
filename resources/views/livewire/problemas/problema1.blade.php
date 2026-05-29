<div class="steps" id="p1-steps">
    <h5>Problema 1</h5>

    {{-- Paso 1 --}}
    @if ($currentStep >= 1)
        <div class="pstep">
            <div class="tag">Paso 1</div>
            <p><strong>Calcula la sección mínima:</strong> d = w − 2r</p>

            <label>
                Tu d (in):
                <input type="number" step="0.001" wire:model="d">
            </label>

            @if ($currentStep == 1 && !$askNextStep)
                <button type="button" class="badge" wire:click="checkStep1">
                    Revisar
                </button>
            @endif

            @include('livewire.partials.step-message', ['step' => 1])
        </div>
    @endif

    {{-- Paso 2 --}}
    @if ($currentStep >= 2)
        <div class="pstep" style="margin-top:14px;">
            <div class="tag">Paso 2</div>
            <p><strong>Calcula el área mínima:</strong> A<sub>min</sub> = d · t</p>

            <label>
                Tu A<sub>min</sub> (in²):
                <input type="number" step="0.001" wire:model="A">
            </label>

            @if ($currentStep == 2 && !$askNextStep)
                <button type="button" class="badge" wire:click="checkStep2">
                    Revisar
                </button>
            @endif

            @include('livewire.partials.step-message', ['step' => 2])
        </div>
    @endif

    {{-- Paso 3 --}}
    @if ($currentStep >= 3)
        <div class="pstep" style="margin-top:14px;">
            <div class="tag">Paso 3</div>
            <p><strong>Esfuerzo nominal:</strong> σ<sub>nom</sub> = F / A<sub>min</sub></p>

            <label>
                Tu σ<sub>nom</sub> (psi):
                <input type="number" step="1" wire:model="snom">
            </label>

            @if ($currentStep == 3 && !$askNextStep)
                <button type="button" class="badge" wire:click="checkStep3">
                    Revisar
                </button>
            @endif

            @include('livewire.partials.step-message', ['step' => 3])
        </div>
    @endif

    {{-- Paso 4 --}}
    @if ($currentStep >= 4)
        <div class="pstep" style="margin-top:14px;">
            <div class="tag">Paso 4</div>
            <p><strong>Lee el Kt de la gráfica</strong> usando r/d y w/d.</p>

            <label>
                Tu K<sub>t</sub>:
                <input type="number" step="0.01" wire:model="kt">
            </label>

            @if ($currentStep == 4 && !$askNextStep)
                <button type="button" class="badge" wire:click="checkStep4">
                    Revisar
                </button>
            @endif

            @include('livewire.partials.step-message', ['step' => 4])

            @if ($showImageStep4)
                <div style="margin-top:12px;">
                    <img
                        src="{{ asset('images/graficas/figura-a-15-3.png') }}"
                        alt="Cómo obtener Kt"
                        style="width:100%; max-width:400px; border:1px solid #ccc; border-radius:10px;"
                    >
                </div>
            @endif
        </div>
    @endif

    {{-- Paso 5 --}}
    @if ($currentStep >= 5)
        <div class="pstep" style="margin-top:14px;">
            <div class="tag">Paso 5</div>
            <p><strong>Esfuerzo máximo:</strong> σ<sub>max</sub> = K<sub>t</sub> · σ<sub>nom</sub></p>

            <label>
                Tu σ<sub>max</sub> (psi):
                <input type="number" step="1" wire:model="smax">
            </label>

            @if ($currentStep == 5)
                <button type="button" class="badge" wire:click="checkStep5">
                    Revisar
                </button>
            @endif

            @include('livewire.partials.step-message', ['step' => 5])
        </div>
    @endif

    {{-- Pregunta: qué paso sigue --}}
    @if ($askNextStep)
        <div class="footer-note" style="margin-top:16px;">
            <strong>¿Qué paso sigue?</strong>

            <p style="margin-top:8px;">
                Antes de continuar, selecciona cuál es el siguiente procedimiento correcto.
            </p>

            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-top:10px;">
                <select wire:model="nextStepAnswer" style="padding:8px 12px; border-radius:10px; border:1px solid var(--borde);">
                    <option value="">Selecciona una opción</option>
                    <option value="area">Calcular el área mínima</option>
                    <option value="esfuerzo_nominal">Calcular el esfuerzo nominal</option>
                    <option value="kt">Obtener Kt de la gráfica</option>
                    <option value="esfuerzo_maximo">Calcular el esfuerzo máximo</option>
                </select>

                <button type="button" class="badge" wire:click="checkNextStep">
                    Responder
                </button>
            </div>

            @include('livewire.partials.step-message', ['step' => 'next'])
        </div>
    @endif

    {{-- Final --}}
    @if ($currentStep >= 6)
        <img
            src="{{ asset('images/ilustraciones/A-2.png') }}"
            alt="Simulación de barra con muesca"
            class="ilustracion-img"
        >

        <div class="footer-note" style="margin-top:16px;">
            ✅ <strong>¡Listo!</strong> Completaste el procedimiento del problema 1.
        </div>
    @endif
</div>