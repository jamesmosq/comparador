<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Institution;
use App\Models\ProgramaFormacion;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $inst1 = Institution::where('name', 'like', '%Comercio%')->first();
        $inst2 = Institution::where('name', 'like', '%Manufactura%')->first();
        $inst3 = Institution::where('name', 'like', '%Agroindustrial%')->first();

        $ads = ProgramaFormacion::where('code', '228118')->first();
        $tps = ProgramaFormacion::where('code', '623619')->first();
        $tge = ProgramaFormacion::where('code', '134207')->first();

        $groups = [
            [
                'institution_id'       => $inst1?->id,
                'programa_formacion_id'=> $ads?->id,
                'name'                 => 'Ficha 2758341 - ADSI',
                'ficha_number'         => '2758341',
                'day_of_week'          => 'Monday',
                'start_time'           => '07:00:00',
                'end_time'             => '12:00:00',
            ],
            [
                'institution_id'       => $inst1?->id,
                'programa_formacion_id'=> $ads?->id,
                'name'                 => 'Ficha 2891045 - ADSI',
                'ficha_number'         => '2891045',
                'day_of_week'          => 'Wednesday',
                'start_time'           => '13:00:00',
                'end_time'             => '18:00:00',
            ],
            [
                'institution_id'       => $inst2?->id,
                'programa_formacion_id'=> $tps?->id,
                'name'                 => 'Ficha 2654789 - Programación',
                'ficha_number'         => '2654789',
                'day_of_week'          => 'Tuesday',
                'start_time'           => '07:00:00',
                'end_time'             => '12:00:00',
            ],
            [
                'institution_id'       => $inst3?->id,
                'programa_formacion_id'=> $tge?->id,
                'name'                 => 'Ficha 2720113 - Gestión Empresarial',
                'ficha_number'         => '2720113',
                'day_of_week'          => 'Thursday',
                'start_time'           => '14:00:00',
                'end_time'             => '18:00:00',
            ],
        ];

        foreach ($groups as $data) {
            if ($data['institution_id']) {
                Group::firstOrCreate(['ficha_number' => $data['ficha_number']], $data);
            }
        }
    }
}
