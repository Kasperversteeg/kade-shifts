<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Mail\SchedulePublished;
use App\Models\Shift;
use App\Models\ShiftPreset;
use App\Models\Team;
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

        $shifts = Shift::with(['user:id,name', 'shiftPreset:id,name,short_name,color'])
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
                'shift_preset_id' => $s->shift_preset_id,
                'preset_name' => $s->shiftPreset?->name,
                'preset_short_name' => $s->shiftPreset?->short_name,
                'preset_color' => $s->shiftPreset?->color,
            ]);

        $allEmployees = User::active()
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        $teams = Team::with(['members' => function ($q) {
            $q->active()->orderBy('name')->select('users.id', 'users.name');
        }])->orderBy('name')->get()->map(fn (Team $team) => [
            'id' => $team->id,
            'name' => $team->name,
            'members' => $team->members->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]),
        ]);

        $teamUserIds = $teams->flatMap(fn ($t) => collect($t['members'])->pluck('id'))->all();
        $ungroupedUsers = User::role('user')->active()
            ->whereNotIn('id', $teamUserIds)
            ->orderBy('name')
            ->select('id', 'name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);

        $hasUnpublished = Shift::whereBetween('date', [$weekStart, $weekEnd])
            ->where('published', false)
            ->exists();

        $shiftPresets = ShiftPreset::active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ShiftPreset $preset) => [
                'id' => $preset->id,
                'name' => $preset->name,
                'short_name' => $preset->short_name,
                'start_time' => substr($preset->start_time, 0, 5),
                'end_time' => substr($preset->end_time, 0, 5),
                'color' => $preset->color,
                'sort_order' => $preset->sort_order,
                'is_active' => $preset->is_active,
            ]);

        return Inertia::render('Admin/ScheduleBoard', [
            'shifts' => $shifts,
            'employees' => $allEmployees,
            'teams' => $teams,
            'ungroupedEmployees' => $ungroupedUsers,
            'days' => $days,
            'currentWeek' => $weekStart->format('Y-m-d'),
            'hasUnpublished' => $hasUnpublished,
            'shiftPresets' => $shiftPresets,
        ]);
    }

    public function store(StoreShiftRequest $request)
    {
        Shift::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', __('Shift created.'));
    }

    public function update(UpdateShiftRequest $request, Shift $shift)
    {
        $shift->update($request->validated());

        return back()->with('success', __('Shift updated.'));
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();

        return back()->with('success', __('Shift deleted.'));
    }

    public function move(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'date' => ['required', 'date'],
        ]);

        $shift->update($validated);

        return back()->with('success', __('Shift moved.'));
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

        return back()->with('success', __('Schedule published.'));
    }
}
