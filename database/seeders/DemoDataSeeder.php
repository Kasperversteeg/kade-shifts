<?php

namespace Database\Seeders;

use App\Models\Invitation;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\SickLeave;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        // Create 12 employees with varied profile completeness
        $employees = $this->createEmployees();

        // Seed time entries for the last 3 months
        $this->createTimeEntries($employees, $admin);

        // Seed shifts for current and next week
        $this->createShifts($employees, $admin);

        // Seed leave requests
        $this->createLeaveRequests($employees, $admin);

        // Seed sick leave records
        $this->createSickLeaves($employees, $admin);

        // Seed accepted invitations
        $this->createInvitations($admin);
    }

    private function createEmployees(): array
    {
        $employeeData = [
            [
                'name' => 'Sophie de Vries',
                'email' => 'sophie@example.com',
                'hourly_rate' => 14.50,
                'contract_type' => 'vast',
                'contract_start_date' => '2024-03-01',
                'contract_end_date' => null,
                'birth_date' => '1998-07-15',
                'start_date' => '2024-03-01',
                'phone' => '06-12345678',
                'address' => 'Keizersgracht 123',
                'city' => 'Amsterdam',
                'postal_code' => '1015 CJ',
                'statutory_leave_days' => 20,
                'extra_leave_days' => 5,
            ],
            [
                'name' => 'Daan Bakker',
                'email' => 'daan@example.com',
                'hourly_rate' => 13.00,
                'contract_type' => 'flex',
                'contract_start_date' => '2025-01-15',
                'contract_end_date' => '2026-07-15',
                'birth_date' => '2001-11-23',
                'start_date' => '2025-01-15',
                'phone' => '06-23456789',
                'address' => 'Oudegracht 45',
                'city' => 'Utrecht',
                'postal_code' => '3511 AB',
                'statutory_leave_days' => 20,
                'extra_leave_days' => 0,
            ],
            [
                'name' => 'Emma Jansen',
                'email' => 'emma@example.com',
                'hourly_rate' => 15.00,
                'contract_type' => 'vast',
                'contract_start_date' => '2023-09-01',
                'contract_end_date' => null,
                'birth_date' => '1995-03-08',
                'start_date' => '2023-09-01',
                'phone' => '06-34567890',
                'address' => 'Grote Markt 12',
                'city' => 'Groningen',
                'postal_code' => '9711 LV',
                'statutory_leave_days' => 20,
                'extra_leave_days' => 5,
            ],
            [
                'name' => 'Lucas van Dijk',
                'email' => 'lucas@example.com',
                'hourly_rate' => 12.50,
                'contract_type' => 'oproep',
                'contract_start_date' => '2025-06-01',
                'contract_end_date' => null,
                'birth_date' => '2003-09-12',
                'start_date' => '2025-06-01',
                'phone' => '06-45678901',
                'address' => null,
                'city' => 'Rotterdam',
                'postal_code' => null,
                'statutory_leave_days' => 20,
                'extra_leave_days' => 0,
            ],
            [
                'name' => 'Mila Visser',
                'email' => 'mila@example.com',
                'hourly_rate' => 14.00,
                'contract_type' => 'flex',
                'contract_start_date' => '2025-09-01',
                'contract_end_date' => '2026-09-01',
                'birth_date' => '2000-01-30',
                'start_date' => '2025-09-01',
                'phone' => '06-56789012',
                'address' => 'Markt 8',
                'city' => 'Eindhoven',
                'postal_code' => '5611 EB',
                'statutory_leave_days' => 20,
                'extra_leave_days' => 2,
            ],
            [
                'name' => 'Noah Mulder',
                'email' => 'noah@example.com',
                'hourly_rate' => 13.50,
                'contract_type' => 'vast',
                'contract_start_date' => '2024-06-01',
                'contract_end_date' => null,
                'birth_date' => '1999-05-20',
                'start_date' => '2024-06-01',
                'phone' => '06-67890123',
                'address' => 'Vrijhof 10',
                'city' => 'Maastricht',
                'postal_code' => '6211 LD',
                'statutory_leave_days' => 20,
                'extra_leave_days' => 3,
            ],
            [
                'name' => 'Lotte Smit',
                'email' => 'lotte@example.com',
                'hourly_rate' => 14.50,
                'contract_type' => 'flex',
                'contract_start_date' => '2025-11-01',
                'contract_end_date' => '2026-05-01',
                'birth_date' => '1997-08-14',
                'start_date' => '2025-11-01',
                'phone' => '06-78901234',
                'address' => 'Herestraat 22',
                'city' => 'Groningen',
                'postal_code' => '9711 LG',
                'statutory_leave_days' => 20,
                'extra_leave_days' => 0,
            ],
            [
                'name' => 'Sem Peters',
                'email' => 'sem@example.com',
                'hourly_rate' => 12.00,
                'contract_type' => 'oproep',
                'contract_start_date' => '2026-01-10',
                'contract_end_date' => null,
                'birth_date' => '2004-12-01',
                'start_date' => '2026-01-10',
                'phone' => null,
                'address' => null,
                'city' => null,
                'postal_code' => null,
                'statutory_leave_days' => 20,
                'extra_leave_days' => 0,
            ],
            [
                'name' => 'Julia Hendriks',
                'email' => 'julia@example.com',
                'hourly_rate' => 15.50,
                'contract_type' => 'vast',
                'contract_start_date' => '2023-04-01',
                'contract_end_date' => null,
                'birth_date' => '1996-06-22',
                'start_date' => '2023-04-01',
                'phone' => '06-89012345',
                'address' => 'Lange Voorhout 5',
                'city' => 'Den Haag',
                'postal_code' => '2514 EA',
                'statutory_leave_days' => 20,
                'extra_leave_days' => 5,
            ],
            [
                'name' => 'Finn de Boer',
                'email' => 'finn@example.com',
                'hourly_rate' => null,
                'contract_type' => null,
                'contract_start_date' => null,
                'contract_end_date' => null,
                'birth_date' => null,
                'start_date' => null,
                'phone' => '06-90123456',
                'address' => null,
                'city' => 'Leiden',
                'postal_code' => null,
                'statutory_leave_days' => 20,
                'extra_leave_days' => 0,
            ],
            [
                'name' => 'Sara van den Berg',
                'email' => 'sara@example.com',
                'hourly_rate' => 13.00,
                'contract_type' => 'flex',
                'contract_start_date' => '2025-08-15',
                'contract_end_date' => '2026-03-15',
                'birth_date' => '2002-04-18',
                'start_date' => '2025-08-15',
                'phone' => '06-01234567',
                'address' => 'Neude 30',
                'city' => 'Utrecht',
                'postal_code' => '3512 AG',
                'statutory_leave_days' => 20,
                'extra_leave_days' => 0,
                'is_active' => false,
                'deactivated_at' => '2026-02-01',
            ],
            [
                'name' => 'Max Willems',
                'email' => 'max@example.com',
                'hourly_rate' => 14.00,
                'contract_type' => 'vast',
                'contract_start_date' => '2024-01-15',
                'contract_end_date' => null,
                'birth_date' => '1997-10-05',
                'start_date' => '2024-01-15',
                'phone' => '06-11223344',
                'address' => 'Brink 7',
                'city' => 'Deventer',
                'postal_code' => '7411 BT',
                'statutory_leave_days' => 20,
                'extra_leave_days' => 3,
            ],
        ];

        $employees = [];
        foreach ($employeeData as $data) {
            $user = User::create(array_merge($data, [
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => $data['is_active'] ?? true,
                'deactivated_at' => isset($data['deactivated_at']) ? Carbon::parse($data['deactivated_at']) : null,
            ]));
            $user->assignRole('user');
            $employees[] = $user;
        }

        return $employees;
    }

    private function createTimeEntries(array $employees, User $admin): void
    {
        $statuses = ['draft', 'submitted', 'approved', 'rejected'];
        $now = Carbon::now();

        foreach ($employees as $employee) {
            if (! $employee->is_active) {
                continue;
            }

            // Generate entries for last 3 months
            for ($monthOffset = 2; $monthOffset >= 0; $monthOffset--) {
                $month = $now->copy()->subMonths($monthOffset);
                $daysInMonth = $month->daysInMonth;

                // 10-18 working days per month
                $workDays = rand(10, min(18, $daysInMonth));
                $usedDays = [];

                for ($i = 0; $i < $workDays; $i++) {
                    $day = rand(1, $daysInMonth);
                    $date = $month->copy()->day($day);

                    // Skip weekends and already-used days
                    if ($date->isWeekend() || in_array($day, $usedDays) || $date->isFuture()) {
                        continue;
                    }
                    $usedDays[] = $day;

                    $shiftStart = sprintf('%02d:%02d', rand(8, 12), [0, 15, 30, 45][rand(0, 3)]);
                    $hoursWorked = [4, 5, 6, 7, 8][rand(0, 4)];
                    $startHour = (int) substr($shiftStart, 0, 2);
                    $startMin = (int) substr($shiftStart, 3, 2);
                    $endMin = $startMin + ($hoursWorked * 60);
                    $shiftEnd = sprintf('%02d:%02d', intdiv($endMin, 60) + $startHour, $endMin % 60);

                    $breakMinutes = $hoursWorked >= 6 ? 30 : 0;
                    $totalHours = TimeEntry::calculateTotalHours($shiftStart, $shiftEnd, $breakMinutes);

                    // Older months mostly approved, current month mixed
                    if ($monthOffset >= 2) {
                        $status = 'approved';
                    } elseif ($monthOffset === 1) {
                        $status = ['approved', 'approved', 'approved', 'submitted'][rand(0, 3)];
                    } else {
                        $status = $statuses[rand(0, 3)];
                    }

                    $entry = TimeEntry::create([
                        'user_id' => $employee->id,
                        'date' => $date->toDateString(),
                        'shift_start' => $shiftStart,
                        'shift_end' => $shiftEnd,
                        'break_minutes' => $breakMinutes,
                        'total_hours' => $totalHours,
                        'notes' => rand(0, 4) === 0 ? 'Shift notes' : null,
                        'status' => $status,
                        'reviewed_by' => in_array($status, ['approved', 'rejected']) ? $admin->id : null,
                        'reviewed_at' => in_array($status, ['approved', 'rejected']) ? $date->copy()->addDays(rand(1, 3)) : null,
                        'rejection_reason' => $status === 'rejected' ? 'Hours do not match the schedule' : null,
                    ]);
                }
            }
        }
    }

    private function createShifts(array $employees, User $admin): void
    {
        $positions = ['Bar', 'Kitchen', 'Floor', 'Terrace', null];
        $activeEmployees = array_filter($employees, fn ($e) => $e->is_active);
        $now = Carbon::now();

        // Current week and next week
        for ($weekOffset = 0; $weekOffset <= 1; $weekOffset++) {
            $weekStart = $now->copy()->startOfWeek()->addWeeks($weekOffset);

            for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
                $date = $weekStart->copy()->addDays($dayOffset);

                // Fewer shifts on weekdays, more on weekends
                $shiftCount = $date->isWeekend() ? rand(4, 6) : rand(2, 4);
                $assignedThisDay = [];

                for ($s = 0; $s < $shiftCount; $s++) {
                    // Pick a random employee or leave unassigned
                    $employee = null;
                    if (rand(0, 5) > 0 && count($activeEmployees) > 0) {
                        $available = array_diff(array_keys($activeEmployees), $assignedThisDay);
                        if (! empty($available)) {
                            $key = $available[array_rand($available)];
                            $employee = $activeEmployees[$key];
                            $assignedThisDay[] = $key;
                        }
                    }

                    $startHour = [9, 10, 11, 12, 15, 16, 17][rand(0, 6)];
                    $duration = [4, 5, 6, 7, 8][rand(0, 4)];

                    Shift::create([
                        'date' => $date->toDateString(),
                        'start_time' => sprintf('%02d:00', $startHour),
                        'end_time' => sprintf('%02d:00', $startHour + $duration),
                        'user_id' => $employee?->id,
                        'position' => $positions[rand(0, 4)],
                        'notes' => null,
                        'published' => $weekOffset === 0,
                        'created_by' => $admin->id,
                    ]);
                }
            }
        }
    }

    private function createLeaveRequests(array $employees, User $admin): void
    {
        $types = ['vakantie', 'bijzonder_verlof', 'onbetaald_verlof'];

        // Past approved vacation
        LeaveRequest::create([
            'user_id' => $employees[0]->id,
            'type' => 'vakantie',
            'start_date' => '2026-01-05',
            'end_date' => '2026-01-09',
            'reason' => 'Winter holiday',
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => '2025-12-20',
        ]);

        LeaveRequest::create([
            'user_id' => $employees[2]->id,
            'type' => 'vakantie',
            'start_date' => '2026-02-16',
            'end_date' => '2026-02-20',
            'reason' => null,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => '2026-02-01',
        ]);

        // Pending requests
        LeaveRequest::create([
            'user_id' => $employees[1]->id,
            'type' => 'vakantie',
            'start_date' => '2026-04-06',
            'end_date' => '2026-04-17',
            'reason' => 'Spring break trip',
            'status' => 'pending',
        ]);

        LeaveRequest::create([
            'user_id' => $employees[4]->id,
            'type' => 'bijzonder_verlof',
            'start_date' => '2026-03-20',
            'end_date' => '2026-03-20',
            'reason' => 'Moving day',
            'status' => 'pending',
        ]);

        LeaveRequest::create([
            'user_id' => $employees[5]->id,
            'type' => 'vakantie',
            'start_date' => '2026-05-11',
            'end_date' => '2026-05-22',
            'reason' => 'Summer vacation',
            'status' => 'pending',
        ]);

        // Rejected request
        LeaveRequest::create([
            'user_id' => $employees[3]->id,
            'type' => 'onbetaald_verlof',
            'start_date' => '2026-03-09',
            'end_date' => '2026-03-13',
            'reason' => 'Personal',
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => '2026-03-02',
            'rejection_reason' => 'Too many staff members already off that week',
        ]);
    }

    private function createSickLeaves(array $employees, User $admin): void
    {
        // Recovered sick leave earlier this year
        SickLeave::create([
            'user_id' => $employees[2]->id,
            'start_date' => '2026-01-20',
            'end_date' => '2026-01-23',
            'notes' => 'Flu',
            'registered_by' => $admin->id,
        ]);

        SickLeave::create([
            'user_id' => $employees[5]->id,
            'start_date' => '2026-02-03',
            'end_date' => '2026-02-05',
            'notes' => null,
            'registered_by' => $admin->id,
        ]);

        // Currently sick employee
        SickLeave::create([
            'user_id' => $employees[6]->id,
            'start_date' => '2026-03-02',
            'end_date' => null,
            'notes' => 'Reported sick via phone',
            'registered_by' => $admin->id,
        ]);
    }

    private function createInvitations(User $admin): void
    {
        // Accepted invitation
        Invitation::create([
            'email' => 'accepted@example.com',
            'token' => Invitation::generateToken(),
            'invited_by' => $admin->id,
            'expires_at' => now()->subDays(20),
            'accepted_at' => now()->subDays(22),
        ]);

        // Pending invitation
        Invitation::create([
            'email' => 'newemployee@example.com',
            'token' => Invitation::generateToken(),
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(5),
            'accepted_at' => null,
        ]);

        // Expired invitation
        Invitation::create([
            'email' => 'expired@example.com',
            'token' => Invitation::generateToken(),
            'invited_by' => $admin->id,
            'expires_at' => now()->subDays(3),
            'accepted_at' => null,
        ]);
    }
}
