<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Report - {{ $monthName }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 20px; margin-bottom: 5px; }
        h2 { font-size: 14px; margin-top: 20px; margin-bottom: 5px; color: #555; }
        .subtitle { color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row { background-color: #f9f9f9; font-weight: bold; }
        .grand-total { font-size: 14px; font-weight: bold; margin-top: 20px; padding: 10px; background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Kade Shifts - Monthly Report</h1>
    <p class="subtitle">{{ $monthName }}</p>

    @foreach($users as $user)
        @if($user->timeEntries->count() > 0)
            <h2>{{ $user->name }} ({{ $user->email }})</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Start</th>
                        <th>End</th>
                        <th class="text-right">Break (min)</th>
                        <th class="text-right">Hours</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($user->timeEntries as $entry)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</td>
                            <td>{{ substr($entry->shift_start, 0, 5) }}</td>
                            <td>{{ substr($entry->shift_end, 0, 5) }}</td>
                            <td class="text-right">{{ $entry->break_minutes }}</td>
                            <td class="text-right">{{ $entry->total_hours }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="4" class="text-right">Total:</td>
                        <td class="text-right">{{ $user->timeEntries->sum('total_hours') }}h</td>
                    </tr>
                </tbody>
            </table>
        @endif
    @endforeach

    <div class="grand-total">
        Grand Total: {{ $grandTotal }}h
    </div>
</body>
</html>
