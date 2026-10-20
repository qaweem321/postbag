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

// Live regions update without a reload: <div id="..." data-live="URL" data-live-every="ms" data-live-key="...">.
// Every few ms the URL (default: this page) is fetched and the element with the same id swapped in.
// A copy without data-live-every means nothing is changing any more, so polling stops. A changed
// data-live-key (a campaign going from sending to sent) reloads the page so header and buttons match.
// Paused while the tab is hidden; a redirect (session ended) stops it.
for (const region of document.querySelectorAll('[data-live]')) {
    const url = region.dataset.live || window.location.href;
    const schedule = () => {
        const every = Number(region.dataset.liveEvery);
        if (every) setTimeout(tick, every);
    };
    const tick = async () => {
        if (!document.hidden) {
            try {
                const response = await fetch(url, { headers: { Accept: 'text/html' } });
                if (!response.ok || response.redirected) return;
                const next = new DOMParser().parseFromString(await response.text(), 'text/html').getElementById(region.id);
                if (next) {
                    if (next.dataset.liveKey !== region.dataset.liveKey) return window.location.reload();
                    region.replaceChildren(...next.childNodes);
                    region.dataset.liveEvery = next.dataset.liveEvery ?? '';
                }
            } catch {
                // Offline for a moment: try again on the next tick.
            }
        }
        schedule();
    };
    schedule();
}

// A list's page checks every 30 s for people who subscribed since it loaded, and offers to show them
// instead of moving rows while someone reads or clicks the table.
const newSubscribers = document.querySelector('[data-new-subscribers]');
if (newSubscribers) {
    const check = async () => {
        if (!document.hidden) {
            try {
                const response = await fetch(newSubscribers.dataset.newSubscribers, { headers: { Accept: 'application/json' } });
                if (!response.ok || response.redirected) return;
                const { count } = await response.json();
                if (count > 0) {
                    newSubscribers.querySelector('[data-count]').textContent =
                        `${count} new ${count === 1 ? 'subscriber' : 'subscribers'}`;
                    newSubscribers.hidden = false;
                }
            } catch {
                // Try again on the next check.
            }
        }
        setTimeout(check, 30000);
    };
    setTimeout(check, 30000);
}

// A page restored from the back/forward cache may show a logged-out or outdated state: reload it.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
});
