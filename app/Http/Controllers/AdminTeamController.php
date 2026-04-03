<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminTeamController extends Controller
{
    public function index()
    {
        $teams = Team::with('members')
            ->orderBy('name')
            ->get()
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'members' => $team->members->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]),
                'member_count' => $team->members->count(),
                'created_at' => $team->created_at->toDateString(),
            ]);

        $employees = User::role('user')
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);

        return Inertia::render('Admin/Teams', [
            'teams' => $teams,
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'member_ids' => 'array',
            'member_ids.*' => ['exists:users,id', function ($attribute, $value, $fail) {
                $user = \App\Models\User::find($value);
                if (!$user || !$user->is_active || $user->hasRole('admin')) {
                    $fail(__('Invalid team member.'));
                }
            }],
        ]);

        $team = Team::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => auth()->id(),
        ]);

        if (!empty($validated['member_ids'])) {
            $team->members()->attach($validated['member_ids']);
        }

        return redirect()->back()->with('success', __('Team created.'));
    }

    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'member_ids' => 'array',
            'member_ids.*' => ['exists:users,id', function ($attribute, $value, $fail) {
                $user = \App\Models\User::find($value);
                if (!$user || !$user->is_active || $user->hasRole('admin')) {
                    $fail(__('Invalid team member.'));
                }
            }],
        ]);

        $team->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $team->members()->sync($validated['member_ids'] ?? []);

        return redirect()->back()->with('success', __('Team updated.'));
    }

    public function destroy(Team $team)
    {
        $team->delete();

        return redirect()->back()->with('success', __('Team deleted.'));
    }
}
