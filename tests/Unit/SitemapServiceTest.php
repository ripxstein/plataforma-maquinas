<?php

use App\Services\SitemapService;

test('extractMarkers finds concept markers from html', function () {
    $html = <<<HTML
<p>Texto previo</p>
<mark class="edu-concept-marker" data-concept-id="c123" data-concept-title="Fatiga de materiales">fatiga</mark>
<p>Otro texto con la misma marca fragmentada</p>
<mark class="edu-concept-marker" data-concept-id="c123" data-concept-title="Fatiga de materiales"> mecánica</mark>
<mark class="edu-concept-marker" data-concept-id="c999" data-concept-title="Límite elástico">límite</mark>
HTML;

    $markers = SitemapService::extractMarkers($html);

    expect($markers)->toHaveCount(2);
    expect($markers[0]['marker_id'])->toBe('c123');
    expect($markers[0]['title'])->toBe('Fatiga de materiales');
    expect($markers[1]['marker_id'])->toBe('c999');
    expect($markers[1]['title'])->toBe('Límite elástico');
});

test('sanitize cleans invalid nodes and duplicate edges', function () {
    $nodes = [
        ['id' => 'n1', 'type' => 'free', 'label' => 'Central', 'x' => 10, 'y' => 20, 'color' => 'blue'],
        ['id' => 'n2', 'type' => 'marker', 'label' => 'Fatiga', 'x' => 100, 'y' => 200, 'color' => 'teal', 'item_id' => 5, 'marker_id' => 'c1'],
        ['id' => 'bad!id', 'type' => 'free', 'label' => 'Invalid', 'x' => 0, 'y' => 0], // id inválido
    ];

    $edges = [
        ['id' => 'e1', 'from' => 'n1', 'to' => 'n2', 'label' => 'se divide en'],
        ['id' => 'e2', 'from' => 'n1', 'to' => 'n2', 'label' => 'duplicado'], // duplicada
        ['id' => 'e3', 'from' => 'n1', 'to' => 'n1', 'label' => 'loop'], // loop mismo nodo
        ['id' => 'e4', 'from' => 'n1', 'to' => 'nonexistent', 'label' => 'hacia nodo fantasma'],
    ];

    $result = SitemapService::sanitize($nodes, $edges);

    expect($result['nodes'])->toHaveCount(2);
    expect($result['edges'])->toHaveCount(1);
    expect($result['edges'][0]['id'])->toBe('e1');
    expect($result['edges'][0]['from'])->toBe('n1');
    expect($result['edges'][0]['to'])->toBe('n2');
});
