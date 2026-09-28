<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ModuleCacheService
{
    public const MODULE_TTL = 3600; // 1 hora

    public const PROGRESS_TTL = 30;  // 30 segundos

    /**
     * Obtiene el modelo Module por slug desde la cache (o BD si no existe).
     * Evita la query remota SELECT * FROM modules WHERE slug = ? (~1.6s).
     */
    public static function getModuleBySlug(string $slug): ?Module
    {
        $key = 'module_slug_'.$slug;

        $attributes = Cache::remember($key, self::MODULE_TTL, function () use ($slug) {
            $module = Module::where('slug', $slug)->first();

            return $module ? $module->getAttributes() : null;
        });

        if (! $attributes) {
            return null;
        }

        $module = new Module;
        $module->setRawAttributes($attributes, true);
        $module->exists = true;

        return $module;
    }

    /**
     * Obtiene los items y problemas de un modulo desde la cache.
     * Retorna una coleccion de ModuleItem con su relacion 'problems' cargada.
     */
    public static function getModuleItemsWithProblems(int $moduleId): Collection
    {
        $key = 'module_items_'.$moduleId;

        $data = Cache::remember($key, self::MODULE_TTL, function () use ($moduleId) {
            $items = ModuleItem::where('module_id', $moduleId)
                ->with(['problems' => fn ($q) => $q->where('is_active', true)->orderBy('order')])
                ->orderBy('order')
                ->get();

            return $items->map(function ($item) {
                $itemData = $item->getAttributes();
                $itemData['_problems'] = $item->problems->map(fn ($p) => $p->getAttributes())->toArray();

                return $itemData;
            })->toArray();
        });

        return collect($data)->map(function ($itemData) {
            $item = new ModuleItem;
            $item->setRawAttributes(Arr::except($itemData, ['_problems']), true);
            $item->exists = true;

            $problems = collect($itemData['_problems'])->map(function ($pData) {
                $problem = new Problem;
                $problem->setRawAttributes($pData, true);
                $problem->exists = true;

                return $problem;
            });

            $item->setRelation('problems', $problems);

            return $item;
        });
    }

    /**
     * Precarga y calienta la cache para TODOS los modulos del sistema.
     * En una sola consulta batch precarga:
     * - sidebar_modules
     * - module_slug_{slug} para cada modulo
     * - module_items_{moduleId} para cada modulo
     *
     * @return array Resumen de modulos precargados
     */
    public static function warmAll(): array
    {
        $modules = Module::with([
            'items' => fn ($q) => $q->orderBy('order'),
            'items.problems' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
        ])->orderBy('order')->get();

        // 1. Cachear sidebar_modules (atributos planos)
        $sidebarData = $modules->map(fn ($m) => [
            'id' => $m->id,
            'title' => $m->title,
            'slug' => $m->slug,
            'order' => $m->order,
        ])->toArray();

        Cache::put('sidebar_modules', $sidebarData, self::MODULE_TTL);

        $summary = [];

        // 2. Cachear cada modulo individual y sus items/problemas
        foreach ($modules as $module) {
            // Cache del modulo por slug
            Cache::put('module_slug_'.$module->slug, $module->getAttributes(), self::MODULE_TTL);

            // Cache de items con sus problemas
            $itemsData = $module->items->map(function ($item) {
                $itemData = $item->getAttributes();
                $itemData['_problems'] = $item->problems->map(fn ($p) => $p->getAttributes())->toArray();

                return $itemData;
            })->toArray();

            Cache::put('module_items_'.$module->id, $itemsData, self::MODULE_TTL);

            $summary[] = [
                'id' => $module->id,
                'title' => $module->title,
                'slug' => $module->slug,
                'items_count' => $module->items->count(),
                'problems_count' => $module->items->sum(fn ($i) => $i->problems->count()),
            ];
        }

        return $summary;
    }

    /**
     * Invalida la cache de un modulo especifico y del sidebar.
     */
    public static function forgetModule(int $moduleId, ?string $slug = null): void
    {
        Cache::forget('sidebar_modules');

        if ($moduleId > 0) {
            Cache::forget('module_items_'.$moduleId);
        }

        if ($slug) {
            Cache::forget('module_slug_'.$slug);
        }
    }
}
