<?php

namespace Database\Seeders;

use App\Models\ProgramaFormacion;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProgramaFormacionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@comparador.com')->first();

        $programas = [
            [
                'name'        => 'Tecnología en Análisis y Desarrollo de Software',
                'code'        => '228118',
                'description' => 'Programa de formación tecnológica orientado al desarrollo de soluciones de software.',
            ],
            [
                'name'        => 'Técnico en Programación de Software',
                'code'        => '623619',
                'description' => 'Programa técnico para la programación y soporte de aplicaciones.',
            ],
            [
                'name'        => 'Tecnología en Gestión Empresarial',
                'code'        => '134207',
                'description' => 'Formación en gestión y administración de empresas.',
            ],
        ];

        foreach ($programas as $data) {
            ProgramaFormacion::firstOrCreate(['code' => $data['code']], array_merge($data, ['user_id' => $admin?->id]));
        }
    }
}
