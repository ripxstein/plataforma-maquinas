<?php

namespace App\Livewire\Admin;

use App\Models\AccessCode;
use Livewire\Component;

class AccessCodesPanel extends Component
{
    public $code;

    public $group_name;

    public $editingId = null;

    public function save()
    {
        $this->validate([
            'code' => 'required|string|max:50|unique:access_codes,code,'.$this->editingId,
            'group_name' => 'required|string|max:20',
        ]);

        AccessCode::updateOrCreate(
            ['id' => $this->editingId],
            [
                'code' => strtoupper($this->code),
                'group_name' => strtoupper($this->group_name),
                'active' => true,
            ]
        );

        $this->reset(['code', 'group_name', 'editingId']);

        session()->flash('message', 'Código guardado correctamente.');
    }

    public function edit($id)
    {
        $accessCode = AccessCode::findOrFail($id);

        $this->editingId = $accessCode->id;
        $this->code = $accessCode->code;
        $this->group_name = $accessCode->group_name;
    }

    public function delete($id)
    {
        AccessCode::findOrFail($id)->delete();

        session()->flash('message', 'Código eliminado correctamente.');
    }

    public function toggleStatus($id)
    {
        $accessCode = AccessCode::findOrFail($id);

        $accessCode->update([
            'active' => ! $accessCode->active,
        ]);

        session()->flash(
            'message',
            $accessCode->active
                ? 'Código activado correctamente.'
                : 'Código desactivado correctamente.'
        );
    }

    public function render()
    {
        return view('livewire.admin.access-codes-panel', [
            'codes' => AccessCode::orderBy('group_name')->get(),
        ]);
    }
}
