<?php

namespace App\Observers;

use App\Models\ContentActivityLog;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ContentActivityObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    private function record(Model $model, string $action): void
    {
        $entityType = match (true) {
            $model instanceof Module => 'module',
            $model instanceof ModuleItem => 'reading',
            $model instanceof Problem => 'problem',
            default => null,
        };

        if ($entityType === null) {
            return;
        }

        $title = $model->title ?? ('#'.$model->id);
        $payload = $model->toArray();

        // En caso de eliminación de problema, podemos enriquecer con pasos si están cargados o disponibles
        if ($model instanceof Problem && $action === 'deleted' && $model->relationLoaded('steps')) {
            $payload['steps'] = $model->steps->toArray();
        }

        try {
            ContentActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $model->id,
                'title' => $title,
                'payload' => $payload,
                'ip_address' => Request::ip(),
            ]);
        } catch (\Throwable $e) {
            // Evitamos que una falla de logging interrumpa la transacción principal, pero la registramos
            report($e);
        }
    }
}
