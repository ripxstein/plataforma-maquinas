<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\ProgressCalculator;
use Livewire\Component;
use Livewire\WithPagination;

class AlumnosPanel extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteAlumno($id)
    {
        $alumno = User::where('role', 'user')->findOrFail($id);

        $alumno->delete();

        session()->flash('message', 'Alumno eliminado correctamente.');
    }

    public function render()
    {
        $query = User::query()
            ->select('id', 'name', 'email')
            ->where('role', 'user')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            });

        $alumnos = app(ProgressCalculator::class)->withUserProgress($query, includeWeights: false)
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.alumnos-panel', [
            'alumnos' => $alumnos,
        ]);
    }
}
