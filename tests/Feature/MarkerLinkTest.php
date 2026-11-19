<?php

use App\Livewire\MarkerNavigation;
use App\Livewire\Problemas\ProblemaDinamico;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\User;
use App\Models\UserItemProgress;
use App\Models\UserModuleProgress;
use App\Models\UserProblemProgress;
use App\Services\MarkerLinkService;
use Livewire\Livewire;

beforeEach(function () {
    $this->student = User::factory()->create(['role' => 'user']);
    $this->module = Module::create(['title' => 'Módulo', 'slug' => 'modulo', 'order' => 1]);
    $this->reading = ModuleItem::create([
        'module_id' => $this->module->id, 'title' => 'Lectura inicial', 'type' => 'lectura', 'order' => 1,
        'content' => '<mark data-concept-id="same" data-concept-title="Concepto inicial">Texto</mark>',
    ]);
    $this->problem = Problem::create([
        'module_item_id' => $this->reading->id, 'title' => 'Ejercicio inicial', 'slug' => 'inicial',
        'component' => 'problemas.problema-dinamico', 'order' => 1, 'is_active' => true, 'is_example' => false,
        'content' => '<mark data-concept-id="same" data-concept-title="Concepto del ejercicio">Texto</mark>',
    ]);
    $this->service = app(MarkerLinkService::class);
});

test('only administrators can request the global marker catalog', function () {
    Livewire::actingAs($this->student)->test(MarkerNavigation::class)->call('catalog')->assertForbidden();
    $admin = User::factory()->create(['role' => 'admin']);
    Livewire::actingAs($admin)->test(MarkerNavigation::class)->call('catalog')
        ->assertReturned($this->service->catalog());
});

test('the catalog includes every module and step without conflating copied marker ids or revealing answers', function () {
    $other = Module::create(['title' => 'Otro módulo', 'slug' => 'otro', 'order' => 2]);
    ModuleItem::create([
        'module_id' => $other->id, 'title' => 'Otra lectura', 'type' => 'lectura', 'order' => 1,
        'content' => $this->reading->content,
    ]);
    $step = $this->problem->steps()->create([
        'step_number' => 1, 'title' => 'Paso con marca', 'answer_type' => 'numeric', 'correct_answer' => 'secret-answer',
        'instruction' => '<mark data-concept-id="step-marker" data-concept-title="Concepto del paso">Texto</mark>',
    ]);
    $catalog = collect($this->service->catalog());
    expect($catalog)->toHaveCount(4)
        ->and($catalog->where('marker_id', 'same'))->toHaveCount(3)
        ->and($catalog->firstWhere('source', 'step')['source_id'])->toBe($step->id)
        ->and(json_encode($catalog))->not->toContain('secret-answer');
});

test('resolving an available reading marker is read only and uses its current module url', function () {
    $target = ['source' => 'reading', 'source_id' => $this->reading->id, 'marker_id' => 'same'];
    $this->module->update(['title' => 'Módulo renombrado', 'slug' => 'renombrado']);
    $result = $this->service->resolve($this->student->id, $target);
    expect($result['ok'])->toBeTrue()->and($result['url'])->toEndWith('/alumno/modulo/renombrado');
    $this->assertDatabaseCount('user_item_progress', 0);
    $this->assertDatabaseCount('user_problem_progress', 0);
    $this->assertDatabaseCount('user_module_progress', 0);
});

test('problem marks require completed reading and all normal problem prerequisites', function () {
    $target = ['source' => 'problem', 'source_id' => $this->problem->id, 'marker_id' => 'same'];
    expect($this->service->resolve($this->student->id, $target)['message'])->toContain('Lectura inicial');
    UserItemProgress::create(['user_id' => $this->student->id, 'module_item_id' => $this->reading->id, 'completed' => true]);
    UserModuleProgress::create(['user_id' => $this->student->id, 'module_id' => $this->module->id, 'unlocked_order' => 1]);
    $example = $this->reading->problems()->create([
        'title' => 'Ejemplo previo', 'slug' => 'previo', 'component' => 'problemas.problema-dinamico',
        'order' => 2, 'is_active' => true, 'is_example' => true,
    ]);
    expect($this->service->resolve($this->student->id, $target)['ok'])->toBeFalse();
    UserProblemProgress::create(['user_id' => $this->student->id, 'problem_id' => $example->id, 'completed' => true]);
    expect($this->service->resolve($this->student->id, $target)['ok'])->toBeTrue();
    $this->problem->update(['is_active' => false]);
    expect($this->service->resolve($this->student->id, $target)['ok'])->toBeFalse();
});

test('a previously over-unlocked reading still reports the pending prerequisite without awarding progress', function () {
    $next = $this->reading->replicate();
    $next->title = 'Segunda lectura';
    $next->slug = null;
    $next->order = 2;
    $next->save();
    UserModuleProgress::create(['user_id' => $this->student->id, 'module_id' => $this->module->id, 'unlocked_order' => 2]);
    UserItemProgress::create(['user_id' => $this->student->id, 'module_item_id' => $this->reading->id, 'completed' => true]);
    $result = $this->service->resolve($this->student->id, ['source' => 'reading', 'source_id' => $next->id, 'marker_id' => 'same']);
    expect($result['ok'])->toBeFalse()->and($result['message'])->toContain('Ejercicio inicial');
    $this->assertDatabaseCount('user_problem_progress', 0);
});

test('missing markers and forged references do not navigate', function () {
    foreach ([
        ['source' => 'reading', 'source_id' => $this->reading->id, 'marker_id' => 'missing'],
        ['source' => 'unknown', 'source_id' => $this->reading->id, 'marker_id' => 'same'],
        ['source' => 'problem', 'source_id' => 99999, 'marker_id' => 'same'],
        [],
    ] as $target) {
        expect($this->service->resolve($this->student->id, $target)['ok'])->toBeFalse();
    }
});

test('step markers use the same problem access checks and do not expose instruction or expected answer', function () {
    $step = $this->problem->steps()->create([
        'step_number' => 1, 'title' => 'Paso', 'answer_type' => 'numeric', 'correct_answer' => 'secret',
        'instruction' => '<mark data-concept-id="step" data-concept-title="Paso">Instrucción privada</mark>',
    ]);
    $target = ['source' => 'step', 'source_id' => $step->id, 'marker_id' => 'step'];
    expect($this->service->resolve($this->student->id, $target)['ok'])->toBeFalse();
    UserItemProgress::create(['user_id' => $this->student->id, 'module_item_id' => $this->reading->id, 'completed' => true]);
    $result = $this->service->resolve($this->student->id, $target);
    expect($result['ok'])->toBeTrue()->and($result['step_id'])->toBe($step->id)
        ->and(json_encode($result))->not->toContain('Instrucción privada', 'secret');
});

test('returning from a marker restores the validated step and typed answer without recording completion', function () {
    $first = $this->problem->steps()->create([
        'step_number' => 1, 'title' => 'Primero', 'answer_type' => 'numeric', 'correct_answer' => '10',
    ]);
    $second = $this->problem->steps()->create([
        'step_number' => 2, 'title' => 'Segundo', 'answer_type' => 'numeric', 'correct_answer' => '20',
    ]);
    UserModuleProgress::create(['user_id' => $this->student->id, 'module_id' => $this->module->id, 'unlocked_order' => 1]);
    UserItemProgress::create(['user_id' => $this->student->id, 'module_item_id' => $this->reading->id, 'completed' => true]);
    $attempt = Livewire::actingAs($this->student)->test(ProblemaDinamico::class, ['problemId' => $this->problem->id])
        ->set('answers.'.$first->id, '10')->call('checkStep', $first->id)
        ->set('answers.'.$second->id, '19')->assertSet('currentStepIndex', 2);
    $token = $attempt->instance()->rememberMarkerOrigin();
    Livewire::withQueryParams(['marker_return' => $token])->test(ProblemaDinamico::class, ['problemId' => $this->problem->id])
        ->assertSet('currentStepIndex', 2)->assertSet('answers.'.$second->id, '19')
        ->assertSet('isCompleted', false);
    $this->assertDatabaseCount('user_problem_progress', 0);
    // Otro alumno no puede recuperar este intento, aunque copie el token.
    $other = User::factory()->create(['role' => 'user']);
    Livewire::actingAs($other)->withQueryParams(['marker_return' => $token])
        ->test(ProblemaDinamico::class, ['problemId' => $this->problem->id])->assertSet('currentStepIndex', 1);
    // Una edición del profesor invalida el estado temporal anterior.
    $second->update(['correct_answer' => '21']);
    Livewire::actingAs($this->student)->withQueryParams(['marker_return' => $token])
        ->test(ProblemaDinamico::class, ['problemId' => $this->problem->id])->assertSet('currentStepIndex', 1);
});
