<x-mail::message>
# Monthly Hours Report - {{ $month }}

Here's the summary of hours worked this month:

<x-mail::table>
| Name | Email | Hours | Entries |
|:-----|:------|------:|--------:|
@foreach($users as $user)
| {{ $user['name'] }} | {{ $user['email'] }} | {{ number_format($user['total_hours'], 2) }} | {{ $user['entries_count'] }} |
@endforeach
| **Total** | | **{{ number_format($grandTotal, 2) }}** | |
</x-mail::table>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
