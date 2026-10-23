<?php

namespace App\Observers;

use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\ModuleSitemap;
use App\Models\Problem;
use App\Models\ProblemStep;
use App\Models\ProblemStepOption;
use App\Services\ModuleCacheService;
use Illuminate\Database\Eloquent\Model;

class ContentCacheObserver
{
    public function saved(Model $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        $ids = match (true) {
            $model instanceof Module => [$model->id],
            $model instanceof ModuleItem, $model instanceof ModuleSitemap => [$model->module_id, $model->getOriginal('module_id')],
            $model instanceof Problem => ModuleItem::whereIn('id', array_filter([
                $model->module_item_id, $model->getOriginal('module_item_id'),
            ]))->pluck('module_id')->all(),
            $model instanceof ProblemStep => ModuleItem::whereIn('id', Problem::whereIn('id', array_filter([
                $model->problem_id, $model->getOriginal('problem_id'),
            ]))->select('module_item_id'))->pluck('module_id')->all(),
            $model instanceof ProblemStepOption => ModuleItem::whereIn('id', Problem::whereIn('id',
                ProblemStep::whereIn('id', array_filter([$model->problem_step_id, $model->getOriginal('problem_step_id')]))
                    ->select('problem_id'))->select('module_item_id'))->pluck('module_id')->all(),
            default => [],
        };
        $slug = $model instanceof Module ? $model->slug : null;
        $previousSlug = $model instanceof Module ? $model->getOriginal('slug') : null;
        $sitemapOnly = $model instanceof ModuleSitemap;
        $invalidate = static function () use ($ids, $slug, $previousSlug, $sitemapOnly) {
            foreach (array_unique(array_filter($ids)) as $id) {
                if ($sitemapOnly) {
                    ModuleCacheService::forgetSitemap((int) $id);
                } else {
                    ModuleCacheService::forgetModule((int) $id, $slug, $previousSlug);
                }
            }
        };

        // Invalidar también al confirmar evita conservar una lectura concurrente anterior al commit.
        $invalidate();
        if ($model->getConnection()->transactionLevel() > 0) {
            $model->getConnection()->afterCommit($invalidate);
        }
    }
}
