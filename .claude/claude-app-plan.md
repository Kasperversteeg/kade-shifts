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

### Phase 1: Project Setup (1-2 hours)

#### Step 1.1: Install Laravel + DDEV
```bash
# Laravel project already created, configure DDEV
ddev config --project-type=laravel --docroot=public
ddev start
```

#### Step 1.2: Install Dependencies
```bash
# Install Laravel Breeze with Vue/Inertia
ddev composer require laravel/breeze --dev
ddev artisan breeze:install vue

# Install Tailwind plugins
ddev ddev npm install -D daisyui@latest
ddev ddev npm install @vite-pwa/assets-generator -D
ddev ddev npm install vite-plugin-pwa -D

# Install date handling
ddev ddev npm install dayjs
```

#### Step 1.3: Configure Database
DDEV auto-configures the database connection. Verify `.env` has:
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db
DB_USERNAME=db
DB_PASSWORD=db
```

#### Step 1.4: Configure Tailwind + DaisyUI
Update `tailwind.config.js`:
```javascript
export default {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.blade.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',
  ],
  theme: {
    extend: {},
  },
  plugins: [require('daisyui')],
  daisyui: {
    themes: ["light", "dark"],
  },
}
```

---

### Phase 2: Database Setup (1 hour)

#### Step 2.1: Create Migrations
```bash
ddev artisan make:migration add_role_to_users_table
ddev artisan make:migration create_time_entries_table
ddev artisan make:migration create_invitations_table
```

#### Migration: add_role_to_users_table
```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->enum('role', ['user', 'admin'])->default('user')->after('email');
    });
}
```

#### Migration: create_time_entries_table
```php
public function up()
{
    Schema::create('time_entries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->date('date');
        $table->time('shift_start');
        $table->time('shift_end');
        $table->integer('break_minutes')->default(0);
        $table->decimal('total_hours', 5, 2);
        $table->text('notes')->nullable();
        $table->timestamps();
        
        $table->index(['user_id', 'date']);
    });
}
```

#### Migration: create_invitations_table
```php
public function up()
{
    Schema::create('invitations', function (Blueprint $table) {
        $table->id();
        $table->string('email');
        $table->string('token')->unique();
        $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
        $table->timestamp('expires_at');
        $table->timestamp('accepted_at')->nullable();
        $table->timestamps();
    });
}
```

#### Step 2.2: Run Migrations
```bash
ddev artisan migrate
```

#### Step 2.3: Create Seeder for Admin User
```bash
ddev artisan make:seeder AdminUserSeeder
```

```php
public function run()
{
    User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);
}
```

```bash
ddev artisan db:seed --class=AdminUserSeeder
```

---

### Phase 3: Models & Business Logic (2 hours)

#### Step 3.1: Update User Model
```php
// app/Models/User.php
protected $fillable = ['name', 'email', 'password', 'role'];

public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function timeEntries()
{
    return $this->hasMany(TimeEntry::class);
}

public function invitations()
{
    return $this->hasMany(Invitation::class, 'invited_by');
}
```

#### Step 3.2: Create TimeEntry Model
```bash
ddev artisan make:model TimeEntry
```

```php
// app/Models/TimeEntry.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeEntry extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'shift_start',
        'shift_end',
        'break_minutes',
        'total_hours',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'shift_start' => 'datetime:H:i',
        'shift_end' => 'datetime:H:i',
        'break_minutes' => 'integer',
        'total_hours' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function calculateTotalHours($shiftStart, $shiftEnd, $breakMinutes)
    {
        $start = Carbon::parse($shiftStart);
        $end = Carbon::parse($shiftEnd);
        
        // If end time is before start time, assume it's next day
        if ($end->lt($start)) {
            $end->addDay();
        }
        
        $totalMinutes = $end->diffInMinutes($start);
        $workMinutes = $totalMinutes - $breakMinutes;
        
        return round($workMinutes / 60, 2);
    }
}
```

#### Step 3.3: Create Invitation Model
```bash
ddev artisan make:model Invitation
```

```php
// app/Models/Invitation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $fillable = [
        'email',
        'token',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public static function generateToken(): string
    {
        return Str::random(32);
    }
}
```

---

### Phase 4: Authentication & Middleware (1 hour)

#### Step 4.1: Create Admin Middleware
```bash
ddev artisan make:middleware AdminMiddleware
```

```php
// app/Http/Middleware/AdminMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
```

#### Step 4.2: Register Middleware
```php
// bootstrap/app.php (Laravel 11)
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
    ]);
})
```

---

### Phase 5: Backend Controllers (3-4 hours)

#### Step 5.1: Dashboard Controller
```bash
ddev artisan make:controller DashboardController
```

```php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // Get current month entries
        $monthEntries = TimeEntry::where('user_id', $user->id)
            ->whereYear('date', $today->year)
            ->whereMonth('date', $today->month)
            ->orderBy('date', 'desc')
            ->get();
        
        $monthTotal = $monthEntries->sum('total_hours');
        
        return Inertia::render('Dashboard', [
            'entries' => $monthEntries,
            'monthTotal' => $monthTotal,
            'currentMonth' => $today->format('Y-m'),
        ]);
    }
}
```

#### Step 5.2: Time Entry Controller
```bash
ddev artisan make:controller TimeEntryController
ddev artisan make:request StoreTimeEntryRequest
```

```php
// app/Http/Requests/StoreTimeEntryRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i',
            'break_minutes' => 'required|integer|min:0|max:480',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
```

```php
// app/Http/Controllers/TimeEntryController.php
namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Http\Requests\StoreTimeEntryRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

class TimeEntryController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');
        
        $entries = TimeEntry::where('user_id', auth()->id())
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date', 'desc')
            ->get();
        
        $monthTotal = $entries->sum('total_hours');
        
        return Inertia::render('TimeEntries/Index', [
            'entries' => $entries,
            'monthTotal' => $monthTotal,
            'currentMonth' => $month,
        ]);
    }

    public function store(StoreTimeEntryRequest $request)
    {
        $validated = $request->validated();
        
        $totalHours = TimeEntry::calculateTotalHours(
            $validated['shift_start'],
            $validated['shift_end'],
            $validated['break_minutes']
        );
        
        TimeEntry::create([
            'user_id' => auth()->id(),
            'date' => $validated['date'],
            'shift_start' => $validated['shift_start'],
            'shift_end' => $validated['shift_end'],
            'break_minutes' => $validated['break_minutes'],
            'total_hours' => $totalHours,
            'notes' => $validated['notes'] ?? null,
        ]);
        
        return redirect()->back()->with('success', 'Time entry added successfully!');
    }

    public function update(StoreTimeEntryRequest $request, TimeEntry $timeEntry)
    {
        // Ensure user can only edit their own entries
        if ($timeEntry->user_id !== auth()->id()) {
            abort(403);
        }
        
        $validated = $request->validated();
        
        $totalHours = TimeEntry::calculateTotalHours(
            $validated['shift_start'],
            $validated['shift_end'],
            $validated['break_minutes']
        );
        
        $timeEntry->update([
            'date' => $validated['date'],
            'shift_start' => $validated['shift_start'],
            'shift_end' => $validated['shift_end'],
            'break_minutes' => $validated['break_minutes'],
            'total_hours' => $totalHours,
            'notes' => $validated['notes'] ?? null,
        ]);
        
        return redirect()->back()->with('success', 'Time entry updated successfully!');
    }

    public function destroy(TimeEntry $timeEntry)
    {
        if ($timeEntry->user_id !== auth()->id()) {
            abort(403);
        }
        
        $timeEntry->delete();
        
        return redirect()->back()->with('success', 'Time entry deleted successfully!');
    }
}
```

#### Step 5.3: Admin Controller
```bash
ddev artisan make:controller AdminController
```

```php
// app/Http/Controllers/AdminController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Mail\MonthlyHoursReport;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    public function overview(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');
        
        $users = User::where('role', 'user')
            ->with(['timeEntries' => function ($query) use ($date) {
                $query->whereYear('date', $date->year)
                      ->whereMonth('date', $date->month);
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_hours' => $user->timeEntries->sum('total_hours'),
                    'entries_count' => $user->timeEntries->count(),
                ];
            });
        
        $grandTotal = $users->sum('total_hours');
        
        return Inertia::render('Admin/Overview', [
            'users' => $users,
            'grandTotal' => $grandTotal,
            'currentMonth' => $month,
        ]);
    }
    
    public function sendMonthlyReport(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');
        
        $users = User::where('role', 'user')
            ->with(['timeEntries' => function ($query) use ($date) {
                $query->whereYear('date', $date->year)
                      ->whereMonth('date', $date->month);
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_hours' => $user->timeEntries->sum('total_hours'),
                    'entries_count' => $user->timeEntries->count(),
                ];
            });
        
        Mail::to(auth()->user()->email)->send(new MonthlyHoursReport($users, $month));
        
        return redirect()->back()->with('success', 'Monthly report sent successfully!');
    }
}
```

#### Step 5.4: Invitation Controller
```bash
ddev artisan make:controller InvitationController
```

```php
// app/Http/Controllers/InvitationController.php
namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\Mail\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function index()
    {
        $invitations = Invitation::with('inviter')
            ->latest()
            ->get();
        
        return Inertia::render('Admin/Invitations', [
            'invitations' => $invitations,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email|unique:invitations,email',
        ]);
        
        $invitation = Invitation::create([
            'email' => $request->email,
            'token' => Invitation::generateToken(),
            'invited_by' => auth()->id(),
            'expires_at' => Carbon::now()->addDays(7),
        ]);
        
        Mail::to($invitation->email)->send(new UserInvitation($invitation));
        
        return redirect()->back()->with('success', 'Invitation sent successfully!');
    }
    
    public function accept($token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();
        
        if ($invitation->isExpired()) {
            return Inertia::render('Auth/InvitationExpired');
        }
        
        if ($invitation->isAccepted()) {
            return redirect()->route('login')->with('info', 'This invitation has already been accepted.');
        }
        
        return Inertia::render('Auth/AcceptInvitation', [
            'invitation' => $invitation,
        ]);
    }
    
    public function complete(Request $request, $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();
        
        if ($invitation->isExpired() || $invitation->isAccepted()) {
            abort(403);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        
        $invitation->update(['accepted_at' => now()]);
        
        auth()->login($user);
        
        return redirect()->route('dashboard')->with('success', 'Welcome! Your account has been created.');
    }
}
```

---

### Phase 6: Email Setup (1 hour)

#### Step 6.1: Create Mail Classes
```bash
ddev artisan make:mail UserInvitation
ddev artisan make:mail MonthlyHoursReport
```

```php
// app/Mail/UserInvitation.php
namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invitation $invitation)
    {}

    public function build()
    {
        $url = route('invitation.accept', $this->invitation->token);
        
        return $this->subject('You\'re invited to join Hour Registration')
                    ->markdown('emails.invitation', [
                        'url' => $url,
                        'expiresAt' => $this->invitation->expires_at,
                    ]);
    }
}
```

```php
// app/Mail/MonthlyHoursReport.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MonthlyHoursReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $users, public $month)
    {}

    public function build()
    {
        return $this->subject("Monthly Hours Report - {$this->month}")
                    ->markdown('emails.monthly-report', [
                        'users' => $this->users,
                        'month' => $this->month,
                        'grandTotal' => collect($this->users)->sum('total_hours'),
                    ]);
    }
}
```

#### Step 6.2: Create Email Views
```bash
# These will be created in resources/views/emails/
```

```blade
{{-- resources/views/emails/invitation.blade.php --}}
@component('mail::message')
# You're Invited!

You've been invited to join the Hour Registration app.

@component('mail::button', ['url' => $url])
Accept Invitation
@endcomponent

This invitation will expire on {{ $expiresAt->format('F j, Y') }}.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

```blade
{{-- resources/views/emails/monthly-report.blade.php --}}
@component('mail::message')
# Monthly Hours Report - {{ $month }}

Here's the summary of hours worked this month:

@component('mail::table')
| Name | Email | Hours | Entries |
|:-----|:------|------:|--------:|
@foreach($users as $user)
| {{ $user['name'] }} | {{ $user['email'] }} | {{ number_format($user['total_hours'], 2) }} | {{ $user['entries_count'] }} |
@endforeach
| **Total** | | **{{ number_format($grandTotal, 2) }}** | |
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

---

### Phase 7: Routes Configuration (30 minutes)

```php
// routes/web.php
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InvitationController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Time Entries
    Route::resource('time-entries', TimeEntryController::class)
        ->except(['show']);
    
    // Admin Routes
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        Route::get('/overview', [AdminController::class, 'overview'])->name('admin.overview');
        Route::post('/send-report', [AdminController::class, 'sendMonthlyReport'])->name('admin.send-report');
        Route::resource('invitations', InvitationController::class)->only(['index', 'store']);
    });
});

// Public invitation acceptance routes
Route::get('/invitation/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');
Route::post('/invitation/{token}/complete', [InvitationController::class, 'complete'])->name('invitation.complete');

require __DIR__.'/auth.php';
```

---

### Phase 8: Frontend Components (4-5 hours)

#### Step 8.1: Update App Layout
```vue
<!-- resources/js/Layouts/AppLayout.vue -->
<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth.user)
const isAdmin = computed(() => user.value?.role === 'admin')
</script>

<template>
  <div class="min-h-screen bg-base-200">
    <!-- Navbar -->
    <div class="navbar bg-base-100 shadow-lg">
      <div class="flex-1">
        <Link href="/dashboard" class="btn btn-ghost text-xl">
          ⏱️ Hour Registration
        </Link>
      </div>
      <div class="flex-none gap-2">
        <Link 
          href="/dashboard" 
          class="btn btn-ghost"
          :class="{ 'btn-active': $page.url === '/dashboard' }"
        >
          Dashboard
        </Link>
        <Link 
          href="/time-entries" 
          class="btn btn-ghost"
          :class="{ 'btn-active': $page.url.startsWith('/time-entries') }"
        >
          My Hours
        </Link>
        <Link 
          v-if="isAdmin"
          href="/admin/overview" 
          class="btn btn-ghost"
          :class="{ 'btn-active': $page.url.startsWith('/admin') }"
        >
          Admin
        </Link>
        <div class="dropdown dropdown-end">
          <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
            <div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center">
              {{ user.name.charAt(0).toUpperCase() }}
            </div>
          </div>
          <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
            <li><a>{{ user.name }}</a></li>
            <li><a>{{ user.email }}</a></li>
            <li>
              <Link href="/logout" method="post" as="button">Logout</Link>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-4">
      <!-- Flash Messages -->
      <div v-if="$page.props.flash.success" class="alert alert-success mb-4">
        <span>{{ $page.props.flash.success }}</span>
      </div>
      <div v-if="$page.props.flash.error" class="alert alert-error mb-4">
        <span>{{ $page.props.flash.error }}</span>
      </div>

      <slot />
    </div>
  </div>
</template>
```

#### Step 8.2: Dashboard Page
```vue
<!-- resources/js/Pages/Dashboard.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import TimeEntryForm from '@/Components/TimeEntryForm.vue'
import TimeEntryCard from '@/Components/TimeEntryCard.vue'
import HoursSummary from '@/Components/HoursSummary.vue'
import { ref } from 'vue'

defineProps({
  entries: Array,
  monthTotal: Number,
  currentMonth: String
})

const showForm = ref(false)
</script>

<template>
  <AppLayout>
    <div class="grid gap-4">
      <!-- Summary Card -->
      <HoursSummary :total="monthTotal" :month="currentMonth" />

      <!-- Quick Add Button -->
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title">Add Hours</h2>
          <button 
            v-if="!showForm"
            @click="showForm = true" 
            class="btn btn-primary"
          >
            ➕ Add New Entry
          </button>
          <TimeEntryForm 
            v-else 
            @cancel="showForm = false"
            @success="showForm = false"
          />
        </div>
      </div>

      <!-- Recent Entries -->
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title mb-4">Recent Entries</h2>
          <div v-if="entries.length === 0" class="text-center py-8 text-base-content/60">
            No entries yet. Add your first time entry above!
          </div>
          <div v-else class="space-y-2">
            <TimeEntryCard 
              v-for="entry in entries.slice(0, 5)" 
              :key="entry.id"
              :entry="entry"
            />
          </div>
          <Link href="/time-entries" class="btn btn-outline btn-sm mt-4">
            View All Entries →
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
```

#### Step 8.3: Time Entry Form Component
```vue
<!-- resources/js/Components/TimeEntryForm.vue -->
<script setup>
import { useForm } from '@inertiajs/vue3'
import dayjs from 'dayjs'

const emit = defineEmits(['cancel', 'success'])

const form = useForm({
  date: dayjs().format('YYYY-MM-DD'),
  shift_start: '09:00',
  shift_end: '17:00',
  break_minutes: 30,
  notes: ''
})

const submit = () => {
  form.post('/time-entries', {
    onSuccess: () => {
      emit('success')
      form.reset()
    }
  })
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Date -->
      <div class="form-control">
        <label class="label">
          <span class="label-text">Date</span>
        </label>
        <input 
          type="date" 
          v-model="form.date" 
          class="input input-bordered"
          :class="{ 'input-error': form.errors.date }"
        />
        <label v-if="form.errors.date" class="label">
          <span class="label-text-alt text-error">{{ form.errors.date }}</span>
        </label>
      </div>

      <!-- Shift Start -->
      <div class="form-control">
        <label class="label">
          <span class="label-text">Shift Start</span>
        </label>
        <input 
          type="time" 
          v-model="form.shift_start" 
          class="input input-bordered"
          :class="{ 'input-error': form.errors.shift_start }"
        />
        <label v-if="form.errors.shift_start" class="label">
          <span class="label-text-alt text-error">{{ form.errors.shift_start }}</span>
        </label>
      </div>

      <!-- Shift End -->
      <div class="form-control">
        <label class="label">
          <span class="label-text">Shift End</span>
        </label>
        <input 
          type="time" 
          v-model="form.shift_end" 
          class="input input-bordered"
          :class="{ 'input-error': form.errors.shift_end }"
        />
        <label v-if="form.errors.shift_end" class="label">
          <span class="label-text-alt text-error">{{ form.errors.shift_end }}</span>
        </label>
      </div>

      <!-- Break Minutes -->
      <div class="form-control">
        <label class="label">
          <span class="label-text">Break (minutes)</span>
        </label>
        <input 
          type="number" 
          v-model.number="form.break_minutes" 
          min="0"
          class="input input-bordered"
          :class="{ 'input-error': form.errors.break_minutes }"
        />
        <label v-if="form.errors.break_minutes" class="label">
          <span class="label-text-alt text-error">{{ form.errors.break_minutes }}</span>
        </label>
      </div>
    </div>

    <!-- Notes -->
    <div class="form-control">
      <label class="label">
        <span class="label-text">Notes (optional)</span>
      </label>
      <textarea 
        v-model="form.notes" 
        class="textarea textarea-bordered"
        :class="{ 'textarea-error': form.errors.notes }"
        rows="2"
      ></textarea>
    </div>

    <!-- Actions -->
    <div class="flex gap-2 justify-end">
      <button 
        type="button" 
        @click="emit('cancel')" 
        class="btn btn-ghost"
      >
        Cancel
      </button>
      <button 
        type="submit" 
        class="btn btn-primary"
        :disabled="form.processing"
      >
        {{ form.processing ? 'Saving...' : 'Save Entry' }}
      </button>
    </div>
  </form>
</template>
```

#### Step 8.4: Time Entry Card Component
```vue
<!-- resources/js/Components/TimeEntryCard.vue -->
<script setup>
import { computed } from 'vue'
import dayjs from 'dayjs'

const props = defineProps({
  entry: Object
})

const formattedDate = computed(() => {
  return dayjs(props.entry.date).format('ddd, MMM D, YYYY')
})
</script>

<template>
  <div class="card bg-base-100 border border-base-300">
    <div class="card-body p-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="font-semibold">{{ formattedDate }}</h3>
          <p class="text-sm text-base-content/60">
            {{ entry.shift_start }} - {{ entry.shift_end }}
            <span v-if="entry.break_minutes > 0">
              ({{ entry.break_minutes }}min break)
            </span>
          </p>
          <p v-if="entry.notes" class="text-sm mt-1">{{ entry.notes }}</p>
        </div>
        <div class="badge badge-primary badge-lg">
          {{ entry.total_hours }}h
        </div>
      </div>
    </div>
  </div>
</template>
```

#### Step 8.5: Hours Summary Component
```vue
<!-- resources/js/Components/HoursSummary.vue -->
<script setup>
import dayjs from 'dayjs'
import { computed } from 'vue'

const props = defineProps({
  total: Number,
  month: String
})

const monthName = computed(() => {
  return dayjs(props.month + '-01').format('MMMM YYYY')
})
</script>

<template>
  <div class="stats shadow">
    <div class="stat">
      <div class="stat-figure text-primary">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <div class="stat-title">{{ monthName }}</div>
      <div class="stat-value text-primary">{{ total }}h</div>
      <div class="stat-desc">Total hours worked</div>
    </div>
  </div>
</template>
```

#### Step 8.6: Month Navigator Component
```vue
<!-- resources/js/Components/MonthNavigator.vue -->
<script setup>
import { router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { computed } from 'vue'

const props = defineProps({
  currentMonth: String
})

const currentDate = computed(() => dayjs(props.currentMonth + '-01'))
const displayMonth = computed(() => currentDate.value.format('MMMM YYYY'))

const goToPreviousMonth = () => {
  const prevMonth = currentDate.value.subtract(1, 'month').format('YYYY-MM')
  router.get(window.location.pathname, { month: prevMonth }, { preserveState: true })
}

const goToNextMonth = () => {
  const nextMonth = currentDate.value.add(1, 'month').format('YYYY-MM')
  router.get(window.location.pathname, { month: nextMonth }, { preserveState: true })
}

const goToCurrentMonth = () => {
  const currentMonth = dayjs().format('YYYY-MM')
  router.get(window.location.pathname, { month: currentMonth }, { preserveState: true })
}

const isCurrentMonth = computed(() => {
  return currentDate.value.format('YYYY-MM') === dayjs().format('YYYY-MM')
})
</script>

<template>
  <div class="flex items-center justify-between">
    <button @click="goToPreviousMonth" class="btn btn-circle btn-sm">
      ‹
    </button>
    <div class="flex items-center gap-2">
      <h2 class="text-xl font-bold">{{ displayMonth }}</h2>
      <button 
        v-if="!isCurrentMonth"
        @click="goToCurrentMonth" 
        class="btn btn-xs btn-outline"
      >
        Today
      </button>
    </div>
    <button @click="goToNextMonth" class="btn btn-circle btn-sm">
      ›
    </button>
  </div>
</template>
```

#### Step 8.7: Time Entries Index Page
```vue
<!-- resources/js/Pages/TimeEntries/Index.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import TimeEntryCard from '@/Components/TimeEntryCard.vue'
import TimeEntryForm from '@/Components/TimeEntryForm.vue'
import MonthNavigator from '@/Components/MonthNavigator.vue'
import HoursSummary from '@/Components/HoursSummary.vue'
import { ref } from 'vue'

defineProps({
  entries: Array,
  monthTotal: Number,
  currentMonth: String
})

const showForm = ref(false)
</script>

<template>
  <AppLayout>
    <div class="space-y-4">
      <MonthNavigator :current-month="currentMonth" />
      
      <HoursSummary :total="monthTotal" :month="currentMonth" />

      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <div class="flex justify-between items-center mb-4">
            <h2 class="card-title">All Entries</h2>
            <button 
              @click="showForm = !showForm" 
              class="btn btn-primary btn-sm"
            >
              {{ showForm ? 'Cancel' : '➕ Add Entry' }}
            </button>
          </div>

          <TimeEntryForm 
            v-if="showForm"
            @cancel="showForm = false"
            @success="showForm = false"
            class="mb-4 p-4 bg-base-200 rounded-lg"
          />

          <div v-if="entries.length === 0" class="text-center py-8 text-base-content/60">
            No entries for this month.
          </div>
          <div v-else class="space-y-2">
            <TimeEntryCard 
              v-for="entry in entries" 
              :key="entry.id"
              :entry="entry"
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
```

#### Step 8.8: Admin Overview Page
```vue
<!-- resources/js/Pages/Admin/Overview.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import MonthNavigator from '@/Components/MonthNavigator.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  users: Array,
  grandTotal: Number,
  currentMonth: String
})

const form = useForm({
  month: props.currentMonth
})

const sendReport = () => {
  form.post('/admin/send-report', {
    onSuccess: () => form.reset()
  })
}
</script>

<template>
  <AppLayout>
    <div class="space-y-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">Admin Overview</h1>
        <Link href="/admin/invitations" class="btn btn-outline btn-sm">
          Manage Invitations
        </Link>
      </div>

      <MonthNavigator :current-month="currentMonth" />

      <!-- Summary Stats -->
      <div class="stats shadow w-full">
        <div class="stat">
          <div class="stat-title">Total Users</div>
          <div class="stat-value">{{ users.length }}</div>
        </div>
        <div class="stat">
          <div class="stat-title">Total Hours</div>
          <div class="stat-value text-primary">{{ grandTotal }}h</div>
        </div>
        <div class="stat">
          <div class="stat-title">Average per User</div>
          <div class="stat-value text-secondary">
            {{ users.length > 0 ? (grandTotal / users.length).toFixed(1) : 0 }}h
          </div>
        </div>
      </div>

      <!-- User Hours Table -->
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <div class="flex justify-between items-center mb-4">
            <h2 class="card-title">User Hours</h2>
            <button 
              @click="sendReport" 
              class="btn btn-primary btn-sm"
              :disabled="form.processing"
            >
              {{ form.processing ? 'Sending...' : '📧 Email Report' }}
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="table table-zebra">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th class="text-right">Entries</th>
                  <th class="text-right">Total Hours</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in users" :key="user.id">
                  <td>{{ user.name }}</td>
                  <td>{{ user.email }}</td>
                  <td class="text-right">{{ user.entries_count }}</td>
                  <td class="text-right">
                    <span class="badge badge-primary">{{ user.total_hours }}h</span>
                  </td>
                </tr>
                <tr v-if="users.length === 0">
                  <td colspan="4" class="text-center text-base-content/60">
                    No users with entries this month
                  </td>
                </tr>
              </tbody>
              <tfoot v-if="users.length > 0">
                <tr class="font-bold">
                  <td colspan="3" class="text-right">Total:</td>
                  <td class="text-right">{{ grandTotal }}h</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
```

#### Step 8.9: Admin Invitations Page
```vue
<!-- resources/js/Pages/Admin/Invitations.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import dayjs from 'dayjs'

defineProps({
  invitations: Array
})

const form = useForm({
  email: ''
})

const sendInvitation = () => {
  form.post('/admin/invitations', {
    onSuccess: () => form.reset()
  })
}
</script>

<template>
  <AppLayout>
    <div class="space-y-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">User Invitations</h1>
        <Link href="/admin/overview" class="btn btn-outline btn-sm">
          ← Back to Overview
        </Link>
      </div>

      <!-- Send Invitation Form -->
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title">Send New Invitation</h2>
          <form @submit.prevent="sendInvitation" class="flex gap-2">
            <div class="form-control flex-1">
              <input 
                type="email" 
                v-model="form.email"
                placeholder="user@example.com"
                class="input input-bordered"
                :class="{ 'input-error': form.errors.email }"
                required
              />
              <label v-if="form.errors.email" class="label">
                <span class="label-text-alt text-error">{{ form.errors.email }}</span>
              </label>
            </div>
            <button 
              type="submit" 
              class="btn btn-primary"
              :disabled="form.processing"
            >
              {{ form.processing ? 'Sending...' : 'Send Invitation' }}
            </button>
          </form>
        </div>
      </div>

      <!-- Invitations List -->
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title">Sent Invitations</h2>
          
          <div class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th>Email</th>
                  <th>Invited By</th>
                  <th>Status</th>
                  <th>Expires At</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="invitation in invitations" :key="invitation.id">
                  <td>{{ invitation.email }}</td>
                  <td>{{ invitation.inviter.name }}</td>
                  <td>
                    <span 
                      class="badge"
                      :class="{
                        'badge-success': invitation.accepted_at,
                        'badge-error': !invitation.accepted_at && dayjs(invitation.expires_at).isBefore(dayjs()),
                        'badge-warning': !invitation.accepted_at && dayjs(invitation.expires_at).isAfter(dayjs())
                      }"
                    >
                      {{ invitation.accepted_at ? 'Accepted' : 
                         dayjs(invitation.expires_at).isBefore(dayjs()) ? 'Expired' : 'Pending' }}
                    </span>
                  </td>
                  <td>{{ dayjs(invitation.expires_at).format('MMM D, YYYY') }}</td>
                </tr>
                <tr v-if="invitations.length === 0">
                  <td colspan="4" class="text-center text-base-content/60">
                    No invitations sent yet
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
```

---

### Phase 9: PWA Setup (1 hour)

#### Step 9.1: Configure Vite PWA Plugin
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: ['favicon.ico', 'apple-touch-icon.png'],
            manifest: {
                name: 'Hour Registration',
                short_name: 'HourReg',
                description: 'Track your work hours easily',
                theme_color: '#ffffff',
                background_color: '#ffffff',
                display: 'standalone',
                icons: [
                    {
                        src: '/pwa-192x192.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: '/pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png'
                    }
                ]
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg}']
            }
        })
    ],
});
```

#### Step 9.2: Add PWA Assets
```bash
# Create icon files in public/ directory
# pwa-192x192.png
# pwa-512x512.png
# favicon.ico
# apple-touch-icon.png
```

---

### Phase 10: Testing & Deployment (2-3 hours)

#### Step 10.1: Basic Feature Testing
```bash
ddev artisan test
```

#### Step 10.2: Configure for Production
```bash
# Update .env for production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Configure mail settings (use your email provider)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Step 10.3: Optimize for Production
```bash
ddev composer install --optimize-autoloader --no-dev
ddev artisan config:cache
ddev artisan route:cache
ddev artisan view:cache
ddev npm run build
```

#### Step 10.4: Deploy to Plesk
1. Upload files via Git or FTP
2. Set document root to `/public`
3. Run migrations: `ddev artisan migrate --force`
4. Set up scheduled tasks for email (if needed later)
5. Configure SSL certificate

---

## 📝 Development Workflow

### Starting Development
```bash
# Start DDEV environment (web + db containers)
ddev start

# Start Vite dev server with HMR
ddev npm run dev

# Run queue worker for emails (separate terminal)
ddev artisan queue:work
```

### Daily Development
1. Create feature branch
2. Make changes
3. Test locally
4. Commit and push
5. Deploy to staging/production

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
- [ ] OAuth login (Google)
- [ ] Export to CSV/PDF
- [ ] Automatic monthly email reports (scheduled)
- [ ] Multiple time zones support
- [ ] Dark mode
- [ ] User preferences

### Phase 3 Features
- [ ] Project/task categorization
- [ ] Hourly rate calculations
- [ ] Invoice generation
- [ ] Team/department grouping
- [ ] Advanced reporting & analytics
- [ ] Mobile native app

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