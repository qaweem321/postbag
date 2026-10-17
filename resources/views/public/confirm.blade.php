<x-layouts.guest title="Confirm subscription">
    <form method="post" action="{{ $action }}" class="card space-y-4 p-6 text-center">
        @csrf
        <h1 class="text-xl font-semibold">Confirm your subscription</h1>
        <p class="muted">Subscribe {{ $subscription->subscriber->email }} to <strong class="text-zinc-900">{{ $subscription->mailingList->name }}</strong>?</p>
        <button class="btn-primary w-full" data-pending="Confirming...">Yes, subscribe me</button>
    </form>
</x-layouts.guest>
