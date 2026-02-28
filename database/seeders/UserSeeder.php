<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@comparador.com'], [
            'name'     => 'Administrador',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
        ]);

        $instructores = [
            ['name' => 'Carlos Andrés Ramírez',  'email' => 'c.ramirez@sena.edu.co'],
            ['name' => 'Luz Marina Pérez',        'email' => 'l.perez@sena.edu.co'],
            ['name' => 'Jorge Iván Castillo',     'email' => 'j.castillo@sena.edu.co'],
        ];

        foreach ($instructores as $data) {
            User::updateOrCreate(['email' => $data['email']], [
                'name'     => $data['name'],
                'password' => Hash::make('sena1234'),
                'role'     => 'teacher',
            ]);
        }
    }
}
