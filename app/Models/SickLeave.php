<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SickLeave extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'notes',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function isActive(): bool
    {
        return is_null($this->end_date);
    }

    public function getDaysAttribute(): int
    {
        $days = 0;
        $endDate = $this->end_date ?? Carbon::today();
        $period = CarbonPeriod::create($this->start_date, $endDate);

        foreach ($period as $date) {
            if ($date->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }
}
