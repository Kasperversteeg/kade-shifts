<?php

namespace Tests\Feature;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_time_entries_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/time-entries');

        $response->assertStatus(200);
    }

    public function test_user_can_view_time_entries_for_specific_month(): void
    {
        $user = User::factory()->create();

        TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-02-10',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        $response = $this->actingAs($user)->get('/time-entries?month=2026-01');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('TimeEntries/Index')
            ->has('entries', 1)
            ->where('currentMonth', '2026-01')
        );
    }

    public function test_user_can_create_time_entry(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/time-entries', [
            'date' => '2026-03-01',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'notes' => 'Test entry',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'total_hours' => 7.50,
        ]);
    }

    public function test_time_entry_calculates_total_hours(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/time-entries', [
            'date' => '2026-03-01',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
        ]);

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'total_hours' => 7.50,
        ]);
    }

    public function test_time_entry_handles_cross_midnight_shift(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/time-entries', [
            'date' => '2026-03-01',
            'shift_start' => '22:00',
            'shift_end' => '06:00',
            'break_minutes' => 0,
        ]);

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'total_hours' => 8.00,
        ]);
    }

    public function test_user_cannot_create_duplicate_date_entry(): void
    {
        $user = User::factory()->create();

        TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        $response = $this->actingAs($user)->post('/time-entries', [
            'date' => '2026-03-01',
            'shift_start' => '10:00',
            'shift_end' => '18:00',
            'break_minutes' => 30,
        ]);

        $response->assertSessionHasErrors('date');
        $this->assertDatabaseCount('time_entries', 1);
    }

    public function test_user_can_update_own_time_entry(): void
    {
        $user = User::factory()->create();

        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

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
            'shift_end' => '16:00',
            'total_hours' => 7.50,
        ]);
    }

    public function test_user_cannot_update_other_users_time_entry(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $entry = TimeEntry::create([
            'user_id' => $otherUser->id,
            'date' => '2026-03-01',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        $response = $this->actingAs($user)->put("/time-entries/{$entry->id}", [
            'date' => '2026-03-01',
            'shift_start' => '08:00',
            'shift_end' => '16:00',
            'break_minutes' => 30,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_time_entry(): void
    {
        $user = User::factory()->create();

        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        $response = $this->actingAs($user)->delete("/time-entries/{$entry->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted($entry);
    }

    public function test_user_cannot_delete_other_users_time_entry(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $entry = TimeEntry::create([
            'user_id' => $otherUser->id,
            'date' => '2026-03-01',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        $response = $this->actingAs($user)->delete("/time-entries/{$entry->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id]);
    }

    public function test_user_can_export_csv(): void
    {
        $user = User::factory()->create();

        TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
            'notes' => 'Test notes',
        ]);

        $response = $this->actingAs($user)->get('/time-entries/export?month=2026-01');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Date,Start,End,Break (min),Total Hours,Notes');
        $response->assertSee('Test notes');
    }

    public function test_validation_requires_all_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/time-entries', []);

        $response->assertSessionHasErrors(['date', 'shift_start', 'shift_end', 'break_minutes']);
    }

    public function test_shift_start_must_be_valid_time_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/time-entries', [
            'date' => '2026-03-01',
            'shift_start' => 'invalid',
            'shift_end' => '17:00',
            'break_minutes' => 30,
        ]);

        $response->assertSessionHasErrors('shift_start');
    }

    public function test_break_minutes_must_be_non_negative(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/time-entries', [
            'date' => '2026-03-01',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => -10,
        ]);

        $response->assertSessionHasErrors('break_minutes');
    }
}
