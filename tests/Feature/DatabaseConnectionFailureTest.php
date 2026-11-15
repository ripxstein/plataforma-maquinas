<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;

test('database connection failures show a temporary error without exposing connection details', function () {
    Route::get('/test-database-failure', function () {
        throw new QueryException('mysql', 'select * from users', [], new PDOException('Private database details', 2002));
    });

    $this->get('/test-database-failure')
        ->assertStatus(503)
        ->assertHeader('Retry-After', '30')
        ->assertSee('Servicio temporalmente no disponible')
        ->assertDontSee('Private database details');

    $this->getJson('/test-database-failure')
        ->assertStatus(503)
        ->assertHeader('Retry-After', '30')
        ->assertJsonPath('message', 'La base de datos no está disponible temporalmente. Intenta de nuevo en unos momentos.');
});

test('non connection query errors retain their normal response', function () {
    Route::get('/test-query-failure', function () {
        throw new QueryException('mysql', 'select missing from users', [], new PDOException('Unknown column', '42S22'));
    });

    $this->get('/test-query-failure')->assertStatus(500);
});

test('mysql connection timeout defaults to five seconds', function () {
    expect(config('database.connections.mysql.options')[PDO::ATTR_TIMEOUT])->toBe(5);
});
