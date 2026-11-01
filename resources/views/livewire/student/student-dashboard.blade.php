<div class="student-dashboard">
    <section class="hero" aria-labelledby="student-welcome-title">
        <h2 id="student-welcome-title">Hola, {{ auth()->user()->name }}.</h2>
        <p>{{ $next ? 'Tu siguiente actividad ya está lista.' : ($summary['readings_total'] ? 'Has terminado las actividades disponibles.' : 'Pronto encontrarás aquí tus lecturas y actividades.') }}</p>

        @if ($next)
            <a class="student-primary-action" href="{{ route('student.modulo', $next['module_slug']) }}#{{ $next['type'] === 'lectura' ? 'lectura-'.$next['item_id'] : 'problema-'.$next['problem_id'] }}">
                Continuar donde te quedaste
            </a>
            <p class="student-next-activity">
                <strong>Siguiente actividad:</strong> {{ ucfirst($next['type']) }}: {{ $next['title'] }}
                <span>En {{ $next['module_title'] }}</span>
            </p>
        @endif
    </section>

    <section class="student-progress-card" aria-labelledby="student-progress-title">
        <h3 id="student-progress-title">Tu avance</h3>
        <p><strong>{{ $progress['total'] }}%</strong> del contenido evaluado completado.</p>
        <div class="progress-bar" role="progressbar" aria-label="Avance total" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress['total'] }}">
            <div class="progress-fill" style="width: {{ $progress['total'] }}%;"></div>
        </div>
        <p>{{ $summary['readings_completed'] }} de {{ $summary['readings_total'] }} lecturas terminadas · {{ $summary['examples_pending'] }} ejemplos por revisar · {{ $summary['exercises_pending'] }} ejercicios pendientes.</p>
        @if ($next && $next['type'] === 'lectura')
            <p>Para continuar, completa la lectura y después sus ejemplos y ejercicios para desbloquear la siguiente.</p>
        @elseif ($next)
            <p>Para desbloquear la siguiente lectura, termina los ejemplos y ejercicios pendientes de la lectura actual.</p>
        @endif
    </section>
</div>
