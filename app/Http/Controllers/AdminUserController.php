<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\User;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function edit(User $user)
    {
        return Inertia::render('Admin/UserEdit', [
            'user' => array_merge($user->toArray(), [
                'bsn' => $user->bsn ? str_repeat('*', 5) . substr($user->bsn, -4) : null,
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

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "User {$status} successfully.");
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
