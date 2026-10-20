@php use App\Enums\CampaignStatus; @endphp
<x-layouts.app :title="$campaign->name">
    <p class="mb-2 text-sm"><a href="{{ route('campaigns.index') }}" class="text-violet-700 dark:text-violet-400 hover:underline">← Campaigns</a></p>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight">{{ $campaign->name }}</h1>
                <x-status-badge :status="$campaign->status" />
            </div>
            <p class="muted mt-1 text-sm">“{{ $campaign->subject }}” to <a class="text-violet-700 dark:text-violet-400 underline" href="{{ route('lists.show', $campaign->mailingList) }}">{{ $campaign->mailingList->name }}</a>
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
                    @csrf @method('delete') <button class="btn-secondary text-red-700 dark:text-red-400" data-pending="Deleting...">Delete</button>
                </form>
            @endif
        </div>
    </div>

    @if ($stats)
        @include('campaigns._stats')
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
