<script>
    // Applied before first paint to avoid a flash of the wrong theme.
    // Light mode is the default: dark mode only activates when explicitly stored.
    (function () {
        var isDark = localStorage.getItem('theme') === 'dark';

        if (isDark) {
            document.documentElement.classList.add('dark');
        }

        var meta = document.getElementById('theme-color-meta');
        if (meta) {
            meta.setAttribute('content', isDark ? '#111827' : '#ffffff');
        }
    })();
</script>
