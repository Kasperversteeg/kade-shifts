<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();

        $monthEntries = TimeEntry::where('user_id', $user->id)
            ->whereYear('date', $today->year)
            ->whereMonth('date', $today->month)
            ->orderBy('date', 'desc')
            ->get();

        $monthTotal = $monthEntries->sum('total_hours');

        return Inertia::render('Dashboard', [
            'entries' => $monthEntries,
            'monthTotal' => $monthTotal,
            'currentMonth' => $today->format('Y-m'),
        ]);
    }
}
