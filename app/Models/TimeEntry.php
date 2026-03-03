<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'date',
        'shift_start',
        'shift_end',
        'break_minutes',
        'total_hours',
        'notes',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'break_minutes' => 'integer',
        'total_hours' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isEditableByEmployee(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public static function calculateTotalHours($shiftStart, $shiftEnd, $breakMinutes): float
    {
        $start = Carbon::parse($shiftStart);
        $end = Carbon::parse($shiftEnd);

        // If end time is before start time, assume it's next day
        if ($end->lt($start)) {
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end, false);
        $workMinutes = $totalMinutes - $breakMinutes;

        return round($workMinutes / 60, 2);
    }
}
