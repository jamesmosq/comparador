<?php

namespace Tests\Feature\Attendance;

use App\Models\Institution;
use App\Models\Group;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_student_linked_to_group()
    {
        $institution = Institution::create(['name' => 'SENA']);
        $group = Group::create(['institution_id' => $institution->id, 'name' => 'ADSO 2026']);

        $student = Student::create([
            'group_id' => $group->id,
            'name' => 'Juan Perez',
            'identifier' => '123456789'
        ]);

        $this->assertDatabaseHas('students', [
            'name' => 'Juan Perez',
            'group_id' => $group->id
        ]);
    }

    public function test_can_record_attendance()
    {
        $institution = Institution::create(['name' => 'SENA']);
        $group = Group::create(['institution_id' => $institution->id, 'name' => 'ADSO 2026']);
        $student = Student::create(['group_id' => $group->id, 'name' => 'Juan Perez']);

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'attendance_date' => now()->format('Y-m-d'),
            'is_present' => true
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'is_present' => true
        ]);
    }
}
