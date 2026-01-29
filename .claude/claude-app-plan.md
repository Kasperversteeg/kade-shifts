# Hour Registration App - Project Plan

## Tech Stack

- **Backend**: Laravel 11
- **Frontend**: Vue 3 (Composition API) + Inertia.js
- **Styling**: Tailwind CSS + DaisyUI
- **Database**: MySQL (via DDEV)
- **Local dev environment**: DDEV
- **Auth**: Laravel Breeze with Inertia
- **Email**: Laravel Mail with Queues
- **PWA**: Vite PWA Plugin

---

## 📋 Database Schema

### Users Table

```sql
- id (primary key)
- name (string)
- email (string, unique)
- password (string)
- role (enum: 'user', 'admin') - default: 'user'
- email_verified_at (timestamp, nullable)
- remember_token (string, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

### Time Entries Table

```sql
- id (primary key)
- user_id (foreign key -> users.id)
- date (date)
- shift_start (time)
- shift_end (time)
- break_minutes (integer) - default: 0
- total_hours (decimal 5,2) - calculated field
- notes (text, nullable)
- created_at (timestamp)
- updated_at (timestamp)

Indexes:
- user_id
- date
- composite: (user_id, date)
```

### Invitations Table (for user invites)

```sql
- id (primary key)
- email (string)
- token (string, unique)
- invited_by (foreign key -> users.id)
- expires_at (timestamp)
- accepted_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

---

## 🏗️ Project Structure

```
hour-registration/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── DashboardController.php
│   │   │   ├── TimeEntryController.php
│   │   │   ├── AdminController.php
│   │   │   └── InvitationController.php
│   │   ├── Middleware/
│   │   │   └── AdminMiddleware.php
│   │   └── Requests/
│   │       ├── StoreTimeEntryRequest.php
│   │       └── SendInvitationRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── TimeEntry.php
│   │   └── Invitation.php
│   ├── Mail/
│   │   ├── UserInvitation.php
│   │   └── MonthlyHoursReport.php
│   └── Services/
│       └── TimeCalculationService.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   │   ├── Auth/
│   │   │   │   ├── Login.vue
│   │   │   │   └── Register.vue
│   │   │   ├── Dashboard.vue
│   │   │   ├── TimeEntries/
│   │   │   │   ├── Index.vue
│   │   │   │   └── Create.vue
│   │   │   └── Admin/
│   │   │       ├── Overview.vue
│   │   │       └── Invitations.vue
│   │   ├── Components/
│   │   │   ├── TimeEntryForm.vue
│   │   │   ├── TimeEntryCard.vue
│   │   │   ├── MonthNavigator.vue
│   │   │   └── HoursSummary.vue
│   │   ├── Layouts/
│   │   │   ├── AppLayout.vue
│   │   │   └── GuestLayout.vue
│   │   └── app.js
│   └── css/
│       └── app.css
├── routes/
│   └── web.php
└── tests/
    ├── Feature/
    └── Unit/
```

---

## 🚀 Implementation Plan

### Phase 2 — Core Enhancements (Step-by-Step)

---

#### 2.1 Edit/Delete Time Entries

The backend (`TimeEntryController@update` and `@destroy`) already exists. This is purely frontend work.

**Step 1 — Create `EditTimeEntryModal.vue` component**

- File: `resources/js/Components/EditTimeEntryModal.vue`
- DaisyUI modal (`<dialog>`) that receives a `timeEntry` prop
- Reuse the same fields as `TimeEntryForm.vue`: date, shift_start, shift_end, break_minutes, notes
- Use `useForm()` from `@inertiajs/vue3` pre-filled with the entry's current values
- Submit via `form.patch(route('time-entries.update', entry.id))`
- Emit `close` event on success or cancel

**Step 2 — Create `ConfirmDeleteModal.vue` component**

- File: `resources/js/Components/ConfirmDeleteModal.vue`
- Generic confirmation modal: receives `title`, `message` props, emits `confirm` / `cancel`
- DaisyUI modal with danger-styled confirm button

**Step 3 — Add edit/delete buttons to `TimeEntryCard.vue`**

- Add an "Edit" icon button and a "Delete" icon button to each card
- Edit button opens `EditTimeEntryModal` with the entry data
- Delete button opens `ConfirmDeleteModal`
- On delete confirm: `router.delete(route('time-entries.destroy', entry.id))`

**Step 4 — Wire modals into `TimeEntries/Index.vue` and `Dashboard.vue`**

- Import and mount both modals in each page
- Track `editingEntry` and `deletingEntry` refs to control which modals are open
- Pass the reactive entry to the modal components

**Step 5 — Test**

- `ddev artisan test --filter=TimeEntry`
- Manual test: edit an entry, verify values update; delete an entry, verify it disappears

---

#### 2.2 One Entry Per Day Constraint

**Step 1 — Migration**

- `ddev artisan make:migration add_unique_user_date_to_time_entries_table`
- In `up()`: `$table->unique(['user_id', 'date'])`
- In `down()`: `$table->dropUnique(['user_id', 'date'])`

**Step 2 — Update `StoreTimeEntryRequest.php`**

- Add a `Rule::unique('time_entries')` rule on the `date` field scoped to the authenticated user:
    ```php
    'date' => [
        'required', 'date',
        Rule::unique('time_entries')->where('user_id', $this->user()->id)
            ->ignore($this->route('time_entry')), // allows update
    ],
    ```
- Add a custom error message: `'date.unique' => 'You already have an entry for this date.'`

**Step 3 — Create `UpdateTimeEntryRequest.php`**

- `ddev artisan make:request UpdateTimeEntryRequest`
- Same rules as `StoreTimeEntryRequest` but the unique rule uses `->ignore($this->route('time_entry'))` to exclude the entry being updated

**Step 4 — Update `TimeEntryController@update`**

- Change the type-hint from `Request` to `UpdateTimeEntryRequest`

**Step 5 — Frontend error display**

- `TimeEntryForm.vue` and `EditTimeEntryModal.vue` should already show per-field errors via `form.errors.date` — verify this works with the new unique error

**Step 6 — Run migration & test**

- `ddev artisan migrate`
- `ddev artisan test --filter=TimeEntry`
- Manual: try adding two entries for the same date, confirm error appears

---

#### 2.3 Admin User Detail Page

**Step 1 — Add controller method**

- In `AdminController.php`, add `userDetail(User $user)` method
- Query the user's time entries for the requested month (use `?month=YYYY-MM` parameter, same pattern as `overview()`)
- Return `Inertia::render('Admin/UserDetail', ['user' => $user, 'entries' => ..., 'monthTotal' => ..., 'currentMonth' => ...])`

**Step 2 — Add route**

- In `routes/web.php`, inside the admin middleware group:
    ```php
    Route::get('/admin/users/{user}', [AdminController::class, 'userDetail'])->name('admin.user-detail');
    ```

**Step 3 — Create `Admin/UserDetail.vue` page**

- File: `resources/js/Pages/Admin/UserDetail.vue`
- Props: `user`, `entries`, `monthTotal`, `currentMonth`
- Show user name/email at the top
- Reuse `MonthNavigator` (point links to same route with `?month=` param)
- Reuse `HoursSummary` for the monthly total
- List entries using `TimeEntryCard` (read-only, no edit/delete for admin)

**Step 4 — Link from `Admin/Overview.vue`**

- Make each user row in the overview table clickable
- Wrap user name in `<Link :href="route('admin.user-detail', user.id)">`

**Step 5 — Test**

- Manual: navigate to admin overview, click a user, verify detail page loads with correct entries

---

#### 2.4 Dark Mode

DaisyUI themes are already configured in `resources/css/app.css` (light default + dark).

**Step 1 — Create `ThemeToggle.vue` component**

- File: `resources/js/Components/ThemeToggle.vue`
- DaisyUI swap or toggle control (sun/moon icons)
- On mount: read `localStorage.getItem('theme')`, default to `'light'`
- On toggle: set `document.documentElement.setAttribute('data-theme', theme)` and persist to `localStorage`

**Step 2 — Mount in `AuthenticatedLayout.vue`**

- Import `ThemeToggle` and place it in the navbar (next to the user avatar dropdown)

**Step 3 — Initialize theme on app load**

- In `resources/js/app.js`, before `createApp()`:
    ```js
    const theme = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", theme);
    ```
    This prevents a flash of wrong theme on page load.

**Step 4 — Also apply to `GuestLayout.vue`**

- Add the same theme initialization for login/invitation pages

**Step 5 — Test**

- Toggle theme, refresh page, confirm it persists
- Check all pages render correctly in dark mode (forms, modals, cards)

---

#### 2.5 User Preferences & Multilingual (i18n)

**Step 1 — Migration: add `preferences` column**

- `ddev artisan make:migration add_preferences_to_users_table`
- `$table->json('preferences')->nullable()->after('role')`
- In User model: add `'preferences'` to `$casts` as `array` and to `$fillable`

**Step 2 — Install vue-i18n**

- `ddev npm install vue-i18n`

**Step 3 — Create translation files**

- `resources/js/lang/en.json` — all English UI strings (buttons, labels, headings, messages)
- `resources/js/lang/nl.json` — Dutch translations
- Organize by section: `{ "dashboard": { "title": "Dashboard", ... }, "timeEntries": { ... }, "admin": { ... }, "nav": { ... } }`

**Step 4 — Configure vue-i18n in `app.js`**

- Import `createI18n` from `vue-i18n`
- Import both locale files
- Create i18n instance with `locale` from page props (falls back to `'en'`)
- Register as Vue plugin: `app.use(i18n)`

**Step 5 — Pass locale via Inertia shared data**

- In `HandleInertiaRequests.php`, add to `share()`:
    ```php
    'locale' => auth()->user()?->preferences['language'] ?? 'en',
    ```

**Step 6 — Create `Preferences.vue` page**

- File: `resources/js/Pages/Preferences.vue`
- Language selector (`<select>` with `en` / `nl` options)
- Theme selector (or note that theme is toggled from navbar)
- Save button: `form.patch(route('preferences.update'))`

**Step 7 — Create `PreferencesController.php`**

- `ddev artisan make:controller PreferencesController`
- `edit()` — return `Inertia::render('Preferences', ['preferences' => auth()->user()->preferences ?? []])`
- `update(Request $request)` — validate and merge into user's `preferences` JSON, redirect back with success

**Step 8 — Add routes**

- In `routes/web.php` (auth group):
    ```php
    Route::get('/preferences', [PreferencesController::class, 'edit'])->name('preferences.edit');
    Route::patch('/preferences', [PreferencesController::class, 'update'])->name('preferences.update');
    ```

**Step 9 — Add nav link**

- In `AuthenticatedLayout.vue`, add "Preferences" link in the user dropdown menu (next to Profile)

**Step 10 — Replace hardcoded strings**

- Go through all Vue pages and components, replace hardcoded text with `{{ $t('section.key') }}`
- Do this page by page: Dashboard, TimeEntries/Index, Admin/Overview, Admin/Invitations, Admin/UserDetail, Preferences, nav/layout

**Step 11 — Run migration & test**

- `ddev artisan migrate`
- Switch language in preferences, confirm all strings change
- Verify locale persists across page navigations

---

#### 2.6 OAuth Login (Google)

**Step 1 — Install Socialite**

- `ddev composer require laravel/socialite`

**Step 2 — Add `google_id` column to users**

- `ddev artisan make:migration add_google_id_to_users_table`
- `$table->string('google_id')->nullable()->unique()->after('email')`
- Make `password` column nullable in users table (Google-only users won't have one)
- Update User model: add `google_id` to `$fillable`

**Step 3 — Configure Google OAuth**

- Add to `config/services.php`:
    ```php
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],
    ```
- Add placeholders to `.env.example`

**Step 4 — Create `SocialiteController.php`**

- `ddev artisan make:controller Auth/SocialiteController`
- `redirectToGoogle()` — return `Socialite::driver('google')->redirect()`
- `handleGoogleCallback()`:
    - Get user info from Google
    - Find existing user by `google_id` or `email`
    - If found: update `google_id` if missing, login
    - If not found: reject (invitation-only system — user must be invited first)
    - Redirect to dashboard

**Step 5 — Add routes**

- In `routes/web.php` (guest middleware):
    ```php
    Route::get('/auth/google', [SocialiteController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback']);
    ```

**Step 6 — Update `Login.vue`**

- Add a "Login with Google" button (DaisyUI btn with Google icon) below the login form
- Links to `route('auth.google')`
- Add a divider between the form and the OAuth button

**Step 7 — Test**

- Set up Google OAuth credentials in `.env`
- Test login with Google for an existing invited user
- Test that non-invited Google users are rejected

---

#### 2.7 Export to CSV/PDF

**Step 1 — Install dompdf**

- `ddev composer require barryvdh/laravel-dompdf`

**Step 2 — Add user CSV export**

- In `TimeEntryController`, add `exportCsv(Request $request)` method
- Query authenticated user's entries for the given `?month=` parameter
- Generate CSV with columns: Date, Start, End, Break (min), Total Hours, Notes
- Return as download response with filename `hours-YYYY-MM.csv`

**Step 3 — Add admin CSV export**

- In `AdminController`, add `exportCsv(Request $request)` method
- Same month query as `overview()` but outputs all users' entries
- CSV columns: User, Date, Start, End, Break, Total Hours
- Filename: `team-hours-YYYY-MM.csv`

**Step 4 — Add admin PDF export**

- In `AdminController`, add `exportPdf(Request $request)` method
- Create Blade view: `resources/views/reports/monthly.blade.php`
    - Company header, month title
    - Table per user: date, start, end, break, hours
    - User totals, grand total at bottom
- Generate PDF via `Pdf::loadView('reports.monthly', $data)->download('report-YYYY-MM.pdf')`

**Step 5 — Add routes**

- In `routes/web.php`:
    ```php
    // Auth group
    Route::get('/time-entries/export', [TimeEntryController::class, 'exportCsv'])->name('time-entries.export');
    // Admin group
    Route::get('/admin/export/csv', [AdminController::class, 'exportCsv'])->name('admin.export-csv');
    Route::get('/admin/export/pdf', [AdminController::class, 'exportPdf'])->name('admin.export-pdf');
    ```

**Step 6 — Add export buttons to frontend**

- `TimeEntries/Index.vue`: add "Export CSV" button next to the month navigator, links to `route('time-entries.export', { month: currentMonth })`
- `Admin/Overview.vue`: add "Export CSV" and "Export PDF" buttons, linking to respective routes with `month` param
- Use `<a>` tags (not Inertia links) so the browser downloads the file

**Step 7 — Test**

- Download CSV as user, open in spreadsheet, verify data
- Download CSV and PDF as admin, verify all users included

---

**Step 5 — Test**

- Set timezone to something different from server timezone
- Add an entry, verify stored in UTC in database
- Verify displayed in user's timezone in the UI

---

#### Recommended Implementation Order

1. **2.2 One Entry Per Day** — small, self-contained, no dependencies
2. **2.1 Edit/Delete Time Entries** — core UX improvement, no new packages
3. **2.3 Admin User Detail** — small addition, builds on existing admin page
4. **2.4 Dark Mode** — quick win, already mostly configured
5. **2.5 Preferences & i18n** — larger feature, needed before 2.8
6. **2.7 Export CSV/PDF** — standalone, can be done in parallel with i18n
7. **2.6 OAuth (Google)** — requires external setup (Google Cloud Console)
8. **2.8 Multiple Time Zones** — depends on 2.5 (preferences), most complex

---

### Phase 3 — Advanced Features

#### 3.1 Shift Approval Workflow

- Add `status` enum column to `time_entries`: `pending`, `approved`, `rejected` (default: `pending`)
- Migration + model update
- Admin UI: approve/reject buttons per entry in `Admin/UserDetail.vue`
- User UI: show status badge on each entry in `TimeEntries/Index.vue`
- Admin UI: bulk approve for a month

#### 3.2 Hourly Rate Calculations

- Add `hourly_rate` decimal column to users table (migration)
- Admin can set rate per user in `Admin/UserDetail.vue`
- Calculate and display earnings per entry and monthly total
- Include earnings in email reports and CSV/PDF exports

#### 3.3 Automatic Monthly Email Reports (Scheduled)

- Create `SendMonthlyReportJob` (queued)
- Register in `app/Console/Kernel.php` or `routes/console.php` — schedule monthly on 1st at 08:00
- Reuse existing `MonthlyHoursReport` mailable
- Send to all admin users automatically

#### 3.4 Mobile Native App

- Evaluate Capacitor.js wrapper around the existing PWA
- Configure native build for iOS and Android

---

## 🎯 MVP Feature Checklist

### User Features

- [ ] User login (email/password)
- [ ] User dashboard with quick add
- [ ] Add time entry (start, end, break)
- [ ] View monthly hours
- [ ] Navigate between months
- [ ] Responsive mobile design

### Admin Features

- [ ] Admin overview of all users
- [ ] Monthly hours per user
- [ ] Send email report (manually)
- [ ] Invite new users via email

### Technical

- [ ] Database setup
- [ ] Authentication
- [ ] Email functionality
- [ ] PWA configuration
- [ ] Deployment ready

---

## 🚀 Future Enhancements (Post-MVP)

### Phase 2 Features

- [ ] Edit/delete time entries
- [ ] user can only have one time entry per day
- [ ] OAuth login (Google)
- [ ] Export to CSV/PDF
- [ ] Dark mode
- [ ] User preferences
- [ ] Multingual, user can set language from preferences
- [ ] User overview for admin

### Phase 3 Features

- [ ] Shift approval workflow (by admins)
- [ ] Approval view for admins only, admins should be able to approve or decline here
- [ ] Approval
- [ ] Hourly rate calculations
- [ ] Automatic monthly email reports (scheduled)

### Phase 4 Features

- [ ] User management, add e-mail address, iban, hourly rate, contract start and end date
- [ ] admin planning tool, can add shifts and assign them to a user.

---

## 💡 Tips for Quick MVP Development

1. **Use Breeze starter kit** - Gets auth working immediately
2. **DaisyUI components** - Minimal CSS needed
3. **Inertia.js** - No API building required
4. **Laravel conventions** - Follow the framework's way
5. **Start simple** - Add features iteratively

## 🐛 Common Issues & Solutions

### Issue: Inertia not rendering

**Solution**: Check `HandleInertiaRequests` middleware is registered

### Issue: Tailwind not working

**Solution**: Run `ddev npm run build` and clear browser cache

### Issue: Emails not sending

**Solution**: Check `.env` mail config and run `ddev artisan queue:work`

### Issue: 403 errors on admin pages

**Solution**: Verify AdminMiddleware is registered and user has admin role

---

## 📚 Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com)
- [Vue 3 Documentation](https://vuejs.org)
- [Tailwind CSS Documentation](https://tailwindcss.com)
- [DaisyUI Components](https://daisyui.com)

---

## 🎉 You're Ready to Build!

Start with Phase 1 and work through each phase sequentially. The MVP should take about 2-3 full days of focused development.

Good luck with your project! 🚀
