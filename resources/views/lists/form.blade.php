<x-layouts.app :title="$list->exists ? 'Edit list' : 'New list'">
    <h1 class="mb-6 text-2xl font-semibold tracking-tight">{{ $list->exists ? 'Edit list' : 'New list' }}</h1>
    <form method="post" action="{{ $list->exists ? route('lists.update', $list) : route('lists.store') }}" class="card max-w-2xl space-y-4 p-6">
        @csrf
        @if ($list->exists) @method('put') @endif
        <div>
            <label class="label" for="name">Name</label>
            <input class="input" id="name" name="name" value="{{ old('name', $list->name) }}" maxlength="120" required placeholder="Weekly digest">
        </div>
        <div>
            <label class="label" for="description">Description</label>
            <textarea class="input" id="description" name="description" rows="3" maxlength="1000">{{ old('description', $list->description) }}</textarea>
        </div>
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="double_opt_in" value="1" class="mt-0.5 accent-violet-700" @checked(old('double_opt_in', $list->double_opt_in))>
            <span><span class="font-medium">Double opt-in</span><br><span class="muted">New subscribers confirm by email first. Recommended: it keeps typos and fake signups off the list, and is expected under GDPR.</span></span>
        </label>
        <div class="flex gap-2">
            <button class="btn-primary">{{ $list->exists ? 'Save changes' : 'Create list' }}</button>
            <a href="{{ $list->exists ? route('lists.show', $list) : route('lists.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</x-layouts.app>
