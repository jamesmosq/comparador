<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $instructor1 = User::where('email', 'c.ramirez@sena.edu.co')->first();
        $instructor2 = User::where('email', 'l.perez@sena.edu.co')->first();
        $instructor3 = User::where('email', 'j.castillo@sena.edu.co')->first();

        $instituciones = [
            ['name' => 'SENA Centro de Comercio y Servicios',           'address' => 'Calle 52 # 13-65, Bogotá',           'user_id' => $instructor1?->id],
            ['name' => 'SENA Centro de Tecnología de la Manufactura',   'address' => 'Carrera 22 # 45-10, Medellín',        'user_id' => $instructor2?->id],
            ['name' => 'SENA Centro Agroindustrial',                    'address' => 'Km 7 Vía Palmira, Valle del Cauca',   'user_id' => $instructor3?->id],
        ];

        foreach ($instituciones as $data) {
            Institution::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
