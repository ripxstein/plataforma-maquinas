<?php

namespace App\Livewire\Admin;

use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\User;
use Livewire\Component;

class ProgresosPanel extends Component
{
    public string $search = '';

    public function render()
    {
        $totalLecturasCatalogo = ModuleItem::count();
        $totalProblemasCatalogo = Problem::where('is_active', true)->count();
        $totalCatalogo = $totalLecturasCatalogo + $totalProblemasCatalogo;

        $alumnos = User::query()
            ->where('role', 'user')
            ->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            })
            ->withCount([
                'readingProgress as lecturas_completadas' => fn ($q) => $q->where('completed', true),
                'problemProgress as problemas_completados' => fn ($q) => $q->where('completed', true),
                'readingProgress as total_lecturas',
                'problemProgress as total_problemas',
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($alumno) use ($totalCatalogo) {
                $completadas = $alumno->lecturas_completadas + $alumno->problemas_completados;
                $total = $totalCatalogo > 0
                    ? $totalCatalogo
                    : max($alumno->total_lecturas + $alumno->total_problemas, 1);

                $alumno->avance_total = min(round(($completadas / $total) * 100), 100);

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
