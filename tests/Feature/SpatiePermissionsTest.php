<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpatiePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_role_has_all_permissions(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->hasPermissionTo('manage-users'));
        $this->assertTrue($admin->hasPermissionTo('approve-hours'));
        $this->assertTrue($admin->hasPermissionTo('manage-planning'));
        $this->assertTrue($admin->hasPermissionTo('manage-invitations'));
        $this->assertTrue($admin->hasPermissionTo('view-all-hours'));
    }

    public function test_user_role_has_no_admin_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->assertFalse($user->hasPermissionTo('manage-users'));
        $this->assertFalse($user->hasPermissionTo('approve-hours'));
        $this->assertFalse($user->hasPermissionTo('manage-planning'));
        $this->assertFalse($user->hasPermissionTo('manage-invitations'));
        $this->assertFalse($user->hasPermissionTo('view-all-hours'));
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/overview')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/invitations')->assertStatus(200);
    }

    public function test_regular_user_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)->get('/admin/overview')->assertStatus(403);
        $this->actingAs($user)->get('/admin/invitations')->assertStatus(403);
    }

    public function test_is_admin_helper_uses_spatie_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($user->isAdmin());
    }

    public function test_invitation_completion_assigns_user_role(): void
    {
        $admin = User::factory()->admin()->create();

        $invitation = \App\Models\Invitation::create([
            'email' => 'test@example.com',
            'token' => \App\Models\Invitation::generateToken(),
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->post("/invitation/{$invitation->token}/complete", [
            'name' => 'Test User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $newUser = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole('user'));
    }
}
