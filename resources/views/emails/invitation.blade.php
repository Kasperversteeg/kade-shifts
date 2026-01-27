<x-mail::message>
# You're Invited!

You've been invited to join the Hour Registration app.

<x-mail::button :url="$url">
Accept Invitation
</x-mail::button>

This invitation will expire on {{ $expiresAt->format('F j, Y') }}.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
