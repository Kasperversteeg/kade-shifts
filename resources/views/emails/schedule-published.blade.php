<x-mail::message>
# Hello {{ $user->name }},

Your schedule for the week of **{{ $weekStart }}** has been published.

<x-mail::table>
| Date | Start | End | Position |
|:-----|:------|:----|:---------|
@foreach ($shifts as $shift)
| {{ $shift->date->format('D d M') }} | {{ $shift->start_time }} | {{ $shift->end_time }} | {{ $shift->position ?? '—' }} |
@endforeach
</x-mail::table>

<x-mail::button :url="route('schedule.index')">
View Schedule
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
