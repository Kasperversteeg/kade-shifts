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
        $activeFilter = $request->get('active', 'active');

        $usersQuery = User::role('user')
            ->with(['timeEntries' => function ($query) use ($date) {
                $query->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month);
            }]);

        if ($activeFilter === 'active') {
            $usersQuery->active();
        } elseif ($activeFilter === 'inactive') {
            $usersQuery->where('is_active', false);
        }

        $users = $usersQuery->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'total_hours' => $user->timeEntries->sum('total_hours'),
                    'entries_count' => $user->timeEntries->count(),
                    'hourly_rate' => (float) $user->hourly_rate,
                ];
            });

        $grandTotal = $users->sum('total_hours');

        $pendingApprovals = TimeEntry::where('status', 'submitted')->count();
        $pendingLeaveRequests = \App\Models\LeaveRequest::where('status', 'pending')->count();
        $expiringContracts = User::active()
            ->whereNotNull('contract_end_date')
            ->where('contract_end_date', '<=', now()->addDays(45))
            ->where('contract_end_date', '>=', today())
            ->count();
        $estimatedMonthlyCost = $users->sum(fn($u) => $u['total_hours'] * $u['hourly_rate']);

        return Inertia::render('Admin/Overview', [
            'users' => $users,
            'grandTotal' => $grandTotal,
            'currentMonth' => $month,
            'activeFilter' => $activeFilter,
            'pendingApprovals' => $pendingApprovals,
            'pendingLeaveRequests' => $pendingLeaveRequests,
            'expiringContracts' => $expiringContracts,
            'estimatedMonthlyCost' => round($estimatedMonthlyCost, 2),
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

        $submittedIds = $entries->where('status', 'submitted')->pluck('id')->values()->all();

        $sickLeaves = $user->sickLeaves()
            ->with('registrar:id,name')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn($sl) => [
                'id' => $sl->id,
                'start_date' => $sl->start_date->format('Y-m-d'),
                'end_date' => $sl->end_date?->format('Y-m-d'),
                'days' => $sl->days,
                'notes' => $sl->notes,
                'is_active' => $sl->isActive(),
                'registrar_name' => $sl->registrar?->name,
                'created_at' => $sl->created_at->format('Y-m-d'),
            ]);

        $documents = $user->documents()->with('uploader')->latest()->get()->map(fn($doc) => [
            'id' => $doc->id,
            'type' => $doc->type,
            'original_filename' => $doc->original_filename,
            'mime_type' => $doc->mime_type,
            'file_size' => $doc->file_size,
            'uploaded_by' => $doc->uploaded_by,
            'uploader_name' => $doc->uploader?->name,
            'created_at' => $doc->created_at->format('Y-m-d'),
        ]);

        return Inertia::render('Admin/UserDetail', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'hourly_rate' => $user->hourly_rate,
                'contract_type' => $user->contract_type,
                'contract_start_date' => $user->contract_start_date?->format('Y-m-d'),
                'contract_end_date' => $user->contract_end_date?->format('Y-m-d'),
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'profile_completeness' => $user->profileCompleteness,
            ],
            'entries' => $entries,
            'documents' => $documents,
            'sickLeaves' => $sickLeaves,
            'isCurrentlySick' => $user->isCurrentlySick(),
            'sickDaysThisYear' => $user->sickDaysThisYear,
            'monthTotal' => $monthTotal,
            'currentMonth' => $month,
            'submittedEntryIds' => $submittedIds,
        ]);
    }

    public function sendMonthlyReport(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $users = User::role('user')
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

        Mail::to(auth()->user()->email)->queue(new MonthlyHoursReport($users, $month));

        return redirect()->back()->with('success', 'Monthly report sent successfully!');
    }

    public function exportCsv(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');
        $statusFilter = $request->get('status');

        $users = User::role('user')
            ->with(['timeEntries' => function ($query) use ($date, $statusFilter) {
                $query->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->orderBy('date');
                if ($statusFilter) {
                    $query->where('status', $statusFilter);
                }
            }])
            ->get();

        $csv = "User,Date,Start,End,Break (min),Total Hours,Status\n";
        foreach ($users as $user) {
            foreach ($user->timeEntries as $entry) {
                $csv .= "\"{$user->name}\",{$entry->date},{$entry->shift_start},{$entry->shift_end},{$entry->break_minutes},{$entry->total_hours},{$entry->status}\n";
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
        $statusFilter = $request->get('status');

        $users = User::role('user')
            ->with(['timeEntries' => function ($query) use ($date, $statusFilter) {
                $query->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->orderBy('date');
                if ($statusFilter) {
                    $query->where('status', $statusFilter);
                }
            }])
            ->get();

        $grandTotal = $users->sum(fn($user) => $user->timeEntries->sum('total_hours'));
        $monthName = $date->format('F Y');

        $pdf = Pdf::loadView('reports.monthly', compact('users', 'grandTotal', 'monthName'));

        return $pdf->download("report-{$month}.pdf");
    }
}
