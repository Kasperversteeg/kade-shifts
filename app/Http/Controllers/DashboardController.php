<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();

        $monthEntries = TimeEntry::where('user_id', $user->id)
            ->whereYear('date', $today->year)
            ->whereMonth('date', $today->month)
            ->orderBy('date', 'desc')
            ->get();

        $monthTotal = $monthEntries->sum('total_hours');

        $nextShift = Shift::where('user_id', $user->id)
            ->where('published', true)
            ->where('date', '>=', $today)
            ->orderBy('date')
            ->orderBy('start_time')
            ->first();

        return Inertia::render('Dashboard', [
            'entries' => $monthEntries,
            'monthTotal' => $monthTotal,
            'currentMonth' => $today->format('Y-m'),
            'nextShift' => $nextShift ? [
                'id' => $nextShift->id,
                'date' => $nextShift->date->format('Y-m-d'),
                'start_time' => substr($nextShift->start_time, 0, 5),
                'end_time' => substr($nextShift->end_time, 0, 5),
                'position' => $nextShift->position,
                'planned_hours' => $nextShift->planned_hours,
            ] : null,
        ]);
    }
}
