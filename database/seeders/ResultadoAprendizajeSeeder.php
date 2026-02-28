<?php

namespace Database\Seeders;

use App\Models\Competencia;
use App\Models\ResultadoAprendizaje;
use Illuminate\Database\Seeder;

class ResultadoAprendizajeSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            '220501001' => [
                ['code' => '220501001-01', 'name' => 'Interpretar los requerimientos del cliente para el diseño de software.',                  'order' => 1],
                ['code' => '220501001-02', 'name' => 'Diseñar la arquitectura del sistema aplicando patrones de diseño.',                       'order' => 2],
                ['code' => '220501001-03', 'name' => 'Implementar módulos de software conforme a los estándares definidos.',                    'order' => 3],
            ],
            '220501002' => [
                ['code' => '220501002-01', 'name' => 'Codificar componentes reutilizables según buenas prácticas de programación.',             'order' => 1],
                ['code' => '220501002-02', 'name' => 'Realizar pruebas unitarias e integración de los componentes desarrollados.',              'order' => 2],
            ],
            '220501003' => [
                ['code' => '220501003-01', 'name' => 'Diseñar el modelo entidad-relación según los requerimientos del sistema.',                'order' => 1],
                ['code' => '220501003-02', 'name' => 'Implementar consultas SQL complejas para reportes y análisis de datos.',                  'order' => 2],
            ],
            '220501010' => [
                ['code' => '220501010-01', 'name' => 'Analizar el diseño técnico para determinar la estrategia de codificación.',              'order' => 1],
                ['code' => '220501010-02', 'name' => 'Codificar los módulos del sistema según el diseño y estándares establecidos.',            'order' => 2],
            ],
            '220501011' => [
                ['code' => '220501011-01', 'name' => 'Identificar fallas en el sistema para aplicar acciones correctivas.',                    'order' => 1],
                ['code' => '220501011-02', 'name' => 'Optimizar el rendimiento de la aplicación mediante técnicas de refactorización.',         'order' => 2],
            ],
            '134207001' => [
                ['code' => '134207001-01', 'name' => 'Planificar actividades administrativas según los objetivos organizacionales.',            'order' => 1],
                ['code' => '134207001-02', 'name' => 'Controlar los procesos administrativos aplicando indicadores de gestión.',               'order' => 2],
            ],
            '134207002' => [
                ['code' => '134207002-01', 'name' => 'Diagnosticar el estado de los procesos empresariales para identificar oportunidades.',   'order' => 1],
                ['code' => '134207002-02', 'name' => 'Implementar planes de mejoramiento continuo en la organización.',                        'order' => 2],
            ],
        ];

        foreach ($map as $competenciaCode => $resultados) {
            $competencia = Competencia::where('code', $competenciaCode)->first();
            if (!$competencia) continue;

            foreach ($resultados as $data) {
                ResultadoAprendizaje::firstOrCreate(
                    ['code' => $data['code']],
                    array_merge($data, ['competencia_id' => $competencia->id])
                );
            }
        }
    }
}
