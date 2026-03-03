<?php

namespace App\Http\Controllers;

use App\Models\SickLeave;
use App\Models\User;
use Illuminate\Http\Request;

class SickLeaveController extends Controller
{
    public function store(Request $request, User $user)
    {
        $request->validate([
            'start_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($user->isCurrentlySick()) {
            return redirect()->back()->with('error', __('This employee is already registered as sick.'));
        }

        SickLeave::create([
            'user_id' => $user->id,
            'start_date' => $request->start_date,
            'notes' => $request->notes,
            'registered_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', __('Sick leave registered.'));
    }

    public function recover(Request $request, SickLeave $sickLeave)
    {
        $request->validate([
            'end_date' => 'required|date|after_or_equal:' . $sickLeave->start_date->format('Y-m-d'),
        ]);

        $sickLeave->update([
            'end_date' => $request->end_date,
        ]);

        return redirect()->back()->with('success', __('Recovery registered.'));
    }
}
