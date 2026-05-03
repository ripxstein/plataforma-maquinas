<div class="steps">
    <h5>Problema 2</h5>

    

    {{-- Paso 1 --}}
    <div class="pstep">
        <div class="tag">Paso 1</div>
        <p><strong>Opción A:</strong> calcula el área neta mínima del taladro.</p>
        <p class="formula">A<sub>nom</sub> = (W − D<sub>taladro</sub>) · t</p>

        <label>
            A<sub>nom,A</sub> (mm²):
            <input type="number" step="0.01" wire:model="areaA">
        </label>

        <button type="button" class="badge" wire:click="checkStep1">Revisar</button>

        @include('livewire.partials.step-message', ['step' => 1])
    </div>

    {{-- Paso 2 --}}
    <div class="pstep" style="{{ $currentStep < 2 ? 'opacity:.55; pointer-events:none;' : 'margin-top:14px;' }}">
        <div class="tag">Paso 2</div>
        <p><strong>Opción A:</strong> calcula el esfuerzo nominal.</p>
        <p class="formula">σ<sub>nom</sub> = P / A<sub>nom</sub></p>

        <label>
            σ<sub>nom,A</sub> (MPa):
            <input type="number" step="0.01" wire:model="sigmaNomA">
        </label>

        <button type="button" class="badge" wire:click="checkStep2">Revisar</button>

        @include('livewire.partials.step-message', ['step' => 2])
    </div>

    {{-- Paso 3 --}}
    <div class="pstep" style="{{ $currentStep < 3 ? 'opacity:.55; pointer-events:none;' : 'margin-top:14px;' }}">
        <div class="tag">Paso 3</div>
        <p><strong>Opción A:</strong> calcula la relación geométrica del agujero.</p>
        <p class="formula">D<sub>taladro</sub> / W</p>

        <label>
            D/W:
            <input type="number" step="0.001" wire:model="relacionA">
        </label>

        <button type="button" class="badge" wire:click="checkStep3">Revisar</button>

        @include('livewire.partials.step-message', ['step' => 3])
    </div>

    {{-- Paso 4 --}}
    <div class="pstep" style="{{ $currentStep < 4 ? 'opacity:.55; pointer-events:none;' : 'margin-top:14px;' }}">
        <div class="tag">Paso 4</div>
        <p><strong>Opción A:</strong> lee el factor K<sub>t</sub> de la gráfica correspondiente.</p>

        <label>
            K<sub>t,A</sub>:
            <input type="number" step="0.01" wire:model="ktA">
        </label>

        <button type="button" class="badge" wire:click="checkStep4">Revisar</button>

        {{-- Imagen de ayuda paso 4 --}}
@if ($showImageStep4)
    <div style="margin-top:12px;">
        <img 
            src="{{ asset('images/graficas/figura-a-15-1.png') }}" 
            alt="Cómo obtener Kt"
            style="width:100%; max-width:400px; border:1px solid #ccc; border-radius:10px;"
        >
    </div>
@endif

        @include('livewire.partials.step-message', ['step' => 4])
    </div>

    {{-- Paso 5 --}}
    <div class="pstep" style="{{ $currentStep < 5 ? 'opacity:.55; pointer-events:none;' : 'margin-top:14px;' }}">
        <div class="tag">Paso 5</div>
        <p><strong>Opción A:</strong> calcula el esfuerzo máximo.</p>
        <p class="formula">σ<sub>max,A</sub> = K<sub>t,A</sub> · σ<sub>nom,A</sub></p>

        <label>
            σ<sub>max,A</sub> (MPa):
            <input type="number" step="0.01" wire:model="sigmaMaxA">
        </label>

        <button type="button" class="badge" wire:click="checkStep5">Revisar</button>

        @include('livewire.partials.step-message', ['step' => 5])
    </div>

    {{-- Paso 6 --}}
    <div class="pstep" style="{{ $currentStep < 6 ? 'opacity:.55; pointer-events:none;' : 'margin-top:14px;' }}">
        <div class="tag">Paso 6</div>
        <p><strong>Opción B:</strong> calcula el área mínima en la zona del filete.</p>
        <p class="formula">A<sub>nom</sub> = d · t</p>

        <label>
            A<sub>nom,B</sub> (mm²):
            <input type="number" step="0.01" wire:model="areaB">
        </label>

        <button type="button" class="badge" wire:click="checkStep6">Revisar</button>

        @include('livewire.partials.step-message', ['step' => 6])
    </div>

    {{-- Paso 7 --}}
    <div class="pstep" style="{{ $currentStep < 7 ? 'opacity:.55; pointer-events:none;' : 'margin-top:14px;' }}">
        <div class="tag">Paso 7</div>
        <p><strong>Opción B:</strong> calcula el esfuerzo nominal.</p>

        <label>
            σ<sub>nom,B</sub> (MPa):
            <input type="number" step="0.01" wire:model="sigmaNomB">
        </label>

        <button type="button" class="badge" wire:click="checkStep7">Revisar</button>

        @include('livewire.partials.step-message', ['step' => 7])
    </div>

    {{-- Paso 8 --}}
    <div class="pstep" style="{{ $currentStep < 8 ? 'opacity:.55; pointer-events:none;' : 'margin-top:14px;' }}">
        <div class="tag">Paso 8</div>
        <p><strong>Opción B:</strong> calcula las relaciones geométricas.</p>

        <label>
            r/d:
            <input type="number" step="0.001" wire:model="rdB">
        </label>

        <label style="margin-left:12px;">
            D/d:
            <input type="number" step="0.001" wire:model="DdB">
        </label>

        <button type="button" class="badge" wire:click="checkStep8">Revisar</button>

        @include('livewire.partials.step-message', ['step' => 8])
    </div>

    {{-- Paso 9 --}}
    <div class="pstep" style="{{ $currentStep < 9 ? 'opacity:.55; pointer-events:none;' : 'margin-top:14px;' }}">
        <div class="tag">Paso 9</div>
        <p><strong>Opción B:</strong> lee K<sub>t</sub> en la gráfica A-15-5.</p>

        <label>
            K<sub>t,B</sub>:
            <input type="number" step="0.01" wire:model="ktB">
        </label>

        <button type="button" class="badge" wire:click="checkStep9">Revisar</button>

        {{-- Imagen de ayuda paso 9 --}}
@if ($showImageStep9)
    <div style="margin-top:12px;">
        <img 
            src="{{ asset('images/graficas/figura-a-15-51.png') }}" 
            alt="Cómo obtener Kt"
            style="width:100%; max-width:400px; border:1px solid #ccc; border-radius:10px;"
        >
    </div>
@endif

        @include('livewire.partials.step-message', ['step' => 9])
    </div>

    {{-- Paso 10 --}}
    <div class="pstep" style="{{ $currentStep < 10 ? 'opacity:.55; pointer-events:none;' : 'margin-top:14px;' }}">
        <div class="tag">Paso 10</div>
        <p><strong>Opción B:</strong> calcula el esfuerzo máximo.</p>
        <p class="formula">σ<sub>max,B</sub> = K<sub>t,B</sub> · σ<sub>nom,B</sub></p>

        <label>
            σ<sub>max,B</sub> (MPa):
            <input type="number" step="0.01" wire:model="sigmaMaxB">
        </label>

        <button type="button" class="badge" wire:click="checkStep10">Revisar</button>

        @include('livewire.partials.step-message', ['step' => 10])
    </div>

    {{-- Paso 11 --}}
    <div class="pstep" style="{{ $currentStep < 11 ? 'opacity:.55; pointer-events:none;' : 'margin-top:14px;' }}">
        <div class="tag">Paso 11</div>
        
        <p><strong>Conclusión:</strong> ¿qué opción es preferible?</p>
        @if ($currentStep >= 11)
        <div class="grid-2">
                  <div class="card">
                    <h4>Opción A: Taladro central</h4>
                    <ul>
                      <li>Área nominal: <strong>(40 − 8) × 10 = 320 mm²</strong></li>
                      <li>Esfuerzo nominal: <strong>62.5 MPa</strong></li>
                      <li>Relación geométrica: <strong>D/W = 0.2</strong></li>
                      <li>Factor teórico: <strong>Kt ≈ 2.5</strong></li>
                      <li>Esfuerzo máximo: <strong>156.25 MPa</strong></li>
                    </ul>
                  </div>
                  <div class="card">
                    <h4>Opción B: Filete</h4>
                    <ul>
                      <li>Área nominal: <strong>32 × 10 = 320 mm²</strong></li>
                      <li>Esfuerzo nominal: <strong>62.5 MPa</strong></li>
                      <li>Relaciones: <strong>r/d = 0.125</strong>, <strong>D/d = 1.25</strong></li>
                      <li>Factor teórico: <strong>Kt ≈ 1.75</strong></li>
                      <li>Esfuerzo máximo: <strong>109.375 MPa</strong></li>
                    </ul>
                  </div>
                </div>
        
    @endif

        <select wire:model="seleccion">
            <option value="">Selecciona una opción</option>
            <option value="A">Opción A: Taladro</option>
            <option value="B">Opción B: Filete</option>
        </select>

        <button type="button" class="badge" wire:click="checkStep11">Revisar</button>

        @include('livewire.partials.step-message', ['step' => 11])
    </div>

    @if ($currentStep >= 12)
        <img
        src="{{ asset('images/ilustraciones/B-2.png') }}"
        alt="Simulacion de barras con muesca"
        class="ilustracion-img"
    >   
        <div class="footer-note" style="margin-top:16px;">
            ✅ <strong>¡Listo!</strong> La opción B es preferible porque reduce el esfuerzo máximo:
            <strong>109.375 MPa &lt; 156.25 MPa</strong>.
        </div>
    @endif
</div>