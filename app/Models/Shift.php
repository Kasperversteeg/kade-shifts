<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'user_id',
        'position',
        'notes',
        'published',
        'created_by',
        'shift_preset_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'published' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shiftPreset(): BelongsTo
    {
        return $this->belongsTo(ShiftPreset::class);
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function isAssigned(): bool
    {
        return !is_null($this->user_id);
    }

    public function getPlannedHoursAttribute(): float
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        if ($end->lte($start)) {
            $end->addDay();
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }
}
