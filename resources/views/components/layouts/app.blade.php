@props(["title" => null])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Postbag: self-hosted newsletters with double opt-in, campaigns and one-click unsubscribe.">
    <title>{{ $title ? "$title · " : '' }}Postbag</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
            {{-- The logo always leads to the dashboard. --}}
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                <img src="/favicon.svg" alt="" class="size-7"> Postbag
            </a>
            <nav class="order-last -mx-1 flex w-full gap-1 overflow-x-auto md:order-none md:w-auto">
                @foreach ([['dashboard', 'Dashboard'], ['lists.index', 'Lists'], ['campaigns.index', 'Campaigns'], ['tokens.index', 'API tokens'], ['settings', 'Settings']] as [$route, $label])
                    <a href="{{ route($route) }}"
                       @class(['rounded-md px-2.5 py-1.5 text-sm whitespace-nowrap',
                               'bg-violet-50 dark:bg-violet-950 font-medium text-violet-800 dark:text-violet-200' => request()->routeIs(Str::before($route, '.').'*'),
                               'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' => ! request()->routeIs(Str::before($route, '.').'*')])>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="ml-auto flex items-center gap-3 text-sm">
                <span class="muted hidden sm:inline">{{ auth()->user()->email }}</span>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-secondary" data-pending="Logging out...">Log out</button>
                </form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <p role="status" class="mb-6 rounded-lg border border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-6 rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950 px-4 py-3 text-sm text-red-800 dark:text-red-200">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
