<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

          /*
        |--------------------------------------------------------------------------
        | Módulo : Creacion de admin por default
        |--------------------------------------------------------------------------
        */
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'admin',
                'password' => Hash::make('adminadmin'),
                'role' => 'admin',
            ]
        );


        $this->call([
            ModuleSeeder::class,
        ]);
    }
}
