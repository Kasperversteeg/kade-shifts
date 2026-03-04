<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivationTest extends TestCase
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

    public function test_admin_can_deactivate_user(): void
    {
        $admin = $this->createAdmin();
        $employee = $this->createUser();

        $response = $this->actingAs($admin)->post(route('admin.user-toggle-active', $employee));

        $response->assertRedirect();
        $employee->refresh();
        $this->assertFalse($employee->is_active);
        $this->assertNotNull($employee->deactivated_at);
    }

    public function test_deactivated_user_is_logged_out(): void
    {
        $employee = $this->createUser();
        $employee->update(['is_active' => false, 'deactivated_at' => now()]);

        $response = $this->actingAs($employee)->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_reactivate_user(): void
    {
        $admin = $this->createAdmin();
        $employee = $this->createUser();
        $employee->update(['is_active' => false, 'deactivated_at' => now()]);

        $response = $this->actingAs($admin)->post(route('admin.user-toggle-active', $employee));

        $response->assertRedirect();
        $employee->refresh();
        $this->assertTrue($employee->is_active);
        $this->assertNull($employee->deactivated_at);
    }

    public function test_reactivated_user_can_access_dashboard(): void
    {
        $employee = $this->createUser();
        // Deactivate then reactivate
        $employee->update(['is_active' => false, 'deactivated_at' => now()]);
        $employee->update(['is_active' => true, 'deactivated_at' => null]);

        $response = $this->actingAs($employee)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_active_filter_on_overview(): void
    {
        $admin = $this->createAdmin();
        $activeUser = $this->createUser();
        $inactiveUser = $this->createUser();
        $inactiveUser->update(['is_active' => false, 'deactivated_at' => now()]);

        // Active filter (default)
        $response = $this->actingAs($admin)->get('/admin/overview?active=active');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Overview')
            ->has('users', 1)
        );

        // Inactive filter
        $response = $this->actingAs($admin)->get('/admin/overview?active=inactive');
        $response->assertInertia(fn ($page) => $page
            ->has('users', 1)
        );

        // All filter
        $response = $this->actingAs($admin)->get('/admin/overview?active=all');
        $response->assertInertia(fn ($page) => $page
            ->has('users', 2)
        );
    }

    public function test_non_admin_cannot_toggle_active(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $response = $this->actingAs($user)->post(route('admin.user-toggle-active', $otherUser));

        $response->assertStatus(403);
    }
}
