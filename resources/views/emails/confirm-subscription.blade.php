<x-mail::message>
# Confirm your subscription

You (or someone using your address) asked to receive **{{ $listName }}**. Click the button to confirm.

<x-mail::button :url="$url">
Yes, subscribe me
</x-mail::button>

If you didn't ask for this, ignore this email and you won't hear from us again.
</x-mail::message>
