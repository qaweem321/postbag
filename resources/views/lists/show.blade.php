<x-layouts.app :title="$list->name">
    <p class="mb-2 text-sm"><a href="{{ route('lists.index') }}" class="text-violet-700 dark:text-violet-400 hover:underline">← Lists</a></p>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $list->name }}</h1>
            <p class="muted mt-1 text-sm">{{ number_format($list->active_subscriptions_count) }} subscribed · {{ $list->double_opt_in ? 'double opt-in' : 'single opt-in' }} · list ID <code class="text-xs">{{ $list->uuid }}</code></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('campaigns.create', ['list' => $list->uuid]) }}" class="btn-primary">New campaign</a>
            <a href="{{ route('lists.edit', $list) }}" class="btn-secondary">Edit</a>
            <form method="post" action="{{ route('lists.destroy', $list) }}" data-confirm="Are you sure? &quot;{{ $list->name }}&quot; and all its subscriptions will be deleted. This can't be undone.">
                @csrf @method('delete')
                <button class="btn-secondary text-red-700 dark:text-red-400" data-pending="Deleting...">Delete</button>
            </form>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2">
            <form method="get" class="mb-4 flex flex-wrap gap-2">
                <input class="input max-w-xs" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by email" aria-label="Search by email">
                <select class="input w-auto" name="status" aria-label="Filter by status">
                    <option value="">Any status</option>
                    @foreach (\App\Enums\SubscriptionStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ ucfirst($status->value) }}</option>
                    @endforeach
                </select>
                <button class="btn-secondary" data-pending="Searching...">Filter</button>
            </form>
            @if ($subscriptions->isEmpty())
                <div class="card px-6 py-12 text-center"><p class="font-medium">No subscribers {{ ($filters['q'] ?? $filters['status'] ?? null) ? 'match these filters' : 'yet' }}</p></div>
            @else
                <div class="card overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Email</th><th>Status</th><th>Source</th><th>Since</th><th><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody>
                        @foreach ($subscriptions as $subscription)
                            <tr>
                                <td>
                                    <span class="font-medium">{{ $subscription->subscriber->email }}</span>
                                    @if ($subscription->subscriber->name)<span class="muted block text-xs">{{ $subscription->subscriber->name }}</span>@endif
                                    @if ($subscription->subscriber->status->isSuppressed())<x-status-badge :status="$subscription->subscriber->status" class="mt-1" />@endif
                                </td>
                                <td><x-status-badge :status="$subscription->status" /></td>
                                <td class="muted">{{ $subscription->source }}</td>
                                <td class="muted whitespace-nowrap"><x-date :value="$subscription->confirmed_at ?? $subscription->created_at" /></td>
                                <td class="text-right">
                                    @if ($subscription->status !== \App\Enums\SubscriptionStatus::Unsubscribed)
                                        <form method="post" action="{{ route('lists.unsubscribe', [$list, $subscription]) }}" data-confirm="Are you sure? {{ $subscription->subscriber->email }} will stop receiving this list.">
                                            @csrf
                                            <button class="text-sm text-red-700 dark:text-red-400 hover:underline" data-pending="...">Unsubscribe</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $subscriptions->links() }}</div>
            @endif
        </section>

        <aside class="space-y-6">
            <form method="post" action="{{ route('lists.subscribers.store', $list) }}" class="card space-y-3 p-5">
                @csrf
                <h2 class="font-medium">Add a subscriber</h2>
                <div><label class="label" for="email">Email</label><input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required></div>
                <div><label class="label" for="name">Name (optional)</label><input class="input" id="name" name="name" value="{{ old('name') }}" maxlength="120"></div>
                @if ($list->double_opt_in)
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="skip_confirmation" value="1" class="mt-0.5 accent-violet-700"> <span>Skip the confirmation email <span class="muted block">Only when this person already agreed to receive the list.</span></span></label>
                @endif
                <button class="btn-primary w-full">Add subscriber</button>
            </form>
            <div class="card p-5 text-sm">
                <h2 class="mb-2 font-medium">Subscribe through the API</h2>
                <pre class="overflow-x-auto rounded bg-zinc-900 p-3 text-xs text-zinc-100">curl -X POST {{ url("/api/v1/lists/{$list->uuid}/subscribers") }} \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -d email=jane@example.com</pre>
                <p class="muted mt-2">Create a token with the “write” permission under <a class="text-violet-700 dark:text-violet-400 underline" href="{{ route('tokens.index') }}">API tokens</a>. The WordPress plugin uses the same endpoint.</p>
            </div>
        </aside>
    </div>
</x-layouts.app>
