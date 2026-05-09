<div>
    <section class="hero">
        <h2>Administrar contenido</h2>
        <p>Crea módulos, lecturas y problemas desde el panel administrativo.</p>
    </section>

    @if (session()->has('message'))
        <div class="footer-note" style="margin-bottom:18px;">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid-2">
        <section class="content-section">
            <div class="section-header">
                <h3>{{ $moduleId ? 'Editar módulo' : 'Nuevo módulo' }}</h3>
            </div>

            <div class="section-body">
                <input class="admin-input" type="text" wire:model="moduleTitle" placeholder="Título del módulo">
                <input class="admin-input" type="text" wire:model="moduleSlug" placeholder="Slug: concentracion">
                <input class="admin-input" type="number" wire:model="moduleOrder" placeholder="Orden">

                <button class="badge" wire:click="saveModule">Guardar módulo</button>

                @if($moduleId)
                    <button class="badge" wire:click="resetModuleForm">Cancelar</button>
                @endif
            </div>
        </section>

        <section class="content-section">
            <div class="section-header">
                <h3>{{ $readingId ? 'Editar lectura' : 'Nueva lectura' }}</h3>
            </div>

            <div class="section-body">
                <select class="admin-input" wire:model="moduleId">
                    <option value="">Selecciona módulo</option>
                    @foreach($modules as $module)
                        <option value="{{ $module->id }}">{{ $module->title }}</option>
                    @endforeach
                </select>

                <input class="admin-input" type="text" wire:model="readingTitle" placeholder="Título de lectura">
                <input class="admin-input" type="text" wire:model="readingSlug" placeholder="Slug">
                <input class="admin-input" type="number" wire:model="readingOrder" placeholder="Orden">
                <input class="admin-input" type="number" wire:model="readingPercentage" placeholder="Porcentaje">

                <textarea class="admin-textarea" wire:model="readingContent" placeholder="Contenido HTML de la lectura"></textarea>

                <button class="badge" wire:click="saveReading">Guardar lectura</button>

                @if($readingId)
                    <button class="badge" wire:click="resetReadingForm">Cancelar</button>
                @endif
            </div>
        </section>
    </div>

    <section class="content-section" style="margin-top:22px;">
        <div class="section-header">
            <h3>{{ $problemId ? 'Editar problema' : 'Nuevo problema' }}</h3>
        </div>

        <div class="section-body">
            <select class="admin-input" wire:model="readingId">
                <option value="">Selecciona lectura asociada</option>

                @foreach($modules as $module)
                    <optgroup label="{{ $module->title }}">
                        @foreach($module->items as $reading)
                            <option value="{{ $reading->id }}">{{ $reading->title }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

            <input class="admin-input" type="text" wire:model="problemTitle" placeholder="Título del problema">
            <input class="admin-input" type="text" wire:model="problemSlug" placeholder="Slug">
            <input class="admin-input" type="text" wire:model="problemComponent" placeholder="Componente: problemas.problema1">
            <input class="admin-input" type="number" wire:model="problemOrder" placeholder="Orden">
            <input class="admin-input" type="number" wire:model="problemPercentage" placeholder="Porcentaje">

            <textarea class="admin-textarea" wire:model="problemContent" placeholder="Enunciado HTML del problema"></textarea>

            <button class="badge" wire:click="saveProblem">Guardar problema</button>

            @if($problemId)
                <button class="badge" wire:click="resetProblemForm">Cancelar</button>
            @endif
        </div>
    </section>

    <section class="content-section" style="margin-top:22px;">
        <div class="section-header">
            <h3>Contenido existente</h3>
        </div>

        <div class="section-body">
            @foreach($modules as $module)
                <div class="card" style="margin-bottom:16px;">
                    <h4>{{ $module->order }}. {{ $module->title }}</h4>

                    <button class="badge" wire:click="editModule({{ $module->id }})">Editar módulo</button>

                    <button
                        class="badge btn-danger"
                        onclick="confirm('¿Eliminar módulo completo?') || event.stopImmediatePropagation()"
                        wire:click="deleteModule({{ $module->id }})"
                    >
                        Eliminar módulo
                    </button>

                    @foreach($module->items as $reading)
                        <div class="footer-note" style="margin-top:14px;">
                            <strong>{{ $reading->order }}. {{ $reading->title }}</strong>
                            <br>
                            Lectura · {{ $reading->percentage }}%

                            <div style="margin-top:8px;">
                                <button class="badge" wire:click="editReading({{ $reading->id }})">Editar lectura</button>

                                <button
                                    class="badge btn-danger"
                                    onclick="confirm('¿Eliminar lectura y sus problemas?') || event.stopImmediatePropagation()"
                                    wire:click="deleteReading({{ $reading->id }})"
                                >
                                    Eliminar lectura
                                </button>
                            </div>

                            @foreach($reading->problems as $problem)
                                <div class="card" style="margin-top:12px;">
                                    <strong>{{ $problem->order }}. {{ $problem->title }}</strong>
                                    <br>
                                    Problema · {{ $problem->percentage }}% · {{ $problem->component }}

                                    <div style="margin-top:8px;">
                                        <button class="badge" wire:click="editProblem({{ $problem->id }})">
                                            Editar problema
                                        </button>

                                        <button
                                            class="badge btn-danger"
                                            onclick="confirm('¿Eliminar problema?') || event.stopImmediatePropagation()"
                                            wire:click="deleteProblem({{ $problem->id }})"
                                        >
                                            Eliminar problema
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>
</div>