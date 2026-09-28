<?php

namespace App\Providers;

use App\Auth\CachedUserProvider;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') || request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        // Registrar proveedor de usuario con cache local para eliminar
        // la query SELECT users en cada request autenticado (~1.35s ahorrados).
        Auth::provider('cached', function ($app, array $config) {
            return new CachedUserProvider(
                $app['hash'],
                $config['model']
            );
        });

        View::composer('partials.sidebar-user', function ($view) {
            // Se cachean solo los campos necesarios como array plano para evitar
            // __PHP_Incomplete_Class al deserializar objetos Eloquent con file cache.
            $moduleData = Cache::remember('sidebar_modules', 600, function () {
                return Module::orderBy('order')
                    ->get(['id', 'title', 'slug', 'order'])
                    ->map(fn ($m) => ['id' => $m->id, 'title' => $m->title, 'slug' => $m->slug])
                    ->toArray();
            });

            // Reconstruir como colección de objetos simples para la vista
            $modules = collect($moduleData)->map(fn ($m) => (object) $m);

            $view->with('modules', $modules);
        });
    }
}
