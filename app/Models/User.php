<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'preferences',
        'google_id',
        'hourly_rate',
        'contract_type',
        'contract_start_date',
        'contract_end_date',
        'birth_date',
        'start_date',
        'bsn',
        'phone',
        'address',
        'city',
        'postal_code',
        'contract_expiry_notified_at',
        'statutory_leave_days',
        'extra_leave_days',
        'is_active',
        'deactivated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'bsn',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'hourly_rate' => 'decimal:2',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'birth_date' => 'date',
            'start_date' => 'date',
            'bsn' => 'encrypted',
            'contract_expiry_notified_at' => 'datetime',
            'statutory_leave_days' => 'integer',
            'extra_leave_days' => 'integer',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getProfileCompletenessAttribute(): array
    {
        $required = ['hourly_rate', 'contract_type', 'contract_start_date', 'birth_date', 'start_date', 'phone'];
        $filled = collect($required)->filter(fn ($field) => !is_null($this->$field));

        return [
            'percentage' => count($required) > 0 ? round(($filled->count() / count($required)) * 100) : 0,
            'missing' => collect($required)->diff($filled)->values()->all(),
        ];
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function sickLeaves(): HasMany
    {
        return $this->hasMany(SickLeave::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function getLeaveBalanceAttribute(): array
    {
        $total = $this->statutory_leave_days + $this->extra_leave_days;

        $used = $this->leaveRequests()
            ->where('type', 'vakantie')
            ->where('status', 'approved')
            ->get()
            ->sum('days');

        return [
            'total' => $total,
            'used' => $used,
            'remaining' => $total - $used,
        ];
    }

    public function isCurrentlySick(): bool
    {
        return $this->sickLeaves()->whereNull('end_date')->exists();
    }

    public function getSickDaysThisYearAttribute(): int
    {
        return $this->sickLeaves()
            ->whereYear('start_date', Carbon::now()->year)
            ->get()
            ->sum('days');
    }
}
