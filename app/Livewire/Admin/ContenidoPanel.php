<?php

namespace App\Livewire\Admin;

use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use Livewire\Component;

class ContenidoPanel extends Component
{
    public $modules;

    public $moduleId;
    public $readingId;
    public $problemId;

    public $moduleTitle;
    public $moduleSlug;
    public $moduleOrder = 1;

    public $readingTitle;
    public $readingSlug;
    public $readingContent;
    public $readingOrder = 1;
    public $readingPercentage = 0;

    public $problemTitle;
    public $problemSlug;
    public $problemContent;
    public $problemComponent;
    public $problemOrder = 1;
    public $problemPercentage = 0;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->modules = Module::with('items.problems')
            ->orderBy('order')
            ->get();
    }

    public function saveModule()
    {
        $this->validate([
            'moduleTitle' => 'required|string|max:255',
            'moduleSlug' => 'required|string|max:255',
            'moduleOrder' => 'required|integer|min:1',
        ]);

        Module::updateOrCreate(
            ['id' => $this->moduleId],
            [
                'title' => $this->moduleTitle,
                'slug' => $this->moduleSlug,
                'order' => $this->moduleOrder,
            ]
        );

        $this->resetModuleForm();
        $this->loadData();

        session()->flash('message', 'Módulo guardado correctamente.');
    }

    public function editModule($id)
    {
        $module = Module::findOrFail($id);

        $this->moduleId = $module->id;
        $this->moduleTitle = $module->title;
        $this->moduleSlug = $module->slug;
        $this->moduleOrder = $module->order;
    }

    public function deleteModule($id)
    {
        Module::findOrFail($id)->delete();

        $this->loadData();
        session()->flash('message', 'Módulo eliminado.');
    }

    public function saveReading()
    {
        $this->validate([
            'moduleId' => 'required|exists:modules,id',
            'readingTitle' => 'required|string|max:255',
            'readingSlug' => 'required|string|max:255',
            'readingContent' => 'nullable|string',
            'readingOrder' => 'required|integer|min:1',
            'readingPercentage' => 'required|integer|min:0|max:100',
        ]);

        ModuleItem::updateOrCreate(
            ['id' => $this->readingId],
            [
                'module_id' => $this->moduleId,
                'title' => $this->readingTitle,
                'slug' => $this->readingSlug,
                'type' => 'lectura',
                'component' => null,
                'content' => $this->readingContent,
                'order' => $this->readingOrder,
                'percentage' => $this->readingPercentage,
            ]
        );

        $this->resetReadingForm();
        $this->loadData();

        session()->flash('message', 'Lectura guardada correctamente.');
    }

    public function editReading($id)
    {
        $reading = ModuleItem::findOrFail($id);

        $this->readingId = $reading->id;
        $this->moduleId = $reading->module_id;
        $this->readingTitle = $reading->title;
        $this->readingSlug = $reading->slug;
        $this->readingContent = $reading->content;
        $this->readingOrder = $reading->order;
        $this->readingPercentage = $reading->percentage;
    }

    public function deleteReading($id)
    {
        ModuleItem::findOrFail($id)->delete();

        $this->loadData();
        session()->flash('message', 'Lectura eliminada.');
    }

    public function saveProblem()
    {
        $this->validate([
            'readingId' => 'required|exists:module_items,id',
            'problemTitle' => 'required|string|max:255',
            'problemSlug' => 'required|string|max:255',
            'problemContent' => 'nullable|string',
            'problemComponent' => 'required|string|max:255',
            'problemOrder' => 'required|integer|min:1',
            'problemPercentage' => 'required|integer|min:0|max:100',
        ]);

        Problem::updateOrCreate(
            ['id' => $this->problemId],
            [
                'module_item_id' => $this->readingId,
                'title' => $this->problemTitle,
                'slug' => $this->problemSlug,
                'content' => $this->problemContent,
                'component' => $this->problemComponent,
                'order' => $this->problemOrder,
                'percentage' => $this->problemPercentage,
            ]
        );

        $this->resetProblemForm();
        $this->loadData();

        session()->flash('message', 'Problema guardado correctamente.');
    }

    public function editProblem($id)
    {
        $problem = Problem::findOrFail($id);

        $this->problemId = $problem->id;
        $this->readingId = $problem->module_item_id;
        $this->problemTitle = $problem->title;
        $this->problemSlug = $problem->slug;
        $this->problemContent = $problem->content;
        $this->problemComponent = $problem->component;
        $this->problemOrder = $problem->order;
        $this->problemPercentage = $problem->percentage;
    }

    public function deleteProblem($id)
    {
        Problem::findOrFail($id)->delete();

        $this->loadData();
        session()->flash('message', 'Problema eliminado.');
    }

    public function resetModuleForm()
    {
        $this->moduleId = null;
        $this->moduleTitle = '';
        $this->moduleSlug = '';
        $this->moduleOrder = 1;
    }

    public function resetReadingForm()
    {
        $this->readingId = null;
        $this->readingTitle = '';
        $this->readingSlug = '';
        $this->readingContent = '';
        $this->readingOrder = 1;
        $this->readingPercentage = 0;
    }

    public function resetProblemForm()
    {
        $this->problemId = null;
        $this->problemTitle = '';
        $this->problemSlug = '';
        $this->problemContent = '';
        $this->problemComponent = '';
        $this->problemOrder = 1;
        $this->problemPercentage = 0;
    }

    public function render()
    {
        return view('livewire.admin.contenido-panel');
    }
}