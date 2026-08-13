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

