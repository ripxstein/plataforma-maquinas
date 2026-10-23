<?php

namespace App\Livewire\Problemas;

use App\Models\Problem;
use App\Models\ProblemStep;
use App\Models\UserProblemProgress;
use App\Services\AnswerEvaluator;
use App\Services\LearningProgressService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProblemaDinamico extends Component
{
    #[Locked]
    public $problemId;

    #[Locked]
    public int $currentStepIndex = 1;

    #[Locked]
    public bool $isCompleted = false;

    #[Locked]
    public string $definitionHash = '';

    public array $answers = [];

    public array $messages = [];

    public array $showTriggerImages = [];

    public function mount($problemId)
    {
        $this->problemId = $problemId;
        $this->loadProblem();
    }

    #[Computed]
    public function problem(): ?Problem
    {
        return Problem::with('steps.options')->find($this->problemId);
    }

    #[Computed]
    public function steps()
    {
        return $this->problem?->steps->sortBy([['step_number', 'asc'], ['id', 'asc']])->values() ?? collect();
    }

    protected function loadProblem(): void
    {
        if (! $this->problem) {
            return;
        }
        $this->definitionHash = $this->currentDefinitionHash();
        foreach ($this->steps as $step) {
            $this->answers[$step->id] = '';
        }

        if (auth()->check() && UserProblemProgress::where('user_id', auth()->id())
            ->where('problem_id', $this->problemId)->where('completed', true)->exists()) {
            $this->isCompleted = true;
            $this->currentStepIndex = $this->steps->count() + 1;
            foreach ($this->steps as $step) {
                $this->messages[$step->id] = ['ok' => true, 'text' => '', 'reminder' => null];
            }
        }
    }

    private function currentDefinitionHash(): string
    {
        return hash('sha256', json_encode([$this->problem->getAttributes(), $this->steps->toArray()]));
    }

    private function expectedStep(int $stepId, bool $example): ProblemStep
    {
        abort_unless(auth()->check() && $this->problem, 403);
        abort_unless(($this->problem->component ?: 'problemas.problema-dinamico') === 'problemas.problema-dinamico', 403);
        app(LearningProgressService::class)->assertProblemAvailable(auth()->id(), $this->problem);
        abort_unless(hash_equals($this->definitionHash, $this->currentDefinitionHash()), 409, 'El problema cambió. Recarga la página para continuar.');
        $step = $this->steps->get($this->currentStepIndex - 1);
        abort_unless(! $this->isCompleted && $this->problem->is_example === $example && $step && $step->id === $stepId, 403);

        return $step;
    }

    public function nextExampleStep(int $stepId)
    {
        $this->expectedStep($stepId, true);
        $this->advance();
        $this->messages[$stepId] = ['ok' => true, 'text' => 'Paso revisado.', 'reminder' => null];
        $this->dispatch('render-math');
    }

    public function checkStep(int $stepId)
    {
        $step = $this->expectedStep($stepId, false);
        $isOk = app(AnswerEvaluator::class)->evaluate(
            $step->getAttributes(), $this->answers[$stepId] ?? null, $step->options->keyBy('id')->toArray()
        );

        if ($isOk) {
            $this->advance();
            $this->messages[$stepId] = [
                'ok' => true,
                'text' => $step->success_message ?: '¡Correcto! Puedes continuar con el siguiente paso.',
                'reminder' => null,
            ];
            if ($step->image_trigger === 'on_success') {
                $this->showTriggerImages[$stepId] = true;
            } elseif ($step->image_trigger === 'on_error') {
                $this->showTriggerImages[$stepId] = false;
            }
        } else {
            $this->messages[$stepId] = [
                'ok' => false,
                'text' => $step->error_message ?: 'Respuesta incorrecta. Revisa tus cálculos.',
                'reminder' => $step->reminder_message,
            ];
            if ($step->image_trigger === 'on_error') {
                $this->showTriggerImages[$stepId] = true;
            }
        }
        $this->dispatch('render-math');
    }

    private function advance(): void
    {
        if ($this->currentStepIndex === $this->steps->count()) {
            app(LearningProgressService::class)->completeProblem(auth()->id(), $this->problemId, 'problemas.problema-dinamico');
            $this->isCompleted = true;
            $this->dispatch('problema-completado', problemId: $this->problemId);
        }
        $this->currentStepIndex++;
    }

    public function render()
    {
        if (! $this->problem) {
            return '<div></div>';
        }

        return view('livewire.problemas.problema-dinamico', ['problem' => $this->problem, 'steps' => $this->steps]);
    }
}
