<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ProgramaFormacion;
use App\Models\Competencia;
use App\Models\ResultadoAprendizaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Volt\Volt;

class ResultadoAprendizajeTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ────────────────────────────────────────────────────────────

    private function makePrograma(User $user, array $attrs = []): ProgramaFormacion
    {
        return ProgramaFormacion::create(array_merge([
            'user_id' => $user->id,
            'name'    => 'Análisis y Desarrollo de Software',
            'code'    => 'ADSO',
        ], $attrs));
    }

    private function makeCompetencia(ProgramaFormacion $programa, array $attrs = []): Competencia
    {
        return Competencia::create(array_merge([
            'programa_formacion_id' => $programa->id,
            'code'                  => '240201501',
            'name'                  => 'Construir módulos de software',
            'total_hours'           => 240,
        ], $attrs));
    }

    private function makeResultado(Competencia $comp, array $attrs = []): ResultadoAprendizaje
    {
        return ResultadoAprendizaje::create(array_merge([
            'competencia_id' => $comp->id,
            'code'           => 'RA1',
            'name'           => 'Identificar requerimientos del cliente',
        ], $attrs));
    }

    // ── Modelo: relaciones ──────────────────────────────────────────────────

    public function test_resultado_aprendizaje_belongs_to_competencia()
    {
        $user  = User::factory()->create();
        $prog  = $this->makePrograma($user);
        $comp  = $this->makeCompetencia($prog);
        $ra    = $this->makeResultado($comp);

        $this->assertInstanceOf(Competencia::class, $ra->competencia);
        $this->assertEquals($comp->id, $ra->competencia->id);
    }

    public function test_competencia_has_many_resultados_aprendizaje()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);

        $this->makeResultado($comp, ['code' => 'RA1', 'name' => 'Primer resultado']);
        $this->makeResultado($comp, ['code' => 'RA2', 'name' => 'Segundo resultado']);
        $this->makeResultado($comp, ['code' => 'RA3', 'name' => 'Tercer resultado']);

        $this->assertCount(3, $comp->resultadosAprendizaje);
    }

    public function test_resultados_ordered_by_order_field()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);

        // Insertados en orden inverso
        $this->makeResultado($comp, ['name' => 'Tercero', 'order' => 3]);
        $this->makeResultado($comp, ['name' => 'Primero', 'order' => 1]);
        $this->makeResultado($comp, ['name' => 'Segundo', 'order' => 2]);

        $resultados = $comp->resultadosAprendizaje()->get();

        $this->assertEquals('Primero', $resultados[0]->name);
        $this->assertEquals('Segundo', $resultados[1]->name);
        $this->assertEquals('Tercero', $resultados[2]->name);
    }

    public function test_multiple_competencias_can_share_same_ra_code()
    {
        $user  = User::factory()->create();
        $prog  = $this->makePrograma($user);
        $comp1 = $this->makeCompetencia($prog, ['name' => 'Competencia 1']);
        $comp2 = $this->makeCompetencia($prog, ['name' => 'Competencia 2']);

        $this->makeResultado($comp1, ['code' => 'RA1', 'name' => 'RA de primera competencia']);
        $this->makeResultado($comp2, ['code' => 'RA1', 'name' => 'RA de segunda competencia']);

        $this->assertEquals(2, ResultadoAprendizaje::count());
        $this->assertEquals(1, $comp1->resultadosAprendizaje()->count());
        $this->assertEquals(1, $comp2->resultadosAprendizaje()->count());
    }

    // ── Modelo: integridad referencial ──────────────────────────────────────

    public function test_resultados_cascade_deleted_when_competencia_deleted()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);

        $this->makeResultado($comp, ['name' => 'RA 1']);
        $this->makeResultado($comp, ['name' => 'RA 2']);

        $this->assertEquals(2, ResultadoAprendizaje::count());

        $comp->delete();

        $this->assertEquals(0, ResultadoAprendizaje::count());
    }

    public function test_resultados_cascade_deleted_when_programa_deleted()
    {
        $user  = User::factory()->create();
        $prog  = $this->makePrograma($user);
        $comp1 = $this->makeCompetencia($prog, ['name' => 'Primera competencia']);
        $comp2 = $this->makeCompetencia($prog, ['name' => 'Segunda competencia']);

        $this->makeResultado($comp1, ['name' => 'RA de comp1']);
        $this->makeResultado($comp2, ['name' => 'RA de comp2']);

        $this->assertEquals(2, ResultadoAprendizaje::count());

        $prog->delete();

        $this->assertEquals(0, Competencia::count());
        $this->assertEquals(0, ResultadoAprendizaje::count());
    }

    // ── Livewire: crear ─────────────────────────────────────────────────────

    public function test_can_add_resultado_via_livewire()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);

        Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog->id)
            ->call('selectCompetencia', $comp->id)
            ->set('new_ra_code', 'RA1')
            ->set('new_ra_name', 'Identificar requerimientos del cliente')
            ->call('addResultado')
            ->assertHasNoErrors()
            ->assertSet('new_ra_name', '')
            ->assertSet('new_ra_code', '');

        $this->assertDatabaseHas('resultados_aprendizaje', [
            'competencia_id' => $comp->id,
            'code'           => 'RA1',
            'name'           => 'Identificar requerimientos del cliente',
        ]);
    }

    public function test_add_resultado_requires_name()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);

        Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog->id)
            ->call('selectCompetencia', $comp->id)
            ->set('new_ra_name', '')
            ->call('addResultado')
            ->assertHasErrors(['new_ra_name']);

        $this->assertEquals(0, ResultadoAprendizaje::count());
    }

    public function test_add_resultado_requires_minimum_name_length()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);

        Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog->id)
            ->call('selectCompetencia', $comp->id)
            ->set('new_ra_name', 'AB') // menos de 3 caracteres
            ->call('addResultado')
            ->assertHasErrors(['new_ra_name']);
    }

    public function test_new_resultado_gets_incremental_order()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);

        $component = Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog->id)
            ->call('selectCompetencia', $comp->id);

        foreach (['Primer RA de aprendizaje', 'Segundo RA de aprendizaje', 'Tercer RA de aprendizaje'] as $name) {
            $component->set('new_ra_name', $name)->call('addResultado');
        }

        $resultados = ResultadoAprendizaje::where('competencia_id', $comp->id)->orderBy('order')->get();
        $this->assertEquals(1, $resultados[0]->order);
        $this->assertEquals(2, $resultados[1]->order);
        $this->assertEquals(3, $resultados[2]->order);
    }

    // ── Livewire: editar ────────────────────────────────────────────────────

    public function test_can_open_edit_modal_with_correct_data()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);
        $ra   = $this->makeResultado($comp, ['code' => 'RA2', 'name' => 'Nombre original del resultado']);

        Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog->id)
            ->call('selectCompetencia', $comp->id)
            ->call('openEditResultado', $ra->id)
            ->assertSet('editing_ra_id',   $ra->id)
            ->assertSet('editing_ra_code', 'RA2')
            ->assertSet('editing_ra_name', 'Nombre original del resultado')
            ->assertSet('showEditRaModal', true);
    }

    public function test_can_edit_resultado_via_livewire()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);
        $ra   = $this->makeResultado($comp, ['code' => 'RA1', 'name' => 'Nombre original']);

        Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog->id)
            ->call('selectCompetencia', $comp->id)
            ->call('openEditResultado', $ra->id)
            ->set('editing_ra_code', 'RA1-MOD')
            ->set('editing_ra_name', 'Nombre modificado correctamente')
            ->call('saveResultado')
            ->assertHasNoErrors()
            ->assertSet('showEditRaModal', false);

        $this->assertDatabaseHas('resultados_aprendizaje', [
            'id'   => $ra->id,
            'code' => 'RA1-MOD',
            'name' => 'Nombre modificado correctamente',
        ]);
    }

    public function test_edit_resultado_requires_name()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);
        $ra   = $this->makeResultado($comp);

        Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog->id)
            ->call('selectCompetencia', $comp->id)
            ->call('openEditResultado', $ra->id)
            ->set('editing_ra_name', '')
            ->call('saveResultado')
            ->assertHasErrors(['editing_ra_name'])
            ->assertSet('showEditRaModal', true); // modal stays open on error
    }

    // ── Livewire: eliminar ──────────────────────────────────────────────────

    public function test_can_delete_resultado_via_livewire()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);
        $ra   = $this->makeResultado($comp);

        $this->assertEquals(1, ResultadoAprendizaje::count());

        Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog->id)
            ->call('selectCompetencia', $comp->id)
            ->call('deleteResultado', $ra->id)
            ->assertHasNoErrors();

        $this->assertEquals(0, ResultadoAprendizaje::count());
    }

    public function test_delete_resultado_updates_competencia_ra_count()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);
        $ra1  = $this->makeResultado($comp, ['name' => 'Resultado 1', 'order' => 1]);
        $ra2  = $this->makeResultado($comp, ['name' => 'Resultado 2', 'order' => 2]);

        $component = Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog->id)
            ->call('selectCompetencia', $comp->id);

        $this->assertCount(2, $component->get('resultados'));

        $component->call('deleteResultado', $ra1->id);

        $this->assertCount(1, $component->get('resultados'));
        $this->assertEquals(1, ResultadoAprendizaje::count());
    }

    // ── Livewire: estado del componente ─────────────────────────────────────

    public function test_resultados_load_when_competencia_selected()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);

        $this->makeResultado($comp, ['code' => 'RA1', 'name' => 'Primer resultado',  'order' => 1]);
        $this->makeResultado($comp, ['code' => 'RA2', 'name' => 'Segundo resultado', 'order' => 2]);

        $component = Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog->id)
            ->call('selectCompetencia', $comp->id);

        $this->assertCount(2, $component->get('resultados'));
    }

    public function test_resultados_cleared_when_different_programa_selected()
    {
        $user  = User::factory()->create();
        $prog1 = $this->makePrograma($user, ['name' => 'Programa 1']);
        $prog2 = $this->makePrograma($user, ['name' => 'Programa 2']);
        $comp  = $this->makeCompetencia($prog1);
        $this->makeResultado($comp);

        $component = Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog1->id)
            ->call('selectCompetencia', $comp->id)
            ->call('selectPrograma', $prog2->id); // cambiar de programa

        $component->assertSet('selected_competencia_id', null);
        $this->assertCount(0, $component->get('resultados'));
    }

    public function test_competencia_ra_count_badge_updates_after_add()
    {
        $user = User::factory()->create();
        $prog = $this->makePrograma($user);
        $comp = $this->makeCompetencia($prog);

        $component = Volt::actingAs($user)->test('manage-programas')
            ->call('selectPrograma', $prog->id)
            ->call('selectCompetencia', $comp->id)
            ->set('new_ra_name', 'Primer resultado de aprendizaje')
            ->call('addResultado');

        // La competencia debe reflejar 1 RA en la lista
        $competencias = $component->get('competencias');
        $compData = collect($competencias)->firstWhere('id', $comp->id);
        $this->assertEquals(1, $compData->resultados_aprendizaje_count);
    }
}
