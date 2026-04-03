<?php

namespace App\Http\Controllers;

use App\Models\ShiftPreset;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminShiftPresetController extends Controller
{
    public function index()
    {
        $presets = ShiftPreset::orderBy('sort_order')
            ->get()
            ->map(fn (ShiftPreset $preset) => [
                'id' => $preset->id,
                'name' => $preset->name,
                'short_name' => $preset->short_name,
                'start_time' => substr($preset->start_time, 0, 5),
                'end_time' => substr($preset->end_time, 0, 5),
                'color' => $preset->color,
                'sort_order' => $preset->sort_order,
                'is_active' => $preset->is_active,
            ]);

        return Inertia::render('Admin/ShiftPresets', [
            'presets' => $presets,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'short_name' => 'required|string|max:10',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'color' => 'required|string|max:7',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        ShiftPreset::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', __('Shift preset created.'));
    }

    public function update(Request $request, ShiftPreset $shiftPreset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'short_name' => 'required|string|max:10',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'color' => 'required|string|max:7',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $shiftPreset->update($validated);

        return redirect()->back()->with('success', __('Shift preset updated.'));
    }

    public function destroy(ShiftPreset $shiftPreset)
    {
        $shiftPreset->delete();

        return redirect()->back()->with('success', __('Shift preset deleted.'));
    }
}
