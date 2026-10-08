<aside class="sidebar-user" :class="{ 'collapsed': sidebarCollapsed }">
    <div class="brand">
        <!-- Contenido expandido -->
        <div class="brand-expanded">
            <div class="brand-header-row">
                <div class="ipn">Instituto Politécnico Nacional · UPIIZ</div>
                <button type="button" 
                        class="sidebar-toggle-btn" 
                        @click="toggleSidebar()" 
                        title="Colapsar barra lateral"
                        aria-label="Colapsar barra lateral">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>
            <h1>Diseño Básico de Elementos de Máquinas</h1>
            <p>Unidad I · Concentración de esfuerzos y teorías de falla estática</p>
        </div>

        <!-- Contenido colapsado -->
        <div class="brand-collapsed">
            <button type="button" 
                    class="sidebar-toggle-btn" 
                    @click="toggleSidebar()" 
                    title="Expandir barra lateral"
                    aria-label="Expandir barra lateral">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                </svg>
            </button>
            <div class="brand-mini-badge" title="Diseño Básico de Elementos de Máquinas - UPIIZ">
                <span>IPN</span>
            </div>
        </div>
    </div>

    <div class="nav-group">
        <div class="nav-title">Navegación</div>

        <a class="nav-link {{ request()->routeIs('student.inicio') ? 'active' : '' }}"
           href="{{ route('student.inicio') }}" 
           wire:navigate
           title="Inicio">
            <span class="nav-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </span>
            <span class="nav-text">Inicio</span>
        </a>

        <a class="nav-link {{ request()->routeIs('student.criterios') ? 'active' : '' }}"
           href="{{ route('student.criterios') }}" 
           wire:navigate
           title="Criterios">
            <span class="nav-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </span>
            <span class="nav-text">Criterios</span>
        </a>

        @php
            $modules = $modules ?? collect(\Illuminate\Support\Facades\Cache::get('sidebar_modules') ?? \App\Models\Module::orderBy('order')->get(['id', 'title', 'slug', 'order']))->map(fn ($m) => is_object($m) ? $m : (object) $m);
            $currentIndex = 1;
        @endphp

        @foreach($modules as $module)
            @php
                $isCurrentModule = request()->is('alumno/modulo/' . $module->slug) || request()->is('alumno/' . $module->slug);
            @endphp
            <a class="nav-link {{ $isCurrentModule ? 'active' : '' }}"
               href="{{ route('student.modulo', $module->slug) }}"
               wire:navigate
               title="{{ $module->title }}">
                <span class="nav-icon">
                    <span class="nav-badge-num">{{ $currentIndex }}</span>
                </span>
                <span class="nav-text">{{ $module->title }}</span>
            </a>
            @php $currentIndex++; @endphp
        @endforeach

        <a class="nav-link {{ request()->routeIs('student.bibliografia') ? 'active' : '' }}"
           href="{{ route('student.bibliografia') }}" 
           wire:navigate
           title="Bibliografía">
            <span class="nav-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </span>
            <span class="nav-text">Bibliografía</span>
        </a>
    </div>
</aside>
