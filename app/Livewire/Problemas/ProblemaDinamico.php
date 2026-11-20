<?php

namespace App\Livewire\Problemas;

use App\Models\Problem;
use App\Models\ProblemStep;
use App\Models\UserProblemProgress;
use App\Services\AnswerEvaluator;
use App\Services\LearningProgressService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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
    public bool $showCompletedAttempt = false;

    #[Locked]
    public string $definitionHash = '';

    public array $answers = [];

    public array $messages = [];

    public array $showTriggerImages = [];

    public function mount($problemId)
    {
        $this->problemId = $problemId;
        $this->loadProblem();
        $this->restoreMarkerOrigin();
    }

    /** Conserva solo el intento de esta navegación; no escribe progreso ni puntajes. */
    public function rememberMarkerOrigin(): string
    {
        abort_unless(auth()->check() && $this->problem, 403);
        app(LearningProgressService::class)->assertProblemAvailable(auth()->id(), $this->problem);
        abort_unless(hash_equals($this->definitionHash, $this->currentDefinitionHash()), 409);
        $token = (string) Str::uuid();
        Cache::put('marker_origin_'.auth()->id().'_'.$token, [
            'problemId' => $this->problemId,
            'definitionHash' => $this->definitionHash,
            'currentStepIndex' => $this->currentStepIndex,
            'isCompleted' => $this->isCompleted,
            'showCompletedAttempt' => $this->showCompletedAttempt,
            'answers' => $this->answers,
            'messages' => $this->messages,
            'showTriggerImages' => $this->showTriggerImages,
        ], now()->addMinutes(30));

        return $token;
    }

    private function restoreMarkerOrigin(): void
    {
        $token = request()->query('marker_return');
        if (! auth()->check() || ! is_string($token) || ! Str::isUuid($token) || ! $this->problem) {
            return;
        }
        $state = Cache::get('marker_origin_'.auth()->id().'_'.$token);
        if (! is_array($state) || (int) $state['problemId'] !== (int) $this->problemId
            || ! hash_equals($this->definitionHash, $state['definitionHash'])
            || ($this->problem->is_example && $this->isCompleted && ! $state['isCompleted'])) {
            return;
        }
        app(LearningProgressService::class)->assertProblemAvailable(auth()->id(), $this->problem);
        foreach (['currentStepIndex', 'isCompleted', 'showCompletedAttempt', 'answers', 'messages', 'showTriggerImages'] as $field) {
            $this->{$field} = $state[$field];
        }
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
            if ($this->problem->is_example) {
                foreach ($this->steps as $step) {
                    $this->messages[$step->id] = ['ok' => true, 'text' => '', 'reminder' => null];
                }
            }
        }
    }

    public function restartProblem(): void
    {
        abort_unless(auth()->check() && $this->problem && ! $this->problem->is_example, 403);
        abort_unless(($this->problem->component ?: 'problemas.problema-dinamico') === 'problemas.problema-dinamico', 403);
        app(LearningProgressService::class)->assertProblemAvailable(auth()->id(), $this->problem);
        abort_unless(UserProblemProgress::where('user_id', auth()->id())
            ->where('problem_id', $this->problemId)->where('completed', true)->exists(), 403);

        $this->definitionHash = $this->currentDefinitionHash();
        $this->currentStepIndex = 1;
        $this->isCompleted = false;
        $this->showCompletedAttempt = false;
        $this->answers = [];
        $this->messages = [];
        $this->showTriggerImages = [];
        foreach ($this->steps as $step) {
            $this->answers[$step->id] = '';
        }
        $this->dispatch('render-math');
    }

    private function currentDefinitionHash(): string
    {
        return hash('sha256', json_encode([$this->problem->getAttributes(), $this->steps->toArray()]));
    }

    /**
     * @return array{0: ProblemStep, 1: bool} Paso solicitado y si ya había sido procesado.
     */
    private function expectedStep(int $stepId, bool $example): array
    {
        abort_unless(auth()->check() && $this->problem, 403);
        abort_unless(($this->problem->component ?: 'problemas.problema-dinamico') === 'problemas.problema-dinamico', 403);
        app(LearningProgressService::class)->assertProblemAvailable(auth()->id(), $this->problem);
        abort_unless(hash_equals($this->definitionHash, $this->currentDefinitionHash()), 409, 'El problema cambió. Recarga la página para continuar.');
        abort_unless($this->problem->is_example === $example, 403);

        $requestedIndex = $this->steps->search(
            fn (ProblemStep $step): bool => (int) $step->id === $stepId
        );
        abort_unless($requestedIndex !== false, 403);

        $step = $this->steps->get($requestedIndex);
        $requestedStepIndex = $requestedIndex + 1;

        // En ejemplos guiados una petición puede llegar tarde después de que Livewire
        // ya avanzó. Confirmarla como atendida evita un 403 sin volver a avanzar ni
        // registrar progreso. Los pasos futuros continúan estrictamente prohibidos.
        if ($example && $requestedStepIndex < $this->currentStepIndex) {
            return [$step, true];
        }

        abort_unless(! $this->isCompleted && $requestedStepIndex === $this->currentStepIndex, 403);

        return [$step, false];
    }

    public function nextExampleStep(int $stepId): void
    {
        [, $alreadyProcessed] = $this->expectedStep($stepId, true);
        if ($alreadyProcessed) {
            $this->dispatch('render-math');

            return;
        }

        $this->advance();
        $this->messages[$stepId] = ['ok' => true, 'text' => 'Paso revisado.', 'reminder' => null];
        $this->dispatch('render-math');
    }

    public function checkStep(int $stepId): void
    {
        [$step] = $this->expectedStep($stepId, false);
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
            if (! $this->problem->is_example) {
                $this->showCompletedAttempt = true;
            }
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
