// Small progressive enhancements for the server-rendered dashboard.

// No double submits: a submitted form's buttons are disabled and say "Saving..." (or their
// data-pending text) until the next page loads.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
    for (const button of form.querySelectorAll('button[type="submit"], button:not([type])')) {
        button.disabled = true;
        button.textContent = button.dataset.pending ?? 'Saving...';
    }
});

// Destructive actions ask first: <form data-confirm="Are you sure? ...">.
document.addEventListener(
    'submit',
    (event) => {
        const form = event.target;
        if (form instanceof HTMLFormElement && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    },
    true,
);

// Password fields: the eye button shows or hides the password. It stays hidden without JavaScript.
for (const button of document.querySelectorAll('[data-password-toggle]')) button.hidden = false;
document.addEventListener('click', (event) => {
    const button = event.target instanceof Element && event.target.closest('[data-password-toggle]');
    if (!button) return;
    const input = button.parentElement.querySelector('input');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    button.querySelector('[data-eye]').toggleAttribute('hidden', show);
    button.querySelector('[data-eye-off]').toggleAttribute('hidden', !show);
});

// Settings → Appearance: save the theme in this browser and apply it right away. The theme script
// in <head> applies it on every page load.
const themeSelect = document.querySelector('[data-theme-select]');
if (themeSelect) {
    const dark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;
    try {
        themeSelect.value = localStorage.getItem('theme') ?? 'system';
    } catch {
        // Storage can be blocked; the select then starts at System.
    }
    themeSelect.addEventListener('change', () => {
        const theme = themeSelect.value;
        try {
            localStorage.setItem('theme', theme);
        } catch {
            // Blocked storage: the choice lasts until the page reloads.
        }
        document.documentElement.classList.toggle('dark', theme === 'dark' || (theme === 'system' && dark()));
    });
}

// Campaign statistics update without a reload: every 3 s while sending, every 15 s after (opens and
// clicks keep coming in). Paused while the tab is hidden. When the status changes (sending → sent),
// the whole page reloads so the header and buttons match.
const liveStats = document.querySelector('[data-live-stats]');
if (liveStats) {
    const status = liveStats.dataset.status;
    const refresh = async () => {
        if (document.hidden) return schedule();
        try {
            const response = await fetch(liveStats.dataset.liveStats, { headers: { Accept: 'text/html' } });
            // A redirect means the session ended; the next navigation shows the login page.
            if (!response.ok || response.redirected) return;
            const next = new DOMParser().parseFromString(await response.text(), 'text/html').querySelector('[data-live-stats]');
            if (!next) return;
            if (next.dataset.status !== status) return window.location.reload();
            liveStats.replaceChildren(...next.childNodes);
        } catch {
            // Offline for a moment: try again on the next tick.
        }
        schedule();
    };
    const schedule = () => setTimeout(refresh, status === 'sending' ? 3000 : 15000);
    schedule();
}

// A page restored from the back/forward cache may show a logged-out or outdated state: reload it.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
});
