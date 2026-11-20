const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {runInNewContext} = require('node:vm');

const layout = readFileSync('resources/views/layouts/app.blade.php', 'utf8');
const implementation = layout.match(
    /    function parseProblemAction\(expression\) \{[\s\S]*?(?=\n    if \(!window\.__problemRequestCaptureInstalled\))/,
);
assert.ok(implementation, 'No se encontraron las funciones de reintento de problemas');

function actionElement(expression, disabled = false) {
    return {
        disabled,
        getAttribute(name) {
            if (name === 'wire:click') return expression;
            return null;
        },
        scrollIntoView() {},
        focus() {},
    };
}

function setup(expressions) {
    const elements = expressions.map(([expression, disabled = false]) => actionElement(expression, disabled));
    const component = {
        getAttribute: name => name === 'wire:id' ? 'component-1' : null,
        querySelectorAll: () => elements,
    };
    const scope = {
        window: {},
        document: {
            querySelectorAll(selector) {
                return selector === '[wire\\:id]' ? [component] : [];
            },
        },
    };
    runInNewContext(
        implementation[0]
        + '; this.availableProblemActionElement = availableProblemActionElement;'
        + 'this.parseProblemAction = parseProblemAction;',
        scope,
    );

    return {scope, elements};
}

test('a retry recognizes the exact action that is still rendered', () => {
    const {scope, elements} = setup([['nextExampleStep(51)']]);

    const found = scope.availableProblemActionElement({
        componentId: 'component-1',
        method: 'nextExampleStep',
        parameters: [51],
    });

    assert.equal(found, elements[0]);
});

test('a delayed retry does not resend a previous example step', () => {
    const {scope} = setup([['nextExampleStep(51)']]);

    const found = scope.availableProblemActionElement({
        componentId: 'component-1',
        method: 'nextExampleStep',
        parameters: [50],
    });

    assert.equal(found, null);
});

test('an action remains identifiable while Livewire is removing its loading state', () => {
    const {scope, elements} = setup([['checkStep(45)', true]]);

    const found = scope.availableProblemActionElement({
        componentId: 'component-1',
        method: 'checkStep',
        parameters: [45],
    });

    assert.equal(found, elements[0]);
});
