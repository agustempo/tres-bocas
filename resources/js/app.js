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

// ── Auth modal store ──────────────────────────────────────────────────────────
Alpine.store('auth', {
    open:    false,
    form:    'login',   // 'login' | 'register'
    loading: false,
    errors:  {},

    // Login fields
    loginEmail:    '',
    loginPassword: '',
    loginRemember: false,

    // Register fields
    regName:     '',
    regEmail:    '',
    regPassword: '',
    regConfirm:  '',

    init() {
        // Global escape handler
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.open) this.hide();
        });
        // Auto-open from URL param: ?modal=login | ?modal=register
        const param = new URLSearchParams(window.location.search).get('modal');
        if (param === 'login' || param === 'register') {
            // Small delay so Alpine finishes hydrating the DOM
            setTimeout(() => this.show(param), 50);
        }
    },

    show(form = 'login') {
        this.form   = form;
        this.errors = {};
        this.open   = true;
        document.body.style.overflow = 'hidden';
        this._focusFirst();
    },

    hide() {
        this.open = false;
        document.body.style.overflow = '';
    },

    switchTo(form) {
        this.form   = form;
        this.errors = {};
        this._focusFirst();
    },

    _focusFirst() {
        setTimeout(() => {
            const attr = this.form === 'login'
                ? '[data-auth-login-first]'
                : '[data-auth-reg-first]';
            document.querySelector(attr)?.focus();
        }, 200);
    },

    // ── Login ─────────────────────────────────────────────────────────────────
    async submitLogin() {
        if (this.loading) return;
        this.loading = true;
        this.errors  = {};
        try {
            const resp = await fetch('/login', {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-CSRF-TOKEN':     document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    email:    this.loginEmail,
                    password: this.loginPassword,
                    remember: this.loginRemember,
                }),
            });

            if (resp.ok) {
                // Stay on current page, reload with auth state applied
                window.location.reload();
            } else if (resp.status === 422) {
                const data  = await resp.json();
                this.errors = data.errors || {};
                if (!Object.keys(this.errors).length && data.message) {
                    this.errors = { email: [data.message] };
                }
            } else if (resp.status === 419) {
                // CSRF expired – reload to get a fresh token
                window.location.reload();
            } else {
                this.errors = { _: ['Ocurrió un error. Intentá de nuevo.'] };
            }
        } catch {
            this.errors = { _: ['Error de red. Revisá tu conexión.'] };
        } finally {
            this.loading = false;
        }
    },

    // ── Register ──────────────────────────────────────────────────────────────
    async submitRegister() {
        if (this.loading) return;
        this.loading = true;
        this.errors  = {};
        try {
            const resp = await fetch('/register', {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-CSRF-TOKEN':     document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    name:                  this.regName,
                    email:                 this.regEmail,
                    password:              this.regPassword,
                    password_confirmation: this.regConfirm,
                }),
            });

            if (resp.ok) {
                window.location.reload();
            } else if (resp.status === 422) {
                const data  = await resp.json();
                this.errors = data.errors || {};
            } else if (resp.status === 419) {
                window.location.reload();
            } else {
                this.errors = { _: ['Ocurrió un error. Intentá de nuevo.'] };
            }
        } catch {
            this.errors = { _: ['Error de red. Revisá tu conexión.'] };
        } finally {
            this.loading = false;
        }
    },
});

Alpine.start();
