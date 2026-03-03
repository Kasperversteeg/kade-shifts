<?php

namespace Tests\Feature;

use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_shows_current_month_entries(): void
    {
        $user = User::factory()->create();
        $now = Carbon::now();

        // Entry for current month
        TimeEntry::create([
            'user_id' => $user->id,
            'date' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        // Entry for previous month
        TimeEntry::create([
            'user_id' => $user->id,
            'date' => $now->copy()->subMonth()->startOfMonth()->format('Y-m-d'),
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('entries', 1)
        );
    }

    public function test_dashboard_shows_correct_month_total(): void
    {
        $user = User::factory()->create();
        $now = Carbon::now();

        TimeEntry::create([
            'user_id' => $user->id,
            'date' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        TimeEntry::create([
            'user_id' => $user->id,
            'date' => $now->copy()->startOfMonth()->addDay()->format('Y-m-d'),
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 0,
            'total_hours' => 8.00,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('monthTotal', 15.50)
        );
    }
}
