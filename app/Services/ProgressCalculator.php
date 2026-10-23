<?php

namespace App\Services;

use App\Models\ModuleItem;
use App\Models\Problem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProgressCalculator
{
    public function percentage(float $completed, float $total, int $empty = 0): int
    {
        return $total > 0 ? (int) min(100, max(0, round(100 * $completed / $total))) : $empty;
    }

    public function module(Collection $items, array $readings, array $problems): array
    {
        $exercises = $items->flatMap(fn ($item) => $item->problems)
            ->where('is_active', true)->where('is_example', false);
        $readingWeight = $items->sum('percentage');
        $problemWeight = $exercises->sum('percentage');
        $read = $items->whereIn('id', $readings)->sum('percentage');
        $solved = $exercises->whereIn('id', $problems)->sum('percentage');

        return [
            'total' => $this->percentage($read + $solved, $readingWeight + $problemWeight),
            'readings' => $this->percentage($read, $readingWeight, 100),
            'problems' => $this->percentage($solved, $problemWeight, 100),
        ];
    }

    public function catalogWeight(): float
    {
        return ModuleItem::sum('percentage')
            + Problem::where('is_active', true)->where('is_example', false)->sum('percentage');
    }

    /** Subconsultas agregadas: el número de consultas no crece con los alumnos. */
    public function withUserProgress(Builder $users, bool $includeWeights = true): Builder
    {
        $readings = DB::table('user_item_progress as progress')
            ->join('module_items as item', 'item.id', '=', 'progress.module_item_id')
            ->whereColumn('progress.user_id', 'users.id')->where('progress.completed', true);
        $problems = DB::table('user_problem_progress as progress')
            ->join('problems as problem', 'problem.id', '=', 'progress.problem_id')
            ->whereColumn('progress.user_id', 'users.id')->where('progress.completed', true)
            ->where('problem.is_active', true)->where('problem.is_example', false);

        $users->addSelect([
            'lecturas_completadas' => (clone $readings)->selectRaw('COUNT(*)'),
            'problemas_completados' => (clone $problems)->selectRaw('COUNT(*)'),
        ]);
        if ($includeWeights) {
            $users->addSelect([
                'reading_weight' => (clone $readings)->selectRaw('COALESCE(SUM(item.percentage), 0)'),
                'problem_weight' => (clone $problems)->selectRaw('COALESCE(SUM(problem.percentage), 0)'),
            ]);
        }

        return $users;
    }
}
