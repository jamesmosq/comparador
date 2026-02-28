<?php

namespace Database\Seeders;

use App\Models\DocentePar;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocenteParSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@comparador.com')->first();

        $docentes = [
            [
                'name'             => 'Prof. Hernando Cifuentes Ríos',
                'document_number'  => '79456123',
                'position'         => 'Docente Universitario',
                'email'            => 'h.cifuentes@universidad.edu.co',
                'institution_name' => 'Universidad Nacional de Colombia',
            ],
            [
                'name'             => 'Prof. Gloria Inés Bermúdez',
                'document_number'  => '43789654',
                'position'         => 'Docente de Planta',
                'email'            => 'g.bermudez@unaula.edu.co',
                'institution_name' => 'Universidad Autónoma Latinoamericana',
            ],
            [
                'name'             => 'Prof. Ricardo Adolfo Mejía',
                'document_number'  => '71234987',
                'position'         => 'Coordinador Académico',
                'email'            => 'r.mejia@eafit.edu.co',
                'institution_name' => 'Universidad EAFIT',
            ],
        ];

        foreach ($docentes as $data) {
            DocentePar::firstOrCreate(
                ['document_number' => $data['document_number']],
                array_merge($data, ['user_id' => $admin?->id])
            );
        }
    }
}
