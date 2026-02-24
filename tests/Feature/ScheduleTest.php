<?php

namespace Tests\Feature;

use Livewire\Livewire;
use App\Models\Institution;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_manage_schedules_via_livewire()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test('manage-schedules')
            ->set('new_institution_name', 'Colegio B')
            ->call('addInstitution')
            ->assertSet('new_institution_name', '')
            ->assertSee('Colegio B');

        $institution = Institution::first();

        Livewire::actingAs($user)->test('manage-schedules')
            ->set('institution_id', $institution->id)
            ->set('new_group_name', 'Grupo 202')
            ->call('addGroup')
            ->assertSet('new_group_name', '')
            ->assertSee('Grupo 202');

        $group = Group::first();

        Livewire::actingAs($user)->test('manage-schedules')
            ->set('institution_id', $institution->id)
            ->set('group_id', $group->id)
            ->set('new_subject', 'Ciencias')
            ->set('new_day', 'Tuesday')
            ->set('new_start', '09:00')
            ->set('new_end', '11:00')
            ->call('addSchedule')
            ->assertSet('new_subject', '')
            ->assertSee('Ciencias');

        $this->assertDatabaseHas('schedules', [
            'subject' => 'Ciencias',
            'group_id' => $group->id
        ]);
    }

    public function test_can_create_institution_group_and_schedule()
    {
        $institution = Institution::create(['name' => 'Colegio A']);
        $this->assertDatabaseHas('institutions', ['name' => 'Colegio A']);

        $group = Group::create([
            'institution_id' => $institution->id,
            'name' => 'Grupo 101'
        ]);
        $this->assertDatabaseHas('groups', ['name' => 'Grupo 101']);

        $schedule = Schedule::create([
            'group_id' => $group->id,
            'subject' => 'Matemáticas',
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '10:00'
        ]);
        $this->assertDatabaseHas('schedules', ['subject' => 'Matemáticas']);
    }

    public function test_horarios_page_is_accessible()
    {
        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->get('/horarios');
        $response->assertStatus(200);
    }
}
