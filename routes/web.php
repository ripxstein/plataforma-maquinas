<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->role === 'admin') {
        return redirect()->route('admin.inicio');
    }

    return redirect()->route('student.inicio');
})->middleware(['auth'])->name('dashboard');

Route::get('/alumno/modulo/{slug}', function ($slug) {
    return view('student.modulo', compact('slug'));
})->middleware(['auth'])->name('student.modulo');

Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->name('admin.')
    ->group(function () {

        Route::view('/inicio', 'admin.inicio')->name('inicio');
        Route::view('/alumnos', 'admin.alumnos')->name('alumnos');
        Route::view('/progresos', 'admin.progresos')->name('progresos');


        Route::get('/', function () {
            return redirect()->route('admin.inicio');
        })->name('dashboard');

    });

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Route::view('/alumno/inicio', 'student.inicio')->name('student.inicio');
    Route::view('/alumno/criterios', 'student.criterios')->name('student.criterios');
    Route::view('/alumno/concentracion', 'student.concentracion')->name('student.concentracion');
    Route::view('/alumno/fallas', 'student.fallas')->name('student.fallas');
    Route::view('/alumno/aplicacion', 'student.aplicacion')->name('student.aplicacion');
    Route::view('/alumno/ejes', 'student.ejes')->name('student.ejes');
    Route::view('/alumno/retos', 'student.retos')->name('student.retos');
    Route::view('/alumno/bibliografia', 'student.bibliografia')->name('student.bibliografia');
});

require __DIR__.'/auth.php';