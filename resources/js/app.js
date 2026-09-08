import TomSelect from 'tom-select';
import Alpine from 'alpinejs';

const THEME_STORAGE_KEY = 'sgh-theme';
const SIDEBAR_COLLAPSED_STORAGE_KEY = 'sgh-sidebar-collapsed';

Alpine.data('dropdown', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    },
}));

// Like `dropdown`, but teleports its panel to <body> and positions it with
// fixed coordinates so it can escape an `overflow-x-auto` table wrapper
// instead of being clipped by it.
Alpine.data('tableDropdown', () => ({
    open: false,
    x: 0,
    y: 0,
    toggle(event) {
        if (!this.open) {
            const rect = event.currentTarget.getBoundingClientRect();
            this.x = rect.right + window.scrollX;
            this.y = rect.bottom + window.scrollY;
        }
        this.open = !this.open;
    },
    close() {
        this.open = false;
    },
}));

Alpine.data('sidebarCollapse', () => ({
    collapsed: false,
    hovering: false,
    init() {
        try {
            this.collapsed = localStorage.getItem(SIDEBAR_COLLAPSED_STORAGE_KEY) === '1';
        } catch {
            // localStorage unavailable (private browsing) — falls back to expanded.
        }
    },
    toggle() {
        this.collapsed = !this.collapsed;
        // Reset hover-expand immediately so the sidebar collapses on click even
        // though the pointer is still resting over it (over the toggle button).
        this.hovering = false;
        try {
            localStorage.setItem(SIDEBAR_COLLAPSED_STORAGE_KEY, this.collapsed ? '1' : '0');
        } catch {
            // localStorage unavailable (private browsing) — collapse state just won't persist.
        }
    },
}));

Alpine.store('sidebarDrawer', {
    open: false,
    show() {
        this.open = true;
    },
    hide() {
        this.open = false;
    },
});

Alpine.data('themeToggle', () => ({
    dark: false,
    init() {
        this.dark = document.documentElement.classList.contains('dark');
    },
    toggleTheme() {
        this.dark = !this.dark;
        const mode = this.dark ? 'dark' : 'light';
        document.documentElement.classList.remove('light', 'dark');
        document.documentElement.classList.add(mode);
        try {
            localStorage.setItem(THEME_STORAGE_KEY, mode);
        } catch {
            // localStorage unavailable (private browsing) — theme just won't persist.
        }
    },
}));

Alpine.store('modal', {
    openId: null,
    show(id) {
        this.openId = id;
    },
    hide() {
        this.openId = null;
    },
});

Alpine.store('attachmentPreview', {
    open: false,
    loading: false,
    url: '',
    name: '',
    show(url, name) {
        this.url = url;
        this.name = name;
        this.loading = true;
        this.open = true;
        document.body.classList.add('overflow-hidden');
    },
    loaded() {
        this.loading = false;
    },
    hide() {
        this.open = false;
        this.loading = false;
        this.url = '';
        this.name = '';
        document.body.classList.remove('overflow-hidden');
    },
});

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-tom-select]').forEach((el) => {
        new TomSelect(el, {
            plugins: el.multiple ? ['remove_button'] : [],
            placeholder: el.dataset.tomSelectPlaceholder || 'Search...',
            maxOptions: null,
            closeAfterSelect: false,
        });
    });
});

const bentoCells = document.querySelectorAll('.bento .cell');

if (bentoCells.length && 'IntersectionObserver' in window) {
    const bentoObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    bentoObserver.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15 },
    );

    bentoCells.forEach((cell, index) => {
        cell.style.setProperty('--i', String(index % 8));
        bentoObserver.observe(cell);
    });
} else {
    bentoCells.forEach((cell) => cell.classList.add('is-visible'));
}
