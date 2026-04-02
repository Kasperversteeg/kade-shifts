<x-mail::message>
# Contract Loopt Binnenkort Af

Het contract van **{{ $employeeName }}** loopt af op **{{ $endDate }}** (nog {{ $daysRemaining }} dagen).

Bekijk het contract van de medewerker en onderneem de benodigde actie.

<x-mail::button :url="$url">
Bekijk Medewerker
</x-mail::button>

Met vriendelijke groet,<br>
{{ config('app.name') }}
</x-mail::message>
