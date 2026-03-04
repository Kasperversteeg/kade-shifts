<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Models\LeaveRequest;
use Inertia\Inertia;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $leaveRequests = $user->leaveRequests()
            ->with('reviewer:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (LeaveRequest $lr) => [
                'id' => $lr->id,
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

        return Inertia::render('Leave/Index', [
            'leaveRequests' => $leaveRequests,
            'leaveBalance' => $user->leaveBalance,
        ]);
    }

    public function store(StoreLeaveRequest $request)
    {
        auth()->user()->leaveRequests()->create($request->validated());

        return redirect()->back()->with('success', __('Leave request submitted.'));
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$leaveRequest->isPending()) {
            return redirect()->back()->with('error', __('Only pending requests can be cancelled.'));
        }

        $leaveRequest->delete();

        return redirect()->back()->with('success', __('Leave request cancelled.'));
    }
}
