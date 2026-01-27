<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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
        'break_minutes' => 'integer',
        'total_hours' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function calculateTotalHours($shiftStart, $shiftEnd, $breakMinutes): float
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
