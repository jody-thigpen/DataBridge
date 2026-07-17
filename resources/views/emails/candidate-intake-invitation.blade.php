<x-mail::message>
# Background screening information needed

Hello {{ $subjectName }},

**{{ $organizationName }}** has started a background screening order through SaffHire DataBridge. Please complete the secure intake form so screening can continue.

You will be asked for personal details such as address and work history, and to authorize {{ $organizationName }} (a SaffHire DataBridge client) to query relevant data sources for information about your history.

<x-mail::button :url="$inviteUrl">
Complete screening intake
</x-mail::button>

This link is unique to you and expires in {{ config('candidate_intake.invite_ttl_days', 3) }} days. Do not forward it. If the link expires, ask {{ $organizationName }} to resend your invitation. If you were not expecting this request, contact {{ $organizationName }} before submitting any information.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
