<?php

use App\Livewire\Admin\ContenidoPanel;
use App\Livewire\Modulos\ModuloViewer;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\User;
use App\Services\ModuleCacheService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('saving and deleting a user invalidate the authentication cache', function () {
    $user = User::factory()->create();
    $key = 'auth_user_'.$user->id;
    $provider = Auth::guard('web')->getProvider();

    expect($provider->retrieveById($user->id)->name)->toBe($user->name);
    $user->update(['name' => 'Nombre actualizado']);
    expect(Cache::has($key))->toBeFalse();
    expect($provider->retrieveById($user->id)->name)->toBe('Nombre actualizado');

    $user->delete();
    expect(Cache::has($key))->toBeFalse();
    expect($provider->retrieveById($user->id))->toBeNull();
});

test('module viewer does not restore each reading from the database on livewire updates', function () {
    $student = User::factory()->create(['role' => 'user']);
    $module = Module::create(['title' => 'Módulo', 'slug' => 'modulo-cache', 'order' => 1]);

    foreach (range(1, 4) as $order) {
        ModuleItem::create([
            'module_id' => $module->id,
            'title' => 'Lectura '.$order,
            'type' => 'lectura',
            'order' => $order,
            'percentage' => 25,
        ]);
    }

    ModuleCacheService::warmAll();
    $component = Livewire::actingAs($student)->test(ModuloViewer::class, ['slug' => $module->slug]);
    $queries = [];

    DB::listen(function ($query) use (&$queries) {
        if (str_contains($query->sql, '"module_items"') || str_contains($query->sql, '`module_items`')) {
            $queries[] = $query->sql;
        }
    });

    $component->call('loadProgress')->assertSee('Lectura 1');

    expect($queries)->toBeEmpty();
});

test('renaming a module invalidates both slug cache keys', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $module = Module::create(['title' => 'Antes', 'slug' => 'antes', 'order' => 1]);
    ModuleCacheService::warmAll();
    Cache::put('module_slug_despues', ['stale' => true], 3600);

    Livewire::actingAs($admin)->test(ContenidoPanel::class)
        ->call('openModuleModal', $module->id)
        ->set('moduleTitle', 'Después')
        ->set('moduleSlug', 'despues')
        ->call('saveModule')
        ->assertHasNoErrors();

    expect(Cache::has('module_slug_antes'))->toBeFalse();
    expect(Cache::has('module_slug_despues'))->toBeFalse();
});

test('moving a reading invalidates both module content caches', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $source = Module::create(['title' => 'Origen', 'slug' => 'origen', 'order' => 1]);
    $destination = Module::create(['title' => 'Destino', 'slug' => 'destino', 'order' => 2]);
    $reading = ModuleItem::create([
        'module_id' => $source->id,
        'title' => 'Lectura',
        'type' => 'lectura',
        'content' => 'Contenido',
        'order' => 1,
        'percentage' => 100,
    ]);
    ModuleCacheService::warmAll();

    Livewire::actingAs($admin)->test(ContenidoPanel::class)
        ->call('openReadingModal', $reading->id)
        ->set('moduleIdForReading', $destination->id)
        ->call('saveReading')
        ->assertHasNoErrors();

    expect(Cache::has('module_items_'.$source->id))->toBeFalse();
    expect(Cache::has('module_items_'.$destination->id))->toBeFalse();
});

test('moving a problem invalidates both module content caches', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $source = Module::create(['title' => 'Origen', 'slug' => 'origen-problema', 'order' => 1]);
    $destination = Module::create(['title' => 'Destino', 'slug' => 'destino-problema', 'order' => 2]);
    $sourceReading = ModuleItem::create([
        'module_id' => $source->id, 'title' => 'Origen', 'type' => 'lectura', 'order' => 1,
    ]);
    $destinationReading = ModuleItem::create([
        'module_id' => $destination->id, 'title' => 'Destino', 'type' => 'lectura', 'order' => 1,
    ]);
    $problem = Problem::create([
        'module_item_id' => $sourceReading->id,
        'title' => 'Problema',
        'slug' => 'problema-movil',
        'component' => 'problemas.problema1',
        'order' => 1,
        'percentage' => 50,
    ]);
    ModuleCacheService::warmAll();

    Livewire::actingAs($admin)->test(ContenidoPanel::class)
        ->call('openProblemModal', $problem->id)
        ->set('readingIdForProblem', $destinationReading->id)
        ->call('saveProblem')
        ->assertHasNoErrors();

    expect(Cache::has('module_items_'.$source->id))->toBeFalse();
    expect(Cache::has('module_items_'.$destination->id))->toBeFalse();
});
