{{-- A password field with an eye button that shows or hides what was typed (wired up in app.js). --}}
<div class="relative">
    <input {{ $attributes->merge(['type' => 'password'])->class('input pr-10') }}>
    <button type="button" data-password-toggle aria-label="Show password" hidden
        class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 focus-visible:outline-2 focus-visible:outline-violet-600">
        <svg data-eye aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>
        </svg>
        <svg data-eye-off aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden>
            <path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-2.2 3.2"/><path d="M6.6 6.6A17.4 17.4 0 0 0 2 12s3.5 7 10 7a10.6 10.6 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="M2 2l20 20"/>
        </svg>
    </button>
</div>
