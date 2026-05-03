<aside>
    <div class="brand">
        <div class="ipn">Instituto Politécnico Nacional · UPIIZ</div>
        <h1>Diseño Básico de Elementos de Máquinas</h1>
    </div>

    <div class="nav-group">
        <div class="nav-title">Administración</div>
        <a class="nav-link {{ request()->routeIs('admin.inicio') ? 'active' : '' }}"
           href="{{ route('admin.inicio') }}" wire:navigate>
            Inicio
        </a>
        <a class="nav-link {{ request()->routeIs('admin.alumnos') ? 'active' : '' }}"
           href="{{ route('admin.alumnos') }}" wire:navigate>
            Alumnos
        </a>
        <a class="nav-link {{ request()->routeIs('admin.progresos') ? 'active' : '' }}"
           href="{{ route('admin.progresos') }}" wire:navigate>
            Progresos
        </a>
    </div>

   
</aside>