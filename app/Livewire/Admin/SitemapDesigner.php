<?php

namespace App\Livewire\Admin;

use App\Models\Module;
use App\Models\ModuleSitemap;
use App\Services\SitemapService;
use Livewire\Component;

/**
 * Diseñador visual del mapa conceptual (sitemap) de un módulo.
 * El lienzo corre en Alpine (public/js/sitemap-diagram.js); este componente
 * solo carga las marcas disponibles y persiste el resultado.
 */
class SitemapDesigner extends Component
{
    public int $moduleId;

    public function mount(int $moduleId): void
    {
        $this->authorizeAdmin();

        Module::findOrFail($moduleId);
        $this->moduleId = $moduleId;
    }

    /**
     * Guarda el mapa. Los datos se sanean en servidor antes de persistirse.
     *
     * @return array{ok: bool, nodes: int, edges: int}
     */
    public function saveSitemap(array $nodes, array $edges, bool $isActive): array
    {
        $this->authorizeAdmin();

        $clean = SitemapService::sanitize($nodes, $edges);

        ModuleSitemap::updateOrCreate(
            ['module_id' => $this->moduleId],
            [
                'nodes' => $clean['nodes'],
                'edges' => $clean['edges'],
                'is_active' => $isActive,
            ]
        );

        return [
            'ok' => true,
            'nodes' => count($clean['nodes']),
            'edges' => count($clean['edges']),
        ];
    }

    public function render()
    {
        $module = Module::with(['items' => fn ($q) => $q->orderBy('order'), 'items.problems' => fn ($q) => $q->orderBy('order'), 'sitemap'])
            ->findOrFail($this->moduleId);

        $readings = [];
        $markers = [];

        foreach ($module->items as $item) {
            $readings[$item->id] = $item->title;

            foreach (SitemapService::extractMarkers($item->content) as $marker) {
                $markers[] = $marker + [
                    'item_id' => $item->id,
                    'item_title' => $item->title,
                    'item_order' => $item->order,
                ];
            }

            foreach ($item->problems as $problem) {
                foreach (SitemapService::extractMarkers($problem->content) as $marker) {
                    $markers[] = $marker + [
                        'item_id' => $item->id,
                        'problem_id' => $problem->id,
                        'item_title' => ($problem->is_example ? 'Ejemplo: ' : 'Ejercicio: ').$problem->title,
                        'item_order' => $item->order,
                    ];
                }
            }
        }

        $config = [
            'mode' => 'edit',
            'nodes' => $module->sitemap?->nodes ?? [],
            'edges' => $module->sitemap?->edges ?? [],
            'isActive' => (bool) $module->sitemap?->is_active,
            'markers' => $markers,
            'readings' => (object) $readings,
        ];

        return view('livewire.admin.sitemap-designer', [
            'module' => $module,
            'config' => $config,
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }
}
