<?php

use App\Livewire\Admin\ComponentesPanel;
use App\Livewire\Modulos\ModuloViewer;
use App\Livewire\Problemas\ProblemaDinamico;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use App\Models\User;
use App\Services\ProblemWriter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->student = User::factory()->create(['role' => 'user']);
    $this->module = Module::create(['title' => 'Módulo', 'slug' => 'opciones', 'order' => 1]);
    $this->reading = ModuleItem::create(['module_id' => $this->module->id, 'title' => 'Lectura', 'type' => 'lectura', 'order' => 1]);
    $this->attributes = [
        'module_item_id' => $this->reading->id, 'title' => 'Elección visual', 'slug' => 'visual',
        'component' => 'problemas.problema-dinamico', 'order' => 1, 'percentage' => 100, 'is_active' => true,
    ];
    $this->choiceOptions = [
        ['option_text' => 'Texto válido', 'is_correct' => true],
        ['option_text' => '', 'image_url' => '/images/diagrama.png', 'image_alt' => 'Diagrama del esfuerzo', 'is_correct' => true],
        ['option_text' => 'Imagen con texto', 'image_url' => 'https://example.org/figura.png', 'is_correct' => true],
        ['option_text' => 'Respuesta incorrecta', 'is_correct' => false],
    ];
    $this->definition = ['title' => 'Elegir figura', 'answer_type' => 'multiple_choice', 'allows_multiple_correct' => true, 'options' => $this->choiceOptions];
});

test('the editor saves more than two correct options with text images or both and retains them on reopening and duplication', function () {
    $editor = Livewire::actingAs($this->admin)->test(ComponentesPanel::class)
        ->call('createComponent')->set('title', $this->attributes['title'])->set('moduleItemId', $this->reading->id)
        ->call('openAddStepModal')->set('stepAnswerType', 'multiple_choice')->set('stepAllowsMultipleCorrect', true)
        ->set('stepOptions', array_map(fn ($option) => $option + ['client_key' => (string) Str::uuid(), 'image_url' => '', 'image_alt' => ''], $this->choiceOptions))
        ->call('saveStepModal')->assertHasNoErrors()->call('saveComponent')->assertHasNoErrors();
    $problem = Problem::findOrFail($editor->get('problemId'));
    $step = $problem->steps()->first();
    expect($step->allows_multiple_correct)->toBeTrue()
        ->and($step->options()->where('is_correct', true)->count())->toBe(3)
        ->and($step->options[1]->option_text)->toBe('')
        ->and($step->options[1]->image_url)->toBe('/images/diagrama.png');
    $editor->call('editComponent', $problem->id)->call('openEditStepModal', 0)
        ->assertSet('stepAllowsMultipleCorrect', true)->assertSet('stepOptions.1.image_alt', 'Diagrama del esfuerzo');
    $copy = app(ProblemWriter::class)->duplicate($problem->id);
    expect($copy->steps->first()->allows_multiple_correct)->toBeTrue()
        ->and($copy->steps->first()->options[1]->image_url)->toBe('/images/diagrama.png')
        ->and($copy->steps->first()->options->where('is_correct', true))->toHaveCount(3);
});

test('the learner and simulator accept any one correct option including an image only option', function (int $choice) {
    $problem = app(ProblemWriter::class)->save(null, $this->attributes, [$this->definition])['problem'];
    $step = $problem->steps()->with('options')->first();
    Livewire::actingAs($this->student)->test(ModuloViewer::class, ['slug' => $this->module->slug])
        ->call('completeReading', $this->reading->id);
    Livewire::actingAs($this->student)->test(ProblemaDinamico::class, ['problemId' => $problem->id])
        ->assertSeeHtml('src="/images/diagrama.png"')->assertSee('Diagrama del esfuerzo')
        ->set('answers.'.$step->id, $step->options[3]->id)->call('checkStep', $step->id)->assertSet('isCompleted', false)
        ->set('answers.'.$step->id, $step->options[$choice]->id)->call('checkStep', $step->id)->assertSet('isCompleted', true);
    Livewire::actingAs($this->admin)->test(ComponentesPanel::class)->call('editComponent', $problem->id)
        ->assertSeeHtml('src="/images/diagrama.png"')
        ->set('previewAnswers.0', '3')->call('testPreviewStep', 0)->assertSet('previewCompleted', false)
        ->set('previewAnswers.0', (string) $choice)->call('testPreviewStep', 0)->assertSet('previewCompleted', true);
})->with([0, 1, 2]);

test('the teacher can toggle several correct options and returning to single mode keeps only the first selected', function () {
    Livewire::actingAs($this->admin)->test(ComponentesPanel::class)->call('openAddStepModal')
        ->set('stepAnswerType', 'multiple_choice')->set('stepAllowsMultipleCorrect', true)
        ->call('addOptionToActiveStep')->call('setOptionAsCorrect', 1)->call('setOptionAsCorrect', 2)
        ->assertSet('stepOptions.0.is_correct', true)->assertSet('stepOptions.1.is_correct', true)->assertSet('stepOptions.2.is_correct', true)
        ->call('setOptionAsCorrect', 1)->assertSet('stepOptions.1.is_correct', false)
        ->set('stepAllowsMultipleCorrect', false)->assertSet('stepOptions.0.is_correct', true)->assertSet('stepOptions.2.is_correct', false);
});

test('switching an existing single choice step to several correct answers survives later updates and validates a non-first answer', function (int $choice) {
    $definition = $this->definition;
    $definition['allows_multiple_correct'] = false;
    foreach ($definition['options'] as $index => &$option) {
        $option['is_correct'] = $index === 0;
    }
    unset($option);
    $problem = app(ProblemWriter::class)->save(null, $this->attributes, [$definition])['problem'];
    $editor = Livewire::actingAs($this->admin)->test(ComponentesPanel::class)
        ->call('editComponent', $problem->id)->call('openEditStepModal', 0)
        ->assertSet('stepAllowsMultipleCorrect', false)
        ->set('stepAllowsMultipleCorrect', true)
        ->call('setOptionAsCorrect', 1)->call('setOptionAsCorrect', 2)
        ->set('stepTitle', 'Paso actualizado después de cambiar la modalidad')
        ->assertSet('stepAllowsMultipleCorrect', true)
        ->assertSet('stepOptions.1.is_correct', true)->assertSet('stepOptions.2.is_correct', true)
        ->call('saveStepModal')->call('saveComponent')->assertHasNoErrors()
        ->call('editComponent', $problem->id)->call('openEditStepModal', 0)
        ->assertSet('stepAllowsMultipleCorrect', true)
        ->assertSet('stepOptions.1.is_correct', true)->assertSet('stepOptions.2.is_correct', true);
    $step = $problem->steps()->with('options')->first();
    expect($step->allows_multiple_correct)->toBeTrue()
        ->and($step->options->where('is_correct', true))->toHaveCount(3);
    Livewire::actingAs($this->student)->test(ModuloViewer::class, ['slug' => $this->module->slug])
        ->call('completeReading', $this->reading->id);
    Livewire::actingAs($this->student)->test(ProblemaDinamico::class, ['problemId' => $problem->id])
        ->set('answers.'.$step->id, $step->options[$choice]->id)->call('checkStep', $step->id)
        ->assertSet('isCompleted', true);
})->with([1, 2]);

test('the writer rejects invalid choices before writing any changes', function (string $invalid) {
    $step = $this->definition;
    if ($invalid === 'single') {
        $step['allows_multiple_correct'] = false;
    }
    if ($invalid === 'none-correct') {
        $step['options'] = array_map(fn ($option) => array_replace($option, ['is_correct' => false]), $step['options']);
    }
    if ($invalid === 'empty') {
        $step['options'][1] = ['option_text' => '  ', 'is_correct' => true];
    }
    if ($invalid === 'url') {
        $step['options'][1]['image_url'] = 'javascript:alert(1)';
    }
    if ($invalid === 'one-option') {
        $step['options'] = [$step['options'][0]];
    }
    expect(fn () => app(ProblemWriter::class)->save(null, $this->attributes, [$step]))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('problems', 0);
    $this->assertDatabaseCount('problem_step_options', 0);
})->with(['single', 'none-correct', 'empty', 'url', 'one-option']);

test('removing an option image clears it on save while preserving the option and its correctness', function () {
    $problem = app(ProblemWriter::class)->save(null, $this->attributes, [$this->definition])['problem'];
    $editor = Livewire::actingAs($this->admin)->test(ComponentesPanel::class)->call('editComponent', $problem->id)->call('openEditStepModal', 0);
    $key = $editor->get('stepOptions.1.client_key');
    $editor->set('stepOptions.1.option_text', 'Ahora solo texto')->call('removeOptionImage', $key)
        ->call('saveStepModal')->call('saveComponent')->assertHasNoErrors();
    $option = $problem->steps->first()->options[1];
    expect($option->image_url)->toBeNull()->and($option->option_text)->toBe('Ahora solo texto')->and($option->is_correct)->toBeTrue();
});

test('option images upload through Livewire and are assigned only to their stable option key', function () {
    $editor = Livewire::actingAs($this->admin)->test(ComponentesPanel::class)->call('openAddStepModal')->set('stepAnswerType', 'multiple_choice');
    $editor->call('addOptionToActiveStep');
    $key = $editor->get('stepOptions.1.client_key');
    $editor->call('removeOptionFromActiveStep', 0);
    $imagePath = null;
    try {
        $editor->set('optionImageUploads.'.$key, UploadedFile::fake()->image('figura.png', 80, 40))->assertHasNoErrors();
        $url = $editor->get('stepOptions.0.image_url');
        expect($url)->toStartWith('/images/uploads/')->toEndWith('.png');
        $imagePath = public_path(ltrim($url, '/'));
        expect(File::exists($imagePath))->toBeTrue();
        $editor->assertSet('stepOptions.1.image_url', '');
    } finally {
        if ($imagePath) {
            File::delete($imagePath);
        }
    }
});

test('unsupported option image uploads report validation errors without assigning an image', function () {
    $editor = Livewire::actingAs($this->admin)->test(ComponentesPanel::class)->call('openAddStepModal')->set('stepAnswerType', 'multiple_choice');
    $key = $editor->get('stepOptions.0.client_key');
    $editor->set('optionImageUploads.'.$key, UploadedFile::fake()->create('documento.txt', 10, 'text/plain'))
        ->assertHasErrors('optionImageUploads.'.$key)->assertSet('stepOptions.0.image_url', '');
});

test('an upload from a closed modal cannot attach its image to a newly opened step', function () {
    $editor = Livewire::actingAs($this->admin)->test(ComponentesPanel::class)->call('openAddStepModal')->set('stepAnswerType', 'multiple_choice');
    $oldKey = $editor->get('stepOptions.0.client_key');
    $editor->call('closeStepModal')->call('openAddStepModal')->set('stepAnswerType', 'multiple_choice');
    expect($editor->get('stepOptions.0.client_key'))->not->toBe($oldKey);
    $editor->set('optionImageUploads.'.$oldKey, UploadedFile::fake()->image('antigua.png'))
        ->assertSet('stepOptions.0.image_url', '')->assertSet('stepOptions.1.image_url', '');
});
