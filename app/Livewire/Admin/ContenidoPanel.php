<?php

namespace App\Livewire\Admin;

use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use Illuminate\Support\Str;
use Livewire\Component;

class ContenidoPanel extends Component
{
    public $modules;

    // Modal Control Flags
    public bool $showModuleModal = false;
    public bool $showReadingModal = false;
    public bool $showProblemModal = false;

    // Form fields - Module
    public $moduleId = null;
    public $moduleTitle = '';
    public $moduleSlug = '';
    public $moduleOrder = 1;

    // Form fields - Reading
    public $readingId = null;
    public $moduleIdForReading = null;
    public $readingTitle = '';
    public $readingSlug = '';
    public $readingContent = '';
    public $readingOrder = 1;
    public $readingPercentage = 100;

    // Form fields - Problem
    public $problemId = null;
    public $readingIdForProblem = null;
    public $problemTitle = '';
    public $problemSlug = '';
    public $problemContent = '';
    public $problemComponent = 'problemas.problema1';
    public $customComponent = '';
    public $problemOrder = 1;
    public $problemPercentage = 100;

    // Available interactive problem components list
    public array $availableComponents = [
        'problemas.problema1' => 'Problema 1: Placa con muescas (Concentración de esfuerzos)',
        'problemas.problema2' => 'Problema 2: Selección de diseño (Taladro vs Filete)',
        'custom' => '⚙️ Componente personalizado...'
    ];

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

    // Auto-slug hooks
    public function updatedModuleTitle($value)
    {
        $this->moduleSlug = Str::slug($value);
    }

    public function updatedReadingTitle($value)
    {
        $this->readingSlug = Str::slug($value);
    }

    public function updatedProblemTitle($value)
    {
        $this->problemSlug = Str::slug($value);
    }

    // Modal Openers
    public function openModuleModal($id = null)
    {
        $this->resetValidation();
        $this->resetModuleForm();

        if ($id) {
            $module = Module::findOrFail($id);
            $this->moduleId = $module->id;
            $this->moduleTitle = $module->title;
            $this->moduleSlug = $module->slug;
            $this->moduleOrder = $module->order;
        } else {
            $this->moduleOrder = (Module::max('order') ?? 0) + 1;
        }

        $this->showModuleModal = true;
    }

    public function closeModuleModal()
    {
        $this->showModuleModal = false;
        $this->resetModuleForm();
    }

    public function openReadingModal($id = null, $parentModuleId = null)
    {
        $this->resetValidation();
        $this->resetReadingForm();

        if ($id) {
            $reading = ModuleItem::findOrFail($id);
            $this->readingId = $reading->id;
            $this->moduleIdForReading = $reading->module_id;
            $this->readingTitle = $reading->title;
            $this->readingSlug = $reading->slug;
            $this->readingContent = $reading->content;
            $this->readingOrder = $reading->order;
            $this->readingPercentage = $reading->percentage;
        } else {
            $this->moduleIdForReading = $parentModuleId ?? ($this->modules->first()->id ?? null);
            $this->readingOrder = (ModuleItem::where('module_id', $this->moduleIdForReading)->max('order') ?? 0) + 1;
            $this->readingPercentage = 100;
        }

        $this->showReadingModal = true;
    }

    public function closeReadingModal()
    {
        $this->showReadingModal = false;
        $this->resetReadingForm();
    }

    public function openProblemModal($id = null, $parentReadingId = null)
    {
        $this->resetValidation();
        $this->resetProblemForm();

        if ($id) {
            $problem = Problem::findOrFail($id);
            $this->problemId = $problem->id;
            $this->readingIdForProblem = $problem->module_item_id;
            $this->problemTitle = $problem->title;
            $this->problemSlug = $problem->slug;
            $this->problemContent = $problem->content;
            $this->problemOrder = $problem->order;
            $this->problemPercentage = $problem->percentage;

            if (array_key_exists($problem->component, $this->availableComponents)) {
                $this->problemComponent = $problem->component;
                $this->customComponent = '';
            } else {
                $this->problemComponent = 'custom';
                $this->customComponent = $problem->component;
            }
        } else {
            $this->readingIdForProblem = $parentReadingId;
            if ($parentReadingId) {
                $this->problemOrder = (Problem::where('module_item_id', $parentReadingId)->max('order') ?? 0) + 1;
            } else {
                $this->problemOrder = 1;
            }
            $this->problemPercentage = 100;
            $this->problemComponent = 'problemas.problema1';
        }

        $this->showProblemModal = true;
    }

    public function closeProblemModal()
    {
        $this->showProblemModal = false;
        $this->resetProblemForm();
    }

    // Save Handlers
    public function saveModule()
    {
        if (empty($this->moduleSlug) && !empty($this->moduleTitle)) {
            $this->moduleSlug = Str::slug($this->moduleTitle);
        }

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

        $this->closeModuleModal();
        $this->loadData();
        session()->flash('message', 'Módulo guardado correctamente.');
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
            'moduleIdForReading' => 'required|exists:modules,id',
            'readingTitle' => 'required|string|max:255',
            'readingContent' => 'nullable|string',
            'readingOrder' => 'required|integer|min:1',
            'readingPercentage' => 'required|integer|min:0|max:100',
        ]);

        // Auto format plain paragraphs if teacher wrote line breaks without tags
        $formattedContent = $this->formatContentIfNeeded($this->readingContent);

        ModuleItem::updateOrCreate(
            ['id' => $this->readingId],
            [
                'module_id' => $this->moduleIdForReading,
                'title' => $this->readingTitle,
                'type' => 'lectura',
                'component' => null,
                'content' => $formattedContent,
                'order' => $this->readingOrder,
                'percentage' => $this->readingPercentage,
            ]
        );

        $this->closeReadingModal();
        $this->loadData();
        session()->flash('message', 'Lectura guardada correctamente.');
    }

    public function deleteReading($id)
    {
        ModuleItem::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('message', 'Lectura eliminada.');
    }

    public function saveProblem()
    {
        if (empty($this->problemSlug) && !empty($this->problemTitle)) {
            $this->problemSlug = Str::slug($this->problemTitle);
        }

        $this->validate([
            'readingIdForProblem' => 'required|exists:module_items,id',
            'problemTitle' => 'required|string|max:255',
            'problemSlug' => 'required|string|max:255',
            'problemContent' => 'nullable|string',
            'problemComponent' => 'required|string',
            'customComponent' => 'required_if:problemComponent,custom',
            'problemOrder' => 'required|integer|min:1',
            'problemPercentage' => 'required|integer|min:0|max:100',
        ]);

        $finalComponent = ($this->problemComponent === 'custom')
            ? trim($this->customComponent)
            : $this->problemComponent;

        $formattedContent = $this->formatContentIfNeeded($this->problemContent);

        Problem::updateOrCreate(
            ['id' => $this->problemId],
            [
                'module_item_id' => $this->readingIdForProblem,
                'title' => $this->problemTitle,
                'slug' => $this->problemSlug,
                'content' => $formattedContent,
                'component' => $finalComponent,
                'order' => $this->problemOrder,
                'percentage' => $this->problemPercentage,
            ]
        );

        $this->closeProblemModal();
        $this->loadData();
        session()->flash('message', 'Problema guardado correctamente.');
    }

    public function deleteProblem($id)
    {
        Problem::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('message', 'Problema eliminado.');
    }

    // Text formatting helpers for non-technical admins
    public function insertTag(string $tag, string $target = 'reading')
    {
        $snippet = match ($tag) {
            'h3' => '<h3>Subtítulo de la lección</h3>',
            'bold' => '<strong>Texto en negrita</strong>',
            'p' => '<p>Escribe tu párrafo explicativo aquí.</p>',
            'list' => "<ul>\n  <li>Primer punto</li>\n  <li>Segundo punto</li>\n</ul>",
            'formula' => '<div class="formula">A = d &times; t</div>',
            default => '',
        };

        if ($target === 'reading') {
            $this->readingContent .= "\n" . $snippet;
        } else {
            $this->problemContent .= "\n" . $snippet;
        }
    }

    private function formatContentIfNeeded(?string $content): ?string
    {
        if (empty($content)) {
            return $content;
        }

        // If content already contains HTML tags, return as is
        if (preg_match('/<[a-z][\s\S]*>/i', $content)) {
            return $content;
        }

        // Convert double line breaks into paragraphs
        $paragraphs = array_filter(array_map('trim', explode("\n\n", $content)));
        return implode("\n", array_map(fn($p) => '<p>' . nl2br(e($p)) . '</p>', $paragraphs));
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
        $this->moduleIdForReading = null;
        $this->readingTitle = '';
        $this->readingSlug = '';
        $this->readingContent = '';
        $this->readingOrder = 1;
        $this->readingPercentage = 100;
    }

    public function resetProblemForm()
    {
        $this->problemId = null;
        $this->readingIdForProblem = null;
        $this->problemTitle = '';
        $this->problemSlug = '';
        $this->problemContent = '';
        $this->problemComponent = 'problemas.problema1';
        $this->customComponent = '';
        $this->problemOrder = 1;
        $this->problemPercentage = 100;
    }

    public function render()
    {
        return view('livewire.admin.contenido-panel');
    }
}