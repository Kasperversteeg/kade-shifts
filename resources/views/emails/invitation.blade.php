<x-mail::message>
# Je bent uitgenodigd!

Je bent uitgenodigd om deel te nemen aan de urenregistratie van Kade Shifts.

<x-mail::button :url="$url">
Uitnodiging Accepteren
</x-mail::button>

Deze uitnodiging is geldig tot {{ $expiresAt->format('j F Y') }}.

Met vriendelijke groet,<br>
{{ config('app.name') }}
</x-mail::message>
