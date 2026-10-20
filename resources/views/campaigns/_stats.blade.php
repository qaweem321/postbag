@php use App\Enums\CampaignStatus; @endphp
{{-- Campaign statistics. Rendered in campaigns/show and by the stats route, which app.js polls (a
     live region) so the numbers update without reloading. --}}
{{-- Every 3 s while sending, every 15 s after: opens and clicks keep coming in. --}}
<div id="campaign-stats" data-live="{{ route('campaigns.stats', $campaign) }}" data-live-key="{{ $campaign->status->value }}"
     data-live-every="{{ $campaign->status === CampaignStatus::Sending ? 3000 : 15000 }}">
    @php
        $pct = fn (?float $rate) => $rate === null ? '–' : number_format($rate * 100, 1).'%';
        $done = $stats['recipients'] - $stats['queued'];
    @endphp
    @if ($campaign->status === CampaignStatus::Sending)
        <div class="card mb-6 p-5">
            <div class="mb-2 flex justify-between text-sm"><span class="font-medium">Sending…</span><span class="tabular-nums">{{ number_format($done) }} of {{ number_format($stats['recipients']) }}</span></div>
            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"><div class="h-full bg-violet-600" style="width: {{ $stats['recipients'] ? round($done / $stats['recipients'] * 100) : 0 }}%"></div></div>
            <p class="muted mt-2 text-xs">Updates automatically. Emails go out in batches through the queue worker.</p>
        </div>
    @endif
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([['Delivered', number_format($stats['sent']), $stats['failed'] ? number_format($stats['failed']).' failed' : 'no failures'],
                   ['Opened', $pct($stats['open_rate']), number_format($stats['opened']).' people'],
                   ['Clicked', $pct($stats['click_rate']), number_format($stats['clicked']).' people'],
                   ['Unsubscribed', number_format($stats['unsubscribed']), 'after this campaign']] as [$label, $value, $note])
            <div class="card p-4"><p class="muted text-sm">{{ $label }}</p><p class="mt-1 text-3xl font-semibold tabular-nums">{{ $value }}</p><p class="muted mt-1 text-xs">{{ $note }}</p></div>
        @endforeach
    </div>
    @if ($stats['links'])
        <div class="card mb-6 overflow-x-auto">
            <table class="table">
                <thead><tr><th>Link</th><th class="text-right">Clicks</th></tr></thead>
                <tbody>
                @foreach ($stats['links'] as $link)
                    <tr><td class="break-all">{{ $link['url'] }}</td><td class="text-right tabular-nums">{{ number_format($link['clicks']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <p class="muted mb-6 text-xs">Opens are approximate: some mail apps load images for privacy (counted as opens), others block them (not counted). Clicks are exact.</p>
</div>
