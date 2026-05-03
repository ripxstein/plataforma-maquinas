<?php

namespace App\Livewire\Modulos;

use App\Models\Module;
use App\Models\UserItemProgress;
use App\Models\UserModuleProgress;
use App\Models\UserProblemProgress;
use Livewire\Component;

class ModuloViewer extends Component
{
    public Module $module;

    public $items;

    public $visibleItems;

    public UserModuleProgress $moduleProgress;

    public int $totalProgress = 0;

    public int $readingProgress = 0;

    public int $problemProgress = 0;

    protected $listeners = [
        'problema-completado' => 'completeProblem',
    ];

    public function mount(string $slug)
    {
        $this->module = Module::where('slug', $slug)
            ->with('items.problems')
            ->firstOrFail();

        $this->moduleProgress = UserModuleProgress::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'module_id' => $this->module->id,
            ],
            [
                'unlocked_order' => 1,
            ]
        );

        $this->loadProgress();
    }

    public function loadProgress()
    {
        $this->items = $this->module->items;

        $this->visibleItems = $this->items
            ->where('order', '<=', $this->moduleProgress->unlocked_order);

        $this->calculateProgress();
    }

    public function isReadingCompleted($itemId): bool
    {
        return UserItemProgress::where('user_id', auth()->id())
            ->where('module_item_id', $itemId)
            ->where('completed', true)
            ->exists();
    }

    public function isProblemCompleted($problemId): bool
    {
        return UserProblemProgress::where('user_id', auth()->id())
            ->where('problem_id', $problemId)
            ->where('completed', true)
            ->exists();
    }

    public function completeReading($itemId)
    {
        $item = $this->items->firstWhere('id', $itemId);

        if (! $item) {
            return;
        }

        UserItemProgress::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'module_item_id' => $item->id,
            ],
            [
                'completed' => true,
                'completed_at' => now(),
            ]
        );

        $nextOrder = $item->order + 1;

        if ($nextOrder > $this->moduleProgress->unlocked_order) {
            $this->moduleProgress->update([
                'unlocked_order' => $nextOrder,
            ]);

            $this->moduleProgress->refresh();
        }

        $this->loadProgress();
    }

    public function completeProblem($problemId)
    {
        UserProblemProgress::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'problem_id' => $problemId,
            ],
            [
                'completed' => true,
                'score' => 100,
                'completed_at' => now(),
            ]
        );

        $this->loadProgress();
    }

    private function calculateProgress()
    {
        $this->totalProgress = 0;
        $readingCompleted = 0;
        $problemCompleted = 0;

        $readingTotal = $this->items->sum('percentage');

        $allProblems = $this->items->flatMap(function ($item) {
            return $item->problems;
        });

        $problemTotal = $allProblems->sum('percentage');

        foreach ($this->items as $item) {
            if ($this->isReadingCompleted($item->id)) {
                $this->totalProgress += $item->percentage;
                $readingCompleted += $item->percentage;
            }

            foreach ($item->problems as $problem) {
                if ($this->isProblemCompleted($problem->id)) {
                    $this->totalProgress += $problem->percentage;
                    $problemCompleted += $problem->percentage;
                }
            }
        }

        $this->readingProgress = $readingTotal > 0
            ? round(($readingCompleted / $readingTotal) * 100)
            : 100;

        $this->problemProgress = $problemTotal > 0
            ? round(($problemCompleted / $problemTotal) * 100)
            : 100;
    }

    public function getUnlockedProblemOrder($moduleItemId): int
    {
        $problems = $this->items
            ->firstWhere('id', $moduleItemId)
            ?->problems;

        if (! $problems || $problems->isEmpty()) {
            return 0;
        }

        foreach ($problems as $problem) {
            if (! $this->isProblemCompleted($problem->id)) {
                return $problem->order;
            }
        }

        return $problems->max('order');
    }

    public function render()
    {
        return view('livewire.modulos.modulo-viewer');
    }
}
