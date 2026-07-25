<x-mail::message>
# You're invited!

**{{ $invitation->invitedBy->name }}** has invited you to join **{{ $invitation->team->name }}** on SiteGuardian AI as a **{{ $invitation->role }}**.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

This invitation expires on {{ $invitation->expires_at->toFormattedDateString() }}. If you don't have a SiteGuardian AI account yet, accepting will let you create one.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
