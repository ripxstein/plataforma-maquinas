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

use Illuminate\Http\Request;
use Illuminate\Support\Str;

Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->name('admin.')
    ->group(function () {

        Route::view('/inicio', 'admin.inicio')->name('inicio');
        Route::view('/alumnos', 'admin.alumnos')->name('alumnos');
        Route::view('/progresos', 'admin.progresos')->name('progresos');
        Route::view('/contenido', 'admin.contenido')->name('contenido');
        Route::view('/codigos', 'admin.codigos')->name('codigos');

        Route::post('/upload-image', function (Request $request) {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            ]);

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                
                $destinationPath = public_path('images/uploads');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $filename);

                return response()->json([
                    'url' => asset('images/uploads/' . $filename)
                ]);
            }

            return response()->json(['error' => 'No se pudo subir la imagen.'], 400);
        })->name('upload-image');

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