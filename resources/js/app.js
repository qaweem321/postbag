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

// A page restored from the back/forward cache may show a logged-out or outdated state: reload it.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
});
