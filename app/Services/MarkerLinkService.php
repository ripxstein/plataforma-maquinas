<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\ProblemStep;
use App\Models\UserItemProgress;
use App\Models\UserModuleProgress;
use App\Models\UserProblemProgress;

class MarkerLinkService
{
    /** Se consulta al abrir el selector, no al renderizar cada editor. */
    public function catalog(): array
    {
        $modules = Module::select('id', 'title', 'slug', 'order')
            ->with(['items' => fn ($q) => $q->select('id', 'module_id', 'title', 'content', 'order')->orderBy('order')->orderBy('id'),
                'items.problems' => fn ($q) => $q->select('id', 'module_item_id', 'title', 'content', 'order', 'is_example')->orderBy('order')->orderBy('id'),
                'items.problems.steps' => fn ($q) => $q->select('id', 'problem_id', 'title', 'instruction', 'step_number')->orderBy('step_number')->orderBy('id')])
            ->orderBy('order')->orderBy('id')->get();
        $result = [];
        foreach ($modules as $module) {
            foreach ($module->items as $item) {
                $sources = [['reading', $item->id, $item->content, $item->title]];
                foreach ($item->problems as $problem) {
                    $title = ($problem->is_example ? 'Ejemplo: ' : 'Ejercicio: ').$problem->title;
                    $sources[] = ['problem', $problem->id, $problem->content, $title];
                    foreach ($problem->steps as $step) {
                        $sources[] = ['step', $step->id, $step->instruction, $title.' · Paso '.$step->step_number.': '.$step->title];
                    }
                }
                foreach ($sources as [$source, $id, $html, $title]) {
                    foreach (SitemapService::extractMarkers($html) as $marker) {
                        $result[] = $marker + [
                            'source' => $source, 'source_id' => $id,
                            'module_title' => $module->title, 'section_title' => $title,
                            'url' => route('student.modulo', $module->slug),
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /** El enlace contiene una referencia, nunca una autorización para desbloquear. */
    public function resolve(int $userId, array $target): array
    {
        $source = $target['source'] ?? '';
        $id = filter_var($target['source_id'] ?? null, FILTER_VALIDATE_INT);
        $markerId = $target['marker_id'] ?? null;
        if (! $id || ! is_string($markerId) || strlen($markerId) > 128) {
            return $this->blocked('El enlace a esta marca no es válido.');
        }
        $record = match ($source) {
            'reading' => ModuleItem::find($id),
            'problem' => Problem::with('moduleItem')->find($id),
            'step' => ProblemStep::with('problem.moduleItem')->find($id),
            default => null,
        };
        $html = $source === 'step' ? $record?->instruction : $record?->content;
        if (! $record || ! collect(SitemapService::extractMarkers($html))->contains('marker_id', $markerId)) {
            return $this->blocked('Esta marca ya no existe. Pide al profesor que actualice el hipervínculo.');
        }
        $problem = $source === 'step' ? $record->problem : ($source === 'problem' ? $record : null);
        $item = $source === 'reading' ? $record : $problem?->moduleItem;
        if (! $item || ($problem && ! $problem->is_active)) {
            return $this->blocked('Este contenido no está disponible actualmente.');
        }
        $items = ModuleItem::select('id', 'module_id', 'title', 'order')->where('module_id', $item->module_id)
            ->with(['activeProblems' => fn ($q) => $q->select('id', 'module_item_id', 'title', 'order', 'is_active', 'is_example')])
            ->orderBy('order')->orderBy('id')->get();
        $completedItems = UserItemProgress::where('user_id', $userId)->where('completed', true)
            ->whereIn('module_item_id', $items->pluck('id'))->pluck('module_item_id')->all();
        $completedProblems = UserProblemProgress::where('user_id', $userId)->where('completed', true)
            ->whereIn('problem_id', $items->flatMap(fn ($i) => $i->activeProblems)->pluck('id'))->pluck('problem_id')->all();
        foreach ($items as $previous) {
            if ($previous->id === $item->id) {
                break;
            }
            if (! in_array($previous->id, $completedItems)) {
                return $this->blocked('Completa primero la lectura «'.$previous->title.'».');
            }
            foreach ($previous->activeProblems as $pending) {
                if (! in_array($pending->id, $completedProblems)) {
                    return $this->blocked(($pending->is_example ? 'Revisa primero el ejemplo «' : 'Completa primero el ejercicio «').$pending->title.'» de la lectura «'.$previous->title.'».');
                }
            }
        }
        $unlocked = UserModuleProgress::where('user_id', $userId)->where('module_id', $item->module_id)->value('unlocked_order') ?? 1;
        if ($item->order > $unlocked) {
            return $this->blocked('Completa las lecturas y actividades anteriores para desbloquear «'.$item->title.'».');
        }
        if ($problem) {
            if (! in_array($item->id, $completedItems)) {
                return $this->blocked('Completa primero la lectura «'.$item->title.'».');
            }
            $siblings = $items->firstWhere('id', $item->id)->activeProblems;
            if (! app(LearningProgressService::class)->isProblemUnlocked($problem, $siblings, $completedProblems)) {
                return $this->blocked(app(StudentActivitySummary::class)->problemLockReason($problem, $siblings, $completedProblems));
            }
        }

        return [
            'ok' => true, 'item_id' => $item->id, 'problem_id' => $problem?->id,
            'step_id' => $source === 'step' ? $record->id : null,
            'marker_id' => $markerId,
            'url' => route('student.modulo', $item->module->slug),
        ];
    }

    private function blocked(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }
}
