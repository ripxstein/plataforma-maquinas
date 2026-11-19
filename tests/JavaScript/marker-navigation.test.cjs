const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {runInNewContext} = require('node:vm');
const {randomUUID} = require('node:crypto');
const code = readFileSync('public/js/marker-links.js', 'utf8');

// Simula red y geometría para verificar cancelación, bloqueo y llegada sin un servidor.
function setup(hash = '') {
    const properties = new Map([['overflow', 'auto']]);
    const storage = new Map();
    const watchdogs = [];
    const background = {inert: false, tagName: 'DIV', contains: () => false};
    const existingInert = {inert: true, tagName: 'DIV', contains: () => false};
    const navigation = {tagName: 'DIV', contains: () => true};
    const target = {
        isConnected: true, scrolls: 0, closest: () => null,
        classList: {add() {}, remove() {}}, getClientRects: () => [{}],
        getBoundingClientRect: () => ({top: 300, bottom: 330}),
        scrollIntoView() { this.scrolls++; },
    };
    const document = new EventTarget();
    Object.assign(document, {
        documentElement: {scrollHeight: 2000, style: {
            getPropertyValue: name => properties.get(name) || '', getPropertyPriority: () => '',
            setProperty: (name, value) => properties.set(name, value), removeProperty: name => properties.delete(name),
        }},
        body: {children: [background, existingInert, navigation]}, images: [], fonts: {status: 'loaded'},
        querySelector(selector) {
            if (selector === '[data-marker-navigation]') return navigation;
            if (selector.includes('[data-marker-loading]')) return {focus() {}};
            return {querySelector: () => target};
        },
    });
    const location = {href: 'https://school.test/alumno/modulo/uno' + hash, pathname: '/alumno/modulo/uno', origin: 'https://school.test', hash, assigned: [], assign(url) { this.assigned.push(url); }};
    const window = new EventTarget();
    Object.assign(window, {innerHeight: 800, scrollY: 100, stopped: 0, stop() { this.stopped++; }, Alpine: true});
    let factory;
    runInNewContext(code, {
        window, document, location, crypto: {randomUUID}, Alpine: {data: (_, value) => { factory = value; }},
        sessionStorage: {getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value)},
        URL, AbortController, CustomEvent: class extends Event {
            constructor(type, options) { super(type); this.detail = options?.detail; }
        }, Node: {DOCUMENT_POSITION_FOLLOWING: 4}, CSS: {escape: value => value},
        history: {replaceState() {}},
        setTimeout(callback, delay) {
            if (delay === 30000) { const timer = {callback}; watchdogs.push(timer); return timer; }
            if (delay === 3600) return null;
            return setTimeout(callback, delay);
        },
        clearTimeout(timer) { if (timer?.callback) timer.cancelled = true; else clearTimeout(timer); },
    });
    const component = factory();
    component.$nextTick = callback => callback();
    const result = {ok: true, url: 'https://school.test/alumno/modulo/uno', item_id: 1, marker_id: 'marker'};
    component.$wire = {resolveMarker: async () => result};
    const link = {id: 'source-link', closest: selector => selector.startsWith('section') ? {id: 'lectura-1'} : null};
    return {component, result, link, target, properties, storage, watchdogs, document, window, location, background, existingInert};
}

test('scroll and background interaction stay locked until the target is visible and stable', async () => {
    const env = setup();
    const pending = env.component.navigate({}, env.link);
    assert.equal(env.component.busy, true);
    assert.equal(env.properties.get('overflow'), 'hidden');
    assert.equal(env.background.inert, true);
    const wheel = new Event('wheel', {cancelable: true});
    env.document.dispatchEvent(wheel);
    assert.equal(wheel.defaultPrevented, true);
    await pending;
    assert.ok(env.target.scrolls >= 4);
    assert.equal(env.component.busy, false);
    assert.equal(env.component.canReturn, true);
    assert.equal(env.properties.get('overflow'), 'auto');
    assert.equal(env.background.inert, false);
    assert.equal(env.existingInert.inert, true);
    const wheelAfterArrival = new Event('wheel', {cancelable: true});
    env.document.dispatchEvent(wheelAfterArrival);
    assert.equal(wheelAfterArrival.defaultPrevented, false);
});

test('cancel ignores a late server response and restores scrolling', async () => {
    const env = setup();
    let respond;
    env.component.$wire.resolveMarker = () => new Promise(resolve => { respond = resolve; });
    const pending = env.component.navigate({}, env.link);
    env.component.cancelNavigation();
    respond(env.result);
    await pending;
    assert.equal(env.component.busy, false);
    assert.equal(env.properties.get('overflow'), 'auto');
    assert.equal(env.target.scrolls, 0);
    assert.equal(env.location.assigned.length, 0);
    assert.equal(env.storage.has('edu-marker-return-stack'), false);
});

test('blocked destinations and failed requests release the lock and explain the failure', async () => {
    for (const fail of [false, true]) {
        const env = setup();
        env.component.$wire.resolveMarker = async () => {
            if (fail) throw new Error('offline');
            return {ok: false, message: 'Completa primero la lectura.'};
        };
        await env.component.navigate({}, env.link);
        assert.equal(env.component.busy, false);
        assert.equal(env.properties.get('overflow'), 'auto');
        assert.ok(env.component.message.length > 0);
        assert.equal(env.target.scrolls, 0);
    }
});

test('a cross-module handoff keeps the indicator and timeout releases an interrupted page load', async () => {
    const env = setup();
    env.result.url = 'https://school.test/alumno/modulo/dos';
    await env.component.navigate({}, env.link);
    assert.equal(env.location.assigned.length, 1);
    assert.equal(env.component.busy, true);
    assert.equal(env.properties.get('overflow'), 'hidden');
    env.watchdogs[0].callback();
    assert.equal(env.window.stopped, 1);
    assert.equal(env.component.busy, false);
    assert.equal(env.properties.get('overflow'), 'auto');
    assert.match(env.component.message, /tardando/);
    assert.deepEqual(JSON.parse(env.storage.get('edu-marker-return-stack')), []);
});

test('arrival with a marker locks before Alpine starts and has an independent safety timeout', () => {
    const env = setup('#marca=example');
    assert.equal(env.properties.get('overflow'), 'hidden');
    env.watchdogs[0].callback();
    assert.equal(env.properties.get('overflow'), 'auto');
});

test('Escape cancels an active navigation and a server response arriving afterwards cannot move the page', async () => {
    const env = setup();
    let respond;
    env.component.$wire.resolveMarker = () => new Promise(resolve => { respond = resolve; });
    const pending = env.component.navigate({}, env.link);
    const escape = new Event('keydown', {cancelable: true});
    Object.defineProperty(escape, 'key', {value: 'Escape'});
    env.document.dispatchEvent(escape);
    respond(env.result);
    await pending;
    assert.equal(escape.defaultPrevented, true);
    assert.equal(env.component.busy, false);
    assert.equal(env.target.scrolls, 0);
});

test('an image above the destination keeps scrolling locked until its layout settles', async () => {
    const env = setup();
    const image = {complete: false, getClientRects: () => [{}], compareDocumentPosition: () => 4};
    env.document.images = [image];
    const pending = env.component.navigate({}, env.link);
    await new Promise(resolve => setTimeout(resolve, 250));
    assert.equal(env.component.busy, true);
    assert.equal(env.properties.get('overflow'), 'hidden');
    image.complete = true;
    await pending;
    assert.equal(env.component.busy, false);
    assert.equal(env.component.canReturn, true);
});

test('cancellation of an old request cannot unlock or redirect a newer navigation', async () => {
    const env = setup();
    let respond;
    env.component.$wire.resolveMarker = () => new Promise(resolve => { respond = resolve; });
    const first = env.component.navigate({}, env.link);
    env.component.cancelNavigation();
    env.component.$wire.resolveMarker = async () => env.result;
    const second = env.component.navigate({}, env.link);
    respond(env.result);
    await first;
    assert.equal(env.component.busy, true);
    assert.equal(env.properties.get('overflow'), 'hidden');
    await second;
    assert.equal(env.component.busy, false);
});

test('navigation accepts reactive proxies without losing ownership of the loading operation', async () => {
    const env = setup();
    let storedOperation;
    Object.defineProperty(env.component, 'operation', {
        get: () => storedOperation ? new Proxy(storedOperation, {}) : null,
        set: value => { storedOperation = value; },
    });
    await env.component.navigate({}, env.link);
    assert.ok(env.target.scrolls >= 4);
    assert.equal(env.component.canReturn, true);
    assert.equal(env.component.busy, false);
    assert.equal(env.properties.get('overflow'), 'auto');
});

test('an invalid marker URL releases the early lock instead of trapping the page', async () => {
    const env = setup('#marca=%invalid');
    await env.component.followHash();
    assert.equal(env.component.busy, false);
    assert.equal(env.properties.get('overflow'), 'auto');
    assert.ok(env.component.message.length > 0);
});
