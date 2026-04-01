<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\SickLeave;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Extend query range to cover the full visible calendar grid
        // (Monday of the first week through Sunday of the last week)
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        // 1. Published shifts for this user (with preset info)
        $shifts = Shift::with('shiftPreset:id,name,short_name,color')
            ->where('user_id', $user->id)
            ->where('published', true)
            ->whereBetween('date', [$gridStart, $gridEnd])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // 2. Time entries for this user
        $timeEntries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('date', [$gridStart, $gridEnd])
            ->orderBy('date')
            ->get();

        // 3. Approved + pending leave requests that overlap with visible grid
        $leaveRequests = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->where('start_date', '<=', $gridEnd)
            ->where('end_date', '>=', $gridStart)
            ->get();

        // 4. Sick leaves that overlap with visible grid
        $sickLeaves = SickLeave::where('user_id', $user->id)
            ->where('start_date', '<=', $gridEnd)
            ->where(function ($q) use ($gridStart) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $gridStart);
            })
            ->get();

        // Build unified events array
        $events = collect();

        // Shift events
        foreach ($shifts as $shift) {
            $events->push([
                'date' => $shift->date->format('Y-m-d'),
                'type' => 'shift',
                'label' => $shift->shiftPreset?->name ?? substr($shift->start_time, 0, 5) . '-' . substr($shift->end_time, 0, 5),
                'detail' => substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5),
                'color' => $shift->shiftPreset?->color ?? '#3B82F6',
                'status' => null,
                'hours' => $shift->planned_hours,
                'id' => $shift->id,
                'source_type' => 'shift',
                'source_id' => $shift->id,
                'start_time' => substr($shift->start_time, 0, 5),
                'end_time' => substr($shift->end_time, 0, 5),
                'position' => $shift->position,
                'preset_short_name' => $shift->shiftPreset?->short_name,
            ]);
        }

        // Time entry events
        foreach ($timeEntries as $entry) {
            $events->push([
                'date' => $entry->date->format('Y-m-d'),
                'type' => 'time_entry',
                'label' => $entry->total_hours . 'u gewerkt',
                'detail' => substr($entry->shift_start, 0, 5) . ' - ' . substr($entry->shift_end, 0, 5),
                'color' => '#10B981',
                'status' => $entry->status,
                'hours' => (float) $entry->total_hours,
                'id' => $entry->id,
                'source_type' => 'time_entry',
                'source_id' => $entry->id,
            ]);
        }

        // Leave request events — expand to individual days
        foreach ($leaveRequests as $leave) {
            $start = $leave->start_date->gt($gridStart) ? $leave->start_date : $gridStart;
            $end = $leave->end_date->lt($gridEnd) ? $leave->end_date : $gridEnd;

            for ($d = Carbon::parse($start); $d->lte($end); $d->addDay()) {
                $events->push([
                    'date' => $d->format('Y-m-d'),
                    'type' => 'leave',
                    'label' => $leave->type,
                    'detail' => null,
                    'color' => '#F59E0B',
                    'status' => $leave->status,
                    'hours' => null,
                    'id' => $leave->id,
                    'source_type' => 'leave_request',
                    'source_id' => $leave->id,
                ]);
            }
        }

        // Sick leave events — expand to individual days
        foreach ($sickLeaves as $sick) {
            $start = $sick->start_date->gt($gridStart) ? $sick->start_date : $gridStart;
            $end = $sick->end_date
                ? ($sick->end_date->lt($gridEnd) ? $sick->end_date : $gridEnd)
                : $gridEnd;

            for ($d = Carbon::parse($start); $d->lte($end); $d->addDay()) {
                $events->push([
                    'date' => $d->format('Y-m-d'),
                    'type' => 'sick',
                    'label' => 'sick',
                    'detail' => null,
                    'color' => '#EF4444',
                    'status' => $sick->end_date ? 'recovered' : 'active',
                    'hours' => null,
                    'id' => $sick->id,
                    'source_type' => 'sick_leave',
                    'source_id' => $sick->id,
                ]);
            }
        }

        // Month totals (scoped to the actual month, not the full grid)
        $monthStartStr = $monthStart->format('Y-m-d');
        $monthEndStr = $monthEnd->format('Y-m-d');
        $totals = [
            'planned_hours' => $shifts->filter(fn ($s) => $s->date->between($monthStart, $monthEnd))->sum('planned_hours'),
            'worked_hours' => $timeEntries->filter(fn ($e) => $e->date->between($monthStart, $monthEnd))->sum('total_hours'),
            'leave_days' => $events->where('type', 'leave')->filter(fn ($e) => $e['date'] >= $monthStartStr && $e['date'] <= $monthEndStr)->count(),
            'sick_days' => $events->where('type', 'sick')->filter(fn ($e) => $e['date'] >= $monthStartStr && $e['date'] <= $monthEndStr)->count(),
        ];

        return Inertia::render('Schedule/Index', [
            'events' => $events->sortBy('date')->values(),
            'currentMonth' => $month,
            'totals' => $totals,
        ]);
    }
}
