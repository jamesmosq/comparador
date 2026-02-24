<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Competencia;
use App\Models\Group;
use App\Models\Institution;
use App\Models\ProgramaFormacion;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportReportTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function makeFixtures(): array
    {
        $user        = User::factory()->create();
        $institution = Institution::create(['user_id' => $user->id, 'name' => 'SENA']);
        $programa    = ProgramaFormacion::create([
            'user_id' => $user->id,
            'name'    => 'Análisis y Desarrollo de Software',
            'code'    => 'ADSO',
        ]);
        $group = Group::create([
            'institution_id'       => $institution->id,
            'programa_formacion_id' => $programa->id,
            'name'                 => 'Ficha 2758431',
        ]);
        $competencia = Competencia::create([
            'programa_formacion_id' => $programa->id,
            'code'                  => '240201501',
            'name'                  => 'Construir módulos de software',
            'total_hours'           => 240,
        ]);
        $student1 = Student::create(['group_id' => $group->id, 'name' => 'Ana García',   'identifier' => '1001']);
        $student2 = Student::create(['group_id' => $group->id, 'name' => 'Luis Pérez',   'identifier' => '1002']);
        $student3 = Student::create(['group_id' => $group->id, 'name' => 'María Torres', 'identifier' => '1003']);

        return compact('user', 'group', 'competencia', 'student1', 'student2', 'student3');
    }

    // ── Acceso ───────────────────────────────────────────────────────────────

    public function test_export_requires_authentication()
    {
        $response = $this->get(route('export.report', ['group_id' => 1]));
        $response->assertRedirect(route('login'));
    }

    public function test_export_returns_404_for_missing_group()
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('export.report', ['group_id' => 9999]))
            ->assertNotFound();
    }

    // ── Descarga del archivo ─────────────────────────────────────────────────

    public function test_export_returns_xlsx_download()
    {
        ['user' => $user, 'group' => $group] = $this->makeFixtures();

        $response = $this->actingAs($user)
            ->get(route('export.report', [
                'group_id' => $group->id,
                'start'    => now()->startOfMonth()->format('Y-m-d'),
                'end'      => now()->format('Y-m-d'),
            ]));

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString(
            '.xlsx',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_export_filename_contains_group_name_and_dates()
    {
        ['user' => $user, 'group' => $group] = $this->makeFixtures();

        $start = '2026-02-01';
        $end   = '2026-02-23';

        $response = $this->actingAs($user)
            ->get(route('export.report', [
                'group_id' => $group->id,
                'start'    => $start,
                'end'      => $end,
            ]));

        $response->assertOk();
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('Ficha_2758431', $disposition);
        $this->assertStringContainsString($start, $disposition);
        $this->assertStringContainsString($end,   $disposition);
    }

    public function test_export_with_attendance_data()
    {
        ['user' => $user, 'group' => $group, 'student1' => $s1, 'student2' => $s2] = $this->makeFixtures();

        $date = now()->format('Y-m-d');

        Attendance::create(['student_id' => $s1->id, 'attendance_date' => $date, 'is_present' => true,  'is_justified' => false]);
        Attendance::create(['student_id' => $s1->id, 'attendance_date' => $date, 'is_present' => true,  'is_justified' => false]);
        Attendance::create(['student_id' => $s2->id, 'attendance_date' => $date, 'is_present' => false, 'is_justified' => false]);

        $response = $this->actingAs($user)
            ->get(route('export.report', [
                'group_id' => $group->id,
                'start'    => $date,
                'end'      => $date,
            ]));

        $response->assertOk();
        // BinaryFileResponse no expone getContent(); verificar Content-Length > 0
        $this->assertGreaterThan(
            0,
            (int) $response->headers->get('Content-Length', 0)
        );
    }

    public function test_export_with_competencia_filter()
    {
        ['user' => $user, 'group' => $group, 'competencia' => $comp, 'student1' => $s1] = $this->makeFixtures();

        $date = now()->format('Y-m-d');

        // Registro con competencia
        Attendance::create([
            'student_id'      => $s1->id,
            'attendance_date' => $date,
            'is_present'      => true,
            'is_justified'    => false,
            'competencia_id'  => $comp->id,
        ]);
        // Registro sin competencia (no debe incluirse)
        Attendance::create([
            'student_id'      => $s1->id,
            'attendance_date' => $date,
            'is_present'      => false,
            'is_justified'    => false,
            'competencia_id'  => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('export.report', [
                'group_id'       => $group->id,
                'start'          => $date,
                'end'            => $date,
                'competencia_id' => $comp->id,
            ]));

        $response->assertOk();
        $this->assertGreaterThan(
            0,
            (int) $response->headers->get('Content-Length', 0)
        );
    }

    public function test_export_group_with_no_students_still_returns_file()
    {
        $user        = User::factory()->create();
        $institution = Institution::create(['user_id' => $user->id, 'name' => 'SENA']);
        $programa    = ProgramaFormacion::create(['user_id' => $user->id, 'name' => 'Prog', 'code' => 'P1']);
        $group       = Group::create([
            'institution_id'        => $institution->id,
            'programa_formacion_id' => $programa->id,
            'name'                  => 'Grupo Vacío',
        ]);

        $response = $this->actingAs($user)
            ->get(route('export.report', [
                'group_id' => $group->id,
                'start'    => '2026-02-01',
                'end'      => '2026-02-23',
            ]));

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_export_uses_default_date_range_when_omitted()
    {
        ['user' => $user, 'group' => $group] = $this->makeFixtures();

        // Sin parámetros start/end → usa inicio de mes y hoy
        $response = $this->actingAs($user)
            ->get(route('export.report', ['group_id' => $group->id]));

        $response->assertOk();
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString(now()->startOfMonth()->format('Y-m-d'), $disposition);
        $this->assertStringContainsString(now()->format('Y-m-d'), $disposition);
    }
}
