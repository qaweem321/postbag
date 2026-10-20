{{-- Sets light or dark before the first paint, so there is no white flash. Follows the saved choice
     (Settings → Appearance), or the device when it is "system", and later changes to either. --}}
<script>
    (function () {
        var media = matchMedia('(prefers-color-scheme: dark)');
        function apply() {
            var theme = null;
            try { theme = localStorage.getItem('theme'); } catch (e) {}
            document.documentElement.classList.toggle('dark', theme === 'dark' || (theme !== 'light' && media.matches));
        }
        apply();
        media.addEventListener('change', apply);
        addEventListener('storage', apply);
    })();
</script>
