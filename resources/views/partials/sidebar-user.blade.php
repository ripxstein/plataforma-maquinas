


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

        <a class="nav-link nav-toggle {{ request()->routeIs('student.concentracion') ? 'active' : '' }}"
           href="{{ route('student.concentracion') }}" wire:navigate>
            1. Concentración de esfuerzos
        </a>
        <div class="submenu" >
        <a href="concentracion#problema1" class="submenu-link">Problema 1</a>
        <a href="concentracion#problema2" class="submenu-link">Problema 2</a>
        </div>

        <a class="nav-link {{ request()->routeIs('student.fallas') ? 'active' : '' }}"
           href="{{ route('student.fallas') }}" wire:navigate>
            2. Fallas por carga estática
        </a>
        

        <a class="nav-link {{ request()->routeIs('student.aplicacion') ? 'active' : '' }}"
           href="{{ route('student.aplicacion') }}" wire:navigate>
            3. Aplicación de teorías de falla
        </a>
        <div class="submenu" >
        <a href="aplicacion#problema3" class="submenu-link">Problema 3</a>
        <a href="aplicacion#problema4" class="submenu-link">Problema 4</a>
        <a href="aplicacion#problema5" class="submenu-link">Problema 5</a>
        </div>

        <a class="nav-link {{ request()->routeIs('student.ejes') ? 'active' : '' }}"
           href="{{ route('student.ejes') }}" wire:navigate>
            4. Diseño de ejes
        </a>
        <div class="submenu" >
        <a href="ejes#problema6" class="submenu-link">Problema 6</a>
        <a href="ejes#problema7" class="submenu-link">Problema 7</a>
        <a href="ejes#problema8" class="submenu-link">Problema 8</a>
        </div>

        <a class="nav-link {{ request()->routeIs('student.retos') ? 'active' : '' }}"
           href="{{ route('student.retos') }}" wire:navigate>
            5. Retos de competencia
        </a>

        <a class="nav-link {{ request()->routeIs('student.bibliografia') ? 'active' : '' }}"
           href="{{ route('student.bibliografia') }}" wire:navigate>
            Bibliografía
        </a>
</div>

</aside>