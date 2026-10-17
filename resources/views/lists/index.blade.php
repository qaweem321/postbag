<x-layouts.app title="Lists">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Lists</h1>
            <p class="muted mt-1 text-sm">People subscribe to a list; campaigns go to one list.</p>
        </div>
        <a href="{{ route('lists.create') }}" class="btn-primary">New list</a>
    </div>
    @if ($lists->isEmpty())
        <div class="card px-6 py-12 text-center">
            <p class="font-medium">No lists yet</p>
            <p class="muted mt-1 text-sm">Create a list, then add subscribers or connect a signup form.</p>
        </div>
    @else
        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>Name</th><th>Subscribed</th><th>Waiting for confirmation</th><th>Double opt-in</th><th>Created</th></tr></thead>
                <tbody>
                @foreach ($lists as $list)
                    <tr>
                        <td><a href="{{ route('lists.show', $list) }}" class="font-medium text-violet-700 hover:underline">{{ $list->name }}</a></td>
                        <td class="tabular-nums">{{ number_format($list->active_subscriptions_count) }}</td>
                        <td class="tabular-nums">{{ number_format($list->pending_count) }}</td>
                        <td>{{ $list->double_opt_in ? 'Yes' : 'No' }}</td>
                        <td class="muted whitespace-nowrap"><x-date :value="$list->created_at" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
