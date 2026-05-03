<div>
    <section class="hero">
        <h2>Progreso de alumnos</h2>
        <p>Vista general del avance en lecturas y problemas.</p>
    </section>

    <div class="grid-2">
        <div class="card">
            <h4>Promedio general</h4>
            <div class="big-number">{{ $promedio }}%</div>

            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $promedio }}%;"></div>
            </div>
        </div>

        <div class="card">
            <h4>Actividad completada</h4>

            <div class="mini-chart">
                <div>
                    <span>Lecturas</span>
                    <div class="chart-bar">
                        <div style="width: {{ min($totalLecturasCompletadas * 10, 100) }}%;"></div>
                    </div>
                    <strong>{{ $totalLecturasCompletadas }}</strong>
                </div>

                <div>
                    <span>Problemas</span>
                    <div class="chart-bar">
                        <div style="width: {{ min($totalProblemasCompletados * 10, 100) }}%;"></div>
                    </div>
                    <strong>{{ $totalProblemasCompletados }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="grid-2" style="margin-top:18px;">
        <div class="card">
            <h4>Alumno con mayor avance</h4>
            @if($topAlumno)
                <p><strong>{{ $topAlumno->name }}</strong></p>
                <p>{{ $topAlumno->avance_total }}% completado</p>
            @endif
        </div>

        <div class="card">
            <h4>Alumno que requiere seguimiento</h4>
            @if($bajoAlumno)
                <p><strong>{{ $bajoAlumno->name }}</strong></p>
                <p>{{ $bajoAlumno->avance_total }}% completado</p>
            @endif
        </div>
    </div>

    <section class="content-section" style="margin-top:22px;">
        <div class="section-header">
            <h3>Detalle por alumno</h3>
        </div>

        <div class="section-body">
            <input
                type="text"
                wire:model.live="search"
                placeholder="Buscar alumno..."
                style="width:100%; padding:12px 14px; border:1px solid var(--borde); border-radius:12px; margin-bottom:18px;"
            >

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Lecturas</th>
                            <th>Problemas</th>
                            <th>Avance total</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($alumnos as $alumno)
                            <tr>
                                <td>
                                    <strong>{{ $alumno->name }}</strong><br>
                                    <small>{{ $alumno->email }}</small>
                                </td>

                                <td>{{ $alumno->lecturas_completadas }}</td>
                                <td>{{ $alumno->problemas_completados }}</td>

                                <td>
                                    <strong>{{ $alumno->avance_total }}%</strong>
                                    <div class="progress-bar small">
                                        <div class="progress-fill" style="width: {{ $alumno->avance_total }}%;"></div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">No se encontraron alumnos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>