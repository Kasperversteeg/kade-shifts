<?php

namespace Tests\Feature;

use App\Models\SickLeave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SickLeaveTest extends TestCase
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

    public function test_admin_can_register_sick_leave(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/sick-leave", [
            'start_date' => '2026-03-03',
            'notes' => 'Called in sick',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sick_leaves', [
            'user_id' => $user->id,
            'end_date' => null,
            'registered_by' => $admin->id,
        ]);
        $sickLeave = SickLeave::where('user_id', $user->id)->first();
        $this->assertEquals('2026-03-03', $sickLeave->start_date->format('Y-m-d'));
    }

    public function test_recovery_can_be_registered(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $sickLeave = SickLeave::create([
            'user_id' => $user->id,
            'start_date' => '2026-03-01',
            'registered_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/sick-leave/{$sickLeave->id}/recover", [
            'end_date' => '2026-03-03',
        ]);

        $response->assertRedirect();
        $sickLeave->refresh();
        $this->assertEquals('2026-03-03', $sickLeave->end_date->format('Y-m-d'));
    }

    public function test_is_currently_sick_check_works(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->assertFalse($user->isCurrentlySick());

        SickLeave::create([
            'user_id' => $user->id,
            'start_date' => '2026-03-01',
            'registered_by' => $admin->id,
        ]);

        $this->assertTrue($user->fresh()->isCurrentlySick());
    }

    public function test_sick_days_calculated_per_year(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        // Recovered sick leave: Mon Mar 2 to Wed Mar 4 = 3 weekdays
        SickLeave::create([
            'user_id' => $user->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-04',
            'registered_by' => $admin->id,
        ]);

        $this->assertEquals(3, $user->fresh()->sickDaysThisYear);
    }

    public function test_non_admin_cannot_register_sick_leave(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $response = $this->actingAs($user)->post("/admin/users/{$otherUser->id}/sick-leave", [
            'start_date' => '2026-03-03',
        ]);

        $response->assertStatus(403);
    }
}
