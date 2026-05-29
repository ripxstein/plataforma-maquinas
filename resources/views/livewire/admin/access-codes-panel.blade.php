<div>
    <section class="hero">
        <h2>Códigos de acceso</h2>
        <p>Crea códigos para permitir el registro de alumnos por grupo.</p>
    </section>

    @if (session()->has('message'))
    <div class="footer-note" style="margin-bottom:18px;">
        {{ session('message') }}
    </div>
    @endif

    <section class="content-section">
        <div class="section-header">
            <h3>{{ $editingId ? 'Editar código' : 'Nuevo código' }}</h3>
        </div>

        <div class="section-body">
            <input class="admin-input" type="text" wire:model="code" placeholder="Código de acceso, ejemplo: ABC123">

            <input class="admin-input" type="text" wire:model="group_name" placeholder="Grupo, ejemplo: 1MM2">

            <button class="badge" wire:click="save">
                Guardar código
            </button>
        </div>
    </section>

    <section class="content-section" style="margin-top:22px;">
        <div class="section-header">
            <h3>Códigos existentes</h3>
        </div>

        <div class="section-body">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Grupo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($codes as $accessCode)
                        <tr>
                            <td><strong>{{ $accessCode->code }}</strong></td>
                            <td>{{ $accessCode->group_name }}</td>
                            <td>{{ $accessCode->active ? 'Activo' : 'Inactivo' }}</td>
                            <td style="display:flex; gap:10px; align-items:center;">
                                <button class="badge" wire:click="edit({{ $accessCode->id }})">
                                    Editar
                                </button>

                                @if($accessCode->active)

                                <button class="badge" style="
                background:#fff3cd;
                color:#856404;
                border:1px solid #ffeeba;
            " wire:click="toggleStatus({{ $accessCode->id }})">
                                    Desactivar
                                </button>

                                @else

                                <button class="badge" style="
                background:#d4edda;
                color:#155724;
                border:1px solid #c3e6cb;
            " wire:click="toggleStatus({{ $accessCode->id }})">
                                    Activar
                                </button>

                                @endif

                                <button class="badge btn-danger"
                                    onclick="confirm('¿Eliminar este código?') || event.stopImmediatePropagation()"
                                    wire:click="delete({{ $accessCode->id }})">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>