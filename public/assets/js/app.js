/* LifeLine Blood Bank — UI behaviour (vanilla JS, progressive enhancement). */
(() => {
    'use strict';

    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    /* ---------------- Sidebar drawer (mobile) ---------------- */
    const sidebar = $('#sidebar');
    const backdrop = $('#backdrop');
    const opener = $('[data-open-sidebar]');

    const setSidebar = (open) => {
        if (!sidebar) return;
        sidebar.classList.toggle('is-open', open);
        backdrop?.classList.toggle('is-open', open);
        opener?.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
        if (open) sidebar.querySelector('a, button')?.focus();
    };

    opener?.addEventListener('click', () => setSidebar(true));
    $$('[data-close-sidebar]').forEach((el) => el.addEventListener('click', () => setSidebar(false)));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar?.classList.contains('is-open')) setSidebar(false);
    });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => e.matches && setSidebar(false));

    /* ---------------- Dialogs ---------------- */
    const openDialog = (id) => {
        const dialog = document.getElementById(id);
        if (!dialog || dialog.open) return;
        dialog.showModal();
        const firstError = $('.has-error .input, .has-error .select', dialog);
        (firstError || $('input:not([type=hidden]):not([type=radio]), select', dialog))?.focus();
    };

    $$('button[data-open-dialog], a[data-open-dialog]').forEach((btn) =>
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openDialog(btn.dataset.openDialog);
        }),
    );

    document.addEventListener('click', (e) => {
        const closer = e.target.closest('[data-close-dialog]');
        if (closer) closer.closest('dialog')?.close();

        // Click on the backdrop (outside the dialog box) closes it.
        if (e.target instanceof HTMLDialogElement && e.target.classList.contains('dialog')) {
            const r = e.target.getBoundingClientRect();
            const inside = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
            if (!inside) e.target.close();
        }
    });

    // Re-open the dialog whose form just failed validation.
    const reopen = document.body.dataset.reopenDialog;
    if (reopen) openDialog(reopen);

    /* ---------------- Edit donation ---------------- */
    const editForm = $('[data-edit-form]');
    const clearErrors = (form) => {
        $$('.has-error', form).forEach((el) => el.classList.remove('has-error'));
        $$('.field-error, .notice.danger', form).forEach((el) => el.remove());
    };

    $$('[data-edit-donation]').forEach((btn) =>
        btn.addEventListener('click', () => {
            if (!editForm) return;
            const data = JSON.parse(btn.dataset.editDonation);
            clearErrors(editForm);
            editForm.action = editForm.dataset.actionBase + data.id;
            editForm.elements._id.value = data.id;

            Object.entries(data).forEach(([name, value]) => {
                const field = editForm.elements[name];
                if (!field || name === 'id') return;
                if (field instanceof RadioNodeList) {
                    field.value = String(value);
                } else {
                    field.value = value ?? '';
                }
            });

            openDialog('donation-edit');
        }),
    );

    /* ---------------- Confirm before destructive / important actions ---------------- */
    const confirmDialog = $('#confirm-dialog');
    let pendingForm = null;

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.dataset.confirm && form.dataset.confirmed !== '1' && confirmDialog) {
            e.preventDefault();
            pendingForm = form;
            $('#confirm-message', confirmDialog).textContent = form.dataset.confirm;
            const ok = $('[data-confirm-ok]', confirmDialog);
            ok.textContent = form.dataset.confirmLabel || 'Confirm';
            ok.classList.toggle('btn-primary', !('confirmDanger' in form.dataset));
            ok.classList.toggle('btn-danger', 'confirmDanger' in form.dataset);
            $('#confirm-title', confirmDialog).textContent =
                'confirmDanger' in form.dataset ? 'Please confirm' : 'Are you sure?';
            confirmDialog.showModal();
            ok.focus();
            return;
        }

        // Guard against double submits.
        const submit = $('button[type=submit]', form);
        if (submit && form.method.toLowerCase() === 'post') {
            requestAnimationFrame(() => {
                submit.disabled = true;
                submit.setAttribute('aria-busy', 'true');
            });
        }
    });

    // Pages restored from the back/forward cache must not keep buttons disabled.
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) $$('button[aria-busy=true]').forEach((b) => { b.disabled = false; b.removeAttribute('aria-busy'); });
    });

    $('[data-confirm-ok]', confirmDialog || document)?.addEventListener('click', () => {
        if (!pendingForm) return;
        pendingForm.dataset.confirmed = '1';
        confirmDialog.close();
        pendingForm.requestSubmit();
    });
    $('[data-confirm-cancel]', confirmDialog || document)?.addEventListener('click', () => {
        pendingForm = null;
        confirmDialog.close();
    });

    /* ---------------- Toasts ---------------- */
    const dismissToast = (toast) => {
        toast.classList.add('is-leaving');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };
    $$('.toast').forEach((toast) => {
        const timer = setTimeout(() => dismissToast(toast), toast.classList.contains('toast-error') ? 8000 : 5000);
        $('[data-dismiss-toast]', toast)?.addEventListener('click', () => {
            clearTimeout(timer);
            dismissToast(toast);
        });
    });

    /* ---------------- Live notifications ---------------- */
    const pollUrl = document.body.dataset.notifPoll;
    if (pollUrl) {
        const INTERVAL = Math.max(5, parseInt(document.body.dataset.pollSeconds || '30', 10)) * 1000;
        const inboxUrl = document.body.dataset.notifPage;
        const baseTitle = document.title.replace(/^\(\d+\+?\)\s*/, '');
        let latest = parseInt(document.body.dataset.notifLatest || '0', 10);
        let timer = null;
        let stopped = false;

        const setCount = (n) => {
            const label = n > 99 ? '99+' : String(n);
            $$('[data-notif-count]').forEach((el) => { el.textContent = label; el.hidden = n === 0; });
            $$('[data-notif-count-plain]').forEach((el) => { el.textContent = String(n); });
            $('[data-notif-bell]')?.setAttribute('aria-label', n ? `Notifications, ${n} unread` : 'Notifications');
            document.title = n ? `(${label}) ${baseTitle}` : baseTitle;
        };

        const el = (tag, className, text) => {
            const node = document.createElement(tag);
            if (className) node.className = className;
            if (text) node.textContent = text;
            return node;
        };

        const showToast = (item) => {
            const box = $('#toasts');
            if (!box) return;
            const toast = el('div', 'toast toast-notif');
            toast.setAttribute('role', 'status');

            const ico = el('span', `notif-icon tone-${item.tone}`);
            ico.innerHTML = `<svg class="icon" aria-hidden="true"><use href="#i-${item.icon}"></use></svg>`;

            const msg = el('a', 'toast-msg');
            msg.href = inboxUrl;
            msg.append(el('strong', '', item.title));
            if (item.body) msg.append(el('span', '', item.body));

            const close = el('button', 'toast-close');
            close.type = 'button';
            close.setAttribute('aria-label', 'Dismiss');
            close.innerHTML = '<svg class="icon icon-sm" aria-hidden="true"><use href="#i-x"></use></svg>';

            toast.append(ico, msg, close);
            box.append(toast);

            const t = setTimeout(() => dismissToast(toast), 8000);
            close.addEventListener('click', () => { clearTimeout(t); dismissToast(toast); });
        };

        const ring = () => {
            const bell = $('[data-notif-bell]');
            if (!bell) return;
            bell.classList.remove('is-ringing');
            void bell.offsetWidth; // restart the animation
            bell.classList.add('is-ringing');
        };

        const schedule = () => {
            clearTimeout(timer);
            if (!stopped && document.visibilityState === 'visible') timer = setTimeout(poll, INTERVAL);
        };

        async function poll() {
            try {
                const res = await fetch(`${pollUrl}?after=${latest}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                // Signed out (redirected to the login page) — stop polling.
                if (!res.ok || !(res.headers.get('content-type') || '').includes('application/json')) {
                    stopped = true;
                    return;
                }
                apply(await res.json());
            } catch {
                // Offline or server restarting — try again next tick.
            } finally {
                schedule();
            }
        }

        /** Same payload from polling and from the live stream. */
        function apply(data) {
            data.items.slice(-3).forEach(showToast);
            if (data.items.length) ring();
            latest = Math.max(latest, data.latest);
            setCount(data.unread);
            if (typeof data.pending === 'number') {
                $$('[data-pending-count]').forEach((n) => { n.textContent = String(data.pending); n.hidden = data.pending === 0; });
            }
        }

        const startPolling = () => {
            schedule();
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') poll();
                else clearTimeout(timer);
            });
        };

        setCount(parseInt(document.body.dataset.notifUnread || '0', 10));

        // Live stream (Server-Sent Events) when the server supports it; the
        // browser reconnects on its own. If it keeps failing before ever
        // delivering anything, fall back to polling.
        const streamUrl = document.body.dataset.notifStream;
        if (document.body.dataset.realtime === 'sse' && streamUrl && 'EventSource' in window) {
            let delivered = false;
            let failures = 0;
            const es = new EventSource(`${streamUrl}?after=${latest}`, { withCredentials: true });
            es.addEventListener('inbox', (e) => {
                delivered = true;
                failures = 0;
                apply(JSON.parse(e.data));
            });
            es.addEventListener('error', () => {
                failures += 1;
                if (!delivered && failures >= 3) {
                    es.close();
                    startPolling();
                }
            });
        } else {
            startPolling();
        }
    }

    /* ---------------- Filters: submit on change ---------------- */
    $$('form[data-auto-submit]').forEach((form) => {
        $$('select', form).forEach((s) => s.addEventListener('change', () => form.requestSubmit()));
        $$('input[type=search]', form).forEach((input) =>
            input.addEventListener('search', () => input.value === '' && form.requestSubmit()),
        );
    });

    /* ---------------- Request form: live stock check ---------------- */
    $$('[data-request-form]').forEach((form) => {
        const stock = JSON.parse(form.dataset.stock || '{}');
        const levels = form.dataset.levels ? JSON.parse(form.dataset.levels) : null;
        const compat = JSON.parse(form.dataset.compat || '{}');
        const notice = $('[data-stock-notice]', form);
        const message = $('[data-stock-message]', form);

        const check = () => {
            const type = form.elements.blood_type?.value;
            const bags = parseInt(form.elements.bags?.value || '0', 10);
            if (!type || !bags || !notice) return;

            // Hospitals see supply levels, never counts.
            if (levels) {
                const lv = levels[type];
                if (!lv || lv.level === 'healthy') {
                    notice.hidden = true;
                    return;
                }
                message.textContent = `${type} supply is ${lv.label.toLowerCase()} right now. You can still submit: ` +
                    'the blood bank may send a compatible group or a partial amount.';
                notice.hidden = false;
                return;
            }

            const available = stock[type] ?? 0;
            if (bags <= available) {
                notice.hidden = true;
                return;
            }

            const compatibleTotal = (compat[type] || []).reduce((sum, g) => sum + (stock[g] ?? 0), 0);
            const alternatives = (compat[type] || []).filter((g) => g !== type && (stock[g] ?? 0) > 0);
            message.textContent =
                `Only ${available} bag${available === 1 ? '' : 's'} of ${type} in stock right now. You can still submit: ` +
                (compatibleTotal >= bags
                    ? `the blood bank can make up the difference from compatible groups (${alternatives.join(', ')}).`
                    : 'the blood bank may only be able to send part of it.');
            notice.hidden = false;
        };

        form.addEventListener('change', check);
        form.addEventListener('input', check);
        check();
    });

    /* ---------------- Allocate & dispatch (staff) ---------------- */
    const allocDialog = $('#allocate-dialog');
    const allocForm = $('[data-allocate-form]', allocDialog || document);

    if (allocDialog && allocForm) {
        const rows = $('[data-allocate-rows]', allocForm);
        const totalEl = $('[data-allocate-total]', allocForm);
        const partial = $('[data-allocate-partial]', allocForm);
        const errorBox = $('[data-allocate-error]', allocForm);
        const errorText = $('[data-allocate-error-text]', allocForm);
        const submit = $('[data-allocate-submit]', allocForm);
        let requested = 0;

        const update = () => {
            let total = 0;
            let problem = '';
            $$('input[type=number]', rows).forEach((input) => {
                const n = parseInt(input.value || '0', 10);
                const max = parseInt(input.max, 10);
                if (Number.isNaN(n) || n < 0) problem = 'Enter whole numbers only.';
                else if (n > max) problem = `Only ${max} bag${max === 1 ? '' : 's'} of ${input.dataset.code} in stock.`;
                total += Number.isNaN(n) ? 0 : n;
            });
            if (!problem && total > requested) problem = `That is ${total} bags, but only ${requested} were requested.`;
            if (!problem && total === 0) problem = 'Allocate at least one bag.';

            totalEl.textContent = `${total} / ${requested}`;
            partial.hidden = !!problem || total >= requested;
            errorBox.hidden = !problem;
            errorText.textContent = problem;
            submit.disabled = !!problem;
        };

        const open = (data) => {
            requested = data.bags;
            allocForm.action = data.action;
            $('[data-allocate-summary]', allocForm).textContent =
                `${data.bags} bag${data.bags === 1 ? '' : 's'} of ${data.type} for ${data.hospital} · ${data.doctor}` +
                (data.priority !== 'routine' ? ` · ${data.priority.toUpperCase()}` : '');

            rows.replaceChildren(...data.options.map((opt) => {
                const row = document.createElement('label');
                row.className = 'alloc-row' + (opt.stock === 0 ? ' is-empty' : '');
                const code = document.createElement('span');
                code.className = 'blood-code';
                code.textContent = opt.code;
                const info = document.createElement('span');
                info.className = 'alloc-info';
                info.textContent = `${opt.stock} in stock` + (opt.exact ? ' · requested group' : ' · compatible') +
                    (opt.expiry ? ` · ${opt.expiry}` : '');
                const input = document.createElement('input');
                input.className = 'input';
                input.type = 'number';
                input.inputMode = 'numeric';
                input.min = '0';
                input.max = String(opt.stock);
                input.name = `alloc[${opt.code}]`;
                input.value = String(opt.suggested);
                input.dataset.code = opt.code;
                input.disabled = opt.stock === 0;
                input.setAttribute('aria-label', `Bags of ${opt.code}`);
                row.append(code, info, input);
                return row;
            }));

            update();
            allocDialog.showModal();
            $('input:not([disabled])', rows)?.focus();
        };

        rows.addEventListener('input', update);

        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement) || !form.dataset.allocate) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            open(JSON.parse(form.dataset.allocate));
        }, true);
    }

    /* ---------------- Sign-in: demo accounts & Caps Lock ---------------- */
    $$('[data-demo-user]').forEach((chip) =>
        chip.addEventListener('click', () => {
            const user = $('#username');
            const pass = $('#password');
            if (!user || !pass) return;
            user.value = chip.dataset.demoUser;
            pass.value = chip.dataset.demoPass;
            $$('[data-demo-user]').forEach((c) => c.classList.toggle('is-picked', c === chip));
            $('.auth-submit')?.focus();
        }),
    );

    const capsHint = $('[data-caps-hint]');
    const passInput = $('#password');
    if (capsHint && passInput) {
        const caps = (e) => { if (e.getModifierState) capsHint.hidden = !e.getModifierState('CapsLock'); };
        passInput.addEventListener('keydown', caps);
        passInput.addEventListener('keyup', caps);
        passInput.addEventListener('blur', () => { capsHint.hidden = true; });
    }

    /* ---------------- Password visibility ---------------- */
    $$('[data-toggle-password]').forEach((btn) =>
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.querySelector('use')?.setAttribute('href', show ? '#i-eye-off' : '#i-eye');
        }),
    );
})();
