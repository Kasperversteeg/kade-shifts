<x-mail::message>
# Contract Expiring Soon

The contract for **{{ $employeeName }}** is expiring on **{{ $endDate }}** ({{ $daysRemaining }} days remaining).

Please review the employee's contract and take the necessary action.

<x-mail::button :url="$url">
View Employee Profile
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
