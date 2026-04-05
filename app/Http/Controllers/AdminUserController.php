<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::role('user')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'hourly_rate' => $user->hourly_rate,
                'contract_type' => $user->contract_type,
                'contract_start_date' => $user->contract_start_date?->toDateString(),
                'contract_end_date' => $user->contract_end_date?->toDateString(),
                'birth_date' => $user->birth_date?->toDateString(),
                'start_date' => $user->start_date?->toDateString(),
                'city' => $user->city,
                'profile_completeness' => $user->profileCompleteness,
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    public function shifts(Request $request, User $user)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $entries = TimeEntry::where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date')
            ->get();

        $monthTotal = $entries->sum('total_hours');
        $approvedTotal = $entries->where('status', 'approved')->sum('total_hours');

        $statusCounts = [
            'draft' => $entries->where('status', 'draft')->count(),
            'submitted' => $entries->where('status', 'submitted')->count(),
            'approved' => $entries->where('status', 'approved')->count(),
            'rejected' => $entries->where('status', 'rejected')->count(),
        ];

        $submittedIds = $entries->where('status', 'submitted')->pluck('id')->values()->all();

        return Inertia::render('Admin/UserShifts', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'hourly_rate' => $user->hourly_rate,
                'contract_type' => $user->contract_type,
                'is_active' => $user->is_active,
            ],
            'entries' => $entries,
            'monthTotal' => $monthTotal,
            'approvedTotal' => $approvedTotal,
            'statusCounts' => $statusCounts,
            'currentMonth' => $month,
            'submittedEntryIds' => $submittedIds,
        ]);
    }

    public function edit(User $user)
    {
        return Inertia::render('Admin/UserEdit', [
            'user' => array_merge($user->toArray(), [
                'bsn' => $user->bsn ? str_repeat('*', 5) . substr($user->bsn, -4) : null,
                'bank_account_number' => $user->bank_account_number,
                'profile_completeness' => $user->profileCompleteness,
            ]),
        ]);
    }

    public function toggleActive(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active,
            'deactivated_at' => $user->is_active ? now() : null,
        ]);

        $message = $user->is_active ? __('User activated successfully.') : __('User deactivated successfully.');

        return redirect()->back()->with('success', $message);
    }

    public function update(UpdateUserProfileRequest $request, User $user)
    {
        $validated = $request->validated();

        // Don't overwrite BSN with masked value
        if (isset($validated['bsn']) && str_contains($validated['bsn'], '*')) {
            unset($validated['bsn']);
        }

        $user->update($validated);

        return redirect()->back()->with('success', __('User profile updated successfully.'));
    }
}
