<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
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

    public function test_employee_can_submit_leave_request(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/leave', [
            'type' => 'vakantie',
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(10)->format('Y-m-d'),
            'reason' => 'Family vacation',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $user->id,
            'type' => 'vakantie',
            'status' => 'pending',
        ]);
    }

    public function test_validation_rejects_end_before_start(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/leave', [
            'type' => 'vakantie',
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('end_date');
    }

    public function test_validation_requires_type(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/leave', [
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_admin_can_approve_leave_request(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'type' => 'vakantie',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post("/admin/leave-requests/{$leaveRequest->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reject_leave_request_with_reason(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'type' => 'vakantie',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post("/admin/leave-requests/{$leaveRequest->id}/reject", [
            'reason' => 'Insufficient staffing',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'rejected',
            'rejection_reason' => 'Insufficient staffing',
        ]);
    }

    public function test_employee_can_cancel_pending_request(): void
    {
        $user = $this->createUser();

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'type' => 'vakantie',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->delete("/leave/{$leaveRequest->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('leave_requests', ['id' => $leaveRequest->id]);
    }

    public function test_employee_cannot_cancel_approved_request(): void
    {
        $user = $this->createUser();

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'type' => 'vakantie',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->delete("/leave/{$leaveRequest->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id]);
    }

    public function test_leave_balance_calculated_correctly(): void
    {
        $user = $this->createUser();
        $user->update(['statutory_leave_days' => 20, 'extra_leave_days' => 5]);

        // Create an approved vacation request (Mon-Fri = 5 weekdays)
        LeaveRequest::create([
            'user_id' => $user->id,
            'type' => 'vakantie',
            'start_date' => '2026-03-09', // Monday
            'end_date' => '2026-03-13',   // Friday
            'status' => 'approved',
        ]);

        $balance = $user->fresh()->leaveBalance;

        $this->assertEquals(25, $balance['total']);
        $this->assertEquals(5, $balance['used']);
        $this->assertEquals(20, $balance['remaining']);
    }

    public function test_non_vacation_requests_dont_count_for_balance(): void
    {
        $user = $this->createUser();
        $user->update(['statutory_leave_days' => 20, 'extra_leave_days' => 5]);

        // Create an approved special leave request
        LeaveRequest::create([
            'user_id' => $user->id,
            'type' => 'bijzonder_verlof',
            'start_date' => '2026-03-09',
            'end_date' => '2026-03-13',
            'status' => 'approved',
        ]);

        $balance = $user->fresh()->leaveBalance;

        $this->assertEquals(25, $balance['total']);
        $this->assertEquals(0, $balance['used']);
        $this->assertEquals(25, $balance['remaining']);
    }
}
