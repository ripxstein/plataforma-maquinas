


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

        <a class="nav-link {{ request()->is('alumno/modulo/concentracion') ? 'active' : '' }}"
   href="{{ route('student.modulo', 'concentracion') }}"
   wire:navigate>
    1. Concentración de esfuerzos
</a>

<a class="nav-link {{ request()->is('alumno/modulo/fallas') ? 'active' : '' }}"
   href="{{ route('student.modulo', 'fallas') }}"
   wire:navigate>
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