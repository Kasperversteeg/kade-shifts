# Plan 07: Backend Feature Tests

## Goal
Write comprehensive feature tests for all application-specific controllers and the TimeEntry model. Currently only Breeze's default auth tests exist — there are no tests for the core business logic.

## Dependencies
- None. This is pure PHP and fully independent of all frontend plans.

## Test Files to Create

### `tests/Feature/TimeEntryTest.php`
Test the full CRUD lifecycle for time entries.

**Setup:** Create a user and authenticate.

Tests:
- `test_user_can_view_time_entries_page` — GET `/time-entries` returns 200 with Inertia page
- `test_user_can_view_time_entries_for_specific_month` — GET `/time-entries?month=2026-01` returns filtered entries
- `test_user_can_create_time_entry` — POST `/time-entries` with valid data creates entry and redirects
- `test_time_entry_calculates_total_hours` — POST with shift_start=09:00, shift_end=17:00, break=30 → total_hours = 7.5
- `test_time_entry_handles_cross_midnight_shift` — POST with shift_start=22:00, shift_end=06:00, break=0 → total_hours = 8.0
- `test_user_cannot_create_duplicate_date_entry` — POST same date twice → validation error
- `test_user_can_update_own_time_entry` — PATCH `/time-entries/{id}` with valid data
- `test_user_cannot_update_other_users_time_entry` — PATCH another user's entry → 403
- `test_user_can_delete_own_time_entry` — DELETE `/time-entries/{id}`
- `test_user_cannot_delete_other_users_time_entry` — DELETE another user's entry → 403
- `test_user_can_export_csv` — GET `/time-entries/export?month=2026-01` returns CSV
- `test_validation_requires_all_fields` — POST with missing fields → validation errors
- `test_shift_start_must_be_valid_time_format` — POST with invalid time → validation error
- `test_break_minutes_must_be_non_negative` — POST with negative break → validation error

### `tests/Feature/DashboardTest.php`
Test the user dashboard.

Tests:
- `test_authenticated_user_can_view_dashboard` — GET `/dashboard` returns 200
- `test_unauthenticated_user_is_redirected_to_login` — GET `/dashboard` without auth → redirect to `/login`
- `test_dashboard_shows_current_month_entries` — creates entries for current and previous month, verifies only current month entries are in props
- `test_dashboard_shows_correct_month_total` — creates entries, verifies `monthTotal` prop

### `tests/Feature/AdminTest.php`
Test admin-only functionality.

**Setup:** Create an admin user and a regular user.

Tests:
- `test_admin_can_view_overview` — GET `/admin/overview` as admin → 200
- `test_regular_user_cannot_view_overview` — GET `/admin/overview` as user → 403
- `test_overview_shows_all_users_hours` — Create entries for multiple users, verify `users` prop contains all
- `test_admin_can_view_user_detail` — GET `/admin/users/{id}` as admin → 200 with user's entries
- `test_admin_can_filter_overview_by_month` — GET `/admin/overview?month=2026-01` filters correctly
- `test_admin_can_send_monthly_report` — POST `/admin/send-report` sends email (use `Mail::fake()`)
- `test_monthly_report_email_contains_all_users` — Verify email content includes each user's hours
- `test_admin_can_export_csv` — GET `/admin/export/csv?month=2026-01` returns CSV with all users
- `test_admin_can_export_pdf` — GET `/admin/export/pdf?month=2026-01` returns PDF response
- `test_regular_user_cannot_send_report` — POST `/admin/send-report` as user → 403
- `test_regular_user_cannot_export_admin_csv` — GET `/admin/export/csv` as user → 403

### `tests/Feature/InvitationTest.php`
Test the invitation flow.

Tests:
- `test_admin_can_view_invitations_page` — GET `/admin/invitations` as admin → 200
- `test_admin_can_send_invitation` — POST `/admin/invitations` with email → creates invitation, sends email (use `Mail::fake()`)
- `test_cannot_invite_already_registered_email` — POST with existing user's email → validation error
- `test_invitation_email_contains_correct_link` — Verify email has link with token
- `test_user_can_view_invitation_acceptance_page` — GET `/invitation/{token}` → 200 with invitation data
- `test_expired_invitation_shows_expired_page` — GET `/invitation/{token}` for expired invitation → expired page
- `test_user_can_complete_registration_via_invitation` — POST `/invitation/{token}/complete` with name/password → creates user, logs in, redirects
- `test_completing_invitation_marks_it_as_accepted` — After completion, invitation has `accepted_at` set
- `test_cannot_reuse_accepted_invitation` — POST `/invitation/{token}/complete` for already-accepted invitation → error
- `test_regular_user_cannot_view_invitations` — GET `/admin/invitations` as user → 403
- `test_regular_user_cannot_send_invitation` — POST `/admin/invitations` as user → 403

### `tests/Unit/TimeEntryCalculationTest.php`
Test the `TimeEntry::calculateTotalHours()` static method in isolation.

Tests:
- `test_standard_day_shift` — 09:00 to 17:00, 30min break → 7.5
- `test_no_break` — 09:00 to 17:00, 0min break → 8.0
- `test_cross_midnight_shift` — 22:00 to 06:00, 0min break → 8.0
- `test_cross_midnight_with_break` — 22:00 to 06:00, 30min break → 7.5
- `test_short_shift` — 09:00 to 13:00, 0min break → 4.0
- `test_full_day_break` — 08:00 to 20:00, 60min break → 11.0

## Testing Patterns

Use Laravel's testing conventions:
```php
use App\Models\User;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

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
}
```

For admin tests, create a factory state or use:
```php
$admin = User::factory()->create(['role' => 'admin']);
```

For mail tests:
```php
Mail::fake();
// ... trigger action ...
Mail::assertSent(MonthlyHoursReport::class);
```

## Verification
```bash
ddev artisan test
ddev artisan test --filter=TimeEntry
ddev artisan test --filter=Admin
ddev artisan test --filter=Invitation
ddev artisan test --filter=Dashboard
```

All tests should pass. Check that existing Breeze auth tests still pass too.
