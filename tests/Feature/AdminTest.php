<?php

namespace Tests\Feature;

use App\Mail\MonthlyHoursReport;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTest extends TestCase
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

    public function test_admin_can_view_overview(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/overview');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Overview'));
    }

    public function test_regular_user_cannot_view_overview(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/admin/overview');

        $response->assertStatus(403);
    }

    public function test_overview_shows_all_users_hours(): void
    {
        $admin = $this->createAdmin();
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        TimeEntry::create([
            'user_id' => $user1->id,
            'date' => '2026-01-15',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        TimeEntry::create([
            'user_id' => $user2->id,
            'date' => '2026-01-15',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 0,
            'total_hours' => 8.00,
        ]);

        $response = $this->actingAs($admin)->get('/admin/overview?month=2026-01');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Overview')
            ->has('users', 2)
            ->where('grandTotal', 15.50)
        );
    }

    public function test_admin_can_view_user_detail(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}?month=2026-01");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/UserDetail')
            ->has('entries', 1)
            ->where('monthTotal', 7.50)
        );
    }

    public function test_admin_can_filter_overview_by_month(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

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
            'date' => '2026-02-15',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 0,
            'total_hours' => 8.00,
        ]);

        $response = $this->actingAs($admin)->get('/admin/overview?month=2026-01');

        $response->assertInertia(fn ($page) => $page
            ->where('grandTotal', 7.50)
            ->where('currentMonth', '2026-01')
        );
    }

    public function test_admin_can_send_monthly_report(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $user = $this->createUser();

        TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        $response = $this->actingAs($admin)->post('/admin/send-report', [
            'month' => '2026-01',
        ]);

        $response->assertRedirect();
        Mail::assertSent(MonthlyHoursReport::class);
    }

    public function test_monthly_report_email_contains_all_users(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        TimeEntry::create([
            'user_id' => $user1->id,
            'date' => '2026-01-15',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        TimeEntry::create([
            'user_id' => $user2->id,
            'date' => '2026-01-15',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 0,
            'total_hours' => 8.00,
        ]);

        $this->actingAs($admin)->post('/admin/send-report', [
            'month' => '2026-01',
        ]);

        Mail::assertSent(MonthlyHoursReport::class, function ($mail) use ($user1, $user2) {
            $userNames = $mail->users->pluck('name')->toArray();
            return in_array($user1->name, $userNames) && in_array($user2->name, $userNames);
        });
    }

    public function test_admin_can_export_csv(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        $response = $this->actingAs($admin)->get('/admin/export/csv?month=2026-01');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee($user->name);
    }

    public function test_admin_can_export_pdf(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        TimeEntry::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'break_minutes' => 30,
            'total_hours' => 7.50,
        ]);

        $response = $this->actingAs($admin)->get('/admin/export/pdf?month=2026-01');

        $response->assertStatus(200);
        $response->assertDownload('report-2026-01.pdf');
    }

    public function test_regular_user_cannot_send_report(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/admin/send-report', [
            'month' => '2026-01',
        ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_export_admin_csv(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/admin/export/csv?month=2026-01');

        $response->assertStatus(403);
    }
}
