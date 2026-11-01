<?php

namespace App\Livewire\Student;

use App\Models\Module;
use App\Models\UserItemProgress;
use App\Models\UserModuleProgress;
use App\Models\UserProblemProgress;
use App\Services\ModuleCacheService;
use App\Services\ProgressCalculator;
use App\Services\StudentActivitySummary;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class StudentDashboard extends Component
{
    public function render()
    {
        $modules = collect(Cache::get('sidebar_modules') ?? Module::orderBy('order')->orderBy('id')
            ->get(['id', 'title', 'slug', 'order'])->toArray())->map(fn ($module) => (object) $module);
        $moduleItems = $modules->mapWithKeys(fn ($module) => [
            $module->id => ModuleCacheService::getModuleItemsWithProblems($module->id),
        ]);
        $items = $moduleItems->flatten(1);
        $itemIds = $items->pluck('id');
        $problemIds = $items->flatMap(fn ($item) => $item->problems)->pluck('id');
        $userId = auth()->id();
        $readingProgress = UserItemProgress::where('user_id', $userId)->where('completed', true)
            ->whereIn('module_item_id', $itemIds)->get(['module_item_id', 'updated_at']);
        $problemProgress = UserProblemProgress::where('user_id', $userId)->where('completed', true)
            ->whereIn('problem_id', $problemIds)->get(['problem_id', 'updated_at']);
        $completedItems = $readingProgress->pluck('module_item_id')->map(fn ($id) => (int) $id)->all();
        $completedProblems = $problemProgress->pluck('problem_id')->map(fn ($id) => (int) $id)->all();
        $unlockedOrders = UserModuleProgress::where('user_id', $userId)
            ->whereIn('module_id', $modules->pluck('id'))->pluck('unlocked_order', 'module_id');
        $summaryService = app(StudentActivitySummary::class);
        $next = null;
        $itemsById = $items->keyBy('id');
        $problemsById = $items->flatMap(fn ($item) => $item->problems)->keyBy('id');
        $lastReading = $readingProgress->sortByDesc('updated_at')->first();
        $lastProblem = $problemProgress->sortByDesc('updated_at')->first();
        $lastModuleId = $lastProblem && (! $lastReading || $lastProblem->updated_at >= $lastReading->updated_at)
            ? $itemsById[$problemsById[$lastProblem->problem_id]->module_item_id]->module_id
            : ($lastReading ? $itemsById[$lastReading->module_item_id]->module_id : null);
        $orderedModules = $modules->sortBy(fn ($module) => [
            (int) $module->id === (int) $lastModuleId ? 0 : 1, $module->order, $module->id,
        ]);

        foreach ($orderedModules as $module) {
            $summary = $summaryService->forModule(
                $moduleItems[$module->id], $completedItems, $completedProblems,
                (int) ($unlockedOrders[$module->id] ?? 1)
            );
            if ($summary['next']) {
                $next = $summary['next'] + ['module_title' => $module->title, 'module_slug' => $module->slug];
                break;
            }
        }

        $summary = $summaryService->forModule($items, $completedItems, $completedProblems, PHP_INT_MAX);
        $progress = app(ProgressCalculator::class)->module($items, $completedItems, $completedProblems);

        return view('livewire.student.student-dashboard', compact('next', 'summary', 'progress'));
    }
}
