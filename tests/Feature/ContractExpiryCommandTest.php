<?php

namespace Tests\Feature;

use App\Mail\ContractExpiryNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContractExpiryCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    private function createUser(array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $user->assignRole('user');

        return $user;
    }

    public function test_command_finds_users_with_expiring_contract(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();
        $employee = $this->createUser([
            'contract_end_date' => now()->addDays(30),
        ]);

        $this->artisan('contracts:check-expiry')
            ->expectsOutputToContain('1 expiring contract')
            ->assertExitCode(0);

        Mail::assertQueued(ContractExpiryNotification::class);
    }

    public function test_notification_is_sent_to_admin(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();
        $employee = $this->createUser([
            'contract_end_date' => now()->addDays(30),
        ]);

        $this->artisan('contracts:check-expiry')->assertExitCode(0);

        Mail::assertQueued(ContractExpiryNotification::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    }

    public function test_contract_expiry_notified_at_is_set(): void
    {
        Mail::fake();
        $this->createAdmin();
        $employee = $this->createUser([
            'contract_end_date' => now()->addDays(30),
        ]);

        $this->artisan('contracts:check-expiry')->assertExitCode(0);

        $employee->refresh();
        $this->assertNotNull($employee->contract_expiry_notified_at);
    }

    public function test_same_user_is_not_notified_again(): void
    {
        Mail::fake();
        $this->createAdmin();
        $employee = $this->createUser([
            'contract_end_date' => now()->addDays(30),
            'contract_expiry_notified_at' => now()->subDay(),
        ]);

        $this->artisan('contracts:check-expiry')
            ->expectsOutputToContain('No expiring contracts')
            ->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_users_without_contract_end_date_are_ignored(): void
    {
        Mail::fake();
        $this->createAdmin();
        $this->createUser(['contract_end_date' => null]);

        $this->artisan('contracts:check-expiry')
            ->expectsOutputToContain('No expiring contracts')
            ->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_inactive_users_are_ignored(): void
    {
        Mail::fake();
        $this->createAdmin();
        $employee = $this->createUser([
            'contract_end_date' => now()->addDays(30),
            'is_active' => false,
        ]);

        $this->artisan('contracts:check-expiry')
            ->expectsOutputToContain('No expiring contracts')
            ->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_far_future_contracts_are_ignored(): void
    {
        Mail::fake();
        $this->createAdmin();
        $this->createUser([
            'contract_end_date' => now()->addDays(90),
        ]);

        $this->artisan('contracts:check-expiry')
            ->expectsOutputToContain('No expiring contracts')
            ->assertExitCode(0);

        Mail::assertNothingQueued();
    }
}
