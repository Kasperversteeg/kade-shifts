<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PreferencesController extends Controller
{
    public function edit()
    {
        return Inertia::render('Preferences', [
            'preferences' => auth()->user()->preferences ?? [],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'language' => 'required|in:en,nl',
        ]);

        $user = auth()->user();
        $preferences = $user->preferences ?? [];
        $preferences['language'] = $validated['language'];
        $user->preferences = $preferences;
        $user->save();

        return redirect()->back()->with('success', __('Preferences saved.'));
    }
}
