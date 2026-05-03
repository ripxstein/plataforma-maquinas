<?php

namespace App\Livewire\Admin;

use App\Models\User;
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
        $alumnos = User::query()
            ->where('role', 'user')
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->withCount([
                'readingProgress as lecturas_completadas' => function ($query) {
                    $query->where('completed', true);
                },
                'problemProgress as problemas_completados' => function ($query) {
                    $query->where('completed', true);
                },
            ])
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.alumnos-panel', [
            'alumnos' => $alumnos,
        ]);
    }
}