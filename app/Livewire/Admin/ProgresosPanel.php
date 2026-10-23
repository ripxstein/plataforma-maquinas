<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\ProgressCalculator;
use Livewire\Component;

class ProgresosPanel extends Component
{
    public string $search = '';

    public function render()
    {
        $calculator = app(ProgressCalculator::class);
        $totalWeight = $calculator->catalogWeight();
        $alumnos = $calculator->withUserProgress(User::query()
            ->select('id', 'name', 'email')
            ->where('role', 'user')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
        )
            ->orderBy('name')
            ->get()
            ->map(function ($alumno) use ($calculator, $totalWeight) {
                $alumno->avance_total = $calculator->percentage($alumno->reading_weight + $alumno->problem_weight, $totalWeight);

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
