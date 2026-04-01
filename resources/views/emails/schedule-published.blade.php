<x-mail::message>
# Hallo {{ $user->name }},

Je rooster voor de week van **{{ $weekStart }}** is gepubliceerd.

<x-mail::table>
| Datum | Start | Einde | Positie |
|:------|:------|:------|:--------|
@foreach ($shifts as $shift)
| {{ $shift->date->format('D d M') }} | {{ $shift->start_time }} | {{ $shift->end_time }} | {{ $shift->position ?? '—' }} |
@endforeach
</x-mail::table>

<x-mail::button :url="route('schedule.index')">
Bekijk Rooster
</x-mail::button>

Met vriendelijke groet,<br>
{{ config('app.name') }}
</x-mail::message>
