<script>
    (() => {
        const preference = localStorage.getItem('fincontrol-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        document.documentElement.classList.toggle(
            'dark',
            preference === 'dark' || (preference !== 'light' && prefersDark),
        );
    })();
</script>
