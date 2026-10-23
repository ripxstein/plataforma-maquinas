<?php

namespace App\Livewire\Problemas\Concerns;

use App\Models\Problem;
use App\Services\AnswerEvaluator;
use App\Services\LearningProgressService;

trait ValidatesLegacyProgress
{
    private function assertCurrentStep(int $step): void
    {
        abort_unless(auth()->check() && $this->currentStep === $step && ! $this->askNextStep, 403);
        $problem = Problem::findOrFail($this->problemId);
        abort_unless($problem->component === $this->getName(), 403);
        app(LearningProgressService::class)->assertProblemAvailable(auth()->id(), $problem);
    }

    private function completeValidatedProblem(): void
    {
        app(LearningProgressService::class)->completeProblem(auth()->id(), $this->problemId, $this->getName());
        $this->dispatch('problema-completado', problemId: $this->problemId);
    }

    private function approxEqual($userValue, $expected, $tolAbs, $tolPct): bool
    {
        return app(AnswerEvaluator::class)->numeric($userValue, $expected, $tolAbs, $tolPct);
    }
}
