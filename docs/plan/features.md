# Implementatieplan Kade Shifts v1 — Gedetailleerd

## Context

Kade Shifts is een urenregistratie-app voor een horecateam van 15-20 personen. De MVP staat (uren CRUD, admin overzicht, export, uitnodigingen, PWA). Dit plan beschrijft de implementatie van alle v1-features verdeeld over 7 fasen, geordend op afhankelijkheden.

---

## Fase 1: Fundament

### 1.1 Spatie Permissions

**Doel**: Vervang de huidige `role` enum-kolom door `spatie/laravel-permission` voor flexibel rolbeheer.

**Stap 1 — Installeer package**
```bash
ddev composer require spatie/laravel-permission
ddev artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
ddev artisan migrate
```

**Stap 2 — Maak seeder: `database/seeders/RolesAndPermissionsSeeder.php`**
- Maak rollen: `admin`, `user`
- Maak permissions: `manage-users`, `approve-hours`, `manage-planning`, `manage-invitations`, `view-all-hours`
- Wijs alle permissions toe aan `admin` rol

**Stap 3 — Migratie: `database/migrations/xxxx_migrate_roles_to_spatie_permissions.php`**
- Loop door alle users, wijs Spatie-rol toe op basis van huidige `role` kolom
- Drop de `role` kolom van `users` tabel

**Stap 4 — Wijzig `app/Models/User.php`**
- Voeg `use HasRoles;` trait toe (van `Spatie\Permission\Traits\HasRoles`)
- Verwijder `'role'` uit `$fillable`
- Wijzig `isAdmin()`:
  ```php
  public function isAdmin(): bool
  {
      return $this->hasRole('admin');
  }
  ```

**Stap 5 — Wijzig `bootstrap/app.php`**
- Vervang middleware alias:
  ```php
  // Oud:
  'admin' => \App\Http\Middleware\AdminMiddleware::class,
  // Nieuw:
  'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
  'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
  ```

**Stap 6 — Wijzig `routes/web.php`**
- Vervang `middleware('admin')` door `middleware('role:admin')` op regel 34

**Stap 7 — Wijzig `app/Http/Controllers/AdminController.php`**
- Vervang alle `User::where('role', 'user')` (regels 21, 76, 101, 127) door `User::role('user')`

**Stap 8 — Wijzig `app/Http/Controllers/InvitationController.php`**
- In `complete()` methode (regel 75): verwijder `'role' => 'user'` uit `User::create()`, voeg toe:
  ```php
  $user->assignRole('user');
  ```

**Stap 9 — Wijzig `app/Http/Middleware/HandleInertiaRequests.php`**
- Voeg role info toe aan shared auth props zodat frontend isAdmin check blijft werken:
  ```php
  'auth' => [
      'user' => $request->user() ? array_merge(
          $request->user()->toArray(),
          ['role' => $request->user()->roles->first()?->name]
      ) : null,
  ],
  ```

**Stap 10 — Wijzig `database/seeders/AdminUserSeeder.php`**
- Verwijder `'role' => 'admin'` uit `User::create()`, voeg toe:
  ```php
  $user->assignRole('admin');
  ```

**Stap 11 — Wijzig `database/seeders/DatabaseSeeder.php`**
- Voeg `$this->call(RolesAndPermissionsSeeder::class);` toe VOOR `AdminUserSeeder`

**Stap 12 — Wijzig `database/factories/UserFactory.php`**
- Voeg `admin()` state toe:
  ```php
  public function admin(): static
  {
      return $this->afterCreating(function ($user) {
          $user->assignRole('admin');
      });
  }
  ```

**Stap 13 — Wijzig `tests/TestCase.php`**
- Voeg `setUp()` toe die `RolesAndPermissionsSeeder` draait:
  ```php
  protected function setUp(): void
  {
      parent::setUp();
      $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
  }
  ```

**Stap 14 — Wijzig bestaande tests**
- `tests/Feature/AdminTest.php`: Vervang `['role' => 'admin']` door `->assignRole('admin')` na factory create
- `tests/Feature/InvitationTest.php`: Idem
- `tests/Feature/TimeEntryTest.php`: Idem waar van toepassing
- `tests/Feature/DashboardTest.php`: Idem

**Stap 15 — Verwijder `app/Http/Middleware/AdminMiddleware.php`**

**Stap 16 — Schrijf tests: `tests/Feature/SpatiePermissionsTest.php`**
- Test dat user met role `admin` admin routes kan bereiken
- Test dat user zonder admin role 403 krijgt op admin routes
- Test dat permissions correct zijn toegewezen aan admin role
- Test dat InvitationController::complete() de `user` rol toekent

---

### 1.2 Soft Deletes

**Doel**: Voorkom harde verwijderingen van users en time entries.

**Stap 1 — Migratie: `database/migrations/xxxx_add_soft_deletes_to_users_and_time_entries.php`**
```php
Schema::table('users', function (Blueprint $table) {
    $table->softDeletes();
});
Schema::table('time_entries', function (Blueprint $table) {
    $table->softDeletes();
});
```

**Stap 2 — Wijzig `app/Models/User.php`**
- Voeg toe: `use Illuminate\Database\Eloquent\SoftDeletes;`
- Voeg `SoftDeletes` toe aan trait-regel: `use HasFactory, Notifiable, HasRoles, SoftDeletes;`

**Stap 3 — Wijzig `app/Models/TimeEntry.php`**
- Voeg `use SoftDeletes;` toe aan model
- Voeg `use Illuminate\Database\Eloquent\SoftDeletes;` import toe

**Stap 4 — Wijzig `tests/Feature/TimeEntryTest.php`**
- Vervang `assertDatabaseMissing('time_entries', ['id' => $entry->id])` door `$this->assertSoftDeleted($entry)`

**Stap 5 — Tests: `tests/Feature/SoftDeleteTest.php`**
- Test dat TimeEntry::destroy() een soft delete doet
- Test dat soft-deleted entries niet verschijnen in queries
- Test dat User soft delete werkt

---

### 1.3 Uitgebreid Gebruikersprofiel

**Doel**: Extra velden voor contract- en persoonsgegevens, admin kan bewerken.

**Stap 1 — Migratie: `database/migrations/xxxx_add_profile_fields_to_users_table.php`**
```php
Schema::table('users', function (Blueprint $table) {
    $table->decimal('hourly_rate', 5, 2)->nullable()->after('google_id');
    $table->string('contract_type')->nullable()->after('hourly_rate'); // vast, flex, oproep
    $table->date('contract_start_date')->nullable()->after('contract_type');
    $table->date('contract_end_date')->nullable()->after('contract_start_date');
    $table->date('birth_date')->nullable()->after('contract_end_date');
    $table->date('start_date')->nullable()->after('birth_date');
    $table->text('bsn')->nullable()->after('start_date'); // encrypted
    $table->string('phone')->nullable()->after('bsn');
    $table->string('address')->nullable()->after('phone');
    $table->string('city')->nullable()->after('address');
    $table->string('postal_code')->nullable()->after('city');
    $table->timestamp('contract_expiry_notified_at')->nullable()->after('postal_code');
});
```

**Stap 2 — Wijzig `app/Models/User.php`**
- Voeg toe aan `$fillable`: `hourly_rate`, `contract_type`, `contract_start_date`, `contract_end_date`, `birth_date`, `start_date`, `bsn`, `phone`, `address`, `city`, `postal_code`, `contract_expiry_notified_at`
- Voeg `'bsn'` toe aan `$hidden`
- Voeg casts toe:
  ```php
  'hourly_rate' => 'decimal:2',
  'contract_start_date' => 'date',
  'contract_end_date' => 'date',
  'birth_date' => 'date',
  'start_date' => 'date',
  'bsn' => 'encrypted',
  'contract_expiry_notified_at' => 'datetime',
  ```
- Voeg `profileCompleteness()` accessor toe:
  ```php
  public function getProfileCompletenessAttribute(): array
  {
      $required = ['hourly_rate', 'contract_type', 'contract_start_date', 'birth_date', 'start_date', 'phone'];
      $filled = collect($required)->filter(fn ($field) => !is_null($this->$field));
      return [
          'percentage' => round(($filled->count() / count($required)) * 100),
          'missing' => collect($required)->diff($filled)->values()->all(),
      ];
  }
  ```

**Stap 3 — Maak `app/Http/Requests/UpdateUserProfileRequest.php`**
```php
public function rules(): array
{
    return [
        'hourly_rate' => 'nullable|numeric|min:0|max:999.99',
        'contract_type' => 'nullable|in:vast,flex,oproep',
        'contract_start_date' => 'nullable|date',
        'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
        'birth_date' => 'nullable|date|before:today',
        'start_date' => 'nullable|date',
        'bsn' => 'nullable|string|size:9',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string|max:255',
        'city' => 'nullable|string|max:255',
        'postal_code' => 'nullable|string|max:10',
    ];
}
```

**Stap 4 — Maak `app/Http/Controllers/AdminUserController.php`**
- `edit(User $user)`: Render `Admin/UserEdit` met user data + profileCompleteness
- `update(UpdateUserProfileRequest $request, User $user)`: Update profiel velden, redirect back met success

**Stap 5 — Voeg routes toe in `routes/web.php`** (binnen admin groep):
```php
Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.user-edit');
Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('admin.user-update');
```

**Stap 6 — Maak `resources/js/Pages/Admin/UserEdit.vue`**
- Formulier met alle profiel velden (useForm)
- PATCH naar `admin.user-update`
- Profiel completeness indicator (progress bar)
- BSN veld met password-type input
- Terug-knop naar UserDetail

**Stap 7 — Wijzig `resources/js/Pages/Admin/UserDetail.vue`**
- Voeg "Bewerk profiel" Link toe naar `admin.user-edit`
- Toon extra profiel info (uurtarief, contracttype, contractdata)

**Stap 8 — Wijzig `app/Http/Controllers/AdminController.php`**
- In `userDetail()`: voeg extra user velden toe aan de response (hourly_rate, contract_type, etc.)

**Stap 9 — Voeg i18n keys toe** aan `resources/js/lang/en.json` en `nl.json`:
- `admin.editUser`, `admin.hourlyRate`, `admin.contractType`, `admin.contractStart`, `admin.contractEnd`, `admin.birthDate`, `admin.startDate`, `admin.bsn`, `admin.phone`, `admin.address`, `admin.city`, `admin.postalCode`, `admin.profileComplete`, `admin.profileIncomplete`, `admin.contractTypes.vast`, `admin.contractTypes.flex`, `admin.contractTypes.oproep`

**Stap 10 — Tests: `tests/Feature/UserProfileTest.php`**
- Test admin kan profiel bewerken
- Test validatie regels
- Test BSN wordt encrypted opgeslagen
- Test profileCompleteness accessor
- Test non-admin kan geen profielen bewerken

---

### 1.4 UX Basis

**Doel**: Bevestigingsdialogen, empty states, loading states.

> Toast notificaties bestaan al in AuthenticatedLayout.vue.

**Stap 1 — Maak `resources/js/Components/ConfirmDialog.vue`**
- Props: `show: boolean`, `title: string`, `message: string`, `confirmLabel: string`, `variant: 'error' | 'warning'`
- Emits: `confirm`, `cancel`
- Gebruikt DaisyUI `<dialog>` modal met `modal-action` buttons
- Voorbeeld:
  ```vue
  <dialog ref="dialogRef" class="modal">
    <div class="modal-box">
      <h3 class="text-lg font-bold">{{ title }}</h3>
      <p class="py-4">{{ message }}</p>
      <div class="modal-action">
        <button class="btn" @click="$emit('cancel')">{{ $t('common.cancel') }}</button>
        <button class="btn" :class="variant === 'error' ? 'btn-error' : 'btn-warning'" @click="$emit('confirm')">
          {{ confirmLabel }}
        </button>
      </div>
    </div>
  </dialog>
  ```

**Stap 2 — Wijzig `resources/js/Components/TimeEntryCard.vue`**
- Vervang `if (confirm(...))` door `ConfirmDialog` component
- Voeg `showDeleteConfirm` ref toe
- Toon dialog bij klik op delete knop

**Stap 3 — Voeg i18n keys toe**:
- `common.confirmDelete`, `common.confirmDeleteMessage`, `common.delete`, `common.cancel`

---

## Fase 2: Accordeerflow

### 2.1 Workflow Status op TimeEntry

**Doel**: Werknemers dienen uren in, admin keurt goed of wijst af.

**Stap 1 — Migratie: `database/migrations/xxxx_add_status_to_time_entries_table.php`**
```php
Schema::table('time_entries', function (Blueprint $table) {
    $table->string('status')->default('draft')->after('notes'); // draft, submitted, approved, rejected
    $table->text('rejection_reason')->nullable()->after('status');
    $table->foreignId('reviewed_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
});
```

**Stap 2 — Wijzig `app/Models/TimeEntry.php`**
- Voeg toe aan `$fillable`: `status`, `rejection_reason`, `reviewed_by`, `reviewed_at`
- Voeg casts toe: `'reviewed_at' => 'datetime'`
- Voeg relatie toe:
  ```php
  public function reviewer()
  {
      return $this->belongsTo(User::class, 'reviewed_by');
  }
  ```
- Voeg status helpers toe:
  ```php
  public function isDraft(): bool { return $this->status === 'draft'; }
  public function isSubmitted(): bool { return $this->status === 'submitted'; }
  public function isApproved(): bool { return $this->status === 'approved'; }
  public function isRejected(): bool { return $this->status === 'rejected'; }
  public function isEditableByEmployee(): bool { return in_array($this->status, ['draft', 'rejected']); }
  ```

**Stap 3 — Wijzig `app/Http/Controllers/TimeEntryController.php`**
- In `store()`: voeg `'status' => 'draft'` toe aan create array
- In `update()`: voeg check toe dat entry editable is:
  ```php
  if (!$timeEntry->isEditableByEmployee()) {
      return redirect()->back()->with('error', __('Cannot edit approved or submitted entries.'));
  }
  ```
  Bij update, reset status naar draft: `'status' => 'draft'`
- In `destroy()`: zelfde editability check
- Voeg `submit()` methode toe:
  ```php
  public function submit(TimeEntry $timeEntry)
  {
      if ($timeEntry->user_id !== auth()->id()) abort(403);
      if (!$timeEntry->isDraft() && !$timeEntry->isRejected()) {
          return redirect()->back()->with('error', __('Entry cannot be submitted.'));
      }
      $timeEntry->update(['status' => 'submitted']);
      return redirect()->back()->with('success', __('Entry submitted for approval.'));
  }
  ```
- Voeg `submitMonth()` methode toe:
  ```php
  public function submitMonth(Request $request)
  {
      $month = $request->input('month', Carbon::now()->format('Y-m'));
      $date = Carbon::parse($month . '-01');
      TimeEntry::where('user_id', auth()->id())
          ->whereYear('date', $date->year)
          ->whereMonth('date', $date->month)
          ->whereIn('status', ['draft', 'rejected'])
          ->update(['status' => 'submitted']);
      return redirect()->back()->with('success', __('All entries submitted for approval.'));
  }
  ```

**Stap 4 — Voeg routes toe in `routes/web.php`** (binnen auth groep):
```php
Route::post('/time-entries/{time_entry}/submit', [TimeEntryController::class, 'submit'])->name('time-entries.submit');
Route::post('/time-entries/submit-month', [TimeEntryController::class, 'submitMonth'])->name('time-entries.submit-month');
```

**Stap 5 — Wijzig `resources/js/Components/TimeEntryCard.vue`**
- Voeg status badge toe (DaisyUI badge component):
  - `draft` → `badge-ghost` (grijs)
  - `submitted` → `badge-warning` (oranje)
  - `approved` → `badge-success` (groen)
  - `rejected` → `badge-error` (rood)
- Toon rejection_reason als tooltip/text bij rejected status
- Conditionally hide edit/delete knoppen als `!isEditableByEmployee`
- Voeg "Indienen" knop toe voor draft/rejected entries (POST naar `time-entries.submit`)

**Stap 6 — Wijzig `resources/js/Pages/TimeEntries/Index.vue`**
- Voeg "Alles indienen" knop toe bovenaan (POST naar `time-entries.submit-month`)
- Alleen tonen als er draft/rejected entries zijn
- Toon samenvattend overzicht van statussen (X concept, Y ingediend, Z goedgekeurd)

**Stap 7 — Voeg i18n keys toe**:
- `status.draft`, `status.submitted`, `status.approved`, `status.rejected`
- `timeEntries.submit`, `timeEntries.submitAll`, `timeEntries.rejectionReason`, `timeEntries.cannotEdit`, `timeEntries.statusSummary`

---

### 2.2 Admin Accordeeroverzicht

**Doel**: Admin kan uren goedkeuren, afwijzen, en bulk-accorderen.

**Stap 1 — Maak `app/Http/Controllers/ApprovalController.php`**
- `approve(TimeEntry $timeEntry)`:
  ```php
  $timeEntry->update([
      'status' => 'approved',
      'reviewed_by' => auth()->id(),
      'reviewed_at' => now(),
      'rejection_reason' => null,
  ]);
  ```
- `reject(Request $request, TimeEntry $timeEntry)`:
  ```php
  $request->validate(['reason' => 'required|string|max:500']);
  $timeEntry->update([
      'status' => 'rejected',
      'reviewed_by' => auth()->id(),
      'reviewed_at' => now(),
      'rejection_reason' => $request->reason,
  ]);
  ```
- `bulkApprove(Request $request)`:
  ```php
  $request->validate(['entry_ids' => 'required|array', 'entry_ids.*' => 'exists:time_entries,id']);
  TimeEntry::whereIn('id', $request->entry_ids)
      ->where('status', 'submitted')
      ->update([
          'status' => 'approved',
          'reviewed_by' => auth()->id(),
          'reviewed_at' => now(),
      ]);
  ```

**Stap 2 — Voeg routes toe in `routes/web.php`** (binnen admin groep):
```php
Route::post('/entries/{time_entry}/approve', [ApprovalController::class, 'approve'])->name('admin.entries.approve');
Route::post('/entries/{time_entry}/reject', [ApprovalController::class, 'reject'])->name('admin.entries.reject');
Route::post('/entries/bulk-approve', [ApprovalController::class, 'bulkApprove'])->name('admin.entries.bulk-approve');
```

**Stap 3 — Wijzig `app/Http/Controllers/AdminController.php`**
- In `overview()`: voeg status counts toe aan user map:
  ```php
  'status_counts' => [
      'draft' => $user->timeEntries->where('status', 'draft')->count(),
      'submitted' => $user->timeEntries->where('status', 'submitted')->count(),
      'approved' => $user->timeEntries->where('status', 'approved')->count(),
      'rejected' => $user->timeEntries->where('status', 'rejected')->count(),
  ],
  ```
- In `userDetail()`: stuur `isAdmin => true` mee zodat frontend approve/reject knoppen toont

**Stap 4 — Maak `resources/js/Components/RejectEntryModal.vue`**
- Props: `show: boolean`, `entryId: number`
- Emits: `close`
- Formulier met textarea voor reden
- POST naar `admin.entries.reject` met `reason`

**Stap 5 — Wijzig `resources/js/Pages/Admin/Overview.vue`**
- Toon status indicators per werknemer (badges met counts voor submitted/approved)
- Voeg statusfilter toe (tabs of dropdown: alle/openstaand/goedgekeurd)
- Voeg bulk-approve knop toe per werknemer (approve alle submitted entries van die user in die maand)

**Stap 6 — Wijzig `resources/js/Pages/Admin/UserDetail.vue`**
- Verwijder `readonly` prop op TimeEntryCards
- Voeg per entry: approve knop (POST) en reject knop (opent RejectEntryModal)
- Voeg bulk approve knop toe bovenaan
- Toon status badge per entry

**Stap 7 — Voeg i18n keys toe**:
- `admin.approve`, `admin.reject`, `admin.bulkApprove`, `admin.rejectionReason`, `admin.pendingApproval`, `admin.allApproved`

**Stap 8 — Tests: `tests/Feature/ApprovalTest.php`**
- Test approve wijzigt status naar approved
- Test reject vereist reden
- Test reject wijzigt status naar rejected met reden
- Test bulk approve werkt voor meerdere entries
- Test employee kan geen entries approven (403)
- Test approved entries zijn locked voor employee
- Test rejected entries kunnen opnieuw bewerkt/ingediend worden
- Test admin kan approved entries nog steeds aanpassen

---

### 2.3 Export Aanpassen

**Stap 1 — Wijzig `app/Http/Controllers/AdminController.php`**
- In `exportCsv()`: voeg `Status` kolom toe aan CSV header en rows
- Voeg `?status=approved` query param support toe (filter op status)
- In `exportPdf()`: zelfde status kolom + filter

**Stap 2 — Wijzig `resources/views/reports/monthly.blade.php`**
- Voeg `Status` kolom toe aan tabel header en body

**Stap 3 — Wijzig `resources/js/Pages/Admin/Overview.vue`**
- Voeg `?status=approved` toe aan export URLs als filter actief is

---

## Fase 3: ATW Compliance

### 3.1–3.3 ATW Waarschuwingen

**Doel**: Zachte waarschuwingen op basis van Arbeidstijdenwet regels.

**Stap 1 — Maak `app/Services/AtwComplianceService.php`**
```php
class AtwComplianceService
{
    public function validateEntry(TimeEntry $entry, ?TimeEntry $previousEntry = null): array
    {
        $warnings = [];
        $shiftHours = $entry->total_hours + ($entry->break_minutes / 60);

        // Pauze validatie
        if ($shiftHours > 5.5 && $entry->break_minutes < 30) {
            $warnings[] = ['type' => 'break_short', 'message' => 'Shift > 5,5u vereist minimaal 30 min pauze'];
        }
        if ($shiftHours > 10 && $entry->break_minutes < 45) {
            $warnings[] = ['type' => 'break_very_short', 'message' => 'Shift > 10u vereist minimaal 45 min pauze'];
        }

        // Dienst limiet
        if ($shiftHours > 12) {
            $warnings[] = ['type' => 'shift_too_long', 'message' => 'Shift overschrijdt 12 uur (ATW maximum)'];
        }

        // Rusttijd tussen diensten
        if ($previousEntry) {
            $prevEnd = Carbon::parse($previousEntry->date->format('Y-m-d') . ' ' . $previousEntry->shift_end);
            if ($prevEnd->gt(Carbon::parse($previousEntry->date->format('Y-m-d') . ' ' . $previousEntry->shift_start))) {
                // niet cross-midnight
            } else {
                $prevEnd->addDay();
            }
            $currentStart = Carbon::parse($entry->date->format('Y-m-d') . ' ' . $entry->shift_start);
            $restHours = $prevEnd->diffInHours($currentStart);
            if ($restHours < 11) {
                $warnings[] = ['type' => 'rest_too_short', 'message' => "Slechts {$restHours}u rust (min. 11u vereist)"];
            }
        }

        return $warnings;
    }

    public function getWeeklyTotals(int $userId, string $month): array
    {
        // Bereken weektotalen voor de maand
        // Return array met per week: weeknummer, totaal uren, warning boolean
    }

    public function checkWeeklyLimit(int $userId, Carbon $weekStart): array
    {
        $entries = TimeEntry::where('user_id', $userId)
            ->whereBetween('date', [$weekStart, $weekStart->copy()->endOfWeek()])
            ->get();
        $total = $entries->sum('total_hours');
        $warnings = [];
        if ($total > 60) {
            $warnings[] = ['type' => 'week_over_60', 'message' => "Week totaal {$total}u overschrijdt 60u maximum"];
        }
        return ['total' => $total, 'warnings' => $warnings];
    }
}
```

**Stap 2 — Wijzig `app/Http/Controllers/TimeEntryController.php`**
- Inject `AtwComplianceService` in constructor
- In `index()`: voeg ATW warnings toe aan elke entry, voeg `weeklyTotals` toe aan response:
  ```php
  $entries = $entries->map(function ($entry, $index) use ($entries) {
      $previousEntry = $entries->get($index + 1); // entries are desc by date
      $entry->atw_warnings = $this->atwService->validateEntry($entry, $previousEntry);
      return $entry;
  });
  ```
- Voeg `weeklyTotals` toe aan Inertia response

**Stap 3 — Wijzig `resources/js/Components/TimeEntryForm.vue`**
- Voeg computed warnings toe die real-time berekenen op basis van formulier waarden:
  - Als shift > 5.5u en pauze < 30 → toon waarschuwing
  - Als shift > 10u en pauze < 45 → toon waarschuwing
  - Als shift > 12u → toon waarschuwing
- Toon als DaisyUI `alert alert-warning` onder het formulier

**Stap 4 — Wijzig `resources/js/Components/TimeEntryCard.vue`**
- Als `entry.atw_warnings` array niet leeg is, toon warning icon (driehoek met !) met tooltip

**Stap 5 — Maak `resources/js/Components/WeeklySummary.vue`**
- Props: `weeklyTotals: Array<{week: number, total: number, warnings: Array}>`
- Toon per week: weeknummer, totaal uren, badge kleur (groen < 48u, oranje 48-60u, rood > 60u)
- Responsive tabel/cards

**Stap 6 — Wijzig `resources/js/Pages/TimeEntries/Index.vue`**
- Voeg `WeeklySummary` component toe boven de entries lijst
- Pass `weeklyTotals` prop door

**Stap 7 — Voeg i18n keys toe**:
- `atw.breakShort`, `atw.breakVeryShort`, `atw.shiftTooLong`, `atw.restTooShort`, `atw.weekOver48`, `atw.weekOver60`, `atw.weeklyTotals`, `atw.week`

**Stap 8 — Tests: `tests/Unit/AtwComplianceTest.php`**
- Test pauze waarschuwing bij >5.5u en <30 min
- Test pauze waarschuwing bij >10u en <45 min
- Test geen waarschuwing bij correcte pauze
- Test shift >12u waarschuwing
- Test rusttijd <11u waarschuwing
- Test weektotaal >60u waarschuwing
- Test geen waarschuwingen bij correcte entry

---

## Fase 4: Document Uploads & Contractgeneratie

### 4.1 Document Upload Systeem

**Stap 1 — Migratie: `database/migrations/xxxx_create_documents_table.php`**
```php
Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->morphs('documentable'); // documentable_type + documentable_id
    $table->string('type'); // id_front, id_back, contract_signed, contract_generated, other
    $table->string('original_filename');
    $table->string('path'); // storage path
    $table->string('mime_type');
    $table->unsignedBigInteger('file_size');
    $table->foreignId('uploaded_by')->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
});
```

**Stap 2 — Maak `app/Models/Document.php`**
```php
class Document extends Model
{
    use SoftDeletes;

    protected $fillable = ['documentable_type', 'documentable_id', 'type', 'original_filename', 'path', 'mime_type', 'file_size', 'uploaded_by'];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
```

**Stap 3 — Wijzig `app/Models/User.php`**
- Voeg relatie toe:
  ```php
  public function documents()
  {
      return $this->morphMany(Document::class, 'documentable');
  }
  ```

**Stap 4 — Maak `app/Http/Controllers/DocumentController.php`**
- `store(Request $request, User $user)` — admin upload voor user:
  ```php
  $request->validate([
      'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
      'type' => 'required|in:id_front,id_back,contract_signed,other',
  ]);
  $path = $request->file('file')->store("documents/{$user->id}", 'local');
  $user->documents()->create([
      'type' => $request->type,
      'original_filename' => $request->file('file')->getClientOriginalName(),
      'path' => $path,
      'mime_type' => $request->file('file')->getMimeType(),
      'file_size' => $request->file('file')->getSize(),
      'uploaded_by' => auth()->id(),
  ]);
  ```
- `storeOwn(Request $request)` — werknemer upload eigen document (ID kaart):
  - Zelfde logica maar `documentable` is auth()->user()
  - Type beperkt tot: `id_front`, `id_back`
- `download(Document $document)` — download via signed response:
  ```php
  // Check: admin mag alles, user mag alleen eigen documenten
  return Storage::disk('local')->download($document->path, $document->original_filename);
  ```
- `destroy(Document $document)` — soft delete (alleen admin)

**Stap 5 — Routes in `routes/web.php`**:
```php
// Auth groep:
Route::post('/my-documents', [DocumentController::class, 'storeOwn'])->name('documents.store');

// Admin groep:
Route::post('/users/{user}/documents', [DocumentController::class, 'store'])->name('admin.documents.store');
Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('admin.documents.download');
Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('admin.documents.destroy');
```

**Stap 6 — Maak `resources/js/Components/DocumentUpload.vue`**
- Props: `uploadUrl: string`, `acceptedTypes: string[]`, `documentType: string`
- File input met `capture="environment"` attribute voor camera op mobiel
- Drag & drop zone
- Upload via Inertia `router.post()` met `FormData`
- Toont progress indicator

**Stap 7 — Wijzig `resources/js/Pages/Admin/UserDetail.vue`**
- Voeg sectie "Documenten" toe onderaan
- Lijst van bestaande documenten met download/delete knoppen
- Upload formulier met DocumentUpload component
- Type selectie (ID voorkant, ID achterkant, Getekend contract, Overig)

**Stap 8 — Wijzig `resources/js/Pages/Profile/Edit.vue`**
- Voeg sectie "Documenten" toe
- Alleen ID-kaart upload (voor + achter)
- Toon bestaande uploads

**Stap 9 — Voeg i18n keys toe**:
- `documents.title`, `documents.upload`, `documents.download`, `documents.delete`, `documents.type.id_front`, `documents.type.id_back`, `documents.type.contract_signed`, `documents.type.contract_generated`, `documents.type.other`, `documents.noDocuments`, `documents.maxSize`

**Stap 10 — Tests: `tests/Feature/DocumentTest.php`**
- Test admin kan document uploaden voor user
- Test user kan eigen ID uploaden
- Test user kan niet andermans documenten downloaden
- Test admin kan alle documenten downloaden
- Test file validatie (max size, mime types)
- Test soft delete werkt

---

### 4.2 Contractgeneratie (extern Python script)

**Stap 1 — Maak `config/contracts.php`**
```php
return [
    'expiry_notification_days' => env('CONTRACT_EXPIRY_DAYS', 45),
    'generator_script' => env('CONTRACT_GENERATOR_PATH', base_path('scripts/contract-generator/generate.py')),
    'python_binary' => env('PYTHON_BINARY', 'python3'),
    'template_path' => env('CONTRACT_TEMPLATE_PATH', base_path('scripts/contract-generator/templates')),
];
```

**Stap 2 — Maak `app/Services/ContractGenerationService.php`**
```php
class ContractGenerationService
{
    public function generate(User $user): string
    {
        $outputPath = storage_path("app/documents/{$user->id}/contract-" . now()->format('Y-m-d') . '.docx');

        $result = Process::run([
            config('contracts.python_binary'),
            config('contracts.generator_script'),
            '--name', $user->name,
            '--birth-date', $user->birth_date->format('Y-m-d'),
            '--hourly-rate', (string) $user->hourly_rate,
            '--contract-type', $user->contract_type,
            '--start-date', $user->contract_start_date->format('Y-m-d'),
            '--end-date', $user->contract_end_date?->format('Y-m-d') ?? '',
            '--address', $user->address ?? '',
            '--city', $user->city ?? '',
            '--postal-code', $user->postal_code ?? '',
            '--bsn', $user->bsn ?? '',
            '--output', $outputPath,
        ]);

        if (!$result->successful()) {
            throw new \RuntimeException('Contract generation failed: ' . $result->errorOutput());
        }

        return $outputPath;
    }

    public function canGenerate(User $user): bool
    {
        return $user->profileCompleteness['percentage'] === 100;
    }
}
```

**Stap 3 — Maak `app/Http/Controllers/ContractController.php`**
- `generate(User $user)`:
  ```php
  if (!$this->contractService->canGenerate($user)) {
      return redirect()->back()->with('error', __('Profile is not complete.'));
  }
  $path = $this->contractService->generate($user);
  // Sla op als document
  $user->documents()->create([
      'type' => 'contract_generated',
      'original_filename' => 'contract-' . Str::slug($user->name) . '.docx',
      'path' => str_replace(storage_path('app/'), '', $path),
      'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'file_size' => filesize($path),
      'uploaded_by' => auth()->id(),
  ]);
  return redirect()->back()->with('success', __('Contract generated successfully.'));
  ```

**Stap 4 — Route in `routes/web.php`** (admin groep):
```php
Route::post('/users/{user}/generate-contract', [ContractController::class, 'generate'])->name('admin.generate-contract');
```

**Stap 5 — Wijzig `resources/js/Pages/Admin/UserDetail.vue`**
- Voeg "Genereer contract" knop toe
- Alleen zichtbaar als profileCompleteness.percentage === 100
- Disabled met tooltip als profiel incompleet
- POST naar `admin.generate-contract`

**Stap 6 — Voeg i18n keys toe**:
- `contracts.generate`, `contracts.generating`, `contracts.profileIncomplete`, `contracts.generated`

**Stap 7 — Tests: `tests/Feature/ContractGenerationTest.php`**
- Test canGenerate() returned false bij incompleet profiel
- Test generate route faalt bij incompleet profiel
- Test generate route succeeds bij compleet profiel (mock Process)
- Test document wordt opgeslagen na generatie

---

## Fase 5: Verlof- & Ziekteregistratie

### 5.1 Verlofaanvraag Systeem

**Stap 1 — Migratie: `database/migrations/xxxx_create_leave_requests_table.php`**
```php
Schema::create('leave_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('type'); // vakantie, bijzonder_verlof, onbetaald_verlof
    $table->date('start_date');
    $table->date('end_date');
    $table->text('reason')->nullable();
    $table->string('status')->default('pending'); // pending, approved, rejected
    $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('reviewed_at')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->timestamps();
});
```

**Stap 2 — Migratie: `database/migrations/xxxx_add_leave_balance_to_users_table.php`**
```php
Schema::table('users', function (Blueprint $table) {
    $table->integer('statutory_leave_days')->default(20)->after('contract_expiry_notified_at');
    $table->integer('extra_leave_days')->default(5)->after('statutory_leave_days');
});
```

**Stap 3 — Maak `app/Models/LeaveRequest.php`**
```php
class LeaveRequest extends Model
{
    protected $fillable = ['user_id', 'type', 'start_date', 'end_date', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'rejection_reason'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function getDaysAttribute(): int
    {
        // Bereken werkdagen tussen start en end
        return $this->start_date->diffInWeekdays($this->end_date) + 1;
    }
}
```

**Stap 4 — Wijzig `app/Models/User.php`**
- Voeg toe aan `$fillable`: `statutory_leave_days`, `extra_leave_days`
- Voeg relaties toe:
  ```php
  public function leaveRequests() { return $this->hasMany(LeaveRequest::class); }
  ```
- Voeg accessor toe:
  ```php
  public function getLeaveBalanceAttribute(): array
  {
      $year = now()->year;
      $total = $this->statutory_leave_days + $this->extra_leave_days;
      $used = $this->leaveRequests()
          ->where('type', 'vakantie')
          ->where('status', 'approved')
          ->whereYear('start_date', $year)
          ->get()
          ->sum('days');
      return ['total' => $total, 'used' => $used, 'remaining' => $total - $used];
  }
  ```

**Stap 5 — Maak `app/Http/Requests/StoreLeaveRequest.php`**
```php
public function rules(): array
{
    return [
        'type' => 'required|in:vakantie,bijzonder_verlof,onbetaald_verlof',
        'start_date' => 'required|date|after_or_equal:today',
        'end_date' => 'required|date|after_or_equal:start_date',
        'reason' => 'nullable|string|max:500',
    ];
}
```

**Stap 6 — Maak `app/Http/Controllers/LeaveRequestController.php`**
- `index()`:
  ```php
  return Inertia::render('Leave/Index', [
      'leaveRequests' => auth()->user()->leaveRequests()->latest()->get(),
      'leaveBalance' => auth()->user()->leaveBalance,
  ]);
  ```
- `store(StoreLeaveRequest $request)`:
  ```php
  auth()->user()->leaveRequests()->create($request->validated());
  return redirect()->back()->with('success', __('Leave request submitted.'));
  ```
- `destroy(LeaveRequest $leaveRequest)`:
  ```php
  if ($leaveRequest->user_id !== auth()->id() || $leaveRequest->status !== 'pending') abort(403);
  $leaveRequest->delete();
  ```

**Stap 7 — Maak `app/Http/Controllers/AdminLeaveController.php`**
- `index()`: Toon alle pending/recente verlofaanvragen
- `approve(LeaveRequest $leaveRequest)`: Status → approved
- `reject(Request $request, LeaveRequest $leaveRequest)`: Status → rejected met reden

**Stap 8 — Routes in `routes/web.php`**:
```php
// Auth groep:
Route::get('/leave', [LeaveRequestController::class, 'index'])->name('leave.index');
Route::post('/leave', [LeaveRequestController::class, 'store'])->name('leave.store');
Route::delete('/leave/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('leave.destroy');

// Admin groep:
Route::get('/leave-requests', [AdminLeaveController::class, 'index'])->name('admin.leave-requests');
Route::post('/leave-requests/{leaveRequest}/approve', [AdminLeaveController::class, 'approve'])->name('admin.leave.approve');
Route::post('/leave-requests/{leaveRequest}/reject', [AdminLeaveController::class, 'reject'])->name('admin.leave.reject');
```

**Stap 9 — Wijzig `resources/js/Layouts/AuthenticatedLayout.vue`**
- Voeg "Verlof" nav item toe (na "Mijn Uren"):
  ```vue
  <li>
      <Link :href="route('leave.index')" :class="{ 'active': route().current('leave.*') }">
          {{ $t('nav.leave') }}
      </Link>
  </li>
  ```
- Voeg toe in zowel desktop als mobiel menu

**Stap 10 — Maak `resources/js/Pages/Leave/Index.vue`**
- Toon verlofsaldo (totaal, gebruikt, resterend) in DaisyUI stats component
- Aanvraag formulier: type (select), start datum, eind datum, reden
- Lijst van eigen aanvragen met status badges
- Annuleer knop bij pending aanvragen

**Stap 11 — Maak `resources/js/Pages/Admin/LeaveRequests.vue`**
- Tabel van alle aanvragen gesorteerd op datum
- Per aanvraag: naam, type, periode, aantal dagen, status
- Goedkeur/Afwijs knoppen voor pending aanvragen
- Afwijs opent modal met reden textarea

**Stap 12 — Voeg i18n keys toe**:
- `nav.leave`
- `leave.title`, `leave.request`, `leave.type`, `leave.startDate`, `leave.endDate`, `leave.reason`, `leave.submit`, `leave.balance`, `leave.total`, `leave.used`, `leave.remaining`, `leave.noRequests`
- `leave.types.vakantie`, `leave.types.bijzonder_verlof`, `leave.types.onbetaald_verlof`
- `leave.status.pending`, `leave.status.approved`, `leave.status.rejected`
- `admin.leaveRequests`, `admin.approveLeave`, `admin.rejectLeave`

**Stap 13 — Tests: `tests/Feature/LeaveRequestTest.php`**
- Test werknemer kan verlofaanvraag indienen
- Test validatie (eind na start, type verplicht)
- Test admin kan goedkeuren
- Test admin kan afwijzen met reden
- Test werknemer kan pending aanvraag annuleren
- Test werknemer kan goedgekeurde aanvraag NIET annuleren
- Test verlofsaldo wordt correct berekend
- Test non-vakantie aanvragen tellen niet mee voor saldo

---

### 5.2 Ziekteregistratie

**Stap 1 — Migratie: `database/migrations/xxxx_create_sick_leaves_table.php`**
```php
Schema::create('sick_leaves', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->date('start_date');
    $table->date('end_date')->nullable(); // null = nog ziek
    $table->text('notes')->nullable();
    $table->foreignId('registered_by')->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

**Stap 2 — Maak `app/Models/SickLeave.php`**
```php
class SickLeave extends Model
{
    protected $fillable = ['user_id', 'start_date', 'end_date', 'notes', 'registered_by'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function user() { return $this->belongsTo(User::class); }
    public function registrar() { return $this->belongsTo(User::class, 'registered_by'); }

    public function isActive(): bool { return is_null($this->end_date); }

    public function getDaysAttribute(): int
    {
        $end = $this->end_date ?? now();
        return $this->start_date->diffInWeekdays($end) + 1;
    }
}
```

**Stap 3 — Wijzig `app/Models/User.php`**
- Voeg relatie toe:
  ```php
  public function sickLeaves() { return $this->hasMany(SickLeave::class); }
  ```
- Voeg helpers toe:
  ```php
  public function isCurrentlySick(): bool
  {
      return $this->sickLeaves()->whereNull('end_date')->exists();
  }

  public function getSickDaysThisYearAttribute(): int
  {
      return $this->sickLeaves()
          ->whereYear('start_date', now()->year)
          ->get()
          ->sum('days');
  }
  ```

**Stap 4 — Maak `app/Http/Controllers/SickLeaveController.php`**
- `store(Request $request, User $user)` — admin registreert ziekmelding:
  ```php
  $request->validate([
      'start_date' => 'required|date',
      'notes' => 'nullable|string|max:500',
  ]);
  $user->sickLeaves()->create([
      'start_date' => $request->start_date,
      'notes' => $request->notes,
      'registered_by' => auth()->id(),
  ]);
  ```
- `recover(Request $request, SickLeave $sickLeave)` — hersteldmelding:
  ```php
  $request->validate(['end_date' => 'required|date|after_or_equal:' . $sickLeave->start_date->format('Y-m-d')]);
  $sickLeave->update(['end_date' => $request->end_date]);
  ```

**Stap 5 — Routes in `routes/web.php`** (admin groep):
```php
Route::post('/users/{user}/sick-leave', [SickLeaveController::class, 'store'])->name('admin.sick-leave.store');
Route::patch('/sick-leave/{sickLeave}/recover', [SickLeaveController::class, 'recover'])->name('admin.sick-leave.recover');
```

**Stap 6 — Wijzig `resources/js/Pages/Admin/UserDetail.vue`**
- Voeg "Ziektehistorie" sectie toe
- Toon actieve ziekte met "Hersteld" knop
- Toon historische ziekteperioden
- "Ziek melden" knop met datum picker
- Toon totaal ziektedagen dit jaar

**Stap 7 — Voeg i18n keys toe**:
- `sickLeave.title`, `sickLeave.register`, `sickLeave.recover`, `sickLeave.startDate`, `sickLeave.endDate`, `sickLeave.notes`, `sickLeave.currentlySick`, `sickLeave.recovered`, `sickLeave.daysThisYear`, `sickLeave.noRecords`

**Stap 8 — Tests: `tests/Feature/SickLeaveTest.php`**
- Test admin kan ziekmelding registreren
- Test hersteldmelding werkt
- Test actieve ziekte check
- Test ziektedagen berekening per jaar
- Test non-admin kan geen ziekte registreren

---

## Fase 6: Planning / Roostering

### 6.1 Data Model

**Stap 1 — Migratie: `database/migrations/xxxx_create_shifts_table.php`**
```php
Schema::create('shifts', function (Blueprint $table) {
    $table->id();
    $table->date('date');
    $table->time('start_time');
    $table->time('end_time');
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = open dienst
    $table->string('position')->nullable(); // bar, bediening, keuken, etc.
    $table->text('notes')->nullable();
    $table->boolean('published')->default(false);
    $table->foreignId('created_by')->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->index(['date', 'user_id']);
    $table->index(['date', 'published']);
});
```

**Stap 2 — Maak `app/Models/Shift.php`**
```php
class Shift extends Model
{
    protected $fillable = ['date', 'start_time', 'end_time', 'user_id', 'position', 'notes', 'published', 'created_by'];

    protected $casts = [
        'date' => 'date',
        'published' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function isOpen(): bool { return is_null($this->user_id); }
}
```

**Stap 3 — Wijzig `app/Models/User.php`**
- Voeg relatie toe:
  ```php
  public function shifts() { return $this->hasMany(Shift::class); }
  ```

---

### 6.2 Admin Planbord

**Stap 1 — Maak `app/Http/Controllers/PlanningController.php`**
- `index(Request $request)`:
  ```php
  $weekStart = $request->get('week')
      ? Carbon::parse($request->get('week'))->startOfWeek()
      : Carbon::now()->startOfWeek();

  $weekEnd = $weekStart->copy()->endOfWeek();

  $shifts = Shift::with('user')
      ->whereBetween('date', [$weekStart, $weekEnd])
      ->orderBy('date')
      ->orderBy('start_time')
      ->get();

  $users = User::role('user')->where('is_active', true)->get(['id', 'name']);

  return Inertia::render('Admin/Planning', [
      'shifts' => $shifts,
      'users' => $users,
      'weekStart' => $weekStart->format('Y-m-d'),
      'weekEnd' => $weekEnd->format('Y-m-d'),
  ]);
  ```
- `store(Request $request)`:
  ```php
  $request->validate([
      'date' => 'required|date',
      'start_time' => 'required|date_format:H:i',
      'end_time' => 'required|date_format:H:i',
      'user_id' => 'nullable|exists:users,id',
      'position' => 'nullable|string|max:100',
      'notes' => 'nullable|string|max:500',
  ]);
  Shift::create([...$request->validated(), 'created_by' => auth()->id()]);
  ```
- `update(Request $request, Shift $shift)`: Update shift velden
- `destroy(Shift $shift)`: Verwijder shift
- `publish(Request $request)`:
  ```php
  $request->validate(['week' => 'required|date']);
  $weekStart = Carbon::parse($request->week)->startOfWeek();
  $weekEnd = $weekStart->copy()->endOfWeek();

  $shifts = Shift::whereBetween('date', [$weekStart, $weekEnd])
      ->where('published', false)
      ->get();

  $shifts->each->update(['published' => true]);

  // Notify werknemers (fase 7.3)
  $userIds = $shifts->pluck('user_id')->unique()->filter();
  $users = User::whereIn('id', $userIds)->get();
  foreach ($users as $user) {
      Mail::to($user->email)->queue(new PlanningPublished($user, $weekStart));
  }
  ```

**Stap 2 — Routes in `routes/web.php`** (admin groep):
```php
Route::get('/planning', [PlanningController::class, 'index'])->name('admin.planning');
Route::post('/planning', [PlanningController::class, 'store'])->name('admin.planning.store');
Route::patch('/planning/{shift}', [PlanningController::class, 'update'])->name('admin.planning.update');
Route::delete('/planning/{shift}', [PlanningController::class, 'destroy'])->name('admin.planning.destroy');
Route::post('/planning/publish', [PlanningController::class, 'publish'])->name('admin.planning.publish');
```

**Stap 3 — Maak `resources/js/Components/WeekNavigator.vue`**
- Props: `weekStart: string`
- Vorige/volgende week knoppen
- "Deze week" knop
- Toon weeknummer en datumbereik

**Stap 4 — Maak `resources/js/Components/ShiftCard.vue`**
- Props: `shift: Shift`, `editable: boolean`
- Toon: tijd, werknemer naam (of "Open"), positie
- Klik → emit edit event

**Stap 5 — Maak `resources/js/Components/ShiftModal.vue`**
- Props: `show: boolean`, `shift: Shift | null`, `users: User[]`, `date: string`
- Formulier: start_time, end_time, user (select met "Open dienst" optie), positie, notities
- POST (create) of PATCH (update) naar planning routes

**Stap 6 — Maak `resources/js/Pages/Admin/Planning.vue`**
- Weekoverzicht: 7 kolommen (ma-zo), rijen per werknemer
- WeekNavigator bovenaan
- Per cel: shift cards voor die dag+werknemer
- Klik op lege cel → ShiftModal voor nieuwe dienst
- Klik op shift card → ShiftModal voor bewerken
- "Publiceer week" knop (alleen als er unpublished shifts zijn)
- Published shifts visueel anders (bijv. check icon)
- Responsive: op mobiel worden dagen gestacked

**Stap 7 — Voeg "Planning" toe aan admin nav** in `resources/js/Layouts/AuthenticatedLayout.vue`:
- Na admin nav item, als admin: planning link
- Of als sub-item in admin dropdown

---

### 6.3 Werknemer Planning-view

**Stap 1 — Maak `app/Http/Controllers/EmployeePlanningController.php`**
- `index(Request $request)`:
  ```php
  $weekStart = $request->get('week')
      ? Carbon::parse($request->get('week'))->startOfWeek()
      : Carbon::now()->startOfWeek();
  $weekEnd = $weekStart->copy()->endOfWeek();

  $shifts = Shift::where('user_id', auth()->id())
      ->where('published', true)
      ->whereBetween('date', [$weekStart, $weekEnd])
      ->orderBy('date')
      ->orderBy('start_time')
      ->get();

  return Inertia::render('Planning/Index', [
      'shifts' => $shifts,
      'weekStart' => $weekStart->format('Y-m-d'),
  ]);
  ```

**Stap 2 — Route in `routes/web.php`** (auth groep):
```php
Route::get('/planning', [EmployeePlanningController::class, 'index'])->name('planning.index');
```

**Stap 3 — Wijzig `app/Http/Controllers/DashboardController.php`**
- Voeg nextShift query toe:
  ```php
  $nextShift = Shift::where('user_id', auth()->id())
      ->where('published', true)
      ->where('date', '>=', today())
      ->orderBy('date')
      ->orderBy('start_time')
      ->first();
  ```
- Pass `nextShift` mee aan Inertia render

**Stap 4 — Maak `resources/js/Pages/Planning/Index.vue`**
- WeekNavigator bovenaan
- Week view: dagen als cards, eigen shifts highlighted
- Per shift: datum, tijd, positie
- Link "Uren invullen" die naar TimeEntry form gaat met prefill data

**Stap 5 — Wijzig `resources/js/Pages/Dashboard.vue`**
- Voeg "Eerstvolgende dienst" card toe boven entries
- Toon datum, dag, tijd, positie
- "Uren invullen" link

**Stap 6 — Wijzig `resources/js/Components/TimeEntryForm.vue`**
- Accepteer optionele `prefill` prop:
  ```typescript
  const props = defineProps<{
      prefill?: { date: string, shift_start: string, shift_end: string }
  }>();
  ```
- Als prefill aanwezig, vul formulier voor

**Stap 7 — Voeg "Planning" toe aan werknemer nav** in `resources/js/Layouts/AuthenticatedLayout.vue`:
```vue
<li>
    <Link :href="route('planning.index')" :class="{ 'active': route().current('planning.*') }">
        {{ $t('nav.planning') }}
    </Link>
</li>
```

**Stap 8 — Voeg i18n keys toe**:
- `nav.planning`
- `planning.title`, `planning.weekView`, `planning.noShifts`, `planning.nextShift`, `planning.position`, `planning.openShift`, `planning.publish`, `planning.published`, `planning.addShift`, `planning.editShift`, `planning.fillHours`

**Stap 9 — Tests: `tests/Feature/PlanningTest.php`**
- Test admin kan shift aanmaken
- Test admin kan shift bewerken
- Test admin kan shift verwijderen
- Test admin kan week publiceren
- Test werknemer ziet alleen eigen published shifts
- Test werknemer ziet geen unpublished shifts
- Test next shift query op dashboard
- Test non-admin kan geen shifts aanmaken (403)

---

## Fase 7: Afronding & Polish

### 7.1 Deactiveer / Archiveer Gebruikers

**Stap 1 — Migratie: `database/migrations/xxxx_add_is_active_to_users_table.php`**
```php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('is_active')->default(true)->after('extra_leave_days');
    $table->timestamp('deactivated_at')->nullable()->after('is_active');
});
```

**Stap 2 — Wijzig `app/Models/User.php`**
- Voeg toe aan `$fillable`: `is_active`, `deactivated_at`
- Voeg cast toe: `'is_active' => 'boolean'`, `'deactivated_at' => 'datetime'`
- Voeg scope toe:
  ```php
  public function scopeActive($query) { return $query->where('is_active', true); }
  ```

**Stap 3 — Maak `app/Http/Middleware/EnsureUserIsActive.php`**
```php
public function handle(Request $request, Closure $next): Response
{
    if ($request->user() && !$request->user()->is_active) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('error', __('Your account has been deactivated.'));
    }
    return $next($request);
}
```

**Stap 4 — Wijzig `bootstrap/app.php`**
- Voeg middleware toe aan web stack:
  ```php
  $middleware->web(append: [
      \App\Http\Middleware\HandleInertiaRequests::class,
      \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
      \App\Http\Middleware\EnsureUserIsActive::class,
  ]);
  ```

**Stap 5 — Voeg `toggleActive()` toe aan `AdminUserController.php`**:
```php
public function toggleActive(User $user)
{
    $user->update([
        'is_active' => !$user->is_active,
        'deactivated_at' => $user->is_active ? now() : null,
    ]);
    $status = $user->is_active ? 'activated' : 'deactivated';
    return redirect()->back()->with('success', __("User {$status} successfully."));
}
```

**Stap 6 — Route** (admin groep):
```php
Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('admin.user-toggle-active');
```

**Stap 7 — Wijzig `resources/js/Pages/Admin/Overview.vue`**
- Voeg filter tabs toe: "Actief" / "Inactief" / "Alle"
- Toon actief/inactief status badge per werknemer

**Stap 8 — Wijzig `resources/js/Pages/Admin/UserDetail.vue`**
- Voeg "Deactiveer" / "Activeer" knop toe

**Stap 9 — Tests: `tests/Feature/UserActivationTest.php`**
- Test admin kan user deactiveren
- Test gedeactiveerde user kan niet inloggen
- Test admin kan user reactiveren
- Test gereactiveerde user kan weer inloggen

---

### 7.2 Admin Dashboard Verbeteren

**Stap 1 — Wijzig `app/Http/Controllers/AdminController.php`** of maak apart `AdminDashboardController.php`
- Pas `overview()` aan om extra data mee te sturen:
  ```php
  'pendingApprovals' => TimeEntry::where('status', 'submitted')->count(),
  'pendingLeaveRequests' => LeaveRequest::where('status', 'pending')->count(),
  'expiringContracts' => User::active()
      ->whereNotNull('contract_end_date')
      ->where('contract_end_date', '<=', now()->addDays(config('contracts.expiry_notification_days')))
      ->where('contract_end_date', '>=', today())
      ->count(),
  'estimatedMonthlyCost' => $users->sum(fn ($u) => $u['total_hours'] * ($u['hourly_rate'] ?? 0)),
  ```

**Stap 2 — Wijzig `resources/js/Pages/Admin/Overview.vue`**
- Voeg alert cards toe bovenaan voor:
  - Openstaande accordeerverzoeken (link naar overzicht)
  - Openstaande verlofaanvragen (link naar leave-requests)
  - Aflopende contracten (link naar betreffende users)
- Voeg geschatte maandkosten toe aan stats

---

### 7.3 E-mail Notificaties

**Stap 1 — Maak `app/Mail/PlanningPublished.php`**
```php
class PlanningPublished extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Carbon $weekStart) {}

    public function envelope(): Envelope
    {
        $weekNum = $this->weekStart->weekOfYear;
        return new Envelope(subject: "Nieuwe planning week {$weekNum}");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.planning-published',
            with: [
                'userName' => $this->user->name,
                'weekStart' => $this->weekStart->format('d-m-Y'),
                'weekEnd' => $this->weekStart->copy()->endOfWeek()->format('d-m-Y'),
                'url' => route('planning.index', ['week' => $this->weekStart->format('Y-m-d')]),
            ],
        );
    }
}
```

**Stap 2 — Maak `resources/views/emails/planning-published.blade.php`**
```blade
<x-mail::message>
# Nieuwe planning beschikbaar

Hoi {{ $userName }},

De planning voor week {{ $weekStart }} t/m {{ $weekEnd }} is gepubliceerd.

<x-mail::button :url="$url">
Bekijk planning
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
```

---

### 7.4 Contract-verloop Controle

**Stap 1 — Maak `app/Console/Commands/CheckContractExpiry.php`**
```php
class CheckContractExpiry extends Command
{
    protected $signature = 'contracts:check-expiry';
    protected $description = 'Check for expiring contracts and notify admin';

    public function handle(): int
    {
        $days = config('contracts.expiry_notification_days', 45);

        $expiringUsers = User::active()
            ->whereNotNull('contract_end_date')
            ->where('contract_end_date', '<=', now()->addDays($days))
            ->where('contract_end_date', '>=', today())
            ->whereNull('contract_expiry_notified_at')
            ->get();

        if ($expiringUsers->isEmpty()) {
            $this->info('No expiring contracts found.');
            return Command::SUCCESS;
        }

        $admins = User::role('admin')->get();

        foreach ($expiringUsers as $user) {
            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue(new ContractExpiryNotification($user));
            }
            $user->update(['contract_expiry_notified_at' => now()]);
        }

        $this->info("Notified about {$expiringUsers->count()} expiring contract(s).");
        return Command::SUCCESS;
    }
}
```

**Stap 2 — Maak `app/Mail/ContractExpiryNotification.php`**
```php
class ContractExpiryNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $employee) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Contract {$this->employee->name} loopt binnenkort af");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contract-expiry',
            with: [
                'employeeName' => $this->employee->name,
                'endDate' => $this->employee->contract_end_date->format('d-m-Y'),
                'daysRemaining' => now()->diffInDays($this->employee->contract_end_date),
                'url' => route('admin.user-detail', $this->employee),
            ],
        );
    }
}
```

**Stap 3 — Maak `resources/views/emails/contract-expiry.blade.php`**

**Stap 4 — Wijzig `routes/console.php`**
```php
use Illuminate\Support\Facades\Schedule;
Schedule::command('contracts:check-expiry')->dailyAt('08:00');
```

**Stap 5 — Tests: `tests/Feature/ContractExpiryCommandTest.php`**
- Test command vindt users met aflopend contract
- Test notificatie wordt verstuurd naar admin
- Test `contract_expiry_notified_at` wordt gezet
- Test zelfde user wordt niet opnieuw genotificeerd
- Test users zonder contract_end_date worden genegeerd
- Test inactive users worden genegeerd

---

## Verificatie

Na elke fase:

1. **Draai migraties**: `ddev artisan migrate`
2. **Draai tests**: `ddev artisan test`
3. **Seed database**: `ddev artisan migrate:fresh --seed`
4. **Start dev server**: `ddev npm run dev`
5. **Test handmatig**:
   - Login als admin (admin@example.com / password)
   - Login als werknemer (maak via uitnodiging)
   - Controleer alle nieuwe features in de browser
   - Test op mobiel (responsive + PWA)

---

## Samenvatting bestanden per fase

| Fase | Nieuwe bestanden | Gewijzigde bestanden |
|------|-----------------|---------------------|
| 1.1 | 2 (seeder, test) | 12 (model, bootstrap, routes, controllers, middleware, seeders, factory, tests) + 1 delete |
| 1.2 | 2 (migration, test) | 3 (models, test) |
| 1.3 | 4 (migration, request, controller, page) | 4 (model, controller, page, i18n) |
| 1.4 | 1 (component) | 2 (component, i18n) |
| 2.1 | 1 (migration) | 5 (model, controller, components, page, i18n) |
| 2.2 | 2 (controller, component) | 3 (controller, pages) |
| 2.3 | 0 | 3 (controller, view, page) |
| 3 | 2 (service, component) | 4 (controller, components, page, i18n) + 1 test |
| 4.1 | 4 (migration, model, controller, component) | 3 (model, pages, i18n) + 1 test |
| 4.2 | 3 (config, service, controller) | 1 (page) + 1 test |
| 5.1 | 6 (2 migrations, model, request, 2 controllers, 2 pages) | 3 (model, layout, routes, i18n) + 1 test |
| 5.2 | 3 (migration, model, controller) | 2 (model, page) + 1 test |
| 6.1 | 2 (migration, model) | 1 (model) |
| 6.2 | 4 (controller, 3 components, page) | 2 (layout, routes) |
| 6.3 | 2 (controller, page) | 4 (controller, components, layout, i18n) + 1 test |
| 7.1 | 2 (migration, middleware) | 4 (model, bootstrap, pages) + 1 test |
| 7.2 | 0 | 2 (controller, page) |
| 7.3 | 2 (mail, template) | 1 (controller) |
| 7.4 | 3 (command, mail, template) | 1 (console routes) + 1 test |
