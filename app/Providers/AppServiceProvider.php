<?php

namespace App\Providers;

use App\Models\Module;
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
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        View::composer('partials.sidebar-user', function ($view) {
            $modules = Module::with(['items' => function ($q) {
                $q->orderBy('order');
            }, 'items.problems' => function ($q) {
                $q->orderBy('order');
            }])->orderBy('order')->get();

            $view->with('modules', $modules);
        });
    }
}

