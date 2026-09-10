<?php

use App\Livewire\Admin\ComponentesPanel;
use App\Livewire\Admin\ProgresosPanel;
use App\Livewire\Modulos\ModuloViewer;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\ProblemStep;
use App\Models\User;
use App\Models\UserItemProgress;
use App\Models\UserModuleProgress;
use App\Models\UserProblemProgress;
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

    // Mock/simulate failure by inserting invalid step data that will cause an SQL or type error
    // For example, ProblemStep with a null non-nullable title when forced or intercepting DB
    try {
        DB::transaction(function () {
            // Inside a test transaction we can verify that rollback restores state
        });
    } catch (Throwable $e) {
    }

    $component = Livewire::actingAs($this->admin)
        ->test(ComponentesPanel::class)
        ->call('createComponent')
        ->set('title', 'Problema Fallido')
        ->set('slug', $slug)
        ->set('moduleItemId', $this->item1->id)
        ->set('percentage', 30);

    // Corrupt one step in stepsData to cause an exception on ProblemStep::updateOrCreate
    // ProblemStep requires title (NOT NULL)
    $steps = $component->get('stepsData');
    $steps[0]['title'] = null; // Will trigger database NOT NULL integrity violation
    $component->set('stepsData', $steps);

    try {
        $component->call('saveComponent');
    } catch (Throwable $e) {
        // Exception expected
    }

    // Because of DB::transaction, the problem itself should NOT exist in the database!
    $this->assertDatabaseMissing('problems', [
        'slug' => $slug,
    ]);
});

test('completeReading rolls back all changes if an exception occurs during the transaction', function () {
    try {
        UserModuleProgress::updating(function () {
            throw new Exception('Simulated failure during module progress update');
        });

        $component = Livewire::actingAs($this->student)
            ->test(ModuloViewer::class, ['slug' => $this->module->slug]);

        try {
            $component->call('completeReading', $this->item1->id);
        } catch (Throwable $e) {
            // Exception expected because update failed
        }

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

test('completeReading atomically saves reading item progress and advances unlocked order on success', function () {
    Livewire::actingAs($this->student)
        ->test(ModuloViewer::class, ['slug' => $this->module->slug])
        ->call('completeReading', $this->item1->id);

    $this->assertDatabaseHas('user_item_progress', [
        'user_id' => $this->student->id,
        'module_item_id' => $this->item1->id,
        'completed' => 1,
    ]);

    $progress = UserModuleProgress::where('user_id', $this->student->id)
        ->where('module_id', $this->module->id)
        ->first();

    expect($progress->unlocked_order)->toBe(2);
});
