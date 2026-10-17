<x-layouts.guest title="Unsubscribe">
    <form method="post" action="{{ $action }}" class="card space-y-4 p-6 text-center">
        @csrf
        <h1 class="text-xl font-semibold">Unsubscribe</h1>
        <p class="muted">Stop sending <strong class="text-zinc-900">{{ $subscription->mailingList->name }}</strong> to {{ $subscription->subscriber->email }}?</p>
        <button class="btn-danger w-full" data-pending="Unsubscribing...">Unsubscribe</button>
    </form>
</x-layouts.guest>
