<?php

namespace Tests\Feature;

use App\Mail\UserInvitation;
use App\Models\Invitation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createUser(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function createInvitation(array $overrides = []): Invitation
    {
        return Invitation::create(array_merge([
            'email' => 'newuser@example.com',
            'token' => Invitation::generateToken(),
            'invited_by' => $this->createAdmin()->id,
            'expires_at' => Carbon::now()->addDays(7),
        ], $overrides));
    }

    public function test_admin_can_view_invitations_page(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/invitations');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Invitations'));
    }

    public function test_admin_can_send_invitation(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/invitations', [
            'email' => 'newuser@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invitations', [
            'email' => 'newuser@example.com',
            'invited_by' => $admin->id,
        ]);
        Mail::assertSent(UserInvitation::class, function ($mail) {
            return $mail->invitation->email === 'newuser@example.com';
        });
    }

    public function test_cannot_invite_already_registered_email(): void
    {
        $admin = $this->createAdmin();
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)->post('/admin/invitations', [
            'email' => 'existing@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_cannot_invite_already_invited_email(): void
    {
        $admin = $this->createAdmin();

        $this->createInvitation([
            'email' => 'invited@example.com',
            'invited_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/invitations', [
            'email' => 'invited@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_view_invitation_acceptance_page(): void
    {
        $invitation = $this->createInvitation();

        $response = $this->get("/invitation/{$invitation->token}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/AcceptInvitation')
            ->has('invitation')
        );
    }

    public function test_expired_invitation_shows_expired_page(): void
    {
        $invitation = $this->createInvitation([
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->get("/invitation/{$invitation->token}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Auth/InvitationExpired'));
    }

    public function test_user_can_complete_registration_via_invitation(): void
    {
        $invitation = $this->createInvitation([
            'email' => 'newuser@example.com',
        ]);

        $response = $this->post("/invitation/{$invitation->token}/complete", [
            'name' => 'New User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'user',
        ]);
    }

    public function test_completing_invitation_marks_it_as_accepted(): void
    {
        $invitation = $this->createInvitation([
            'email' => 'newuser@example.com',
        ]);

        $this->post("/invitation/{$invitation->token}/complete", [
            'name' => 'New User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
    }

    public function test_cannot_reuse_accepted_invitation(): void
    {
        $invitation = $this->createInvitation([
            'email' => 'newuser@example.com',
            'accepted_at' => Carbon::now(),
        ]);

        $response = $this->post("/invitation/{$invitation->token}/complete", [
            'name' => 'Another User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_view_invitations(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/admin/invitations');

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_send_invitation(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/admin/invitations', [
            'email' => 'someone@example.com',
        ]);

        $response->assertStatus(403);
    }
}
