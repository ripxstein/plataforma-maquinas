


<aside>
    <div class="brand">
        <div class="ipn">Instituto Politécnico Nacional · UPIIZ</div>
        <h1>Diseño Básico de Elementos de Máquinas</h1>
        <p>Unidad I · Concentración de esfuerzos y teorías de falla estática</p>
    </div>

    <div class="nav-group">
        <div class="nav-title">Navegación</div>

        <a class="nav-link {{ request()->routeIs('student.inicio') ? 'active' : '' }}"
           href="{{ route('student.inicio') }}" wire:navigate>
            Inicio
        </a>

        <a class="nav-link {{ request()->routeIs('student.criterios') ? 'active' : '' }}"
           href="{{ route('student.criterios') }}" wire:navigate>
            Criterios
        </a>

        @php
            $modules = $modules ?? \App\Models\Module::with(['items' => function($q) {
                $q->orderBy('order');
            }, 'items.problems' => function($q) {
                $q->orderBy('order');
            }])->orderBy('order')->get();

            $currentIndex = 1;
        @endphp

        @foreach($modules as $module)
            @php
                $isCurrentModule = request()->is('alumno/modulo/' . $module->slug) || request()->is('alumno/' . $module->slug);
            @endphp
            <a class="nav-link {{ $isCurrentModule ? 'active' : '' }}"
               href="{{ route('student.modulo', $module->slug) }}"
               wire:navigate>
                {{ $currentIndex }}. {{ $module->title }}
            </a>
            @php $currentIndex++; @endphp

            @php
                $problems = $module->items->flatMap->problems;
            @endphp

            @if($problems->isNotEmpty())
                <div class="submenu">
                    @foreach($problems as $problem)
                        <a href="{{ route('student.modulo', $module->slug) }}#problema-{{ $problem->id }}" class="submenu-link">
                            {{ $problem->title }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach

        @if(! $modules->pluck('slug')->contains('aplicacion'))
            <a class="nav-link {{ request()->routeIs('student.aplicacion') ? 'active' : '' }}"
               href="{{ route('student.aplicacion') }}" wire:navigate>
                {{ $currentIndex }}. Aplicación de teorías de falla
            </a>
            @php $currentIndex++; @endphp
            <div class="submenu">
                <a href="{{ route('student.aplicacion') }}#problema3" class="submenu-link">Problema 3</a>
                <a href="{{ route('student.aplicacion') }}#problema4" class="submenu-link">Problema 4</a>
                <a href="{{ route('student.aplicacion') }}#problema5" class="submenu-link">Problema 5</a>
            </div>
        @endif

        @if(! $modules->pluck('slug')->contains('ejes'))
            <a class="nav-link {{ request()->routeIs('student.ejes') ? 'active' : '' }}"
               href="{{ route('student.ejes') }}" wire:navigate>
                {{ $currentIndex }}. Diseño de ejes
            </a>
            @php $currentIndex++; @endphp
            <div class="submenu">
                <a href="{{ route('student.ejes') }}#problema6" class="submenu-link">Problema 6</a>
                <a href="{{ route('student.ejes') }}#problema7" class="submenu-link">Problema 7</a>
                <a href="{{ route('student.ejes') }}#problema8" class="submenu-link">Problema 8</a>
            </div>
        @endif

        <a class="nav-link {{ request()->routeIs('student.retos') ? 'active' : '' }}"
           href="{{ route('student.retos') }}" wire:navigate>
            {{ $currentIndex }}. Retos de competencia
        </a>

        <a class="nav-link {{ request()->routeIs('student.bibliografia') ? 'active' : '' }}"
           href="{{ route('student.bibliografia') }}" wire:navigate>
            Bibliografía
        </a>
    </div>
</aside>