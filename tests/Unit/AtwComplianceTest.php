<?php

namespace Tests\Unit;

use App\Models\TimeEntry;
use App\Models\User;
use App\Services\AtwComplianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtwComplianceTest extends TestCase
{
    use RefreshDatabase;

    private AtwComplianceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AtwComplianceService();
    }

    private function makeEntry(array $attributes = []): TimeEntry
    {
        $user = User::factory()->create();

        return TimeEntry::create(array_merge([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
            'status' => 'draft',
        ], $attributes));
    }

    public function test_no_warning_for_normal_shift(): void
    {
        $entry = $this->makeEntry([
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
        ]);

        $warnings = $this->service->validateEntry($entry);

        $this->assertEmpty($warnings);
    }

    public function test_break_warning_for_shift_over_5_5_hours_with_short_break(): void
    {
        $entry = $this->makeEntry([
            'shift_start' => '09:00',
            'shift_end' => '16:00',
            'break_minutes' => 15,
        ]);

        $warnings = $this->service->validateEntry($entry);

        $this->assertCount(1, $warnings);
        $this->assertEquals('break_short', $warnings[0]['type']);
    }

    public function test_no_break_warning_when_break_is_sufficient(): void
    {
        $entry = $this->makeEntry([
            'shift_start' => '09:00',
            'shift_end' => '16:00',
            'break_minutes' => 30,
        ]);

        $warnings = $this->service->validateEntry($entry);

        $this->assertEmpty($warnings);
    }

    public function test_break_very_short_warning_for_shift_over_10_hours(): void
    {
        $entry = $this->makeEntry([
            'shift_start' => '07:00',
            'shift_end' => '18:00',
            'break_minutes' => 30,
        ]);

        $warnings = $this->service->validateEntry($entry);

        $types = collect($warnings)->pluck('type')->all();
        $this->assertContains('break_very_short', $types);
    }

    public function test_no_break_very_short_warning_with_45_min_break(): void
    {
        $entry = $this->makeEntry([
            'shift_start' => '07:00',
            'shift_end' => '18:00',
            'break_minutes' => 45,
        ]);

        $warnings = $this->service->validateEntry($entry);

        $types = collect($warnings)->pluck('type')->all();
        $this->assertNotContains('break_very_short', $types);
    }

    public function test_shift_too_long_warning_over_12_hours(): void
    {
        $entry = $this->makeEntry([
            'shift_start' => '06:00',
            'shift_end' => '19:00',
            'break_minutes' => 60,
        ]);

        $warnings = $this->service->validateEntry($entry);

        $types = collect($warnings)->pluck('type')->all();
        $this->assertContains('shift_too_long', $types);
    }

    public function test_no_shift_too_long_warning_for_12_hour_shift(): void
    {
        $entry = $this->makeEntry([
            'shift_start' => '06:00',
            'shift_end' => '18:00',
            'break_minutes' => 45,
        ]);

        $warnings = $this->service->validateEntry($entry);

        $types = collect($warnings)->pluck('type')->all();
        $this->assertNotContains('shift_too_long', $types);
    }

    public function test_rest_too_short_warning(): void
    {
        $user = User::factory()->create();

        $previous = TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'shift_start' => '14:00',
            'shift_end' => '23:00',
            'break_minutes' => 30,
            'total_hours' => 8.50,
            'status' => 'draft',
        ]);

        $current = TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-02',
            'shift_start' => '07:00',
            'shift_end' => '15:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
            'status' => 'draft',
        ]);

        $warnings = $this->service->validateEntry($current, $previous);

        $types = collect($warnings)->pluck('type')->all();
        $this->assertContains('rest_too_short', $types);
    }

    public function test_no_rest_warning_with_sufficient_rest(): void
    {
        $user = User::factory()->create();

        $previous = TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
            'status' => 'draft',
        ]);

        $current = TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-02',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
            'status' => 'draft',
        ]);

        $warnings = $this->service->validateEntry($current, $previous);

        $types = collect($warnings)->pluck('type')->all();
        $this->assertNotContains('rest_too_short', $types);
    }

    public function test_weekly_totals_over_60_hours(): void
    {
        $user = User::factory()->create();

        // Create 6 entries of 11 hours each = 66 hours in one week
        for ($i = 1; $i <= 6; $i++) {
            TimeEntry::create([
                'user_id' => $user->id,
                'date' => "2026-03-0{$i}", // Mon-Sat of first week of March 2026
                'shift_start' => '07:00',
                'shift_end' => '19:00',
                'break_minutes' => 60,
                'total_hours' => 11.00,
                'status' => 'draft',
            ]);
        }

        $weeklyTotals = $this->service->getWeeklyTotals($user->id, '2026-03');

        // Find the week containing March 2-7
        $week = collect($weeklyTotals)->first(fn ($w) => $w['totalHours'] >= 60);

        $this->assertNotNull($week);
        $this->assertEquals(66.00, $week['totalHours']);
        $types = collect($week['warnings'])->pluck('type')->all();
        $this->assertContains('week_over_60', $types);
    }

    public function test_weekly_totals_over_48_hours_warning(): void
    {
        $user = User::factory()->create();

        // Create 5 entries of 10 hours each = 50 hours in one week
        for ($i = 2; $i <= 6; $i++) {
            TimeEntry::create([
                'user_id' => $user->id,
                'date' => "2026-03-0{$i}",
                'shift_start' => '08:00',
                'shift_end' => '19:00',
                'break_minutes' => 60,
                'total_hours' => 10.00,
                'status' => 'draft',
            ]);
        }

        $weeklyTotals = $this->service->getWeeklyTotals($user->id, '2026-03');

        $week = collect($weeklyTotals)->first(fn ($w) => $w['totalHours'] >= 48);

        $this->assertNotNull($week);
        $types = collect($week['warnings'])->pluck('type')->all();
        $this->assertContains('week_over_48', $types);
    }

    public function test_no_weekly_warning_for_normal_hours(): void
    {
        $user = User::factory()->create();

        // Create 5 entries of 8 hours each = 40 hours in one week
        for ($i = 2; $i <= 6; $i++) {
            TimeEntry::create([
                'user_id' => $user->id,
                'date' => "2026-03-0{$i}",
                'shift_start' => '09:00',
                'shift_end' => '17:30',
                'break_minutes' => 30,
                'total_hours' => 8.00,
                'status' => 'draft',
            ]);
        }

        $weeklyTotals = $this->service->getWeeklyTotals($user->id, '2026-03');

        $week = collect($weeklyTotals)->first(fn ($w) => $w['totalHours'] >= 30);

        $this->assertNotNull($week);
        $this->assertEmpty($week['warnings']);
    }

    public function test_cross_midnight_shift_duration(): void
    {
        $duration = $this->service->calculateShiftDuration('22:00', '06:00');

        $this->assertEquals(8.0, $duration);
    }

    public function test_add_warnings_to_entries_collection(): void
    {
        $user = User::factory()->create();

        $entry1 = TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'shift_start' => '14:00',
            'shift_end' => '23:00',
            'break_minutes' => 15,
            'total_hours' => 8.75,
            'status' => 'draft',
        ]);

        $entry2 = TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-03-02',
            'shift_start' => '07:00',
            'shift_end' => '15:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
            'status' => 'draft',
        ]);

        // Pass entries in DESC order (as controller does)
        $entries = collect([$entry2, $entry1]);
        $result = $this->service->addWarningsToEntries($entries);

        // entry1 has short break (9h shift, 15min break)
        $e1 = $result->first(fn ($e) => $e->id === $entry1->id);
        $this->assertNotEmpty($e1->atw_warnings);

        // entry2 should have rest_too_short (23:00 to 07:00 = 8h)
        $e2 = $result->first(fn ($e) => $e->id === $entry2->id);
        $types = collect($e2->atw_warnings)->pluck('type')->all();
        $this->assertContains('rest_too_short', $types);
    }
}
