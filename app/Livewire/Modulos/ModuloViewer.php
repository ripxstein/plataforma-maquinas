<?php

namespace App\Livewire\Modulos;

use App\Models\Module;
use App\Models\Problem;
use App\Models\UserItemProgress;
use App\Models\UserModuleProgress;
use App\Models\UserProblemProgress;
use App\Services\LearningProgressService;
use App\Services\ModuleCacheService;
use App\Services\ProgressCalculator;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ModuloViewer extends Component
{
    #[Locked]
    public string $slug;

    #[Locked]
    public int $unlockedOrder = 1;

    public int $totalProgress = 0;

    public int $readingProgress = 0;

    public int $problemProgress = 0;

    /** @var array<int> */
    #[Locked]
    public array $completedItemIds = [];

    /** @var array<int> */
    #[Locked]
    public array $completedProblemIds = [];

    protected $listeners = [
        'problema-completado' => 'completeProblem',
    ];

    public function mount(string $slug)
    {
        $this->slug = $slug;

        $moduleProgress = UserModuleProgress::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'module_id' => $this->module->id,
            ],
            [
                'unlocked_order' => 1,
            ]
        );

        $this->unlockedOrder = $moduleProgress->unlocked_order;

        $this->loadProgress();
    }

    #[Computed]
    public function module(): Module
    {
        return ModuleCacheService::getModuleBySlug($this->slug)
            ?? Module::where('slug', $this->slug)->firstOrFail();
    }

    #[Computed]
    public function items()
    {
        return ModuleCacheService::getModuleItemsWithProblems($this->module->id);
    }

    #[Computed]
    public function visibleItems()
    {
        return $this->items->where('order', '<=', $this->unlockedOrder);
    }

    /**
     * Mapa conceptual del módulo (null si no existe, está oculto o no tiene nodos).
     * Se obtiene desde cache y no se serializa en el payload de Livewire.
     */
    #[Computed]
    public function sitemap(): ?array
    {
        $data = ModuleCacheService::getSitemap($this->module->id);

        if (empty($data['is_active']) || empty($data['nodes'])) {
            return null;
        }

        return $data;
    }

    public function loadProgress()
    {
        $userId = auth()->id();
        $itemIds = $this->items->pluck('id');
        $problemIds = $this->items->flatMap(fn ($item) => $item->problems)->pluck('id');

        // Cache de progreso por usuario+modulo (30s).
        // Elimina 2 queries (~580ms) en cada interaccion Livewire.
        // Se invalida inmediatamente al completar una lectura o problema.
        $progressKey = ModuleCacheService::progressKey($userId, $this->module->id);

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

    public function completeReading(int $itemId)
    {
        abort_unless(auth()->check(), 403);
        $this->unlockedOrder = app(LearningProgressService::class)
            ->completeReading(auth()->id(), $this->module->id, $itemId);
        unset($this->visibleItems);
        $this->loadProgress();
    }

    // El evento solo refresca la vista; jamás acredita una resolución.
    public function completeProblem(int $problemId)
    {
        abort_unless(auth()->check(), 403);
        $problem = Problem::whereHas('moduleItem', fn ($query) => $query->where('module_id', $this->module->id))
            ->where('is_active', true)->findOrFail($problemId);
        abort_unless(UserProblemProgress::where('user_id', auth()->id())
            ->where('problem_id', $problem->id)->where('completed', true)->exists(), 403);
        app(LearningProgressService::class)->forgetProgress(auth()->id(), $this->module->id);
        $this->loadProgress();
    }

    private function calculateProgress()
    {
        $progress = app(ProgressCalculator::class)->module($this->items, $this->completedItemIds, $this->completedProblemIds);
        $this->totalProgress = $progress['total'];
        $this->readingProgress = $progress['readings'];
        $this->problemProgress = $progress['problems'];
    }

    public function areExamplesCompleted($moduleItemId): bool
    {
        $examples = $this->items
            ->firstWhere('id', $moduleItemId)
            ?->problems
            ?->where('is_active', true)
            ->where('is_example', true);

        if (! $examples || $examples->isEmpty()) {
            return true;
        }

        foreach ($examples as $example) {
            if (! $this->isProblemCompleted($example->id)) {
                return false;
            }
        }

        return true;
    }

    public function isProblemUnlocked($problem, $moduleItemId): bool
    {
        $item = $this->items->firstWhere('id', $moduleItemId);

        return $item && $item->order <= $this->unlockedOrder && $this->isReadingCompleted($item->id)
            && app(LearningProgressService::class)->isProblemUnlocked($problem, $item->problems, $this->completedProblemIds);
    }

    public function render()
    {
        return view('livewire.modulos.modulo-viewer', [
            'module' => $this->module,
            'items' => $this->items,
            'visibleItems' => $this->visibleItems,
        ]);
    }
}
