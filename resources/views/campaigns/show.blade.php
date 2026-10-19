@php use App\Enums\CampaignStatus; @endphp
<x-layouts.app :title="$campaign->name">
    <p class="mb-2 text-sm"><a href="{{ route('campaigns.index') }}" class="text-violet-700 hover:underline">← Campaigns</a></p>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight">{{ $campaign->name }}</h1>
                <x-status-badge :status="$campaign->status" />
            </div>
            <p class="muted mt-1 text-sm">“{{ $campaign->subject }}” to <a class="text-violet-700 underline" href="{{ route('lists.show', $campaign->mailingList) }}">{{ $campaign->mailingList->name }}</a>
                @if ($campaign->status === CampaignStatus::Scheduled) · scheduled for <x-date :value="$campaign->scheduled_at" time /> @endif
                @if ($campaign->started_at) · started <x-date :value="$campaign->started_at" time /> @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($campaign->status->isEditable())
                <a href="{{ route('campaigns.edit', $campaign) }}" class="btn-secondary">Edit</a>
            @endif
            @if ($campaign->status === CampaignStatus::Sending)
                <form method="post" action="{{ route('campaigns.cancel', $campaign) }}" data-confirm="Are you sure? Emails not sent yet won't be sent. Emails already sent can't be recalled.">
                    @csrf <button class="btn-danger" data-pending="Cancelling...">Cancel sending</button>
                </form>
            @elseif ($campaign->status !== CampaignStatus::Sending)
                <form method="post" action="{{ route('campaigns.destroy', $campaign) }}" data-confirm="Are you sure? &quot;{{ $campaign->name }}&quot; and its statistics will be deleted.">
                    @csrf @method('delete') <button class="btn-secondary text-red-700" data-pending="Deleting...">Delete</button>
                </form>
            @endif
        </div>
    </div>

    @if ($stats)
        @php
            $pct = fn (?float $rate) => $rate === null ? '–' : number_format($rate * 100, 1).'%';
            $done = $stats['recipients'] - $stats['queued'];
        @endphp
        @if ($campaign->status === CampaignStatus::Sending)
            <div class="card mb-6 p-5">
                <div class="mb-2 flex justify-between text-sm"><span class="font-medium">Sending…</span><span class="tabular-nums">{{ number_format($done) }} of {{ number_format($stats['recipients']) }}</span></div>
                <div class="h-2 overflow-hidden rounded-full bg-zinc-100"><div class="h-full bg-violet-600" style="width: {{ $stats['recipients'] ? round($done / $stats['recipients'] * 100) : 0 }}%"></div></div>
                <p class="muted mt-2 text-xs">Reload to update. Emails go out in batches through the queue worker.</p>
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
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2">
            <h2 class="mb-2 font-medium">Preview</h2>
            <iframe srcdoc="{{ $previewHtml }}" title="Email preview" sandbox="allow-same-origin" class="card h-[36rem] w-full bg-white"></iframe>
        </section>
        @if ($campaign->status->isEditable())
            <aside class="space-y-6">
                <form method="post" action="{{ route('campaigns.test', $campaign) }}" class="card space-y-3 p-5">
                    @csrf
                    <h2 class="font-medium">Send a test</h2>
                    <input class="input" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required aria-label="Test email address">
                    <button class="btn-secondary w-full" data-pending="Sending...">Send test email</button>
                </form>
                <div class="card space-y-3 p-5">
                    <h2 class="font-medium">Send</h2>
                    <p class="muted text-sm">Goes to {{ number_format($audience) }} subscribed {{ Str::plural('person', $audience) }} on “{{ $campaign->mailingList->name }}”.</p>
                    <form method="post" action="{{ route('campaigns.send', $campaign) }}" data-confirm="Are you sure? &quot;{{ $campaign->subject }}&quot; will be emailed to {{ number_format($audience) }} {{ Str::plural('person', $audience) }} now. This can't be undone.">
                        @csrf <button class="btn-primary w-full" data-pending="Starting..." @disabled($audience === 0)>Send now</button>
                    </form>
                    @if ($campaign->status === CampaignStatus::Scheduled)
                        <form method="post" action="{{ route('campaigns.unschedule', $campaign) }}">@csrf <button class="btn-secondary w-full">Unschedule</button></form>
                    @else
                        <form method="post" action="{{ route('campaigns.schedule', $campaign) }}" class="space-y-2">
                            @csrf
                            <label class="label" for="scheduled_at">Or schedule (UTC)</label>
                            <input class="input" id="scheduled_at" type="datetime-local" name="scheduled_at" required>
                            <button class="btn-secondary w-full" data-pending="Scheduling...">Schedule</button>
                        </form>
                    @endif
                </div>
            </aside>
        @endif
    </div>
</x-layouts.app>
