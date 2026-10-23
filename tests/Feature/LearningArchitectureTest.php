<?php

use App\Livewire\Admin\AlumnosPanel;
use App\Livewire\Admin\ProgresosPanel;
use App\Livewire\Modulos\ModuloViewer;
use App\Livewire\Problemas\Problema1;
use App\Livewire\Problemas\Problema2;
use App\Livewire\Problemas\ProblemaDinamico;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\User;
use App\Models\UserItemProgress;
use App\Models\UserProblemProgress;
use App\Services\AnswerEvaluator;
use App\Services\ModuleCacheService;
use App\Services\ProblemWriter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $this->student = User::factory()->create(['role' => 'user']);
    $this->module = Module::create(['title' => 'Módulo', 'slug' => 'arquitectura', 'order' => 1]);
    $this->reading = ModuleItem::create([
        'module_id' => $this->module->id, 'title' => 'Lectura', 'type' => 'lectura', 'order' => 1, 'percentage' => 30,
    ]);
    $this->problem = Problem::create([
        'module_item_id' => $this->reading->id, 'title' => 'Ejercicio', 'slug' => 'ejercicio',
        'component' => 'problemas.problema-dinamico', 'order' => 1, 'percentage' => 70, 'is_active' => true, 'is_example' => false,
    ]);
    $this->steps = collect([1, 2])->map(fn ($number) => $this->problem->steps()->create([
        'title' => 'Paso '.$number, 'step_number' => $number, 'answer_type' => 'numeric', 'correct_answer' => '10',
    ]));
});

function openArchitectureReading($context)
{
    return Livewire::actingAs($context->student)->test(ModuloViewer::class, ['slug' => $context->module->slug])
        ->call('completeReading', $context->reading->id)->assertStatus(200);
}

test('reading actions reject locked and foreign readings without writing progress', function () {
    $locked = ModuleItem::create([
        'module_id' => $this->module->id, 'title' => 'Futura', 'type' => 'lectura', 'order' => 2,
    ]);
    Livewire::actingAs($this->student)->test(ModuloViewer::class, ['slug' => $this->module->slug])
        ->call('completeReading', $locked->id)->assertForbidden();
    $other = Module::create(['title' => 'Otro', 'slug' => 'otro', 'order' => 2]);
    $foreign = $other->items()->create(['title' => 'Ajena', 'type' => 'lectura', 'order' => 1]);
    expect(fn () => Livewire::actingAs($this->student)->test(ModuloViewer::class, ['slug' => $this->module->slug])
        ->call('completeReading', $foreign->id))->toThrow(ModelNotFoundException::class);
    expect(UserItemProgress::count())->toBe(0);
});

test('a forged completion event cannot award progress', function () {
    openArchitectureReading($this)->dispatch('problema-completado', problemId: $this->problem->id)->assertForbidden();
    expect(UserProblemProgress::count())->toBe(0);
});

test('dynamic actions require the reading and the expected step', function () {
    Livewire::actingAs($this->student)->test(ProblemaDinamico::class, ['problemId' => $this->problem->id])
        ->set('answers.'.$this->steps[0]->id, '10')->call('checkStep', $this->steps[0]->id)->assertForbidden();
    openArchitectureReading($this);
    Livewire::test(ProblemaDinamico::class, ['problemId' => $this->problem->id])
        ->set('answers.'.$this->steps[1]->id, '10')->call('checkStep', $this->steps[1]->id)->assertForbidden();
    Livewire::test(ProblemaDinamico::class, ['problemId' => $this->problem->id])
        ->call('nextExampleStep', $this->steps[0]->id)->assertForbidden();
    expect(UserProblemProgress::count())->toBe(0);
});

test('client cannot change trusted dynamic state', function (string $property, mixed $value) {
    openArchitectureReading($this);
    Livewire::test(ProblemaDinamico::class, ['problemId' => $this->problem->id])->set($property, $value);
})->with([
    ['currentStepIndex', 2], ['problemId', 999], ['isCompleted', true], ['definitionHash', 'forged'],
])->throws(CannotUpdateLockedPropertyException::class);

test('validated dynamic resolution persists before the event and reloads as completed', function () {
    $viewer = openArchitectureReading($this);
    $component = Livewire::test(ProblemaDinamico::class, ['problemId' => $this->problem->id]);
    $component->set('answers.'.$this->steps[0]->id, 'wrong')->call('checkStep', $this->steps[0]->id)
        ->assertSet('currentStepIndex', 1);
    foreach ($this->steps as $step) {
        $component->set('answers.'.$step->id, '10')->call('checkStep', $step->id)->assertStatus(200);
    }
    $component->assertSet('isCompleted', true)->assertDispatched('problema-completado', problemId: $this->problem->id);
    $this->assertDatabaseHas('user_problem_progress', ['user_id' => $this->student->id, 'problem_id' => $this->problem->id, 'completed' => true]);
    $viewer->dispatch('problema-completado', problemId: $this->problem->id)->assertSet('totalProgress', 100);
    Livewire::test(ProblemaDinamico::class, ['problemId' => $this->problem->id])->assertSet('isCompleted', true);
    $component->call('checkStep', $this->steps[1]->id)->assertForbidden();
    expect(UserProblemProgress::count())->toBe(1);
});

test('examples must be reviewed sequentially before evaluated exercises', function () {
    $this->problem->update(['is_example' => true]);
    $exercise = Problem::create([
        'module_item_id' => $this->reading->id, 'title' => 'Después', 'slug' => 'despues',
        'component' => 'problemas.problema-dinamico', 'order' => 2, 'percentage' => 70, 'is_active' => true,
    ]);
    $step = $exercise->steps()->create(['title' => 'Paso', 'step_number' => 1, 'answer_type' => 'text', 'correct_answer' => 'ok']);
    openArchitectureReading($this);
    Livewire::test(ProblemaDinamico::class, ['problemId' => $exercise->id])->set('answers.'.$step->id, 'ok')
        ->call('checkStep', $step->id)->assertForbidden();
    Livewire::test(ProblemaDinamico::class, ['problemId' => $this->problem->id])
        ->call('nextExampleStep', $this->steps[1]->id)->assertForbidden();
    $example = Livewire::test(ProblemaDinamico::class, ['problemId' => $this->problem->id]);
    foreach ($this->steps as $exampleStep) {
        $example->call('nextExampleStep', $exampleStep->id)->assertStatus(200);
    }
    Livewire::test(ProblemaDinamico::class, ['problemId' => $exercise->id])->set('answers.'.$step->id, 'ok')
        ->call('checkStep', $step->id)->assertSet('isCompleted', true);
});

test('changes to problem availability or definition invalidate an open attempt', function (bool $deactivate) {
    openArchitectureReading($this);
    $component = Livewire::test(ProblemaDinamico::class, ['problemId' => $this->problem->id]);
    if ($deactivate) {
        $this->problem->update(['is_active' => false]);
    } else {
        $this->steps[0]->update(['correct_answer' => '50']);
    }
    $component->set('answers.'.$this->steps[0]->id, '10')->call('checkStep', $this->steps[0]->id)
        ->assertStatus($deactivate ? 403 : 409);
    expect(UserProblemProgress::count())->toBe(0);
})->with([true, false]);

test('legacy problems reject skipping to the last step', function (string $class, string $alias, int $last) {
    $this->problem->update(['component' => $alias]);
    openArchitectureReading($this);
    Livewire::test($class, ['problemId' => $this->problem->id])->call('checkStep'.$last)->assertForbidden();
    expect(UserProblemProgress::count())->toBe(0);
})->with([[Problema1::class, 'problemas.problema1', 5], [Problema2::class, 'problemas.problema2', 11]]);

test('legacy problem one retains its valid sequence and records completion', function () {
    $this->problem->update(['component' => 'problemas.problema1']);
    openArchitectureReading($this);
    $component = Livewire::test(Problema1::class, ['problemId' => $this->problem->id]);
    foreach ([['d', 1, 'area'], ['A', 0.5, 'esfuerzo_nominal'], ['snom', 12000, 'kt'], ['kt', 1.9, 'esfuerzo_maximo']] as $index => [$field, $answer, $next]) {
        $component->set($field, $answer)->call('checkStep'.($index + 1))
            ->set('nextStepAnswer', $next)->call('checkNextStep')->assertSet('currentStep', $index + 2);
    }
    $component->set('smax', 22800)->call('checkStep5')->assertSet('currentStep', 6);
    expect(UserProblemProgress::where('user_id', $this->student->id)->where('completed', true)->count())->toBe(1);
});

test('legacy problem two retains its valid sequence and records completion', function () {
    $this->problem->update(['component' => 'problemas.problema2']);
    openArchitectureReading($this);
    $component = Livewire::test(Problema2::class, ['problemId' => $this->problem->id]);
    $answers = [
        [['areaA' => 320], 'sigma_nom_a'],
        [['sigmaNomA' => 62.5], 'relacion_a'],
        [['relacionA' => 0.2], 'kt_a'],
        [['ktA' => 2.5], 'sigma_max_a'],
        [['sigmaMaxA' => 156.25], 'area_b'],
        [['areaB' => 320], 'sigma_nom_b'],
        [['sigmaNomB' => 62.5], 'relaciones_b'],
        [['rdB' => 0.125, 'DdB' => 1.25], 'kt_b'],
        [['ktB' => 1.75], 'sigma_max_b'],
        [['sigmaMaxB' => 109.375], 'comparacion'],
    ];
    foreach ($answers as $index => [$values, $next]) {
        foreach ($values as $field => $answer) {
            $component->set($field, $answer);
        }
        $component->call('checkStep'.($index + 1))->set('nextStepAnswer', $next)
            ->call('checkNextStep')->assertSet('currentStep', $index + 2);
    }
    $component->set('seleccion', 'B')->call('checkStep11')->assertSet('currentStep', 12);
    expect(UserProblemProgress::where('user_id', $this->student->id)->where('completed', true)->count())->toBe(1);
});

test('exercises with equal order use their id as a stable sequence', function () {
    $later = Problem::create(array_merge($this->problem->only($this->problem->getFillable()), ['slug' => 'later']));
    $step = $later->steps()->create(['title' => 'Paso', 'step_number' => 1, 'answer_type' => 'text', 'correct_answer' => 'ok']);
    openArchitectureReading($this);
    Livewire::test(ProblemaDinamico::class, ['problemId' => $later->id])->set('answers.'.$step->id, 'ok')
        ->call('checkStep', $step->id)->assertForbidden();
    expect(UserProblemProgress::count())->toBe(0);
});

test('a zero weight catalog produces a bounded progress without division by zero', function () {
    $this->reading->update(['percentage' => 0]);
    $this->problem->update(['percentage' => 0]);
    openArchitectureReading($this)->assertSet('totalProgress', 0);
    Livewire::test(ProgresosPanel::class)->assertViewHas('alumnos', fn ($students) => $students->every(fn ($student) => $student->avance_total === 0));
});

test('student and admin use the same weighted percentage excluding examples and inactive problems', function () {
    $this->reading->update(['percentage' => 50]);
    $this->problem->update(['percentage' => 100]);
    foreach ([['is_example' => true], ['is_active' => false]] as $index => $attributes) {
        $excluded = Problem::create(array_merge($this->problem->only($this->problem->getFillable()), $attributes, ['slug' => 'excluded-'.$index]));
        UserProblemProgress::create(['user_id' => $this->student->id, 'problem_id' => $excluded->id, 'completed' => true]);
    }
    openArchitectureReading($this)->assertSet('totalProgress', 33);
    $admin = User::factory()->create(['role' => 'admin']);
    Livewire::actingAs($admin)->test(ProgresosPanel::class)->assertViewHas('alumnos', function ($students) {
        $student = $students->firstWhere('id', $this->student->id);

        return $student->avance_total === 33 && (int) $student->problemas_completados === 0;
    });
    Livewire::test(AlumnosPanel::class)->assertViewHas('alumnos', fn ($students) => (int) $students->firstWhere('id', $this->student->id)->problemas_completados === 0);
});

test('content observers invalidate both modules when content moves outside a panel', function () {
    $destination = Module::create(['title' => 'Destino', 'slug' => 'destino', 'order' => 2]);
    $destinationItem = $destination->items()->create(['title' => 'Destino', 'type' => 'lectura', 'order' => 1]);
    ModuleCacheService::warmAll();
    $oldKey = ModuleCacheService::progressKey($this->student->id, $this->module->id);
    $this->problem->update(['module_item_id' => $destinationItem->id]);
    expect(Cache::has('module_items_'.$this->module->id))->toBeFalse()
        ->and(Cache::has('module_items_'.$destination->id))->toBeFalse()
        ->and(ModuleCacheService::progressKey($this->student->id, $this->module->id))->not->toBe($oldKey);
    ModuleCacheService::warmAll();
    $this->reading->update(['module_id' => $destination->id]);
    expect(Cache::has('module_items_'.$this->module->id))->toBeFalse()
        ->and(Cache::has('module_items_'.$destination->id))->toBeFalse();
});

test('direct module rename deletion and step edits invalidate content caches', function () {
    ModuleCacheService::warmAll();
    $this->module->update(['slug' => 'renamed']);
    expect(Cache::has('module_slug_arquitectura'))->toBeFalse();
    ModuleCacheService::warmAll();
    $this->steps[0]->update(['correct_answer' => '30']);
    expect(Cache::has('module_items_'.$this->module->id))->toBeFalse();
    ModuleCacheService::warmAll();
    $this->module->delete();
    expect(Cache::has('module_slug_renamed'))->toBeFalse()
        ->and(Cache::has('module_items_'.$this->module->id))->toBeFalse();
});

test('committing a content transaction removes cache repopulated before commit', function () {
    DB::transaction(function () {
        $this->reading->update(['title' => 'Nueva lectura']);
        // Simula una petición concurrente que aún leyó el contenido anterior.
        Cache::put('module_items_'.$this->module->id, ['stale' => true], 3600);
    });
    expect(Cache::has('module_items_'.$this->module->id))->toBeFalse();
});

test('editing and deleting options outside panels also invalidate the module cache', function () {
    $option = $this->steps[0]->options()->create(['option_text' => 'A', 'is_correct' => true, 'order' => 1]);
    ModuleCacheService::warmAll();
    $option->update(['option_text' => 'B']);
    expect(Cache::has('module_items_'.$this->module->id))->toBeFalse();
    ModuleCacheService::warmAll();
    $option->delete();
    expect(Cache::has('module_items_'.$this->module->id))->toBeFalse();
});

test('problem writer cannot take a step from another problem and rolls back the parent write', function () {
    $attributes = $this->problem->only($this->problem->getFillable());
    $attributes['slug'] = 'new-problem';
    try {
        app(ProblemWriter::class)->save(null, $attributes, [$this->steps[0]->toArray()]);
        $this->fail('Se esperaba rechazar el paso ajeno.');
    } catch (ModelNotFoundException) {
        $this->assertDatabaseMissing('problems', ['slug' => 'new-problem']);
        expect($this->steps[0]->fresh()->problem_id)->toBe($this->problem->id);
    }
});

test('shared evaluator retains numeric tolerances and rejects invalid boolean input', function () {
    $evaluator = app(AnswerEvaluator::class);
    expect($evaluator->evaluate(['answer_type' => 'numeric', 'correct_answer' => 100, 'tolerance' => 0.01], 101))->toBeTrue()
        ->and($evaluator->evaluate(['answer_type' => 'numeric', 'correct_answer' => 100, 'tolerance' => 0.01], 110))->toBeFalse()
        ->and($evaluator->evaluate(['answer_type' => 'true_false', 'correct_answer' => '0'], 'inventado'))->toBeFalse()
        ->and($evaluator->evaluate(['answer_type' => 'true_false', 'correct_answer' => '0'], 'falso'))->toBeTrue();
});
