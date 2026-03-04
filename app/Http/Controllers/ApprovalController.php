<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');
        $statusFilter = $request->get('status', 'submitted');

        $query = TimeEntry::with('user:id,name,email')
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month);

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $entries = $query->orderBy('date')->orderBy('shift_start')->get()->map(fn($entry) => [
            'id' => $entry->id,
            'user_id' => $entry->user_id,
            'user_name' => $entry->user->name,
            'user_email' => $entry->user->email,
            'date' => $entry->date,
            'shift_start' => $entry->shift_start,
            'shift_end' => $entry->shift_end,
            'break_minutes' => $entry->break_minutes,
            'total_hours' => $entry->total_hours,
            'notes' => $entry->notes,
            'status' => $entry->status,
            'rejection_reason' => $entry->rejection_reason,
        ]);

        $allEntries = TimeEntry::whereYear('date', $date->year)
            ->whereMonth('date', $date->month);

        $statusCounts = [
            'submitted' => (clone $allEntries)->where('status', 'submitted')->count(),
            'approved' => (clone $allEntries)->where('status', 'approved')->count(),
            'rejected' => (clone $allEntries)->where('status', 'rejected')->count(),
            'draft' => (clone $allEntries)->where('status', 'draft')->count(),
        ];

        $submittedIds = TimeEntry::whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->where('status', 'submitted')
            ->pluck('id')
            ->values()
            ->all();

        return Inertia::render('Admin/Approvals', [
            'entries' => $entries,
            'statusCounts' => $statusCounts,
            'currentMonth' => $month,
            'activeStatus' => $statusFilter,
            'submittedEntryIds' => $submittedIds,
        ]);
    }

    public function approve(TimeEntry $timeEntry)
    {
        if (!$timeEntry->isSubmitted()) {
            return redirect()->back()->with('error', __('Only submitted entries can be approved.'));
        }

        $timeEntry->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()->back()->with('success', __('Entry approved.'));
    }

    public function reject(Request $request, TimeEntry $timeEntry)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (!$timeEntry->isSubmitted()) {
            return redirect()->back()->with('error', __('Only submitted entries can be rejected.'));
        }

        $timeEntry->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $request->reason,
        ]);

        return redirect()->back()->with('success', __('Entry rejected.'));
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'entry_ids' => 'required|array',
            'entry_ids.*' => 'exists:time_entries,id',
        ]);

        $count = TimeEntry::whereIn('id', $request->entry_ids)
            ->where('status', 'submitted')
            ->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

        return redirect()->back()->with('success', __(':count entries approved.', ['count' => $count]));
    }
}
