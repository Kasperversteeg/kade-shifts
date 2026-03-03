<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeEntryRequest;
use App\Http\Requests\UpdateTimeEntryRequest;
use App\Models\TimeEntry;
use App\Services\AtwComplianceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimeEntryController extends Controller
{
    public function __construct(
        private AtwComplianceService $atwService,
    ) {}

    public function index(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $entries = TimeEntry::where('user_id', auth()->id())
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date', 'desc')
            ->get();

        $entries = $this->atwService->addWarningsToEntries($entries);

        $monthTotal = $entries->sum('total_hours');

        $statusCounts = [
            'draft' => $entries->where('status', 'draft')->count(),
            'submitted' => $entries->where('status', 'submitted')->count(),
            'approved' => $entries->where('status', 'approved')->count(),
            'rejected' => $entries->where('status', 'rejected')->count(),
        ];

        $weeklyTotals = $this->atwService->getWeeklyTotals(auth()->id(), $month);

        return Inertia::render('TimeEntries/Index', [
            'entries' => $entries,
            'monthTotal' => $monthTotal,
            'currentMonth' => $month,
            'statusCounts' => $statusCounts,
            'weeklyTotals' => $weeklyTotals,
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
            'status' => 'draft',
        ]);

        return redirect()->back()->with('success', __('Time entry added successfully!'));
    }

    public function update(UpdateTimeEntryRequest $request, TimeEntry $timeEntry)
    {
        if ($timeEntry->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$timeEntry->isEditableByEmployee()) {
            return redirect()->back()->with('error', __('Cannot edit approved or submitted entries.'));
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
            'status' => 'draft',
        ]);

        return redirect()->back()->with('success', __('Time entry updated successfully!'));
    }

    public function submit(TimeEntry $timeEntry)
    {
        if ($timeEntry->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$timeEntry->isDraft() && !$timeEntry->isRejected()) {
            return redirect()->back()->with('error', __('Entry cannot be submitted.'));
        }

        $timeEntry->update([
            'status' => 'submitted',
            'rejection_reason' => null,
        ]);

        return redirect()->back()->with('success', __('Entry submitted for approval.'));
    }

    public function submitMonth(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $count = TimeEntry::where('user_id', auth()->id())
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->whereIn('status', ['draft', 'rejected'])
            ->update([
                'status' => 'submitted',
                'rejection_reason' => null,
            ]);

        return redirect()->back()->with('success', __(':count entries submitted for approval.', ['count' => $count]));
    }

    public function exportCsv(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $entries = TimeEntry::where('user_id', auth()->id())
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date')
            ->get();

        $csv = "Date,Start,End,Break (min),Total Hours,Status,Notes\n";
        foreach ($entries as $entry) {
            $notes = str_replace('"', '""', $entry->notes ?? '');
            $csv .= "{$entry->date},{$entry->shift_start},{$entry->shift_end},{$entry->break_minutes},{$entry->total_hours},{$entry->status},\"{$notes}\"\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"hours-{$month}.csv\"",
        ]);
    }

    public function destroy(TimeEntry $timeEntry)
    {
        if ($timeEntry->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$timeEntry->isEditableByEmployee()) {
            return redirect()->back()->with('error', __('Cannot delete approved or submitted entries.'));
        }

        $timeEntry->delete();

        return redirect()->back()->with('success', 'Time entry deleted successfully!');
    }
}
