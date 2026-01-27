<?php

namespace App\Http\Controllers;

use App\Mail\MonthlyHoursReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function overview(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $users = User::where('role', 'user')
            ->with(['timeEntries' => function ($query) use ($date) {
                $query->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month);
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_hours' => $user->timeEntries->sum('total_hours'),
                    'entries_count' => $user->timeEntries->count(),
                ];
            });

        $grandTotal = $users->sum('total_hours');

        return Inertia::render('Admin/Overview', [
            'users' => $users,
            'grandTotal' => $grandTotal,
            'currentMonth' => $month,
        ]);
    }

    public function sendMonthlyReport(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $users = User::where('role', 'user')
            ->with(['timeEntries' => function ($query) use ($date) {
                $query->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month);
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_hours' => $user->timeEntries->sum('total_hours'),
                    'entries_count' => $user->timeEntries->count(),
                ];
            });

        Mail::to(auth()->user()->email)->send(new MonthlyHoursReport($users, $month));

        return redirect()->back()->with('success', 'Monthly report sent successfully!');
    }
}
