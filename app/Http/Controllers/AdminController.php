<?php

namespace App\Http\Controllers;

use App\Mail\MonthlyHoursReport;
use App\Models\TimeEntry;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function userDetail(Request $request, User $user)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $entries = TimeEntry::where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date', 'desc')
            ->get();

        $monthTotal = $entries->sum('total_hours');

        return Inertia::render('Admin/UserDetail', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'entries' => $entries,
            'monthTotal' => $monthTotal,
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

    public function exportCsv(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $users = User::where('role', 'user')
            ->with(['timeEntries' => function ($query) use ($date) {
                $query->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->orderBy('date');
            }])
            ->get();

        $csv = "User,Date,Start,End,Break (min),Total Hours\n";
        foreach ($users as $user) {
            foreach ($user->timeEntries as $entry) {
                $csv .= "\"{$user->name}\",{$entry->date},{$entry->shift_start},{$entry->shift_end},{$entry->break_minutes},{$entry->total_hours}\n";
            }
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"team-hours-{$month}.csv\"",
        ]);
    }

    public function exportPdf(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $users = User::where('role', 'user')
            ->with(['timeEntries' => function ($query) use ($date) {
                $query->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->orderBy('date');
            }])
            ->get();

        $grandTotal = $users->sum(fn ($user) => $user->timeEntries->sum('total_hours'));
        $monthName = $date->format('F Y');

        $pdf = Pdf::loadView('reports.monthly', compact('users', 'grandTotal', 'monthName'));

        return $pdf->download("report-{$month}.pdf");
    }
}
