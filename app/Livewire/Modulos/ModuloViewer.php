<?php

namespace App\Livewire\Modulos;

use App\Models\Module;
use App\Models\UserItemProgress;
use App\Models\UserModuleProgress;
use App\Models\UserProblemProgress;
use App\Services\ModuleCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    /** @var array<int> */
    public array $completedItemIds = [];

    /** @var array<int> */
    public array $completedProblemIds = [];

    protected $listeners = [
        'problema-completado' => 'completeProblem',
    ];

    public function mount(string $slug)
    {
        // Cargamos el módulo desde caché o BD (ahorra ~1.6s de round-trip en BD)
        $this->module = ModuleCacheService::getModuleBySlug($slug)
            ?? Module::where('slug', $slug)->firstOrFail();

        // Items y problems desde cache (evita 2 round-trips a BD remota)
        $this->hydrateModuleRelations();

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

    /**
     * Carga items y problems desde cache mediante ModuleCacheService.
     * Evita 2 queries lentas a BD remota en cada request/rehydration de Livewire.
     */
    private function hydrateModuleRelations(): void
    {
        $items = ModuleCacheService::getModuleItemsWithProblems($this->module->id);
        $this->module->setRelation('items', $items);
    }

    public function loadProgress()
    {
        // En rehydrations de Livewire la relacion no persiste; la recargamos desde cache
        if (! $this->module->relationLoaded('items')) {
            $this->hydrateModuleRelations();
        }

        $this->items = $this->module->items;

        $this->visibleItems = $this->items
            ->where('order', '<=', $this->moduleProgress->unlocked_order);

        $userId = auth()->id();
        $itemIds = $this->items->pluck('id');
        $problemIds = $this->items->flatMap(fn ($item) => $item->problems)->pluck('id');

        // Cache de progreso por usuario+modulo (30s).
        // Elimina 2 queries (~580ms) en cada interaccion Livewire.
        // Se invalida inmediatamente al completar una lectura o problema.
        $progressKey = "user_progress_{$userId}_{$this->module->id}";

        $progress = Cache::remember($progressKey, 30, function () use ($userId, $itemIds, $problemIds) {
            return [
                'item_ids' => UserItemProgress::where('user_id', $userId)
                    ->whereIn('module_item_id', $itemIds)
                    ->where('completed', true)
                    ->pluck('module_item_id')
                    ->map(fn ($id) => (int) $id)
                    ->toArray(),
                'problem_ids' => UserProblemProgress::where('user_id', $userId)
                    ->whereIn('problem_id', $problemIds)
                    ->where('completed', true)
                    ->pluck('problem_id')
                    ->map(fn ($id) => (int) $id)
                    ->toArray(),
            ];
        });

        $this->completedItemIds = $progress['item_ids'];
        $this->completedProblemIds = $progress['problem_ids'];

        $this->calculateProgress();
    }

    public function isReadingCompleted($itemId): bool
    {
        return in_array((int) $itemId, $this->completedItemIds, true);
    }

    public function isProblemCompleted($problemId): bool
    {
        return in_array((int) $problemId, $this->completedProblemIds, true);
    }

    public function completeReading($itemId)
    {
        $item = $this->items->firstWhere('id', $itemId);

        if (! $item) {
            return;
        }

        DB::transaction(function () use ($item) {
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

            if ($this->moduleProgress && $nextOrder > $this->moduleProgress->unlocked_order) {
                $this->moduleProgress->update([
                    'unlocked_order' => $nextOrder,
                ]);

                $this->moduleProgress->refresh();
            }
        });

        // Invalidar cache de progreso para que el siguiente loadProgress sea fresco
        Cache::forget('user_progress_'.auth()->id().'_'.$this->module->id);

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

        // Invalidar cache de progreso
        Cache::forget('user_progress_'.auth()->id().'_'.$this->module->id);

        $this->loadProgress();
    }

    private function calculateProgress()
    {
        $this->totalProgress = 0;
        $readingCompleted = 0;
        $problemCompleted = 0;

        $readingTotal = $this->items->sum('percentage');

        $allProblems = $this->items->flatMap(function ($item) {
            return $item->problems->where('is_active', true);
        });

        $problemTotal = $allProblems->sum('percentage');

        foreach ($this->items as $item) {
            if ($this->isReadingCompleted($item->id)) {
                $this->totalProgress += $item->percentage;
                $readingCompleted += $item->percentage;
            }

            foreach ($item->problems->where('is_active', true) as $problem) {
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
            ?->problems
            ?->where('is_active', true);

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
