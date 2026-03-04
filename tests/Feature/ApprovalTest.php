<?php

namespace Tests\Feature;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalTest extends TestCase
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

    private function createEntry(User $user, string $status = 'draft'): TimeEntry
    {
        return TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
            'status' => $status,
        ]);
    }

    public function test_admin_can_approve_submitted_entry(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'submitted');

        $response = $this->actingAs($admin)->post("/admin/entries/{$entry->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_cannot_approve_draft_entry(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'draft');

        $response = $this->actingAs($admin)->post("/admin/entries/{$entry->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'status' => 'draft',
        ]);
    }

    public function test_admin_can_reject_submitted_entry_with_reason(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'submitted');

        $response = $this->actingAs($admin)->post("/admin/entries/{$entry->id}/reject", [
            'reason' => 'Hours seem incorrect',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'status' => 'rejected',
            'rejection_reason' => 'Hours seem incorrect',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_reject_requires_reason(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'submitted');

        $response = $this->actingAs($admin)->post("/admin/entries/{$entry->id}/reject", [
            'reason' => '',
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'status' => 'submitted',
        ]);
    }

    public function test_bulk_approve_works_for_multiple_entries(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $entry1 = $this->createEntry($user, 'submitted');
        $entry2 = TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-02',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($admin)->post('/admin/entries/bulk-approve', [
            'entry_ids' => [$entry1->id, $entry2->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', ['id' => $entry1->id, 'status' => 'approved']);
        $this->assertDatabaseHas('time_entries', ['id' => $entry2->id, 'status' => 'approved']);
    }

    public function test_employee_cannot_approve_entries(): void
    {
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'submitted');

        $response = $this->actingAs($user)->post("/admin/entries/{$entry->id}/approve");

        $response->assertStatus(403);
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'status' => 'submitted',
        ]);
    }

    public function test_approved_entries_are_locked_for_employee(): void
    {
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'approved');

        $response = $this->actingAs($user)->put("/time-entries/{$entry->id}", [
            'date' => '2026-03-01',
            'shift_start' => '08:00',
            'shift_end' => '16:00',
            'break_minutes' => 30,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'shift_start' => '09:00',
        ]);
    }

    public function test_submitted_entries_are_locked_for_employee(): void
    {
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'submitted');

        $response = $this->actingAs($user)->put("/time-entries/{$entry->id}", [
            'date' => '2026-03-01',
            'shift_start' => '08:00',
            'shift_end' => '16:00',
            'break_minutes' => 30,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_rejected_entries_can_be_edited_by_employee(): void
    {
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'rejected');

        $response = $this->actingAs($user)->put("/time-entries/{$entry->id}", [
            'date' => '2026-03-01',
            'shift_start' => '08:00',
            'shift_end' => '16:00',
            'break_minutes' => 30,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'shift_start' => '08:00',
            'status' => 'draft',
        ]);
    }

    public function test_rejected_entries_can_be_resubmitted(): void
    {
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'rejected');

        $response = $this->actingAs($user)->post("/time-entries/{$entry->id}/submit");

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'status' => 'submitted',
        ]);
    }

    public function test_employee_can_submit_draft_entry(): void
    {
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'draft');

        $response = $this->actingAs($user)->post("/time-entries/{$entry->id}/submit");

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'status' => 'submitted',
        ]);
    }

    public function test_employee_cannot_submit_approved_entry(): void
    {
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'approved');

        $response = $this->actingAs($user)->post("/time-entries/{$entry->id}/submit");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'status' => 'approved',
        ]);
    }

    public function test_submit_month_submits_all_draft_and_rejected_entries(): void
    {
        $user = $this->createUser();

        $draft = $this->createEntry($user, 'draft');
        $rejected = TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-02',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
            'status' => 'rejected',
        ]);
        $approved = TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-03',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->post('/time-entries/submit-month', [
            'month' => '2026-03',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', ['id' => $draft->id, 'status' => 'submitted']);
        $this->assertDatabaseHas('time_entries', ['id' => $rejected->id, 'status' => 'submitted']);
        $this->assertDatabaseHas('time_entries', ['id' => $approved->id, 'status' => 'approved']);
    }

    public function test_employee_cannot_delete_submitted_entry(): void
    {
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'submitted');

        $response = $this->actingAs($user)->delete("/time-entries/{$entry->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id, 'deleted_at' => null]);
    }

    public function test_employee_cannot_delete_approved_entry(): void
    {
        $user = $this->createUser();
        $entry = $this->createEntry($user, 'approved');

        $response = $this->actingAs($user)->delete("/time-entries/{$entry->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id, 'deleted_at' => null]);
    }
}
