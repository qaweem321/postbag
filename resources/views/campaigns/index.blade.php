<x-layouts.app title="Campaigns">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold tracking-tight">Campaigns</h1>
        <a href="{{ route('campaigns.create') }}" class="btn-primary">New campaign</a>
    </div>
    @if ($campaigns->isEmpty())
        <div class="card px-6 py-12 text-center">
            <p class="font-medium">No campaigns yet</p>
            <p class="muted mt-1 text-sm">Write one, send yourself a test, then send it to a list.</p>
        </div>
    @else
        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>Campaign</th><th>List</th><th>Status</th><th>Recipients</th><th>Date</th></tr></thead>
                <tbody>
                @foreach ($campaigns as $campaign)
                    <tr>
                        <td><a href="{{ route('campaigns.show', $campaign) }}" class="font-medium text-violet-700 hover:underline">{{ $campaign->name }}</a><span class="muted block text-xs">{{ $campaign->subject }}</span></td>
                        <td>{{ $campaign->mailingList->name }}</td>
                        <td><x-status-badge :status="$campaign->status" /></td>
                        <td class="tabular-nums">{{ $campaign->status->isEditable() ? '–' : number_format($campaign->recipients_count) }}</td>
                        <td class="muted whitespace-nowrap"><x-date :value="$campaign->finished_at ?? $campaign->scheduled_at ?? $campaign->updated_at" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $campaigns->links() }}</div>
    @endif
</x-layouts.app>
