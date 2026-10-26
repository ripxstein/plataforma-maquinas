<?php

use App\Livewire\Admin\ComponentesPanel;
use App\Livewire\Admin\ProgresosPanel;
use App\Livewire\Modulos\ModuloViewer;
use App\Livewire\Problemas\ProblemaDinamico;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\ProblemStep;
use App\Models\User;
use App\Models\UserItemProgress;
use App\Models\UserModuleProgress;
use App\Models\UserProblemProgress;
use App\Observers\ContentCacheObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'email' => 'admin_'.uniqid().'@example.com',
    ]);

    $this->student = User::factory()->create([
        'role' => 'user',
        'email' => 'student_'.uniqid().'@example.com',
    ]);

    $this->module = Module::create([
        'title' => 'Módulo de Rendimiento',
        'slug' => 'modulo-rendimiento-'.uniqid(),
        'order' => 1,
    ]);

    $this->item1 = ModuleItem::create([
        'module_id' => $this->module->id,
        'title' => 'Lectura 1',
        'type' => 'lectura',
        'percentage' => 50,
        'order' => 1,
    ]);

    $this->item2 = ModuleItem::create([
        'module_id' => $this->module->id,
        'title' => 'Lectura 2',
        'type' => 'lectura',
        'percentage' => 50,
        'order' => 2,
    ]);

    $this->problem1 = Problem::create([
        'module_item_id' => $this->item1->id,
        'title' => 'Problema 1',
        'slug' => 'problema-1-'.uniqid(),
        'component' => 'problemas.problema-dinamico',
        'order' => 1,
        'percentage' => 50,
        'is_active' => true,
    ]);
});

test('ModuloViewer batch-loads completed items and problems without N+1 queries', function () {
    UserItemProgress::create([
        'user_id' => $this->student->id,
        'module_item_id' => $this->item1->id,
        'completed' => true,
        'completed_at' => now(),
    ]);

    UserProblemProgress::create([
        'user_id' => $this->student->id,
        'problem_id' => $this->problem1->id,
        'completed' => true,
        'score' => 100,
        'completed_at' => now(),
    ]);

    $component = Livewire::actingAs($this->student)
        ->test(ModuloViewer::class, ['slug' => $this->module->slug]);

    expect($component->instance()->isReadingCompleted($this->item1->id))->toBeTrue();
    expect($component->instance()->isReadingCompleted($this->item2->id))->toBeFalse();
    expect($component->instance()->isProblemCompleted($this->problem1->id))->toBeTrue();

    // Now listen to DB queries during method execution - should execute zero DB queries!
    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $resReading1 = $component->instance()->isReadingCompleted($this->item1->id);
    $resReading2 = $component->instance()->isReadingCompleted($this->item2->id);
    $resProblem = $component->instance()->isProblemCompleted($this->problem1->id);

    expect($resReading1)->toBeTrue();
    expect($resReading2)->toBeFalse();
    expect($resProblem)->toBeTrue();
    expect($queryCount)->toBe(0); // In-memory lookup, zero queries!
});

test('ProgresosPanel renders with aggregated queries and no N+1 per student', function () {
    // Create several students with progress
    $students = User::factory()->count(5)->create([
        'role' => 'user',
    ]);

    foreach ($students as $idx => $st) {
        if ($idx % 2 === 0) {
            UserItemProgress::create([
                'user_id' => $st->id,
                'module_item_id' => $this->item1->id,
                'completed' => true,
                'completed_at' => now(),
            ]);
        }
    }

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $component = Livewire::actingAs($this->admin)
        ->test(ProgresosPanel::class);

    $component->assertStatus(200);

    // Queries should be fixed (count catalog items + count catalog problems + 1 withCount users query),
    // definitely not running 2 queries per student (which would be >= 10 queries for 5 students)!
    expect($queryCount)->toBeLessThanOrEqual(5);
});

test('saveComponent rolls back all changes if an exception occurs during the transaction', function () {
    $slug = 'problema-transaccion-'.uniqid();
    $component = Livewire::actingAs($this->admin)
        ->test(ComponentesPanel::class)
        ->call('createComponent')
        ->set('title', 'Problema Fallido')
        ->set('slug', $slug)
        ->set('moduleItemId', $this->item1->id)
        ->call('openAddStepModal')
        ->set('stepTitle', 'Paso con fallo simulado')
        ->set('stepCorrectAnswer', '10')
        ->call('saveStepModal');

    ProblemStep::creating(function ($step) {
        if ($step->title === 'Paso con fallo simulado') {
            throw new RuntimeException('Fallo simulado al guardar un paso');
        }
    });

    try {
        expect(fn () => $component->call('saveComponent'))
            ->toThrow(RuntimeException::class, 'Fallo simulado al guardar un paso');
        $this->assertDatabaseMissing('problems', ['slug' => $slug]);
        expect($component->get('problemId'))->toBeNull();
    } finally {
        ProblemStep::flushEventListeners();
        ProblemStep::observe(ContentCacheObserver::class);
    }
});
test('completeReading rolls back all changes if an exception occurs during the transaction', function () {
    $this->problem1->update(['is_active' => false]);
    try {
        UserModuleProgress::updating(function () {
            throw new Exception('Simulated failure during module progress update');
        });

        $component = Livewire::actingAs($this->student)
            ->test(ModuloViewer::class, ['slug' => $this->module->slug]);

        expect(fn () => $component->call('completeReading', $this->item1->id))
            ->toThrow(Exception::class, 'Simulated failure during module progress update');

        // Because of DB::transaction, UserItemProgress should NOT exist in database!
        $this->assertDatabaseMissing('user_item_progress', [
            'user_id' => $this->student->id,
            'module_item_id' => $this->item1->id,
        ]);

        // Module progress unlocked_order should still remain at initial order (1)
        $progress = UserModuleProgress::where('user_id', $this->student->id)
            ->where('module_id', $this->module->id)
            ->first();

        expect($progress->unlocked_order)->toBe(1);
    } finally {
        UserModuleProgress::flushEventListeners();
    }
});

test('completeReading saves the reading but keeps the next one locked while a problem is pending', function () {
    Livewire::actingAs($this->student)
        ->test(ModuloViewer::class, ['slug' => $this->module->slug])
        ->call('completeReading', $this->item1->id)
        ->assertDontSee('Lectura 2');

    $this->assertDatabaseHas('user_item_progress', [
        'user_id' => $this->student->id,
        'module_item_id' => $this->item1->id,
        'completed' => 1,
    ]);

    $progress = UserModuleProgress::where('user_id', $this->student->id)
        ->where('module_id', $this->module->id)
        ->first();

    expect($progress->unlocked_order)->toBe(1);
});

test('completing the final problem reveals the next reading immediately and after reload', function () {
    $step = $this->problem1->steps()->create([
        'title' => 'Respuesta', 'step_number' => 1, 'answer_type' => 'numeric', 'correct_answer' => '10',
    ]);
    $viewer = Livewire::actingAs($this->student)
        ->test(ModuloViewer::class, ['slug' => $this->module->slug])
        ->call('completeReading', $this->item1->id)
        ->assertDontSee('Lectura 2');

    Livewire::test(ProblemaDinamico::class, ['problemId' => $this->problem1->id])
        ->set('answers.'.$step->id, '10')->call('checkStep', $step->id)
        ->assertSet('isCompleted', true);

    $viewer->dispatch('problema-completado', problemId: $this->problem1->id)
        ->assertSee('Lectura 2');
    Livewire::test(ModuloViewer::class, ['slug' => $this->module->slug])->assertSee('Lectura 2');
    expect(UserModuleProgress::where('user_id', $this->student->id)
        ->where('module_id', $this->module->id)->value('unlocked_order'))->toBe(2);
});

test('a reading without active problems unlocks the next reading', function () {
    $this->problem1->update(['is_active' => false]);
    Livewire::actingAs($this->student)
        ->test(ModuloViewer::class, ['slug' => $this->module->slug])
        ->call('completeReading', $this->item1->id)
        ->assertSee('Lectura 2');
    expect(UserModuleProgress::where('user_id', $this->student->id)
        ->where('module_id', $this->module->id)->value('unlocked_order'))->toBe(2);
});

test('modules:cache command preloads all modules, items, problems, and sidebar into cache', function () {
    Cache::flush();

    expect(Cache::has('sidebar_modules'))->toBeFalse();
    expect(Cache::has('module_slug_'.$this->module->slug))->toBeFalse();
    expect(Cache::has('module_items_'.$this->module->id))->toBeFalse();

    $this->artisan('modules:cache')
        ->assertSuccessful();

    expect(Cache::has('sidebar_modules'))->toBeTrue();
    expect(Cache::has('module_slug_'.$this->module->slug))->toBeTrue();
    expect(Cache::has('module_items_'.$this->module->id))->toBeTrue();

    // Verify ModuloViewer uses the cached data seamlessly
    $component = Livewire::actingAs($this->student)
        ->test(ModuloViewer::class, ['slug' => $this->module->slug]);

    $component->assertSee('Lectura 1')
        ->call('completeReading', $this->item1->id)
        ->assertSee('Problema 1');
});
