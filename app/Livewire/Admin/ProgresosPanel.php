<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\UserItemProgress;
use App\Models\UserProblemProgress;
use Livewire\Component;

class ProgresosPanel extends Component
{
    public string $search = '';

    public function render()
    {
        $alumnos = User::query()
            ->where('role', 'user')
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->withCount([
                'readingProgress as lecturas_completadas' => fn ($q) => $q->where('completed', true),
                'problemProgress as problemas_completados' => fn ($q) => $q->where('completed', true),
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($alumno) {
                $totalLecturas =  UserItemProgress::where('user_id', $alumno->id)->count();
                $totalProblemas = UserProblemProgress::where('user_id', $alumno->id)->count();

                $completadas = $alumno->lecturas_completadas + $alumno->problemas_completados;
                $total = max($totalLecturas + $totalProblemas, 1);

                $alumno->avance_total = round(($completadas / $total) * 100);

                return $alumno;
            });

        $promedio = round($alumnos->avg('avance_total') ?? 0);

        $totalLecturasCompletadas = $alumnos->sum('lecturas_completadas');
        $totalProblemasCompletados = $alumnos->sum('problemas_completados');

        return view('livewire.admin.progresos-panel', [
            'alumnos' => $alumnos,
            'promedio' => $promedio,
            'totalLecturasCompletadas' => $totalLecturasCompletadas,
            'totalProblemasCompletados' => $totalProblemasCompletados,
            'topAlumno' => $alumnos->sortByDesc('avance_total')->first(),
            'bajoAlumno' => $alumnos->sortBy('avance_total')->first(),
        ]);
    }
}