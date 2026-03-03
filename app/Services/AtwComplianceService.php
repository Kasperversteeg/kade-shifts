<?php

namespace App\Services;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AtwComplianceService
{
    /**
     * Validate a single entry against ATW rules.
     * Returns an array of warning arrays with 'type' and 'message' keys.
     */
    public function validateEntry(TimeEntry $entry, ?TimeEntry $previousEntry = null): array
    {
        $warnings = [];

        $shiftHours = $this->calculateShiftDuration($entry->shift_start, $entry->shift_end);

        // Break validation: >5.5h requires 30min break
        if ($shiftHours > 5.5 && $entry->break_minutes < 30) {
            $warnings[] = [
                'type' => 'break_short',
                'message' => 'atw.breakShort',
            ];
        }

        // Break validation: >10h requires 45min break
        if ($shiftHours > 10 && $entry->break_minutes < 45) {
            $warnings[] = [
                'type' => 'break_very_short',
                'message' => 'atw.breakVeryShort',
            ];
        }

        // Shift length: max 12 hours
        if ($shiftHours > 12) {
            $warnings[] = [
                'type' => 'shift_too_long',
                'message' => 'atw.shiftTooLong',
            ];
        }

        // Rest time between shifts: min 11 hours
        if ($previousEntry) {
            $restHours = $this->calculateRestHours($previousEntry, $entry);
            if ($restHours !== null && $restHours < 11) {
                $warnings[] = [
                    'type' => 'rest_too_short',
                    'message' => 'atw.restTooShort',
                    'hours' => round($restHours, 1),
                ];
            }
        }

        return $warnings;
    }

    /**
     * Calculate the total shift duration in hours (including break time).
     */
    public function calculateShiftDuration(string $shiftStart, string $shiftEnd): float
    {
        $start = Carbon::parse($shiftStart);
        $end = Carbon::parse($shiftEnd);

        if ($end->lt($start)) {
            $end->addDay();
        }

        return $start->diffInMinutes($end) / 60;
    }

    /**
     * Calculate rest hours between the end of the previous shift and the start of the current shift.
     */
    public function calculateRestHours(TimeEntry $previous, TimeEntry $current): ?float
    {
        $prevEnd = Carbon::parse($previous->date->format('Y-m-d') . ' ' . $previous->shift_end);
        $prevStart = Carbon::parse($previous->date->format('Y-m-d') . ' ' . $previous->shift_start);

        // Handle cross-midnight shift for previous entry
        if ($prevEnd->lt($prevStart)) {
            $prevEnd->addDay();
        }

        $currentStart = Carbon::parse($current->date->format('Y-m-d') . ' ' . $current->shift_start);

        // Only relevant if current shift is after previous shift
        if ($currentStart->lte($prevEnd)) {
            return null;
        }

        return $prevEnd->diffInMinutes($currentStart) / 60;
    }

    /**
     * Get weekly totals for a user in a given month.
     * Returns array of week data with week number, total hours, and warnings.
     */
    public function getWeeklyTotals(int $userId, string $month): array
    {
        $date = Carbon::parse($month . '-01');
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        // Get all entries for weeks that overlap with this month
        $weekStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $entries = TimeEntry::where('user_id', $userId)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->orderBy('date')
            ->get();

        $weeks = [];
        $currentWeek = $weekStart->copy();

        while ($currentWeek->lte($monthEnd)) {
            $currentWeekEnd = $currentWeek->copy()->endOfWeek(Carbon::SUNDAY);
            $weekNumber = $currentWeek->weekOfYear;

            $weekEntries = $entries->filter(function ($entry) use ($currentWeek, $currentWeekEnd) {
                return $entry->date->gte($currentWeek) && $entry->date->lte($currentWeekEnd);
            });

            $totalHours = (float) $weekEntries->sum('total_hours');

            $warnings = [];
            if ($totalHours > 60) {
                $warnings[] = [
                    'type' => 'week_over_60',
                    'message' => 'atw.weekOver60',
                ];
            } elseif ($totalHours > 48) {
                $warnings[] = [
                    'type' => 'week_over_48',
                    'message' => 'atw.weekOver48',
                ];
            }

            $weeks[] = [
                'week' => $weekNumber,
                'weekStart' => $currentWeek->format('Y-m-d'),
                'weekEnd' => $currentWeekEnd->format('Y-m-d'),
                'totalHours' => round($totalHours, 2),
                'warnings' => $warnings,
            ];

            $currentWeek->addWeek();
        }

        return $weeks;
    }

    /**
     * Add ATW warnings to a collection of entries.
     * Entries should be ordered by date DESC (newest first).
     */
    public function addWarningsToEntries(Collection $entries): Collection
    {
        // Work with entries in ASC order for rest-time calculation
        $sorted = $entries->sortBy('date')->values();

        return $entries->map(function ($entry) use ($sorted) {
            $index = $sorted->search(fn ($e) => $e->id === $entry->id);
            $previousEntry = $index > 0 ? $sorted->get($index - 1) : null;
            $entry->atw_warnings = $this->validateEntry($entry, $previousEntry);

            return $entry;
        });
    }
}
