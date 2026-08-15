<?php

namespace App\Livewire\Admin;

use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\ProblemStep;
use App\Models\ProblemStepOption;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ComponentesPanel extends Component
{
    use WithFileUploads;

    // View state: 'list' or 'builder'
    public string $viewMode = 'list';

    // Filters for list
    public string $search = '';
    public string $moduleFilter = '';

    // Active Problem Data (General Settings)
    public ?int $problemId = null;
    public ?int $moduleItemId = null;
    public string $title = '';
    public string $slug = '';
    public ?string $content = '';
    public string $component = 'problemas.problema-dinamico';
    public int $order = 1;
    public int $percentage = 35;
    public bool $isActive = true;

    // Steps Collection in Builder (in-memory or synced)
    public array $stepsData = [];

    // Step Editor Modal/Drawer state
    public bool $showStepModal = false;
    public ?int $editingStepIndex = null;

    // Form fields for active step editing
    public string $stepTitle = '';
    public string $stepInstruction = '';
    public string $stepAnswerType = 'numeric'; // numeric, multiple_choice, true_false, text
    public string $stepCorrectAnswer = '';
    public $stepTolerance = 0.01;
    public string $stepToleranceType = 'absolute'; // absolute, percentage
    public string $stepUnit = '';
    public string $stepSuccessMessage = '¡Correcto! Puedes continuar con el siguiente paso.';
    public string $stepErrorMessage = 'Respuesta incorrecta. Revisa tus cálculos.';
    public string $stepReminderMessage = '';

    // Step Image settings
    public string $stepImageUrl = '';
    public string $stepImageAlt = '';
    public string $stepImageCaption = '';
    public string $stepImageSource = '';
    public string $stepImageAlign = 'align-center';
    public string $stepImageMaxWidth = '75%';
    public string $stepImageTrigger = 'always'; // always, on_error, on_success

    // Step Multiple Choice Options
    public array $stepOptions = [
        ['option_text' => '', 'is_correct' => false],
        ['option_text' => '', 'is_correct' => false],
    ];

    // Real-time Student Preview Simulator State
    public int $previewCurrentStep = 1;
    public array $previewAnswers = [];
    public array $previewMessages = [];
    public array $previewShowImages = [];
    public bool $previewCompleted = false;

    public function mount()
    {
        // Default init
    }

    // Auto-generate slug when title changes
    public function updatedTitle($value)
    {
        if (empty($this->slug) || $this->slug === Str::slug($this->title)) {
            $this->slug = Str::slug($value);
        }
    }

    public function createComponent()
    {
        $this->resetValidation();
        $this->problemId = null;
        
        $firstReading = ModuleItem::orderBy('module_id')->orderBy('order')->first();
        $this->moduleItemId = $firstReading ? $firstReading->id : null;
        $this->title = 'Nuevo Problema Interactivo';
        $this->slug = 'nuevo-problema-' . time();
        $this->content = '<p>Describe aquí el enunciado general del problema para el estudiante.</p>';
        $this->component = 'problemas.problema-dinamico';
        $this->order = (Problem::where('module_item_id', $this->moduleItemId)->max('order') ?? 0) + 1;
        $this->percentage = 35;
        $this->isActive = true;

        // Initialize with 2 default sample steps
        $this->stepsData = [
            [
                'id' => null,
                'step_number' => 1,
                'title' => 'Paso 1: Identificación o Cálculo Inicial',
                'instruction' => 'Calcula el valor del primer parámetro requerido con las fórmulas dadas.',
                'answer_type' => 'numeric',
                'correct_answer' => '100',
                'tolerance' => 0.01,
                'tolerance_type' => 'absolute',
                'unit' => 'MPa',
                'success_message' => '¡Correcto! Has determinado el valor inicial.',
                'error_message' => 'Revisa la fórmula inicial y tus unidades.',
                'reminder_message' => 'Recuerda verificar las dimensiones geométricas.',
                'image_url' => '',
                'image_alt' => '',
                'image_caption' => '',
                'image_source' => '',
                'image_align' => 'align-center',
                'image_max_width' => '75%',
                'image_trigger' => 'always',
                'options' => [],
            ],
            [
                'id' => null,
                'step_number' => 2,
                'title' => 'Paso 2: Cálculo de Esfuerzo o Factor',
                'instruction' => 'Con el resultado anterior, determina el esfuerzo resultante.',
                'answer_type' => 'numeric',
                'correct_answer' => '250',
                'tolerance' => 0.01,
                'tolerance_type' => 'absolute',
                'unit' => 'MPa',
                'success_message' => '¡Excelente! Has completado el problema.',
                'error_message' => 'Revisa la multiplicación por el factor de concentración.',
                'reminder_message' => 'Asegúrate de sustituir los valores obtenidos en el Paso 1.',
                'image_url' => '',
                'image_alt' => '',
                'image_caption' => '',
                'image_source' => '',
                'image_align' => 'align-center',
                'image_max_width' => '75%',
                'image_trigger' => 'on_error',
                'options' => [],
            ]
        ];

        $this->resetPreviewSimulator();
        $this->viewMode = 'builder';
    }

    public function editComponent(int $id)
    {
        $this->resetValidation();
        $problem = Problem::with(['steps.options' => function($q) {
            $q->orderBy('order');
        }])->findOrFail($id);

        $this->problemId = $problem->id;
        $this->moduleItemId = $problem->module_item_id;
        $this->title = $problem->title;
        $this->slug = $problem->slug;
        $this->content = $problem->content;
        $this->component = $problem->component ?: 'problemas.problema-dinamico';
        $this->order = $problem->order;
        $this->percentage = $problem->percentage;
        $this->isActive = $problem->is_active ?? true;

        // Load steps
        $this->stepsData = [];
        foreach ($problem->steps as $index => $step) {
            $options = [];
            foreach ($step->options as $opt) {
                $options[] = [
                    'id' => $opt->id,
                    'option_text' => $opt->option_text,
                    'is_correct' => (bool)$opt->is_correct,
                ];
            }

            $this->stepsData[] = [
                'id' => $step->id,
                'step_number' => $index + 1,
                'title' => $step->title,
                'instruction' => $step->instruction,
                'answer_type' => $step->answer_type,
                'correct_answer' => (string)$step->correct_answer,
                'tolerance' => $step->tolerance ?? 0.01,
                'tolerance_type' => $step->tolerance_type ?? 'absolute',
                'unit' => $step->unit ?? '',
                'success_message' => $step->success_message ?? '¡Correcto! Puedes continuar.',
                'error_message' => $step->error_message ?? 'Respuesta incorrecta.',
                'reminder_message' => $step->reminder_message ?? '',
                'image_url' => $step->image_url ?? '',
                'image_alt' => $step->image_alt ?? '',
                'image_caption' => $step->image_caption ?? '',
                'image_source' => $step->image_source ?? '',
                'image_align' => $step->image_align ?? 'align-center',
                'image_max_width' => $step->image_max_width ?? '75%',
                'image_trigger' => $step->image_trigger ?? 'always',
                'options' => $options,
            ];
        }

        $this->resetPreviewSimulator();
        $this->viewMode = 'builder';
    }

    public function saveComponent()
    {
        if (empty($this->slug)) {
            $this->slug = Str::slug($this->title);
        }

        $this->validate([
            'moduleItemId' => 'required|exists:module_items,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:problems,slug,' . ($this->problemId ?? 'NULL') . ',id',
            'order' => 'required|integer|min:1',
            'percentage' => 'required|integer|min:0|max:100',
        ]);

        if (empty($this->stepsData)) {
            $this->addError('steps', 'El problema debe tener al menos un paso interactivo.');
            return;
        }

        // Save Problem
        $problem = Problem::updateOrCreate(
            ['id' => $this->problemId],
            [
                'module_item_id' => $this->moduleItemId,
                'title' => $this->title,
                'slug' => $this->slug,
                'content' => $this->content,
                'component' => $this->component ?: 'problemas.problema-dinamico',
                'order' => $this->order,
                'percentage' => $this->percentage,
                'is_active' => $this->isActive,
            ]
        );

        $this->problemId = $problem->id;

        // Keep track of existing step IDs to delete removed steps
        $keptStepIds = [];

        foreach ($this->stepsData as $index => $stepData) {
            $step = ProblemStep::updateOrCreate(
                ['id' => $stepData['id'] ?? null],
                [
                    'problem_id' => $problem->id,
                    'step_number' => $index + 1,
                    'title' => $stepData['title'],
                    'instruction' => $stepData['instruction'],
                    'answer_type' => $stepData['answer_type'],
                    'correct_answer' => $stepData['correct_answer'],
                    'tolerance' => $stepData['tolerance'],
                    'tolerance_type' => $stepData['tolerance_type'] ?? 'absolute',
                    'unit' => $stepData['unit'] ?? '',
                    'success_message' => $stepData['success_message'],
                    'error_message' => $stepData['error_message'],
                    'reminder_message' => $stepData['reminder_message'],
                    'image_url' => $stepData['image_url'] ?? null,
                    'image_alt' => $stepData['image_alt'] ?? null,
                    'image_caption' => $stepData['image_caption'] ?? null,
                    'image_source' => $stepData['image_source'] ?? null,
                    'image_align' => $stepData['image_align'] ?? 'align-center',
                    'image_max_width' => $stepData['image_max_width'] ?? '75%',
                    'image_trigger' => $stepData['image_trigger'] ?? 'always',
                ]
            );

            $keptStepIds[] = $step->id;
            $this->stepsData[$index]['id'] = $step->id;

            // Handle options for multiple choice
            $keptOptionIds = [];
            if ($stepData['answer_type'] === 'multiple_choice' && !empty($stepData['options'])) {
                foreach ($stepData['options'] as $optIdx => $optData) {
                    if (empty(trim($optData['option_text'] ?? ''))) continue;

                    $option = ProblemStepOption::updateOrCreate(
                        ['id' => $optData['id'] ?? null],
                        [
                            'problem_step_id' => $step->id,
                            'option_text' => $optData['option_text'],
                            'is_correct' => (bool)($optData['is_correct'] ?? false),
                            'order' => $optIdx + 1,
                        ]
                    );
                    $keptOptionIds[] = $option->id;
                    $this->stepsData[$index]['options'][$optIdx]['id'] = $option->id;
                }
            }

            // Remove deleted options for this step
            ProblemStepOption::where('problem_step_id', $step->id)
                ->whereNotIn('id', $keptOptionIds)
                ->delete();
        }

        // Remove deleted steps
        ProblemStep::where('problem_id', $problem->id)
            ->whereNotIn('id', $keptStepIds)
            ->delete();

        session()->flash('message', '¡Componente interactivo guardado exitosamente!');
        $this->resetPreviewSimulator();
    }

    public function backToList()
    {
        $this->viewMode = 'list';
        $this->resetValidation();
    }

    public function duplicateComponent(int $id)
    {
        $original = Problem::with('steps.options')->findOrFail($id);

        $newTitle = 'Copia de ' . $original->title;
        $newSlug = Str::slug($newTitle) . '-' . rand(100, 999);

        $newProblem = Problem::create([
            'module_item_id' => $original->module_item_id,
            'title' => $newTitle,
            'slug' => $newSlug,
            'content' => $original->content,
            'component' => $original->component ?: 'problemas.problema-dinamico',
            'order' => (Problem::where('module_item_id', $original->module_item_id)->max('order') ?? 0) + 1,
            'percentage' => $original->percentage,
            'is_active' => $original->is_active ?? true,
        ]);

        foreach ($original->steps as $step) {
            $newStep = ProblemStep::create([
                'problem_id' => $newProblem->id,
                'step_number' => $step->step_number,
                'title' => $step->title,
                'instruction' => $step->instruction,
                'answer_type' => $step->answer_type,
                'correct_answer' => $step->correct_answer,
                'tolerance' => $step->tolerance,
                'tolerance_type' => $step->tolerance_type,
                'unit' => $step->unit,
                'success_message' => $step->success_message,
                'error_message' => $step->error_message,
                'reminder_message' => $step->reminder_message,
                'image_url' => $step->image_url,
                'image_alt' => $step->image_alt,
                'image_caption' => $step->image_caption,
                'image_source' => $step->image_source,
                'image_align' => $step->image_align,
                'image_max_width' => $step->image_max_width,
                'image_trigger' => $step->image_trigger,
            ]);

            foreach ($step->options as $opt) {
                ProblemStepOption::create([
                    'problem_step_id' => $newStep->id,
                    'option_text' => $opt->option_text,
                    'is_correct' => $opt->is_correct,
                    'order' => $opt->order,
                ]);
            }
        }

        session()->flash('message', 'Componente «' . $original->title . '» duplicado con éxito.');
    }

    public function deleteComponent(int $id)
    {
        $problem = Problem::findOrFail($id);
        $title = $problem->title;
        $problem->delete();
        session()->flash('message', 'Componente «' . $title . '» eliminado.');
    }

    // ==========================================
    // STEP MANAGER ACTIONS (IN-BUILDER)
    // ==========================================

    public function openAddStepModal()
    {
        $this->editingStepIndex = null;
        $nextNum = count($this->stepsData) + 1;
        
        $this->stepTitle = "Paso {$nextNum}";
        $this->stepInstruction = '';
        $this->stepAnswerType = 'numeric';
        $this->stepCorrectAnswer = '';
        $this->stepTolerance = 0.01;
        $this->stepToleranceType = 'absolute';
        $this->stepUnit = '';
        $this->stepSuccessMessage = '¡Correcto! Puedes continuar con el siguiente paso.';
        $this->stepErrorMessage = 'Respuesta incorrecta. Revisa tus cálculos.';
        $this->stepReminderMessage = '';

        $this->stepImageUrl = '';
        $this->stepImageAlt = '';
        $this->stepImageCaption = '';
        $this->stepImageSource = '';
        $this->stepImageAlign = 'align-center';
        $this->stepImageMaxWidth = '75%';
        $this->stepImageTrigger = 'always';

        $this->stepOptions = [
            ['option_text' => '', 'is_correct' => true],
            ['option_text' => '', 'is_correct' => false],
        ];

        $this->showStepModal = true;
    }

    public function openEditStepModal(int $index)
    {
        if (!isset($this->stepsData[$index])) return;

        $this->editingStepIndex = $index;
        $s = $this->stepsData[$index];

        $this->stepTitle = $s['title'] ?? '';
        $this->stepInstruction = $s['instruction'] ?? '';
        $this->stepAnswerType = $s['answer_type'] ?? 'numeric';
        $this->stepCorrectAnswer = $s['correct_answer'] ?? '';
        $this->stepTolerance = $s['tolerance'] ?? 0.01;
        $this->stepToleranceType = $s['tolerance_type'] ?? 'absolute';
        $this->stepUnit = $s['unit'] ?? '';
        $this->stepSuccessMessage = $s['success_message'] ?? '¡Correcto!';
        $this->stepErrorMessage = $s['error_message'] ?? 'Respuesta incorrecta.';
        $this->stepReminderMessage = $s['reminder_message'] ?? '';

        $this->stepImageUrl = $s['image_url'] ?? '';
        $this->stepImageAlt = $s['image_alt'] ?? '';
        $this->stepImageCaption = $s['image_caption'] ?? '';
        $this->stepImageSource = $s['image_source'] ?? '';
        $this->stepImageAlign = $s['image_align'] ?? 'align-center';
        $this->stepImageMaxWidth = $s['image_max_width'] ?? '75%';
        $this->stepImageTrigger = $s['image_trigger'] ?? 'always';

        $this->stepOptions = !empty($s['options']) ? $s['options'] : [
            ['option_text' => '', 'is_correct' => true],
            ['option_text' => '', 'is_correct' => false],
        ];

        $this->showStepModal = true;
    }

    public function saveStepModal()
    {
        $this->validate([
            'stepTitle' => 'required|string|max:255',
            'stepAnswerType' => 'required|in:numeric,multiple_choice,true_false,text',
        ]);

        if ($this->stepAnswerType === 'numeric') {
            $this->validate([
                'stepCorrectAnswer' => 'required|numeric',
            ]);
        } elseif ($this->stepAnswerType === 'multiple_choice') {
            $hasCorrect = collect($this->stepOptions)->contains(fn($o) => !empty($o['is_correct']));
            if (!$hasCorrect) {
                $this->addError('stepOptions', 'Debes marcar al menos una opción como correcta.');
                return;
            }
        } elseif ($this->stepAnswerType === 'true_false') {
            if ($this->stepCorrectAnswer === '') {
                $this->stepCorrectAnswer = '1';
            }
        } else {
            $this->validate([
                'stepCorrectAnswer' => 'required|string',
            ]);
        }

        $stepPayload = [
            'id' => ($this->editingStepIndex !== null && isset($this->stepsData[$this->editingStepIndex]['id']))
                ? $this->stepsData[$this->editingStepIndex]['id']
                : null,
            'step_number' => ($this->editingStepIndex !== null)
                ? ($this->editingStepIndex + 1)
                : (count($this->stepsData) + 1),
            'title' => $this->stepTitle,
            'instruction' => $this->stepInstruction,
            'answer_type' => $this->stepAnswerType,
            'correct_answer' => (string)$this->stepCorrectAnswer,
            'tolerance' => is_numeric($this->stepTolerance) ? (float)$this->stepTolerance : 0.01,
            'tolerance_type' => $this->stepToleranceType,
            'unit' => $this->stepUnit,
            'success_message' => $this->stepSuccessMessage,
            'error_message' => $this->stepErrorMessage,
            'reminder_message' => $this->stepReminderMessage,
            'image_url' => $this->stepImageUrl,
            'image_alt' => $this->stepImageAlt,
            'image_caption' => $this->stepImageCaption,
            'image_source' => $this->stepImageSource,
            'image_align' => $this->stepImageAlign,
            'image_max_width' => $this->stepImageMaxWidth,
            'image_trigger' => $this->stepImageTrigger,
            'options' => $this->stepOptions,
        ];

        if ($this->editingStepIndex !== null) {
            $this->stepsData[$this->editingStepIndex] = $stepPayload;
        } else {
            $this->stepsData[] = $stepPayload;
        }

        $this->showStepModal = false;
        $this->resetPreviewSimulator();
    }

    public function closeStepModal()
    {
        $this->showStepModal = false;
    }

    public function duplicateStep(int $index)
    {
        if (!isset($this->stepsData[$index])) return;

        $stepToCopy = $this->stepsData[$index];
        $cloned = $stepToCopy;
        $cloned['id'] = null;
        $cloned['title'] = $cloned['title'] . ' (Copia)';
        $cloned['step_number'] = count($this->stepsData) + 1;

        if (!empty($cloned['options'])) {
            foreach ($cloned['options'] as $k => $opt) {
                $cloned['options'][$k]['id'] = null;
            }
        }

        array_splice($this->stepsData, $index + 1, 0, [$cloned]);
        $this->reindexSteps();
        $this->resetPreviewSimulator();
    }

    public function deleteStep(int $index)
    {
        if (isset($this->stepsData[$index])) {
            array_splice($this->stepsData, $index, 1);
            $this->reindexSteps();
            $this->resetPreviewSimulator();
        }
    }

    public function moveStepUp(int $index)
    {
        if ($index > 0 && isset($this->stepsData[$index])) {
            $temp = $this->stepsData[$index - 1];
            $this->stepsData[$index - 1] = $this->stepsData[$index];
            $this->stepsData[$index] = $temp;
            $this->reindexSteps();
            $this->resetPreviewSimulator();
        }
    }

    public function moveStepDown(int $index)
    {
        if ($index < count($this->stepsData) - 1 && isset($this->stepsData[$index])) {
            $temp = $this->stepsData[$index + 1];
            $this->stepsData[$index + 1] = $this->stepsData[$index];
            $this->stepsData[$index] = $temp;
            $this->reindexSteps();
            $this->resetPreviewSimulator();
        }
    }

    private function reindexSteps()
    {
        foreach ($this->stepsData as $i => $step) {
            $this->stepsData[$i]['step_number'] = $i + 1;
        }
    }

    // Step Options helpers for Multiple Choice
    public function addOptionToActiveStep()
    {
        $this->stepOptions[] = [
            'id' => null,
            'option_text' => '',
            'is_correct' => false,
        ];
    }

    public function removeOptionFromActiveStep(int $index)
    {
        if (isset($this->stepOptions[$index])) {
            unset($this->stepOptions[$index]);
            $this->stepOptions = array_values($this->stepOptions);
        }
    }

    public function setOptionAsCorrect(int $index)
    {
        foreach ($this->stepOptions as $i => $opt) {
            $this->stepOptions[$i]['is_correct'] = ($i === $index);
        }
    }

    // ==========================================
    // REAL-TIME PREVIEW SIMULATOR METHODS
    // ==========================================

    public function resetPreviewSimulator()
    {
        $this->previewCurrentStep = 1;
        $this->previewAnswers = [];
        $this->previewMessages = [];
        $this->previewShowImages = [];
        $this->previewCompleted = false;

        foreach ($this->stepsData as $idx => $step) {
            $this->previewAnswers[$idx] = '';
        }
    }

    public function testPreviewStep(int $index)
    {
        if (!isset($this->stepsData[$index])) return;

        $step = $this->stepsData[$index];
        $userAnswer = $this->previewAnswers[$index] ?? null;
        $isOk = false;

        switch ($step['answer_type']) {
            case 'numeric':
                $userVal = (float)$userAnswer;
                $expVal = (float)$step['correct_answer'];
                $tol = (float)($step['tolerance'] ?? 0.01);
                
                if ($userAnswer !== null && $userAnswer !== '' && is_numeric($userAnswer)) {
                    if (($step['tolerance_type'] ?? 'absolute') === 'percentage') {
                        $pct = $tol > 1 ? ($tol / 100) : $tol;
                        $isOk = abs($userVal - $expVal) <= (abs($expVal) * $pct) || abs($userVal - $expVal) <= 0.0001;
                    } else {
                        $isOk = abs($userVal - $expVal) <= $tol || abs($userVal - $expVal) <= (abs($expVal) * 0.02);
                    }
                }
                break;

            case 'multiple_choice':
                if (!empty($step['options'])) {
                    foreach ($step['options'] as $optIdx => $opt) {
                        if ($opt['is_correct'] && (string)$optIdx === (string)$userAnswer) {
                            $isOk = true;
                            break;
                        }
                    }
                }
                break;

            case 'true_false':
                $userNorm = strtolower(trim((string)$userAnswer));
                $expNorm = strtolower(trim((string)$step['correct_answer']));
                $userBool = in_array($userNorm, ['1', 'true', 'verdadero', 'v', 't']);
                $expBool = in_array($expNorm, ['1', 'true', 'verdadero', 'v', 't']);
                $isOk = ($userBool === $expBool) && ($userNorm !== '');
                break;

            case 'text':
            default:
                $userNorm = mb_strtolower(trim((string)$userAnswer));
                $expNorm = mb_strtolower(trim((string)$step['correct_answer']));
                $isOk = ($userNorm !== '' && $userNorm === $expNorm);
                break;
        }

        if ($isOk) {
            $this->previewMessages[$index] = [
                'ok' => true,
                'text' => $step['success_message'] ?: '¡Correcto! Puedes continuar con el siguiente paso.',
                'reminder' => null,
            ];

            if (($step['image_trigger'] ?? 'always') === 'on_success') {
                $this->previewShowImages[$index] = true;
            } elseif (($step['image_trigger'] ?? 'always') === 'on_error') {
                $this->previewShowImages[$index] = false;
            }

            if ($index + 1 >= count($this->stepsData)) {
                $this->previewCompleted = true;
                $this->previewCurrentStep = count($this->stepsData) + 1;
            } else {
                $this->previewCurrentStep = max($this->previewCurrentStep, $index + 2);
            }
        } else {
            $this->previewMessages[$index] = [
                'ok' => false,
                'text' => $step['error_message'] ?: 'Respuesta incorrecta. Revisa tus cálculos.',
                'reminder' => $step['reminder_message'] ?? '',
            ];

            if (($step['image_trigger'] ?? 'always') === 'on_error') {
                $this->previewShowImages[$index] = true;
            }
        }
    }

    public function render()
    {
        $modules = Module::with(['items.problems.steps'])
            ->orderBy('order')
            ->get();

        $query = Problem::with(['moduleItem.module', 'steps.options'])
            ->orderBy('module_item_id')
            ->orderBy('order');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->moduleFilter)) {
            $query->whereHas('moduleItem', function($q) {
                $q->where('module_id', $this->moduleFilter);
            });
        }

        $problemsList = $query->get();

        return view('livewire.admin.componentes-panel', [
            'modules' => $modules,
            'problemsList' => $problemsList,
        ]);
    }
}
