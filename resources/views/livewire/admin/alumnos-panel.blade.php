<div>
    <section class="hero">
        <h2>Alumnos Registrados</h2>
        <p>Consulta el avance general de los estudiantes registrados.</p>
    </section>

    @if (session()->has('message'))
        <div class="footer-note" style="margin-bottom:18px;">
            {{ session('message') }}
        </div>
    @endif

    <section class="content-section">
        <div class="section-header">
            <h3>Alumnos registrados</h3>
        </div>

        <div class="section-body">
            <div style="margin-bottom:18px;">
                <input
                    type="text"
                    wire:model.live="search"
                    placeholder="Buscar alumno por nombre o correo..."
                    style="width:100%; padding:12px 14px; border:1px solid var(--borde); border-radius:12px;"
                >
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Correo</th>
                            <th>Lecturas completadas</th>
                            <th>Problemas completados</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($alumnos as $alumno)
                            <tr>
                                <td>{{ $alumno->name }}</td>
                                <td>{{ $alumno->email }}</td>
                                <td>{{ $alumno->lecturas_completadas }}</td>
                                <td>{{ $alumno->problemas_completados }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="badge"
                                        onclick="confirm('¿Seguro que deseas eliminar este alumno?') || event.stopImmediatePropagation()"
                                        wire:click="deleteAlumno({{ $alumno->id }})"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    No se encontraron alumnos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top:18px;">
                {{ $alumnos->links() }}
            </div>
        </div>
    </section>
</div>