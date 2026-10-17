<x-layouts.app title="API tokens">
    <h1 class="text-2xl font-semibold tracking-tight">API tokens</h1>
    <p class="muted mt-1 mb-6 text-sm">For the WordPress plugin and your own apps. Send it as <code>Authorization: Bearer &lt;token&gt;</code>.</p>
    @if (session('new_token'))
        <div class="card mb-6 border-violet-300 p-5">
            <p class="font-medium">Your new token</p>
            <p class="muted text-sm">Copy it now. Only a hash is stored, so it can't be shown again.</p>
            <code class="mt-2 block break-all rounded bg-zinc-900 p-3 text-sm text-zinc-100">{{ session('new_token') }}</code>
        </div>
    @endif
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2">
            @if ($tokens->isEmpty())
                <div class="card px-6 py-12 text-center"><p class="font-medium">No tokens yet</p></div>
            @else
                <div class="card overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Name</th><th>Permissions</th><th>Last used</th><th>Created</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($tokens as $token)
                            <tr>
                                <td class="font-medium">{{ $token->name }}</td>
                                <td>{{ implode(', ', $token->abilities) }}</td>
                                <td class="muted whitespace-nowrap"><x-date :value="$token->last_used_at" /></td>
                                <td class="muted whitespace-nowrap"><x-date :value="$token->created_at" /></td>
                                <td class="text-right">
                                    <form method="post" action="{{ route('tokens.destroy', $token->id) }}" data-confirm="Are you sure? Anything using &quot;{{ $token->name }}&quot; stops working immediately.">
                                        @csrf @method('delete') <button class="text-sm text-red-700 hover:underline" data-pending="...">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
        <form method="post" action="{{ route('tokens.store') }}" class="card h-fit space-y-3 p-5">
            @csrf
            <h2 class="font-medium">New token</h2>
            <div><label class="label" for="token-name">Name</label><input class="input" id="token-name" name="name" required maxlength="100" placeholder="WordPress site"></div>
            <fieldset class="space-y-1 text-sm">
                <legend class="label">Permissions</legend>
                <label class="flex items-center gap-2"><input type="checkbox" name="abilities[]" value="read" checked class="accent-violet-700"> Read lists, subscribers and campaigns</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="abilities[]" value="write" checked class="accent-violet-700"> Subscribe and unsubscribe people</label>
            </fieldset>
            <button class="btn-primary w-full" data-pending="Creating...">Create token</button>
        </form>
    </div>
</x-layouts.app>
