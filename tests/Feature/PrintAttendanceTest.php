<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Group;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\User;
use App\Models\ProgramaFormacion;
use App\Models\Competencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_page_requires_authentication()
    {
        $response = $this->get('/imprimir/asistencia?group_id=1&date=2026-01-01');
        $response->assertRedirect('/login');
    }

    public function test_print_page_returns_404_for_nonexistent_group()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/imprimir/asistencia?group_id=9999&date=2026-01-01');
        $response->assertStatus(404);
    }

    public function test_print_page_shows_student_list()
    {
        $user        = User::factory()->create();
        $institution = Institution::create(['name' => 'SENA', 'user_id' => $user->id]);
        $group       = Group::create(['institution_id' => $institution->id, 'name' => 'ADSO 2026']);
        Student::create(['group_id' => $group->id, 'name' => 'Juan Perez',   'identifier' => '1001234567']);
        Student::create(['group_id' => $group->id, 'name' => 'Maria Lopez',  'identifier' => '1007654321']);

        $date     = date('Y-m-d');
        $response = $this->actingAs($user)
            ->get("/imprimir/asistencia?group_id={$group->id}&date={$date}");

        $response->assertStatus(200);
        $response->assertSee('Juan Perez');
        $response->assertSee('Maria Lopez');
        $response->assertSee('1001234567');
        $response->assertSee('ADSO 2026');
        $response->assertSee('SENA');
    }

    public function test_print_page_marks_present_student()
    {
        $user        = User::factory()->create();
        $institution = Institution::create(['name' => 'SENA', 'user_id' => $user->id]);
        $group       = Group::create(['institution_id' => $institution->id, 'name' => 'ADSO 2026']);
        $student     = Student::create(['group_id' => $group->id, 'name' => 'Juan Perez']);

        $date = date('Y-m-d');
        Attendance::create([
            'student_id'      => $student->id,
            'attendance_date' => $date,
            'is_present'      => true,
            'is_justified'    => false,
        ]);

        $response = $this->actingAs($user)
            ->get("/imprimir/asistencia?group_id={$group->id}&date={$date}");

        $response->assertStatus(200);
        $response->assertSee('status-box present-box');
    }

    public function test_print_page_marks_absent_and_justified_students()
    {
        $user        = User::factory()->create();
        $institution = Institution::create(['name' => 'SENA', 'user_id' => $user->id]);
        $group       = Group::create(['institution_id' => $institution->id, 'name' => 'ADSO 2026']);
        $s1          = Student::create(['group_id' => $group->id, 'name' => 'Juan Perez']);
        $s2          = Student::create(['group_id' => $group->id, 'name' => 'Maria Lopez']);

        $date = date('Y-m-d');
        Attendance::create([
            'student_id'      => $s1->id,
            'attendance_date' => $date,
            'is_present'      => false,
            'is_justified'    => false,
        ]);
        Attendance::create([
            'student_id'      => $s2->id,
            'attendance_date' => $date,
            'is_present'      => false,
            'is_justified'    => true,
        ]);

        $response = $this->actingAs($user)
            ->get("/imprimir/asistencia?group_id={$group->id}&date={$date}");

        $response->assertStatus(200);
        $response->assertSee('status-box absent-box');
        $response->assertSee('status-box justified-box');
    }

    public function test_print_page_shows_competencia_info()
    {
        $user        = User::factory()->create();
        $institution = Institution::create(['name' => 'SENA', 'user_id' => $user->id]);
        $programa    = ProgramaFormacion::create([
            'user_id' => $user->id,
            'name'    => 'Análisis y Desarrollo de Software',
            'code'    => 'ADSO',
        ]);
        $competencia = Competencia::create([
            'programa_formacion_id' => $programa->id,
            'name'                  => 'Bases de Datos',
            'code'                  => 'BD001',
            'total_hours'           => 80,
        ]);
        $group = Group::create([
            'institution_id'       => $institution->id,
            'name'                 => 'ADSO 2026',
            'programa_formacion_id' => $programa->id,
        ]);
        Student::create(['group_id' => $group->id, 'name' => 'Ana Garcia']);

        $date     = date('Y-m-d');
        $response = $this->actingAs($user)
            ->get("/imprimir/asistencia?group_id={$group->id}&date={$date}&competencia_id={$competencia->id}");

        $response->assertStatus(200);
        $response->assertSee('Bases de Datos');
        $response->assertSee('BD001');
        $response->assertSee('80');
        $response->assertSee('Análisis y Desarrollo de Software');
    }

    public function test_print_page_shows_no_attendance_boxes_when_no_records()
    {
        $user        = User::factory()->create();
        $institution = Institution::create(['name' => 'SENA', 'user_id' => $user->id]);
        $group       = Group::create(['institution_id' => $institution->id, 'name' => 'ADSO 2026']);
        Student::create(['group_id' => $group->id, 'name' => 'Ana Garcia']);

        $date     = date('Y-m-d');
        $response = $this->actingAs($user)
            ->get("/imprimir/asistencia?group_id={$group->id}&date={$date}");

        $response->assertStatus(200);
        // No attendance record → no colored box classes applied to any element
        $response->assertDontSee('status-box present-box');
        $response->assertDontSee('status-box absent-box');
        $response->assertDontSee('status-box justified-box');
    }
}
