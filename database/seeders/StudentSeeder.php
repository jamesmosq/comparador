<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $aprendices = [
            '2758341' => [
                ['name' => 'Valentina Torres Gómez',      'email' => 'v.torres@aprendiz.sena.edu.co',    'identifier' => '1023456789'],
                ['name' => 'Sebastián López Herrera',     'email' => 's.lopez@aprendiz.sena.edu.co',     'identifier' => '1034567890'],
                ['name' => 'Camila Rodríguez Martínez',   'email' => 'c.rodriguez@aprendiz.sena.edu.co', 'identifier' => '1045678901'],
                ['name' => 'Andrés Felipe Mora',          'email' => 'a.mora@aprendiz.sena.edu.co',      'identifier' => '1056789012'],
                ['name' => 'María Alejandra Sánchez',     'email' => 'm.sanchez@aprendiz.sena.edu.co',   'identifier' => '1067890123'],
                ['name' => 'Juan Pablo Vargas',           'email' => 'j.vargas@aprendiz.sena.edu.co',    'identifier' => '1078901234'],
                ['name' => 'Daniela Ospina Rivera',       'email' => 'd.ospina@aprendiz.sena.edu.co',    'identifier' => '1089012345'],
                ['name' => 'Diego Alejandro Patiño',      'email' => 'd.patino@aprendiz.sena.edu.co',    'identifier' => '1090123456'],
            ],
            '2891045' => [
                ['name' => 'Laura Sofía Cardona',         'email' => 'l.cardona@aprendiz.sena.edu.co',   'identifier' => '1011234567'],
                ['name' => 'Nicolás Arango Bedoya',       'email' => 'n.arango@aprendiz.sena.edu.co',    'identifier' => '1022345678'],
                ['name' => 'Isabella Giraldo Muñoz',      'email' => 'i.giraldo@aprendiz.sena.edu.co',   'identifier' => '1033456789'],
                ['name' => 'Mateo Salazar Oquendo',       'email' => 'm.salazar@aprendiz.sena.edu.co',   'identifier' => '1044567890'],
                ['name' => 'Sara Montoya Álvarez',        'email' => 's.montoya@aprendiz.sena.edu.co',   'identifier' => '1055678901'],
                ['name' => 'Samuel Tobón Restrepo',       'email' => 'sa.tobon@aprendiz.sena.edu.co',    'identifier' => '1066789012'],
            ],
            '2654789' => [
                ['name' => 'Juliana Cárdenas Peña',       'email' => 'j.cardenas@aprendiz.sena.edu.co',  'identifier' => '1077890123'],
                ['name' => 'David Esteban Ruiz',          'email' => 'd.ruiz@aprendiz.sena.edu.co',      'identifier' => '1088901234'],
                ['name' => 'Natalia Quintero Lagos',      'email' => 'n.quintero@aprendiz.sena.edu.co',  'identifier' => '1099012345'],
                ['name' => 'Alejandro Mendoza Cortés',    'email' => 'a.mendoza@aprendiz.sena.edu.co',   'identifier' => '1110123456'],
                ['name' => 'Paola Andrea Nieto',          'email' => 'p.nieto@aprendiz.sena.edu.co',     'identifier' => '1121234567'],
            ],
            '2720113' => [
                ['name' => 'Carolina Gómez Suárez',       'email' => 'c.gomez@aprendiz.sena.edu.co',     'identifier' => '1132345678'],
                ['name' => 'Felipe Rincón Díaz',          'email' => 'f.rincon@aprendiz.sena.edu.co',    'identifier' => '1143456789'],
                ['name' => 'Ana Lucía Valderrama',        'email' => 'a.valderrama@aprendiz.sena.edu.co','identifier' => '1154567890'],
                ['name' => 'Esteban Gutiérrez Mora',      'email' => 'e.gutierrez@aprendiz.sena.edu.co', 'identifier' => '1165678901'],
                ['name' => 'Manuela Lozano Acosta',       'email' => 'm.lozano@aprendiz.sena.edu.co',    'identifier' => '1176789012'],
            ],
        ];

        foreach ($aprendices as $ficha => $lista) {
            $group = Group::where('ficha_number', $ficha)->first();
            if (!$group) continue;

            foreach ($lista as $data) {
                Student::firstOrCreate(
                    ['identifier' => $data['identifier']],
                    array_merge($data, ['group_id' => $group->id])
                );
            }
        }
    }
}
