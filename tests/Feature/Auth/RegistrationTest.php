<?php

namespace Tests\Feature\Auth;

use App\Models\AccessCode;
use App\Models\User;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('new users can register', function () {
    AccessCode::create([
        'code' => 'ACC123',
        'group_name' => 'GRUPO-A',
        'active' => true,
    ]);

    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('access_code', 'ACC123')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('cannot register with an existing email', function () {
    AccessCode::create([
        'code' => 'ACC123',
        'group_name' => 'GRUPO-A',
        'active' => true,
    ]);

    User::factory()->create([
        'email' => 'duplicate@example.com',
    ]);

    $component = Volt::test('pages.auth.register')
        ->set('name', 'Another User')
        ->set('email', 'duplicate@example.com')
        ->set('access_code', 'ACC123')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertHasErrors(['email' => 'unique']);
});
