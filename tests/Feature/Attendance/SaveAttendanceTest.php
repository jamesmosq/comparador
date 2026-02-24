<?php

namespace Tests\Feature\Attendance;

use App\Models\Institution;
use App\Models\Group;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Volt\Volt;

class SaveAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_save_attendance_via_livewire()
    {
        $user = User::factory()->create();
        $institution = Institution::create(['name' => 'SENA', 'user_id' => $user->id]);
        $group = Group::create(['institution_id' => $institution->id, 'name' => 'ADSO 2026']);
        $student1 = Student::create(['group_id' => $group->id, 'name' => 'Juan Perez']);
        $student2 = Student::create(['group_id' => $group->id, 'name' => 'Maria Lopez']);

        $attendanceDate = date('Y-m-d');

        $component = Volt::actingAs($user)->test('attendance-manager')
            ->set('institution_id', $institution->id)
            ->set('group_id', $group->id)
            ->set('attendance_date', $attendanceDate);

        $attendanceStatus = $component->get('attendance_status');
        $this->assertArrayHasKey($student1->id, $attendanceStatus);
        $this->assertArrayHasKey($student2->id, $attendanceStatus);

        $component->set('attendance_status.' . $student1->id, 'present')
            ->set('attendance_status.' . $student2->id, 'absent')
            ->call('saveAttendance');

        $component->assertHasNoErrors();
        $this->assertDatabaseHas('attendances', [
            'student_id'      => $student1->id,
            'attendance_date' => $attendanceDate,
            'is_present'      => 1,
            'is_justified'    => 0,
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id'      => $student2->id,
            'attendance_date' => $attendanceDate,
            'is_present'      => 0,
            'is_justified'    => 0,
        ]);
    }

    public function test_can_save_justified_absence()
    {
        $user = User::factory()->create();
        $institution = Institution::create(['name' => 'SENA', 'user_id' => $user->id]);
        $group = Group::create(['institution_id' => $institution->id, 'name' => 'ADSO 2026']);
        $student = Student::create(['group_id' => $group->id, 'name' => 'Ana Garcia']);

        $attendanceDate = date('Y-m-d');

        $component = Volt::actingAs($user)->test('attendance-manager')
            ->set('institution_id', $institution->id)
            ->set('group_id', $group->id)
            ->set('attendance_date', $attendanceDate)
            ->set('attendance_status.' . $student->id, 'justified')
            ->call('saveAttendance');

        $component->assertHasNoErrors();
        $this->assertDatabaseHas('attendances', [
            'student_id'      => $student->id,
            'attendance_date' => $attendanceDate,
            'is_present'      => 0,
            'is_justified'    => 1,
        ]);
    }

    public function test_can_update_existing_attendance()
    {
        $user = User::factory()->create();
        $institution = Institution::create(['name' => 'SENA', 'user_id' => $user->id]);
        $group = Group::create(['institution_id' => $institution->id, 'name' => 'ADSO 2026']);
        $student = Student::create(['group_id' => $group->id, 'name' => 'Juan Perez']);

        $attendanceDate = date('Y-m-d');

        // Crear asistencia previa (Ausente)
        Attendance::create([
            'student_id'      => $student->id,
            'attendance_date' => $attendanceDate,
            'is_present'      => false,
            'is_justified'    => false,
        ]);

        $component = Volt::actingAs($user)->test('attendance-manager')
            ->set('institution_id', $institution->id)
            ->set('group_id', $group->id)
            ->set('attendance_date', $attendanceDate)
            // Cambiar a Presente
            ->set('attendance_status.' . $student->id, 'present')
            ->call('saveAttendance');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('attendances', [
            'student_id'      => $student->id,
            'attendance_date' => $attendanceDate,
            'is_present'      => 1,
        ]);

        $this->assertEquals(1, Attendance::count());
    }
}
