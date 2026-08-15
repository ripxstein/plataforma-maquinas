<?php

use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\ProblemStep;
use App\Models\ProblemStepOption;
use App\Models\User;
use App\Models\UserProblemProgress;
use App\Livewire\Admin\ComponentesPanel;
use App\Livewire\Problemas\ProblemaDinamico;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'email' => 'admin_' . uniqid() . '@example.com',
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'email' => 'student_' . uniqid() . '@example.com',
    ]);

    $this->module = Module::create([
        'title' => 'Módulo de Prueba',
        'slug' => 'modulo-prueba-' . uniqid(),
        'order' => 1,
    ]);

    $this->reading = ModuleItem::create([
        'module_id' => $this->module->id,
        'title' => 'Lectura 1',
        'type' => 'lectura',
        'percentage' => 50,
        'order' => 1,
    ]);
});

test('admin can access componentes page', function () {
    $response = $this->actingAs($this->admin)->get('/admin/componentes');
    $response->assertStatus(200);
    $response->assertSee('Constructor de Componentes');
});

test('student cannot access admin componentes page', function () {
    $response = $this->actingAs($this->student)->get('/admin/componentes');
    $response->assertRedirect('/dashboard');
});

test('admin can create a dynamic problem with steps via ComponentesPanel', function () {
    $component = Livewire::actingAs($this->admin)
        ->test(ComponentesPanel::class)
        ->call('createComponent')
        ->set('title', 'Problema Dinámico de Prueba')
        ->set('slug', 'problema-dinamico-prueba')
        ->set('moduleItemId', $this->reading->id)
        ->set('percentage', 40)
        ->call('saveComponent');

    $component->assertHasNoErrors();

    $this->assertDatabaseHas('problems', [
        'title' => 'Problema Dinámico de Prueba',
        'slug' => 'problema-dinamico-prueba',
        'module_item_id' => $this->reading->id,
    ]);

    $createdProblem = Problem::where('slug', 'problema-dinamico-prueba')->first();
    expect($createdProblem->steps()->count())->toBe(2);
});

test('admin can duplicate an interactive component with its steps and options', function () {
    $problem = Problem::create([
        'module_item_id' => $this->reading->id,
        'title' => 'Problema Original',
        'slug' => 'problema-original-' . uniqid(),
        'component' => 'problemas.problema-dinamico',
        'order' => 1,
        'percentage' => 30,
        'is_active' => true,
    ]);

    $step = ProblemStep::create([
        'problem_id' => $problem->id,
        'step_number' => 1,
        'title' => 'Paso 1: Selección',
        'instruction' => 'Selecciona la opción correcta',
        'answer_type' => 'multiple_choice',
    ]);

    ProblemStepOption::create([
        'problem_step_id' => $step->id,
        'option_text' => 'Opción A',
        'is_correct' => true,
        'order' => 1,
    ]);

    ProblemStepOption::create([
        'problem_step_id' => $step->id,
        'option_text' => 'Opción B',
        'is_correct' => false,
        'order' => 2,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ComponentesPanel::class)
        ->call('duplicateComponent', $problem->id);

    $duplicated = Problem::where('title', 'like', 'Copia de Problema Original%')->first();
    expect($duplicated)->not->toBeNull();
    expect($duplicated->steps()->count())->toBe(1);
    expect($duplicated->steps->first()->options()->count())->toBe(2);
});

test('problema dinamico validates step responses and emits completion event on final step', function () {
    $problem = Problem::create([
        'module_item_id' => $this->reading->id,
        'title' => 'Problema Test Pasos',
        'slug' => 'problema-test-pasos-' . uniqid(),
        'component' => 'problemas.problema-dinamico',
        'order' => 1,
        'percentage' => 30,
    ]);

    // Step 1: Numeric
    $step1 = ProblemStep::create([
        'problem_id' => $problem->id,
        'step_number' => 1,
        'title' => 'Paso Numérico',
        'instruction' => 'Calcula d',
        'answer_type' => 'numeric',
        'correct_answer' => '150.5',
        'tolerance' => 0.1,
        'unit' => 'MPa',
    ]);

    // Step 2: Multiple choice
    $step2 = ProblemStep::create([
        'problem_id' => $problem->id,
        'step_number' => 2,
        'title' => 'Paso Selección',
        'instruction' => 'Selecciona',
        'answer_type' => 'multiple_choice',
    ]);

    $opt1 = ProblemStepOption::create([
        'problem_step_id' => $step2->id,
        'option_text' => 'Correcta',
        'is_correct' => true,
        'order' => 1,
    ]);

    $opt2 = ProblemStepOption::create([
        'problem_step_id' => $step2->id,
        'option_text' => 'Incorrecta',
        'is_correct' => false,
        'order' => 2,
    ]);

    $lw = Livewire::actingAs($this->student)
        ->test(ProblemaDinamico::class, ['problemId' => $problem->id]);

    // Check wrong numeric answer on Step 1
    $lw->set('answers.' . $step1->id, '100')
       ->call('checkStep', $step1->id)
       ->assertSet('currentStepIndex', 1)
       ->assertSee('Respuesta incorrecta');

    // Check correct numeric answer on Step 1 -> advances to Step 2
    $lw->set('answers.' . $step1->id, '150.52')
       ->call('checkStep', $step1->id)
       ->assertSet('currentStepIndex', 2)
       ->assertSee('¡Correcto!');

    // Check wrong option on Step 2
    $lw->set('answers.' . $step2->id, $opt2->id)
       ->call('checkStep', $step2->id)
       ->assertSet('isCompleted', false);

    // Check correct option on Step 2 -> Completes problem & dispatches event
    $lw->set('answers.' . $step2->id, $opt1->id)
       ->call('checkStep', $step2->id)
       ->assertSet('isCompleted', true)
       ->assertDispatched('problema-completado', problemId: $problem->id);
});

test('inactive problems are not displayed in modulo viewer for students', function () {
    $activeProblem = Problem::create([
        'module_item_id' => $this->reading->id,
        'title' => 'Problema Activo Visible',
        'slug' => 'problema-activo-' . uniqid(),
        'component' => 'problemas.problema-dinamico',
        'order' => 1,
        'percentage' => 30,
        'is_active' => true,
    ]);

    $inactiveProblem = Problem::create([
        'module_item_id' => $this->reading->id,
        'title' => 'Problema Inactivo Oculto',
        'slug' => 'problema-inactivo-' . uniqid(),
        'component' => 'problemas.problema-dinamico',
        'order' => 2,
        'percentage' => 30,
        'is_active' => false,
    ]);

    // Complete reading so problems accordion is shown
    Livewire::actingAs($this->student)
        ->test(\App\Livewire\Modulos\ModuloViewer::class, ['slug' => $this->module->slug])
        ->call('completeReading', $this->reading->id)
        ->assertSee('Problema Activo Visible')
        ->assertDontSee('Problema Inactivo Oculto');
});
