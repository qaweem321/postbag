<x-layouts.app title="Dashboard">
    <h1 class="mb-6 text-2xl font-semibold tracking-tight">Dashboard</h1>
    @if ($listCount === 0)
        <div class="card px-6 py-12 text-center">
            <p class="font-medium">Welcome to Postbag</p>
            <p class="muted mt-1 text-sm">Start with a list: people subscribe to it, and campaigns are sent to it.</p>
            <a href="{{ route('lists.create') }}" class="btn-primary mt-4">Create your first list</a>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['Subscribed', $subscribed, 'across '.Str::plural('list', $listCount, true)],
                       ['New', $newThisMonth, 'last 30 days'],
                       ['Unsubscribed', $unsubscribedThisMonth, 'last 30 days'],
                       ['Campaigns sent', $sentThisMonth, 'last 30 days']] as [$label, $value, $note])
                <div class="card p-4"><p class="muted text-sm">{{ $label }}</p><p class="mt-1 text-3xl font-semibold tabular-nums">{{ number_format($value) }}</p><p class="muted mt-1 text-xs">{{ $note }}</p></div>
            @endforeach
        </div>
        <section class="mt-8">
            <div class="mb-3 flex items-center justify-between"><h2 class="font-medium">Recent campaigns</h2><a href="{{ route('campaigns.create') }}" class="btn-primary">New campaign</a></div>
            @if ($recentCampaigns->isEmpty())
                <div class="card px-6 py-10 text-center"><p class="muted">No campaigns yet.</p></div>
            @else
                <div class="card divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($recentCampaigns as $campaign)
                        <a href="{{ route('campaigns.show', $campaign) }}" class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <span><span class="font-medium">{{ $campaign->name }}</span><span class="muted block text-xs">{{ $campaign->mailingList->name }}</span></span>
                            <x-status-badge :status="$campaign->status" />
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</x-layouts.app>
