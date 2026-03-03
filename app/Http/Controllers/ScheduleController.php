<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $weekStart = $request->input('week')
            ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        for ($d = $weekStart->copy(); $d->lte($weekEnd); $d->addDay()) {
            $days[] = $d->format('Y-m-d');
        }

        $shifts = Shift::where('user_id', $user->id)
            ->where('published', true)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Shift $s) => [
                'id' => $s->id,
                'date' => $s->date->format('Y-m-d'),
                'start_time' => substr($s->start_time, 0, 5),
                'end_time' => substr($s->end_time, 0, 5),
                'user_id' => $s->user_id,
                'user_name' => $user->name,
                'position' => $s->position,
                'notes' => $s->notes,
                'published' => $s->published,
                'planned_hours' => $s->planned_hours,
            ]);

        $weekTotal = $shifts->sum('planned_hours');

        return Inertia::render('Schedule/Index', [
            'shifts' => $shifts,
            'days' => $days,
            'currentWeek' => $weekStart->format('Y-m-d'),
            'weekTotal' => $weekTotal,
        ]);
    }
}
