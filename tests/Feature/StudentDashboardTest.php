<?php

use App\Livewire\Student\StudentDashboard;
use App\Livewire\Modulos\ModuloViewer;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\User;
use App\Models\UserItemProgress;
use App\Models\UserModuleProgress;
use App\Models\UserProblemProgress;
use Livewire\Livewire;

test('the dashboard points to the next available activity without changing progress', function () {
    $student = User::factory()->create(['role' => 'user']);
    $module = Module::create(['title' => 'Concentración', 'slug' => 'concentracion', 'order' => 1]);
    $reading = ModuleItem::create([
        'module_id' => $module->id, 'title' => 'Primera lectura', 'type' => 'lectura',
        'order' => 1, 'percentage' => 30,
    ]);
    $nextReading = ModuleItem::create([
        'module_id' => $module->id, 'title' => 'Segunda lectura', 'type' => 'lectura',
        'order' => 2, 'percentage' => 20,
    ]);
    $example = Problem::create([
        'module_item_id' => $reading->id, 'title' => 'Ejemplo guiado', 'slug' => 'ejemplo',
        'component' => 'problemas.problema-dinamico', 'order' => 1, 'percentage' => 0,
        'is_active' => true, 'is_example' => true,
    ]);
    $exercise = Problem::create([
        'module_item_id' => $reading->id, 'title' => 'Ejercicio evaluado', 'slug' => 'ejercicio',
        'component' => 'problemas.problema-dinamico', 'order' => 2, 'percentage' => 50,
        'is_active' => true, 'is_example' => false,
    ]);
    UserModuleProgress::create(['user_id' => $student->id, 'module_id' => $module->id, 'unlocked_order' => 1]);

    Livewire::actingAs($student)->test(StudentDashboard::class)
        ->assertSee('Continuar donde te quedaste')
        ->assertSee('Primera lectura')
        ->assertSee('0 de 2 lecturas terminadas')
        ->assertSee('1 ejercicios pendientes');

    UserItemProgress::create(['user_id' => $student->id, 'module_item_id' => $reading->id, 'completed' => true]);
    Livewire::actingAs($student)->test(StudentDashboard::class)
        ->assertSee('Ejemplo guiado')
        ->assertDontSee('Ejercicio evaluado');

    UserProblemProgress::create(['user_id' => $student->id, 'problem_id' => $example->id, 'completed' => true]);
    Livewire::actingAs($student)->test(StudentDashboard::class)->assertSee('Ejercicio evaluado');

    UserProblemProgress::create(['user_id' => $student->id, 'problem_id' => $exercise->id, 'completed' => true]);
    UserModuleProgress::where('user_id', $student->id)->where('module_id', $module->id)
        ->update(['unlocked_order' => 2]);
    Livewire::actingAs($student)->test(StudentDashboard::class)->assertSee('Segunda lectura');

    expect(UserItemProgress::count())->toBe(1)
        ->and(UserProblemProgress::count())->toBe(2)
        ->and($nextReading->exists)->toBeTrue();
});

test('continuing favors the module with the most recent completed activity', function () {
    $student = User::factory()->create(['role' => 'user']);
    $first = Module::create(['title' => 'Primer módulo', 'slug' => 'primero', 'order' => 1]);
    ModuleItem::create([
        'module_id' => $first->id, 'title' => 'Lectura inicial', 'type' => 'lectura', 'order' => 1,
    ]);
    $second = Module::create(['title' => 'Segundo módulo', 'slug' => 'segundo', 'order' => 2]);
    $reading = ModuleItem::create([
        'module_id' => $second->id, 'title' => 'Lectura reciente', 'type' => 'lectura', 'order' => 1,
    ]);
    Problem::create([
        'module_item_id' => $reading->id, 'title' => 'Actividad por terminar', 'slug' => 'pendiente',
        'component' => 'problemas.problema-dinamico', 'order' => 1, 'is_active' => true,
        'is_example' => false,
    ]);
    UserItemProgress::create([
        'user_id' => $student->id, 'module_item_id' => $reading->id, 'completed' => true,
    ]);

    Livewire::actingAs($student)->test(StudentDashboard::class)
        ->assertSee('Actividad por terminar')
        ->assertSee('Segundo módulo')
        ->assertDontSee('Lectura inicial');
});

test('the module header counts only readings currently visible to the student', function () {
    $student = User::factory()->create(['role' => 'user']);
    $module = Module::create(['title' => 'Módulo con lecturas bloqueadas', 'slug' => 'bloqueadas', 'order' => 1]);
    $first = ModuleItem::create([
        'module_id' => $module->id, 'title' => 'Primera lectura', 'type' => 'lectura', 'order' => 1,
    ]);
    $second = ModuleItem::create([
        'module_id' => $module->id, 'title' => 'Segunda lectura', 'type' => 'lectura', 'order' => 2,
    ]);
    $third = ModuleItem::create([
        'module_id' => $module->id, 'title' => 'Lectura oculta', 'type' => 'lectura', 'order' => 3,
    ]);
    $problem = Problem::create([
        'module_item_id' => $second->id, 'title' => 'Ejercicio pendiente', 'slug' => 'pendiente',
        'component' => 'problemas.problema-dinamico', 'order' => 1, 'is_active' => true,
        'is_example' => false,
    ]);
    $problem->steps()->create([
        'title' => 'Paso', 'step_number' => 1, 'answer_type' => 'numeric', 'correct_answer' => '1',
    ]);
    foreach ([$first, $second, $third] as $reading) {
        UserItemProgress::create([
            'user_id' => $student->id, 'module_item_id' => $reading->id, 'completed' => true,
        ]);
    }
    UserModuleProgress::create([
        'user_id' => $student->id, 'module_id' => $module->id, 'unlocked_order' => 3,
    ]);

    Livewire::actingAs($student)->test(ModuloViewer::class, ['slug' => $module->slug])
        ->assertSee('2/2')
        ->assertSee('lecturas visibles completadas')
        ->assertDontSee('3/3')
        ->assertDontSee('Lectura oculta');
});
