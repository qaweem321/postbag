<x-layouts.app title="Settings">
    <h1 class="mb-6 text-2xl font-semibold tracking-tight">Settings</h1>
    <section class="card max-w-xl space-y-4 p-6">
        <div>
            <h2 class="font-medium">Appearance</h2>
            <p class="muted text-sm">Saved in this browser. System follows your device.</p>
        </div>
        <div>
            <label class="label" for="theme">Theme</label>
            <select class="input" id="theme" data-theme-select>
                <option value="system">System</option>
                <option value="light">Light</option>
                <option value="dark">Dark</option>
            </select>
        </div>
    </section>
</x-layouts.app>
