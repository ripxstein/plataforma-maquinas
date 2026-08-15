<?php

namespace App\Livewire\Problemas;

use App\Models\Problem;
use App\Models\ProblemStep;
use Livewire\Component;

class ProblemaDinamico extends Component
{
    public $problemId;
    public $problem;
    public $steps;
    
    public int $currentStepIndex = 1;
    public array $answers = [];
    public array $messages = [];
    public array $showTriggerImages = [];
    public bool $isCompleted = false;

    public function mount($problemId)
    {
        $this->problemId = $problemId;
        $this->loadProblem();
    }

    public function loadProblem()
    {
        $this->problem = Problem::with(['steps.options' => function ($query) {
            $query->orderBy('order');
        }])->findOrFail($this->problemId);

        $this->steps = $this->problem->steps;

        // Initialize answers
        foreach ($this->steps as $step) {
            if (!isset($this->answers[$step->id])) {
                $this->answers[$step->id] = '';
            }
        }
    }

    public function approxEqual($userValue, $expected, $tolerance, $toleranceType = 'absolute'): bool
    {
        if ($userValue === null || $userValue === '' || !is_numeric($userValue)) {
            return false;
        }

        $userVal = (float) $userValue;
        $expVal = (float) $expected;
        $tol = (float) ($tolerance ?? 0.01);

        if ($toleranceType === 'percentage') {
            // E.g. tol = 2% or tol = 0.02
            $pct = $tol > 1 ? ($tol / 100) : $tol;
            return abs($userVal - $expVal) <= (abs($expVal) * $pct) || abs($userVal - $expVal) <= 0.0001;
        }

        // Default: absolute tolerance with a small proportional fallback
        return abs($userVal - $expVal) <= $tol || abs($userVal - $expVal) <= (abs($expVal) * 0.02);
    }

    public function checkStep(int $stepId)
    {
        $step = $this->steps->firstWhere('id', $stepId);
        if (!$step) {
            return;
        }

        $userAnswer = $this->answers[$stepId] ?? null;
        $isOk = false;

        switch ($step->answer_type) {
            case 'numeric':
                $isOk = $this->approxEqual(
                    $userAnswer,
                    $step->correct_answer,
                    $step->tolerance,
                    $step->tolerance_type ?? 'absolute'
                );
                break;

            case 'multiple_choice':
                $selectedOption = $step->options->firstWhere('id', $userAnswer);
                $isOk = $selectedOption && $selectedOption->is_correct;
                break;

            case 'true_false':
                $normalizedUser = strtolower(trim((string)$userAnswer));
                $normalizedExpected = strtolower(trim((string)$step->correct_answer));
                
                // Support true/false, 1/0, v/f, verdadero/falso
                $userBool = in_array($normalizedUser, ['1', 'true', 'verdadero', 'v', 't']);
                $expectedBool = in_array($normalizedExpected, ['1', 'true', 'verdadero', 'v', 't']);
                
                $isOk = ($userBool === $expectedBool) && ($normalizedUser !== '');
                break;

            case 'text':
            default:
                $normalizedUser = mb_strtolower(trim((string)$userAnswer));
                $normalizedExpected = mb_strtolower(trim((string)$step->correct_answer));
                $isOk = ($normalizedUser !== '' && $normalizedUser === $normalizedExpected);
                break;
        }

        if ($isOk) {
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

            // Find current position in list
            $currentIndex = $this->steps->search(fn($s) => $s->id === $stepId);
            $nextIndex = $currentIndex !== false ? $currentIndex + 2 : $this->currentStepIndex + 1;

            if ($nextIndex > count($this->steps)) {
                $this->isCompleted = true;
                $this->currentStepIndex = count($this->steps) + 1;
                $this->dispatch('problema-completado', problemId: $this->problemId);
            } else {
                $this->currentStepIndex = max($this->currentStepIndex, $nextIndex);
            }
        } else {
            $errorText = $step->error_message ?: 'Respuesta incorrecta. Revisa tus cálculos.';
            $this->messages[$stepId] = [
                'ok' => false,
                'text' => $errorText,
                'reminder' => $step->reminder_message,
            ];

            if ($step->image_trigger === 'on_error') {
                $this->showTriggerImages[$stepId] = true;
            }
        }
    }

    public function render()
    {
        return view('livewire.problemas.problema-dinamico');
    }
}
