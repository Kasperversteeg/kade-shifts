<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminLeaveController extends Controller
{
    public function index()
    {
        $leaveRequests = LeaveRequest::with(['user:id,name,email', 'reviewer:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (LeaveRequest $lr) => [
                'id' => $lr->id,
                'user_id' => $lr->user_id,
                'user_name' => $lr->user->name,
                'user_email' => $lr->user->email,
                'type' => $lr->type,
                'start_date' => $lr->start_date->format('Y-m-d'),
                'end_date' => $lr->end_date->format('Y-m-d'),
                'days' => $lr->days,
                'reason' => $lr->reason,
                'status' => $lr->status,
                'rejection_reason' => $lr->rejection_reason,
                'reviewer_name' => $lr->reviewer?->name,
                'reviewed_at' => $lr->reviewed_at?->format('Y-m-d'),
                'created_at' => $lr->created_at->format('Y-m-d'),
            ]);

        return Inertia::render('Admin/LeaveRequests', [
            'leaveRequests' => $leaveRequests,
        ]);
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        if (!$leaveRequest->isPending()) {
            return redirect()->back()->with('error', __('Only pending requests can be approved.'));
        }

        $leaveRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->back()->with('success', __('Leave request approved.'));
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (!$leaveRequest->isPending()) {
            return redirect()->back()->with('error', __('Only pending requests can be rejected.'));
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $request->reason,
        ]);

        return redirect()->back()->with('success', __('Leave request rejected.'));
    }
}
