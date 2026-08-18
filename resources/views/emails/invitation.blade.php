<x-mail::message>
# You're invited!

**{{ $inviterName }}** has invited you to join the **{{ config('app.name') }}** petty cash tracker as a **{{ ucfirst($role) }}**.

Accept the invitation below to create your account and start tracking your cash collections and expenses.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

This invitation link expires in 7 days. If you weren't expecting this, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
