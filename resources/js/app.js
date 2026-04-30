import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ── Theme store ───────────────────────────────────────────────────────────────
Alpine.store('theme', {
    current: 'system',
    _dark: false,

    get isDark() { return this._dark; },

    init() {
        this.current = localStorage.getItem('theme') || 'system';
        this._apply();

        // React to OS preference changes when "system" is selected
        window.matchMedia('(prefers-color-scheme: dark)')
              .addEventListener('change', () => {
                  if (this.current === 'system') this._apply();
              });
    },

    set(value) {
        this.current = value;
        localStorage.setItem('theme', value);
        this._apply();
    },

    _apply() {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        this._dark = this.current === 'dark' ||
                     (this.current === 'system' && prefersDark);
        document.documentElement.classList.toggle('dark', this._dark);
    },
});

Alpine.start();
