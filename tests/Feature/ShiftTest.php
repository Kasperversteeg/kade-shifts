<?php

namespace Tests\Feature;

use App\Mail\SchedulePublished;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        return $user;
    }

    public function test_admin_can_view_schedule_board(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/schedule');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/ScheduleBoard')
            ->has('shifts')
            ->has('employees')
            ->has('days', 7)
        );
    }

    public function test_admin_can_create_assigned_shift(): void
    {
        $admin = $this->createAdmin();
        $employee = $this->createUser();

        $response = $this->actingAs($admin)->post('/admin/shifts', [
            'date' => '2026-03-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'user_id' => $employee->id,
            'position' => 'Bar',
        ]);

        $response->assertRedirect();
        $shift = Shift::first();
        $this->assertNotNull($shift);
        $this->assertEquals('2026-03-09', $shift->date->format('Y-m-d'));
        $this->assertEquals($employee->id, $shift->user_id);
        $this->assertEquals('Bar', $shift->position);
        $this->assertEquals($admin->id, $shift->created_by);
    }

    public function test_admin_can_create_unassigned_shift(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/shifts', [
            'date' => '2026-03-09',
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);

        $response->assertRedirect();
        $shift = Shift::first();
        $this->assertNotNull($shift);
        $this->assertEquals('2026-03-09', $shift->date->format('Y-m-d'));
        $this->assertNull($shift->user_id);
        $this->assertEquals($admin->id, $shift->created_by);
    }

    public function test_admin_can_update_shift(): void
    {
        $admin = $this->createAdmin();
        $shift = Shift::create([
            'date' => '2026-03-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/shifts/{$shift->id}", [
            'date' => '2026-03-09',
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);

        $response->assertRedirect();
        $shift->refresh();
        $this->assertStringContainsString('10:00', $shift->start_time);
        $this->assertStringContainsString('18:00', $shift->end_time);
    }

    public function test_admin_can_delete_shift(): void
    {
        $admin = $this->createAdmin();
        $shift = Shift::create([
            'date' => '2026-03-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/shifts/{$shift->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    public function test_admin_can_move_shift(): void
    {
        $admin = $this->createAdmin();
        $employee = $this->createUser();

        $shift = Shift::create([
            'date' => '2026-03-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'user_id' => null,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/shifts/{$shift->id}/move", [
            'user_id' => $employee->id,
            'date' => '2026-03-10',
        ]);

        $response->assertRedirect();
        $shift->refresh();
        $this->assertEquals($employee->id, $shift->user_id);
        $this->assertEquals('2026-03-10', $shift->date->format('Y-m-d'));
    }

    public function test_admin_can_publish_week(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();
        $employee = $this->createUser();

        Shift::create([
            'date' => '2026-03-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'user_id' => $employee->id,
            'published' => false,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/schedule/publish', [
            'week' => '2026-03-09',
        ]);

        $response->assertRedirect();
        $shift = Shift::first();
        $this->assertTrue($shift->published);

        Mail::assertQueued(SchedulePublished::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email);
        });
    }

    public function test_publish_skips_email_for_unassigned_shifts(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();

        Shift::create([
            'date' => '2026-03-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'user_id' => null,
            'published' => false,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/schedule/publish', [
            'week' => '2026-03-09',
        ]);

        $response->assertRedirect();
        $shift = Shift::first();
        $this->assertTrue($shift->published);

        Mail::assertNothingQueued();
    }

    public function test_employee_can_view_own_schedule(): void
    {
        $employee = $this->createUser();
        $admin = $this->createAdmin();

        Shift::create([
            'date' => '2026-03-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'user_id' => $employee->id,
            'published' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($employee)->get('/schedule?week=2026-03-09');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Schedule/Index')
            ->has('shifts', 1)
        );
    }

    public function test_employee_cannot_see_unpublished_shifts(): void
    {
        $employee = $this->createUser();
        $admin = $this->createAdmin();

        Shift::create([
            'date' => '2026-03-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'user_id' => $employee->id,
            'published' => false,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($employee)->get('/schedule?week=2026-03-09');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Schedule/Index')
            ->has('shifts', 0)
        );
    }

    public function test_employee_cannot_see_other_employees_shifts(): void
    {
        $employee1 = $this->createUser();
        $employee2 = $this->createUser();
        $admin = $this->createAdmin();

        Shift::create([
            'date' => '2026-03-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'user_id' => $employee2->id,
            'published' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($employee1)->get('/schedule?week=2026-03-09');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Schedule/Index')
            ->has('shifts', 0)
        );
    }

    public function test_non_admin_cannot_access_schedule_board(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/admin/schedule');

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_create_shift(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/admin/shifts', [
            'date' => '2026-03-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $response->assertStatus(403);
    }

    public function test_dashboard_shows_next_shift(): void
    {
        $employee = $this->createUser();
        $admin = $this->createAdmin();

        Shift::create([
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'user_id' => $employee->id,
            'published' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($employee)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('nextShift')
            ->where('nextShift.start_time', '09:00')
        );
    }

    public function test_shift_validation_requires_date_and_times(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/shifts', []);

        $response->assertSessionHasErrors(['date', 'start_time', 'end_time']);
    }

    public function test_shift_validation_user_id_must_exist(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/shifts', [
            'date' => '2026-03-09',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'user_id' => 99999,
        ]);

        $response->assertSessionHasErrors('user_id');
    }
}
