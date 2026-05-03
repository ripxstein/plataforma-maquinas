<?php

namespace App\Livewire\Modulos;

use Livewire\Component;
use App\Models\Module;
use App\Models\UserItemProgress;
use App\Models\UserModuleProgress;

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
        'problema-completado' => 'completeCurrentProblem',
    ];

    public function mount(string $slug)
    {
        $this->module = Module::where('slug', $slug)
            ->with('items')
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

    public function isCompleted($itemId): bool
    {
        return UserItemProgress::where('user_id', auth()->id())
            ->where('module_item_id', $itemId)
            ->where('completed', true)
            ->exists();
    }

    public function completeItem($itemId)
    {
        $item = $this->items->firstWhere('id', $itemId);

        if (!$item) {
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

    public function completeCurrentProblem($itemId = null)
    {
        if (!$itemId) {
            return;
        }

        $this->completeItem($itemId);
    }

    public function goBack()
    {
        if ($this->moduleProgress->unlocked_order > 1) {
            $this->moduleProgress->update([
                'unlocked_order' => $this->moduleProgress->unlocked_order - 1,
            ]);

            $this->moduleProgress->refresh();
            $this->loadProgress();
        }
    }

    private function calculateProgress()
    {
        $this->totalProgress = 0;
        $this->readingProgress = 0;
        $this->problemProgress = 0;

        $readingTotal = $this->items->where('type', 'lectura')->sum('percentage');
        $problemTotal = $this->items->where('type', 'problema')->sum('percentage');

        $readingCompleted = 0;
        $problemCompleted = 0;

        foreach ($this->items as $item) {
            if ($this->isCompleted($item->id)) {
                $this->totalProgress += $item->percentage;

                if ($item->type === 'lectura') {
                    $readingCompleted += $item->percentage;
                }

                if ($item->type === 'problema') {
                    $problemCompleted += $item->percentage;
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

    public function render()
    {
        return view('livewire.modulos.modulo-viewer');
    }
}