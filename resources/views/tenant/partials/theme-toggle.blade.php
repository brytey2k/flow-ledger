<div>
    <!-- Theme Mode -->
    <script data-navigate-once>
        (function() {
            if (!window.defaultThemeMode) {
                window.defaultThemeMode = 'light'; // light|dark|system
            }
            let themeMode;

            if (document.documentElement) {
                if (localStorage.getItem('sgh-theme')) {
                    themeMode = localStorage.getItem('sgh-theme');
                } else {
                    themeMode = window.defaultThemeMode;
                }

                if (themeMode === 'system') {
                    themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ?
                        'dark' :
                        'light';
                }

                document.documentElement.classList.add(themeMode);
            }
        })();
    </script>
    <!-- End of Theme Mode -->
</div>
