<?php

namespace Database\Seeders;

use App\Models\Competencia;
use App\Models\ProgramaFormacion;
use Illuminate\Database\Seeder;

class CompetenciaSeeder extends Seeder
{
    public function run(): void
    {
        $ads = ProgramaFormacion::where('code', '228118')->first();
        $tps = ProgramaFormacion::where('code', '623619')->first();
        $tge = ProgramaFormacion::where('code', '134207')->first();

        $competencias = [
            // ADS
            ['programa_formacion_id' => $ads?->id, 'code' => '220501001', 'name' => 'Desarrollar aplicaciones bajo estándares de ingeniería de software',   'total_hours' => 220, 'order' => 1],
            ['programa_formacion_id' => $ads?->id, 'code' => '220501002', 'name' => 'Construir componentes de software según requerimientos del cliente',      'total_hours' => 180, 'order' => 2],
            ['programa_formacion_id' => $ads?->id, 'code' => '220501003', 'name' => 'Gestionar bases de datos con tecnologías actuales',                       'total_hours' => 160, 'order' => 3],
            // TPS
            ['programa_formacion_id' => $tps?->id, 'code' => '220501010', 'name' => 'Programar aplicaciones de software según diseño técnico',                 'total_hours' => 200, 'order' => 1],
            ['programa_formacion_id' => $tps?->id, 'code' => '220501011', 'name' => 'Mantener y optimizar sistemas de información',                           'total_hours' => 120, 'order' => 2],
            // TGE
            ['programa_formacion_id' => $tge?->id, 'code' => '134207001', 'name' => 'Gestionar procesos administrativos según políticas organizacionales',    'total_hours' => 200, 'order' => 1],
            ['programa_formacion_id' => $tge?->id, 'code' => '134207002', 'name' => 'Coordinar acciones de mejoramiento empresarial',                         'total_hours' => 180, 'order' => 2],
        ];

        foreach ($competencias as $data) {
            if ($data['programa_formacion_id']) {
                Competencia::firstOrCreate(['code' => $data['code']], $data);
            }
        }
    }
}
