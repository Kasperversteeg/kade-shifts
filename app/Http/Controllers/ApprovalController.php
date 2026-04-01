<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function index()
    {
        $entries = TimeEntry::where('status', 'submitted')
            ->with('user:id,name,email')
            ->orderBy('date', 'desc')
            ->get();

        $grouped = $entries->groupBy('user_id')->map(function ($userEntries) {
            $user = $userEntries->first()->user;
            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'entries' => $userEntries->values(),
                'total_hours' => $userEntries->sum('total_hours'),
            ];
        })->values();

        $allEntryIds = $entries->pluck('id')->values()->all();

        return Inertia::render('Admin/Approvals', [
            'groups' => $grouped,
            'totalPending' => $entries->count(),
            'allEntryIds' => $allEntryIds,
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
