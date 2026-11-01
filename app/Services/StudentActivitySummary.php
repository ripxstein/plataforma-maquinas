<?php

namespace App\Services;

use Illuminate\Support\Collection;

class StudentActivitySummary
{
    public function forModule(Collection $items, array $completedItems, array $completedProblems, int $unlockedOrder): array
    {
        $readings = array_fill_keys($completedItems, true);
        $problems = array_fill_keys($completedProblems, true);
        $orderedItems = $items->sortBy([['order', 'asc'], ['id', 'asc']]);
        $activeProblems = $items->flatMap(fn ($item) => $item->problems)->where('is_active', true);
        $exercises = $activeProblems->where('is_example', false);
        $next = null;
        $previous = null;

        foreach ($orderedItems as $item) {
            if ($item->order > $unlockedOrder || ($previous &&
                (! isset($readings[$previous->id]) ||
                    $previous->problems->where('is_active', true)
                        ->contains(fn ($problem) => ! isset($problems[$problem->id]))))) {
                break;
            }

            if (! isset($readings[$item->id])) {
                $next = ['type' => 'lectura', 'title' => $item->title, 'item_id' => $item->id];
                break;
            }

            foreach ($item->problems->where('is_active', true)->sortBy([['order', 'asc'], ['id', 'asc']]) as $problem) {
                if (! isset($problems[$problem->id]) &&
                    app(LearningProgressService::class)->isProblemUnlocked($problem, $item->problems, $completedProblems)) {
                    $next = [
                        'type' => $problem->is_example ? 'ejemplo' : 'problema',
                        'title' => $problem->title,
                        'item_id' => $item->id,
                        'problem_id' => $problem->id,
                    ];
                    break 2;
                }
            }

            $previous = $item;
        }

        return [
            'readings_completed' => $orderedItems->filter(fn ($item) => isset($readings[$item->id]))->count(),
            'readings_total' => $orderedItems->count(),
            'exercises_pending' => $exercises->filter(fn ($problem) => ! isset($problems[$problem->id]))->count(),
            'examples_pending' => $activeProblems->where('is_example', true)
                ->filter(fn ($problem) => ! isset($problems[$problem->id]))->count(),
            'next' => $next,
        ];
    }

    public function problemLockReason($problem, Collection $siblings, array $completedProblems): string
    {
        $completed = array_fill_keys($completedProblems, true);
        $ordered = $siblings->where('is_active', true)->sortBy([['order', 'asc'], ['id', 'asc']]);

        foreach ($ordered as $sibling) {
            $required = (! $problem->is_example && $sibling->is_example)
                || ($sibling->is_example === $problem->is_example
                    && [$sibling->order, $sibling->id] < [$problem->order, $problem->id]);

            if ($required && ! isset($completed[$sibling->id])) {
                return $sibling->is_example
                    ? 'Revisa primero el ejemplo anterior de esta lectura.'
                    : 'Completa primero el problema anterior de esta lectura.';
            }
        }

        return 'Completa primero la lectura y las actividades anteriores.';
    }
}
