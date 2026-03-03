<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Mail\SchedulePublished;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $weekStart = $request->input('week')
            ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        for ($d = $weekStart->copy(); $d->lte($weekEnd); $d->addDay()) {
            $days[] = $d->format('Y-m-d');
        }

        $shifts = Shift::with('user:id,name')
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->orderBy('start_time')
            ->get()
            ->map(fn (Shift $s) => [
                'id' => $s->id,
                'date' => $s->date->format('Y-m-d'),
                'start_time' => substr($s->start_time, 0, 5),
                'end_time' => substr($s->end_time, 0, 5),
                'user_id' => $s->user_id,
                'user_name' => $s->user?->name,
                'position' => $s->position,
                'notes' => $s->notes,
                'published' => $s->published,
                'planned_hours' => $s->planned_hours,
            ]);

        $employees = User::select('id', 'name')->orderBy('name')->get();

        $hasUnpublished = Shift::whereBetween('date', [$weekStart, $weekEnd])
            ->where('published', false)
            ->exists();

        return Inertia::render('Admin/ScheduleBoard', [
            'shifts' => $shifts,
            'employees' => $employees,
            'days' => $days,
            'currentWeek' => $weekStart->format('Y-m-d'),
            'hasUnpublished' => $hasUnpublished,
        ]);
    }

    public function store(StoreShiftRequest $request)
    {
        Shift::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Shift created.');
    }

    public function update(UpdateShiftRequest $request, Shift $shift)
    {
        $shift->update($request->validated());

        return back()->with('success', 'Shift updated.');
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();

        return back()->with('success', 'Shift deleted.');
    }

    public function move(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'date' => ['required', 'date'],
        ]);

        $shift->update($validated);

        return back()->with('success', 'Shift moved.');
    }

    public function publish(Request $request)
    {
        $weekStart = Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $shifts = Shift::whereBetween('date', [$weekStart, $weekEnd])
            ->where('published', false)
            ->get();

        $shifts->each(fn (Shift $s) => $s->update(['published' => true]));

        // Group published shifts by user and send emails
        $assignedShifts = $shifts->whereNotNull('user_id')->groupBy('user_id');

        foreach ($assignedShifts as $userId => $userShifts) {
            $user = User::find($userId);
            if ($user) {
                Mail::to($user)->queue(new SchedulePublished(
                    $user,
                    $userShifts->sortBy('date'),
                    $weekStart->format('D d M Y'),
                ));
            }
        }

        return back()->with('success', 'Schedule published.');
    }
}
