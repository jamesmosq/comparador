<?php

namespace Database\Seeders;

use App\Models\Acta;
use App\Models\ActaCompromiso;
use App\Models\Competencia;
use App\Models\DocentePar;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActaSeeder extends Seeder
{
    public function run(): void
    {
        $instructor1 = User::where('email', 'c.ramirez@sena.edu.co')->first();
        $instructor2 = User::where('email', 'l.perez@sena.edu.co')->first();

        $group1 = Group::where('ficha_number', '2758341')->first();
        $group2 = Group::where('ficha_number', '2891045')->first();
        $group3 = Group::where('ficha_number', '2654789')->first();

        $docente1 = DocentePar::where('document_number', '79456123')->first();
        $docente2 = DocentePar::where('document_number', '43789654')->first();

        $comp1 = Competencia::where('code', '220501001')->first();
        $comp2 = Competencia::where('code', '220501002')->first();
        $comp3 = Competencia::where('code', '220501010')->first();

        $actas = [
            [
                'acta' => [
                    'user_id'       => $instructor1?->id,
                    'group_id'      => $group1?->id,
                    'docente_par_id'=> $docente1?->id,
                    'competencia_id'=> $comp1?->id,
                    'tipo'          => 'inicio_ficha',
                    'numero_acta'   => 'ACTA-2024-001',
                    'fecha'         => '2024-02-05',
                    'lugar'         => 'Aula 302 - SENA Centro de Comercio y Servicios',
                    'hora_inicio'   => '08:00:00',
                    'hora_fin'      => '10:00:00',
                    'estado'        => 'finalizada',
                    'objetivo'      => 'Verificar el proceso de inicio de la ficha 2758341 del programa ADSI.',
                    'agenda'        => "1. Presentación del instructor y aprendices\n2. Revisión del plan de estudios\n3. Verificación de condiciones de aprendizaje\n4. Compromisos",
                    'desarrollo'    => 'Se realizó la visita de inicio de ficha. Se verificó el ambiente de aprendizaje, equipos de cómputo disponibles y el plan de formación. Los aprendices manifestaron conocer el reglamento del aprendiz.',
                    'observaciones' => 'El ambiente de aprendizaje cuenta con 20 equipos en buen estado con acceso a internet.',
                ],
                'compromisos' => [
                    ['descripcion' => 'Enviar cronograma de formación actualizado al docente par.', 'responsable' => 'instructor_sena', 'fecha_limite' => '2024-02-15', 'orden' => 1],
                    ['descripcion' => 'Verificar la entrega de materiales de formación a los aprendices.', 'responsable' => 'ambos', 'fecha_limite' => '2024-02-20', 'orden' => 2],
                ],
                'grupos' => [$group1?->id],
            ],
            [
                'acta' => [
                    'user_id'       => $instructor1?->id,
                    'group_id'      => $group1?->id,
                    'docente_par_id'=> $docente1?->id,
                    'competencia_id'=> $comp2?->id,
                    'tipo'          => 'visita_seguimiento',
                    'numero_acta'   => 'ACTA-2024-002',
                    'fecha'         => '2024-04-18',
                    'lugar'         => 'Aula 302 - SENA Centro de Comercio y Servicios',
                    'hora_inicio'   => '09:00:00',
                    'hora_fin'      => '11:00:00',
                    'estado'        => 'finalizada',
                    'objetivo'      => 'Realizar seguimiento al proceso formativo de la competencia 220501002.',
                    'agenda'        => "1. Revisión de avance de la competencia\n2. Revisión de proyectos formativos\n3. Evaluación del proceso\n4. Compromisos y cierre",
                    'desarrollo'    => 'Se verificó el avance en el desarrollo de la competencia. Los aprendices se encuentran en la fase de codificación de componentes. Se revisaron los proyectos formativos y se evidenció cumplimiento del plan.',
                    'observaciones' => 'Se recomienda fortalecer las prácticas de pruebas de software.',
                ],
                'compromisos' => [
                    ['descripcion' => 'Implementar sesión práctica de pruebas unitarias con los aprendices.', 'responsable' => 'instructor_sena', 'fecha_limite' => '2024-05-01', 'orden' => 1],
                    ['descripcion' => 'Revisar y retroalimentar los proyectos entregados por los aprendices.', 'responsable' => 'docente_par', 'fecha_limite' => '2024-05-10', 'orden' => 2],
                    ['descripcion' => 'Socializar resultados de la visita con la coordinación académica.', 'responsable' => 'ambos', 'fecha_limite' => '2024-05-15', 'orden' => 3],
                ],
                'grupos' => [$group1?->id, $group2?->id],
            ],
            [
                'acta' => [
                    'user_id'       => $instructor2?->id,
                    'group_id'      => $group3?->id,
                    'docente_par_id'=> $docente2?->id,
                    'competencia_id'=> $comp3?->id,
                    'tipo'          => 'seguimiento',
                    'numero_acta'   => 'ACTA-2024-003',
                    'fecha'         => '2024-05-22',
                    'lugar'         => 'Sala de Sistemas - SENA Centro de Tecnología de la Manufactura',
                    'hora_inicio'   => '10:00:00',
                    'hora_fin'      => '12:00:00',
                    'estado'        => 'borrador',
                    'objetivo'      => 'Seguimiento al proceso formativo de la ficha 2654789.',
                    'agenda'        => "1. Revisión de asistencia y avance\n2. Evaluación de evidencias\n3. Compromisos",
                    'desarrollo'    => 'Se realizó visita de seguimiento. Se verificaron las evidencias de aprendizaje y el avance en la competencia de programación.',
                    'observaciones' => null,
                ],
                'compromisos' => [
                    ['descripcion' => 'Actualizar el diario de campo con las actividades de la semana.', 'responsable' => 'instructor_sena', 'fecha_limite' => '2024-05-31', 'orden' => 1],
                ],
                'grupos' => [$group3?->id],
            ],
        ];

        foreach ($actas as $item) {
            $acta = Acta::firstOrCreate(
                ['numero_acta' => $item['acta']['numero_acta']],
                $item['acta']
            );

            // Sincronizar grupos en la tabla pivote
            $grupos = array_filter($item['grupos']);
            if (!empty($grupos)) {
                $acta->groups()->syncWithoutDetaching($grupos);
            }

            // Crear compromisos
            foreach ($item['compromisos'] as $comp) {
                ActaCompromiso::firstOrCreate(
                    ['acta_id' => $acta->id, 'orden' => $comp['orden']],
                    array_merge($comp, ['acta_id' => $acta->id])
                );
            }
        }
    }
}
