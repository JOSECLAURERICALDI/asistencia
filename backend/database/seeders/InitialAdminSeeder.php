<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Carrera;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        $carrera = Carrera::create([
            'nombre'      => 'Ingeniería de Sistemas',
            'sigla'       => 'CARSIS',
            'descripcion' => 'Carrera de Ingeniería de Sistemas',
        ]);

        Admin::create([
            'nombre'      => 'Director de Carrera',
            'email'       => 'director@carsis.edu.bo',
            'password'    => Hash::make('director123'),
            'carrera_id'  => $carrera->id,
            'super_admin' => false,
        ]);

        Admin::create([
            'nombre'      => 'Super Administrador',
            'email'       => 'admin@sistema.edu.bo',
            'password'    => Hash::make('admin123'),
            'carrera_id'  => null,
            'super_admin' => true,
        ]);
    }
}
