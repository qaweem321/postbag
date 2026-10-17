<x-layouts.guest title="Log in">
    <div class="mb-6 flex justify-center">
        <span class="flex items-center gap-2 text-lg font-semibold"><img src="/favicon.svg" alt="" class="size-8"> Postbag</span>
    </div>
    <form method="post" action="{{ route('login') }}" class="card space-y-4 p-6">
        @csrf
        <h1 class="text-xl font-semibold">Log in</h1>
        @error('email')
            <p role="alert" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">{{ $message }}</p>
        @enderror
        <div>
            <label class="label" for="email">Email</label>
            <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </div>
        <div>
            <label class="label" for="password">Password</label>
            <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="accent-violet-700"> Keep me logged in</label>
        <button class="btn-primary w-full" data-pending="Logging in...">Log in</button>
    </form>
</x-layouts.guest>
