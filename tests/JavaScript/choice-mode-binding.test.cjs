const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {runInNewContext} = require('node:vm');

const livewire = readFileSync('vendor/livewire/livewire/dist/livewire.js', 'utf8');
const view = readFileSync('resources/views/livewire/admin/componentes-panel.blade.php', 'utf8');
const selectMarkup = view.match(/<select\b[^>]*id="step-choice-mode"[^>]*>[\s\S]*?<\/select>/)[0];
const optionValues = [...selectMarkup.matchAll(/<option\b[^>]*value="([^"]+)"/g)].map(match => match[1]);

// Ejecutar el enlace real de Alpine incluido en Livewire: un set() de PHP no reproduce este fallo.
const scope = {};
for (const name of ['updateSelect', 'safeParseBoolean']) {
    const implementation = livewire.match(new RegExp(`  function ${name}\\([^]*?^  }`, 'm'));
    assert.ok(implementation, `No se encontró ${name} en Livewire`);
    runInNewContext(implementation[0] + `; this.${name} = ${name};`, scope);
}

test('the mode selector uses explicit boolean conversion when sending the selected value', () => {
    assert.match(selectMarkup, /wire:model\.live\.boolean="stepAllowsMultipleCorrect"/);
    assert.deepEqual(optionValues.map(value => scope.safeParseBoolean(value)), [false, true]);
});

for (const mode of [false, true]) {
    test(`mode ${mode} remains selected after subsequent server refreshes`, () => {
        const select = {options: optionValues.map(value => ({value, selected: false}))};
        for (let refresh = 0; refresh < 4; refresh++) {
            scope.updateSelect(select, mode);
            assert.deepEqual(select.options.filter(option => option.selected).map(option => option.value), [String(mode)]);
        }
    });
}
