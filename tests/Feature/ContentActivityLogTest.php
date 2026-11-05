<?php

use App\Models\ContentActivityLog;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\User;

test('it records activity log when a module is created and deleted', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $module = Module::create([
        'title' => 'Módulo de Prueba Auditoría',
        'slug' => 'modulo-prueba-auditoria',
        'order' => 99,
    ]);

    expect(ContentActivityLog::where('entity_type', 'module')
        ->where('entity_id', $module->id)
        ->where('action', 'created')
        ->where('user_id', $admin->id)
        ->exists())->toBeTrue();

    $module->delete();

    expect(ContentActivityLog::where('entity_type', 'module')
        ->where('entity_id', $module->id)
        ->where('action', 'deleted')
        ->where('user_id', $admin->id)
        ->exists())->toBeTrue();
});

test('it records activity log when a reading and problem are created and deleted', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $module = Module::create([
        'title' => 'Módulo Auditoría',
        'slug' => 'modulo-auditoria',
        'order' => 100,
    ]);

    $reading = ModuleItem::create([
        'module_id' => $module->id,
        'title' => 'Lectura Auditoría',
        'content' => '<p>Contenido</p>',
        'order' => 1,
        'percentage' => 50,
        'type' => 'lectura',
    ]);

    expect(ContentActivityLog::where('entity_type', 'reading')
        ->where('entity_id', $reading->id)
        ->where('action', 'created')
        ->where('title', 'Lectura Auditoría')
        ->exists())->toBeTrue();

    $problem = Problem::create([
        'module_item_id' => $reading->id,
        'title' => 'Problema Auditoría',
        'slug' => 'problema-auditoria',
        'component' => 'problemas.problema-dinamico',
        'order' => 1,
        'percentage' => 50,
        'is_active' => true,
        'is_example' => false,
    ]);

    expect(ContentActivityLog::where('entity_type', 'problem')
        ->where('entity_id', $problem->id)
        ->where('action', 'created')
        ->where('title', 'Problema Auditoría')
        ->exists())->toBeTrue();

    $problem->delete();

    $deletedProblemLog = ContentActivityLog::where('entity_type', 'problem')
        ->where('entity_id', $problem->id)
        ->where('action', 'deleted')
        ->first();

    expect($deletedProblemLog)->not->toBeNull();
    expect($deletedProblemLog->payload['title'])->toBe('Problema Auditoría');

    $reading->delete();

    expect(ContentActivityLog::where('entity_type', 'reading')
        ->where('entity_id', $reading->id)
        ->where('action', 'deleted')
        ->exists())->toBeTrue();
});
