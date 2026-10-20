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

// A page restored from the back/forward cache may show a logged-out or outdated state: reload it.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
});
