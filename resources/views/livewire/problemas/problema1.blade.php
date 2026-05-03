<div class="steps" id="p1-steps">
    <h5>Problema 1</h5>


    {{-- Paso 1 --}}
    <div class="pstep">
        <div class="tag">Paso 1</div>
        <p><strong>Calcula la sección mínima:</strong> d = w − 2r</p>

        <label>
            Tu d (in):
            <input type="number" step="0.001" wire:model="d">
        </label>

        <button type="button" class="badge" wire:click="checkStep1">
            Revisar
        </button>

        @include('livewire.partials.step-message', ['step' => 1])
    </div>

    {{-- Paso 2 --}}
    <div class="pstep" style="{{ $currentStep < 2 ? 'opacity:.55; pointer-events:none;' : '' }}">
        <div class="tag">Paso 2</div>
        <p><strong>Calcula el área mínima:</strong> A<sub>min</sub> = d · t</p>

        <label>
            Tu A<sub>min</sub> (in²):
            <input type="number" step="0.001" wire:model="A">
        </label>

        <button type="button" class="badge" wire:click="checkStep2">
            Revisar
        </button>

        @include('livewire.partials.step-message', ['step' => 2])
    </div>

    {{-- Paso 3 --}}
    <div class="pstep" style="{{ $currentStep < 3 ? 'opacity:.55; pointer-events:none;' : '' }}">
        <div class="tag">Paso 3</div>
        <p><strong>Esfuerzo nominal:</strong> σ<sub>nom</sub> = F / A<sub>min</sub></p>

        <label>
            Tu σ<sub>nom</sub> (psi):
            <input type="number" step="1" wire:model="snom">
        </label>

        <button type="button" class="badge" wire:click="checkStep3">
            Revisar
        </button>

        @include('livewire.partials.step-message', ['step' => 3])
    </div>

    {{-- Paso 4 --}}
    <div class="pstep" style="{{ $currentStep < 4 ? 'opacity:.55; pointer-events:none;' : '' }}">
        <div class="tag">Paso 4</div>
        <p><strong>Lee el Kt de la gráfica</strong> usando r/d y w/d.</p>

        <label>
            Tu K<sub>t</sub>:
            <input type="number" step="0.01" wire:model="kt">
        </label>

        <button type="button" class="badge" wire:click="checkStep4">
            Revisar
        </button>
        {{-- Imagen de ayuda paso 4 --}}
@if ($showImageStep4)
    <div style="margin-top:12px;">
        <img 
            src="{{ asset('images/graficas/figura-a-15-3.png') }}" 
            alt="Cómo obtener Kt"
            style="width:100%; max-width:400px; border:1px solid #ccc; border-radius:10px;"
        >
    </div>
@endif
        @include('livewire.partials.step-message', ['step' => 4])
        
    </div>

    {{-- Paso 5 --}}
    <div class="pstep" style="{{ $currentStep < 5 ? 'opacity:.55; pointer-events:none;' : '' }}">
        <div class="tag">Paso 5</div>
        <p><strong>Esfuerzo máximo:</strong> σ<sub>max</sub> = K<sub>t</sub> · σ<sub>nom</sub></p>

        <label>
            Tu σ<sub>max</sub> (psi):
            <input type="number" step="1" wire:model="smax">
        </label>

        <button type="button" class="badge" wire:click="checkStep5">
            Revisar
        </button>

        @include('livewire.partials.step-message', ['step' => 5])
    </div>

    @if ($currentStep >= 6)
         <img
        src="{{ asset('images/ilustraciones/A-2.png') }}"
        alt="Simulacion de barra con muesca"
        class="ilustracion-img"
    >   
        <div class="footer-note" style="margin-top:16px;">
            ✅ <strong>¡Listo!</strong> Completaste el procedimiento del problema 1.
        </div>
    @endif
</div>