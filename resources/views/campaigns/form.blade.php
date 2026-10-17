<x-layouts.app :title="$campaign->exists ? 'Edit campaign' : 'New campaign'">
    <h1 class="mb-6 text-2xl font-semibold tracking-tight">{{ $campaign->exists ? 'Edit campaign' : 'New campaign' }}</h1>
    @if ($lists->isEmpty())
        <div class="card px-6 py-12 text-center">
            <p class="font-medium">Create a list first</p>
            <a href="{{ route('lists.create') }}" class="btn-primary mt-4">New list</a>
        </div>
    @else
    <form method="post" action="{{ $campaign->exists ? route('campaigns.update', $campaign) : route('campaigns.store') }}" class="card max-w-3xl space-y-4 p-6">
        @csrf
        @if ($campaign->exists) @method('put') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="name">Internal name</label><input class="input" id="name" name="name" value="{{ old('name', $campaign->name) }}" required maxlength="120" placeholder="October newsletter"></div>
            <div>
                <label class="label" for="list">Send to</label>
                <select class="input" id="list" name="list" required>
                    @foreach ($lists as $list)
                        <option value="{{ $list->uuid }}" @selected(old('list', $selectedList) === $list->uuid)>{{ $list->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div><label class="label" for="subject">Subject</label><input class="input" id="subject" name="subject" value="{{ old('subject', $campaign->subject) }}" required maxlength="200"></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="from_name">From name</label><input class="input" id="from_name" name="from_name" value="{{ old('from_name', $campaign->from_name) }}" required maxlength="120"></div>
            <div><label class="label" for="from_email">From email</label><input class="input" id="from_email" type="email" name="from_email" value="{{ old('from_email', $campaign->from_email) }}" required></div>
        </div>
        <div>
            <label class="label" for="body_markdown">Content (Markdown)</label>
            <textarea class="input font-mono" id="body_markdown" name="body_markdown" rows="16" required>{{ old('body_markdown', $campaign->body_markdown) }}</textarea>
            <p class="muted mt-1 text-xs">Use <code>@{{ name }}</code> and <code>@{{ email }}</code> to personalize. Links are tracked; an unsubscribe link is added automatically.</p>
        </div>
        <div class="flex gap-2">
            <button class="btn-primary">{{ $campaign->exists ? 'Save changes' : 'Save draft' }}</button>
            <a href="{{ $campaign->exists ? route('campaigns.show', $campaign) : route('campaigns.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
    @endif
</x-layouts.app>
