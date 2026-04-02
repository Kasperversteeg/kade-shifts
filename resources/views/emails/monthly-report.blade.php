<x-mail::message>
# Maandelijks Urenrapport - {{ $month }}

Hieronder het overzicht van de gewerkte uren deze maand:

<x-mail::table>
| Naam | E-mail | Uren | Registraties |
|:-----|:-------|-----:|-------------:|
@foreach($users as $user)
| {{ $user['name'] }} | {{ $user['email'] }} | {{ number_format($user['total_hours'], 2) }} | {{ $user['entries_count'] }} |
@endforeach
| **Totaal** | | **{{ number_format($grandTotal, 2) }}** | |
</x-mail::table>

Met vriendelijke groet,<br>
{{ config('app.name') }}
</x-mail::message>
