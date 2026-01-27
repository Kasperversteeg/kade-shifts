<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeEntryRequest;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimeEntryController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $entries = TimeEntry::where('user_id', auth()->id())
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date', 'desc')
            ->get();

        $monthTotal = $entries->sum('total_hours');

        return Inertia::render('TimeEntries/Index', [
            'entries' => $entries,
            'monthTotal' => $monthTotal,
            'currentMonth' => $month,
        ]);
    }

    public function store(StoreTimeEntryRequest $request)
    {
        $validated = $request->validated();

        $totalHours = TimeEntry::calculateTotalHours(
            $validated['shift_start'],
            $validated['shift_end'],
            $validated['break_minutes']
        );

        TimeEntry::create([
            'user_id' => auth()->id(),
            'date' => $validated['date'],
            'shift_start' => $validated['shift_start'],
            'shift_end' => $validated['shift_end'],
            'break_minutes' => $validated['break_minutes'],
            'total_hours' => $totalHours,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Time entry added successfully!');
    }

    public function update(StoreTimeEntryRequest $request, TimeEntry $timeEntry)
    {
        if ($timeEntry->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validated();

        $totalHours = TimeEntry::calculateTotalHours(
            $validated['shift_start'],
            $validated['shift_end'],
            $validated['break_minutes']
        );

        $timeEntry->update([
            'date' => $validated['date'],
            'shift_start' => $validated['shift_start'],
            'shift_end' => $validated['shift_end'],
            'break_minutes' => $validated['break_minutes'],
            'total_hours' => $totalHours,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Time entry updated successfully!');
    }

    public function destroy(TimeEntry $timeEntry)
    {
        if ($timeEntry->user_id !== auth()->id()) {
            abort(403);
        }

        $timeEntry->delete();

        return redirect()->back()->with('success', 'Time entry deleted successfully!');
    }
}
