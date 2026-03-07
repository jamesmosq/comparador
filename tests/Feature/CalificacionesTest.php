<?php

namespace Tests\Feature;

use App\Models\Calificacion;
use App\Models\Competencia;
use App\Models\Group;
use App\Models\Institution;
use App\Models\ProgramaFormacion;
use App\Models\ResultadoAprendizaje;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CalificacionesTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function setupScenario(): array
    {
        $user        = User::factory()->create();
        $institution = Institution::create(['name' => 'SENA Test', 'user_id' => $user->id]);
        $programa    = ProgramaFormacion::create(['name' => 'ADSO', 'code' => 'P01']);
        $group       = Group::create([
            'institution_id'      => $institution->id,
            'name'                => 'Ficha 785412',
            'ficha_number'        => '785412',
            'programa_formacion_id' => $programa->id,
        ]);
        $competencia = Competencia::create([
            'programa_formacion_id' => $programa->id,
            'name'                  => 'Diseñar soluciones',
            'code'                  => 'C01',
            'order'                 => 1,
        ]);
        $ra1 = ResultadoAprendizaje::create([
            'competencia_id' => $competencia->id,
            'name'           => 'Identificar requerimientos',
            'code'           => 'RA1',
            'order'          => 1,
        ]);
        $ra2 = ResultadoAprendizaje::create([
            'competencia_id' => $competencia->id,
            'name'           => 'Modelar la solución',
            'code'           => 'RA2',
            'order'          => 2,
        ]);
        $student1 = Student::create(['group_id' => $group->id, 'name' => 'Ana Torres', 'identifier' => '1001']);
        $student2 = Student::create(['group_id' => $group->id, 'name' => 'Luis Pérez', 'identifier' => '1002']);

        return compact('user', 'institution', 'programa', 'group', 'competencia', 'ra1', 'ra2', 'student1', 'student2');
    }

    // ── 1. Guardar calificaciones (flujo completo con hydration Livewire) ─────

    /**
     * Reproduce el bug: Livewire hidrata colecciones como arrays PHP planos.
     * Si guardar() usa $student->id (acceso de objeto), retorna null en arrays.
     * data_get() debe resolver tanto objetos como arrays.
     */
    public function test_guardar_persiste_notas_en_bd()
    {
        ['user' => $user, 'group' => $group, 'competencia' => $competencia,
         'ra1' => $ra1, 'ra2' => $ra2, 'student1' => $student1, 'student2' => $student2] = $this->setupScenario();

        $notas = [
            "{$student1->id}_{$ra1->id}" => '3.5',
            "{$student1->id}_{$ra2->id}" => '4.0',
            "{$student2->id}_{$ra1->id}" => '2.5',
            "{$student2->id}_{$ra2->id}" => '3.0',
        ];

        // Simula el flujo completo: seleccionar ficha → competencia → guardar
        // Cada ->set() y ->call() es un round-trip Livewire completo (hydrate/dehydrate)
        Volt::actingAs($user)
            ->test('calificaciones')
            ->set('group_id', $group->id)       // round-trip 1: updatedGroupId()
            ->set('competencia_id', $competencia->id) // round-trip 2: updatedCompetenciaId()
            ->call('saveNotasData', $notas, []); // round-trip 3: guardar() con hydration

        // Las 4 notas deben estar en la BD
        $this->assertDatabaseCount('calificaciones', 4);

        $this->assertDatabaseHas('calificaciones', [
            'student_id'               => $student1->id,
            'resultado_aprendizaje_id' => $ra1->id,
            'group_id'                 => $group->id,
            'nota'                     => 3.5,
        ]);
        $this->assertDatabaseHas('calificaciones', [
            'student_id'               => $student2->id,
            'resultado_aprendizaje_id' => $ra2->id,
            'group_id'                 => $group->id,
            'nota'                     => 3.0,
        ]);
    }

    // ── 2. Notas vacías no crean registros ────────────────────────────────────

    public function test_notas_vacias_no_se_guardan()
    {
        ['user' => $user, 'group' => $group, 'competencia' => $competencia,
         'ra1' => $ra1, 'student1' => $student1, 'student2' => $student2] = $this->setupScenario();

        // Solo student1 tiene nota; student2 la deja en blanco
        $notas = [
            "{$student1->id}_{$ra1->id}" => '4.0',
            "{$student2->id}_{$ra1->id}" => null,
        ];

        Volt::actingAs($user)
            ->test('calificaciones')
            ->set('group_id', $group->id)
            ->set('competencia_id', $competencia->id)
            ->call('saveNotasData', $notas, []);

        $this->assertDatabaseCount('calificaciones', 1);
        $this->assertDatabaseHas('calificaciones', ['student_id' => $student1->id, 'nota' => 4.0]);
        $this->assertDatabaseMissing('calificaciones', ['student_id' => $student2->id]);
    }

    // ── 3. updateOrCreate: re-guardar actualiza la nota existente ─────────────

    public function test_guardar_actualiza_nota_existente()
    {
        ['user' => $user, 'group' => $group, 'competencia' => $competencia,
         'ra1' => $ra1, 'student1' => $student1] = $this->setupScenario();

        $notas = ["{$student1->id}_{$ra1->id}" => '3.0'];

        $component = Volt::actingAs($user)
            ->test('calificaciones')
            ->set('group_id', $group->id)
            ->set('competencia_id', $competencia->id);

        // Guardar por primera vez
        $component->call('saveNotasData', $notas, []);
        $this->assertDatabaseHas('calificaciones', ['student_id' => $student1->id, 'nota' => 3.0]);

        // Actualizar la nota
        $component->call('saveNotasData', ["{$student1->id}_{$ra1->id}" => '4.5'], []);
        $this->assertDatabaseHas('calificaciones', ['student_id' => $student1->id, 'nota' => 4.5]);
        $this->assertDatabaseCount('calificaciones', 1); // no duplicados
    }

    // ── 4. Validación: notas fuera de rango son rechazadas ───────────────────

    public function test_nota_mayor_a_5_es_rechazada()
    {
        ['user' => $user, 'group' => $group, 'competencia' => $competencia,
         'ra1' => $ra1, 'student1' => $student1] = $this->setupScenario();

        Volt::actingAs($user)
            ->test('calificaciones')
            ->set('group_id', $group->id)
            ->set('competencia_id', $competencia->id)
            ->call('saveNotasData', ["{$student1->id}_{$ra1->id}" => '5.5'], []);

        $this->assertDatabaseCount('calificaciones', 0);
    }

    public function test_nota_menor_a_1_es_rechazada()
    {
        ['user' => $user, 'group' => $group, 'competencia' => $competencia,
         'ra1' => $ra1, 'student1' => $student1] = $this->setupScenario();

        Volt::actingAs($user)
            ->test('calificaciones')
            ->set('group_id', $group->id)
            ->set('competencia_id', $competencia->id)
            ->call('saveNotasData', ["{$student1->id}_{$ra1->id}" => '0.5'], []);

        $this->assertDatabaseCount('calificaciones', 0);
    }

    public function test_nota_negativa_es_rechazada()
    {
        ['user' => $user, 'group' => $group, 'competencia' => $competencia,
         'ra1' => $ra1, 'student1' => $student1] = $this->setupScenario();

        Volt::actingAs($user)
            ->test('calificaciones')
            ->set('group_id', $group->id)
            ->set('competencia_id', $competencia->id)
            ->call('saveNotasData', ["{$student1->id}_{$ra1->id}" => '-1.0'], []);

        $this->assertDatabaseCount('calificaciones', 0);
    }

    // ── 5. cargarCalificaciones: carga notas guardadas al re-seleccionar ──────

    public function test_carga_notas_guardadas_al_seleccionar_competencia()
    {
        ['user' => $user, 'group' => $group, 'competencia' => $competencia,
         'ra1' => $ra1, 'student1' => $student1] = $this->setupScenario();

        // Crear calificación preexistente directamente en BD
        Calificacion::create([
            'student_id'               => $student1->id,
            'resultado_aprendizaje_id' => $ra1->id,
            'group_id'                 => $group->id,
            'nota'                     => 4.2,
            'user_id'                  => $user->id,
        ]);

        // Simular que el usuario selecciona ficha y competencia
        $component = Volt::actingAs($user)
            ->test('calificaciones')
            ->set('group_id', $group->id)
            ->set('competencia_id', $competencia->id);

        // Las notas deben estar cargadas en el estado del componente
        $notas = $component->get('notas');
        $key   = "{$student1->id}_{$ra1->id}";

        $this->assertArrayHasKey($key, $notas, "La nota del aprendiz no fue cargada en el estado del componente");
        $this->assertEquals('4.2', $notas[$key]);
    }

    // ── 6. Sin grupo/competencia seleccionado no guarda ──────────────────────

    public function test_sin_group_id_no_guarda()
    {
        $user = User::factory()->create();

        Volt::actingAs($user)
            ->test('calificaciones')
            ->call('saveNotasData', ['1_1' => '3.5'], []);

        $this->assertDatabaseCount('calificaciones', 0);
    }

    // ── 7. Observación se guarda con la calificación ──────────────────────────

    public function test_observacion_se_guarda()
    {
        ['user' => $user, 'group' => $group, 'competencia' => $competencia,
         'ra1' => $ra1, 'student1' => $student1] = $this->setupScenario();

        $notas        = ["{$student1->id}_{$ra1->id}" => '3.5'];
        $observaciones = [(string) $student1->id => 'Debe mejorar la atención'];

        Volt::actingAs($user)
            ->test('calificaciones')
            ->set('group_id', $group->id)
            ->set('competencia_id', $competencia->id)
            ->call('saveNotasData', $notas, $observaciones);

        $this->assertDatabaseHas('calificaciones', [
            'student_id'  => $student1->id,
            'nota'        => 3.5,
            'observacion' => 'Debe mejorar la atención',
        ]);
    }
}
