<?php

namespace App\Services;

use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\UserItemProgress;
use App\Models\UserModuleProgress;
use App\Models\UserProblemProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LearningProgressService
{
    public function completeReading(int $userId, int $moduleId, int $itemId): int
    {
        $order = DB::transaction(function () use ($userId, $moduleId, $itemId) {
            $progress = UserModuleProgress::where('user_id', $userId)
                ->where('module_id', $moduleId)->lockForUpdate()->firstOrFail();
            $item = ModuleItem::where('module_id', $moduleId)->findOrFail($itemId);
            abort_unless($item->order <= $progress->unlocked_order, 403);

            UserItemProgress::updateOrCreate(
                ['user_id' => $userId, 'module_item_id' => $item->id],
                ['completed' => true, 'completed_at' => now()]
            );

            if ($item->order + 1 > $progress->unlocked_order) {
                $progress->update(['unlocked_order' => $item->order + 1]);
            }

            return (int) $progress->unlocked_order;
        });

        $this->forgetProgress($userId, $moduleId);

        return $order;
    }

    /** Verifica contra la BD; el estado del navegador y la caché no autorizan avances. */
    public function assertProblemAvailable(int $userId, Problem $problem): int
    {
        abort_unless($problem->is_active, 403);
        $item = ModuleItem::whereKey($problem->module_item_id)
            ->whereHas('progress', fn ($query) => $query->where('user_id', $userId)->where('completed', true))
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('user_module_progress')
                ->where('user_id', $userId)->whereColumn('module_id', 'module_items.module_id')
                ->whereColumn('unlocked_order', '>=', 'module_items.order'))
            ->first(['id', 'module_id', 'order']);
        abort_unless($item, 403);

        $siblings = $item->activeProblems()->select('id', 'order', 'is_active', 'is_example')
            ->addSelect(['completed_by_user' => UserProblemProgress::selectRaw('1')
                ->whereColumn('problem_id', 'problems.id')->where('user_id', $userId)->where('completed', true)->limit(1)])
            ->orderBy('id')->get();
        $completed = $siblings->where('completed_by_user', 1)->pluck('id')->all();
        abort_unless($this->isProblemUnlocked($problem, $siblings, $completed), 403);

        return (int) $item->module_id;
    }

    public function isProblemUnlocked(Problem $problem, Collection $siblings, array $completed): bool
    {
        $ordered = $siblings->where('is_active', true)->sortBy([['order', 'asc'], ['id', 'asc']]);
        foreach ($ordered as $sibling) {
            $required = (! $problem->is_example && $sibling->is_example)
                || ($sibling->is_example === $problem->is_example
                    && [$sibling->order, $sibling->id] < [$problem->order, $problem->id]);
            if ($required && ! in_array($sibling->id, $completed)) {
                return false;
            }
        }

        return $problem->is_active;
    }

    /** Invocar únicamente desde el último paso validado del evaluador, nunca desde un evento del cliente. */
    public function completeProblem(int $userId, int $problemId, string $component): void
    {
        $moduleId = DB::transaction(function () use ($userId, $problemId, $component) {
            $problem = Problem::lockForUpdate()->findOrFail($problemId);
            abort_unless(($problem->component ?: 'problemas.problema-dinamico') === $component, 403);
            $moduleId = $this->assertProblemAvailable($userId, $problem);
            $progress = UserProblemProgress::firstOrNew(['user_id' => $userId, 'problem_id' => $problemId]);
            if (! $progress->completed) {
                $progress->fill(['completed' => true, 'score' => 100, 'completed_at' => now()])->save();
            }

            return $moduleId;
        });
        $this->forgetProgress($userId, $moduleId);
    }

    public function forgetProgress(int $userId, int $moduleId): void
    {
        Cache::forget(ModuleCacheService::progressKey($userId, $moduleId));
    }
}
