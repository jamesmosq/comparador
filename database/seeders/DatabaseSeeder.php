<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            InstitutionSeeder::class,
            ProgramaFormacionSeeder::class,
            CompetenciaSeeder::class,
            ResultadoAprendizajeSeeder::class,
            GroupSeeder::class,
            StudentSeeder::class,
            DocenteParSeeder::class,
            ActaSeeder::class,
        ]);
    }
}
