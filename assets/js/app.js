/* ==========================================================================
   Theological Center Portal - small UX helpers. Plain JS, no libraries
   needed (uses Bootstrap's modal if it's loaded, otherwise falls back to
   the browser's own confirm box).
   ========================================================================== */
(function () {
    'use strict';

    var ready = function (fn) {
        if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
    };

    /* ---------- 1. Busy buttons: stop double submits ------------------ */
    function setBusy(form) {
        var btn = form.querySelector('button[type="submit"], button:not([type])');
        if (!btn || btn.dataset.busy) { return; }
        btn.dataset.busy = '1';
        btn.dataset.label = btn.innerHTML;
        btn.style.minWidth = btn.offsetWidth + 'px';
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Please wait…';
        // Disable a tick later so the button's own value still submits.
        setTimeout(function () { btn.disabled = true; }, 0);
    }

    // Restore buttons if the user comes back with the Back button.
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('button[data-busy]').forEach(function (btn) {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.label;
            delete btn.dataset.busy;
        });
    });

    /* ---------- 2. Styled confirm dialog ------------------------------ */
    var modalEl = null, modal = null, pendingAction = null;

    function askConfirm(message, okLabel, danger, onOk) {
        if (!window.bootstrap || !window.bootstrap.Modal) {
            if (window.confirm(message)) { onOk(); }
            return;
        }
        if (!modalEl) {
            modalEl = document.getElementById('confirmModal');
            modal = new bootstrap.Modal(modalEl);
            modalEl.querySelector('[data-confirm-yes]').addEventListener('click', function () {
                var fn = pendingAction; pendingAction = null;
                modal.hide();
                if (fn) { fn(); }
            });
        }
        modalEl.querySelector('.confirm-message').textContent = message;
        var yes = modalEl.querySelector('[data-confirm-yes]');
        yes.textContent = okLabel || 'Continue';
        yes.className = 'btn ' + (danger ? 'btn-danger' : 'btn-primary');
        pendingAction = onOk;
        modal.show();
    }

    /* ---------- 3. Password show/hide + strength ---------------------- */
    var EYE = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    var EYE_OFF = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    function initPasswordFields() {
        document.querySelectorAll('.password-field').forEach(function (wrap) {
            var input = wrap.querySelector('input');
            if (!input || wrap.querySelector('.pw-toggle')) { return; }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pw-toggle';
            btn.setAttribute('aria-label', 'Show password');
            btn.innerHTML = EYE;
            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.innerHTML = show ? EYE_OFF : EYE;
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
            wrap.appendChild(btn);
        });

        document.querySelectorAll('input[data-strength]').forEach(function (input) {
            var meter = input.closest('.mb-3') && input.closest('.mb-3').querySelector('.strength-meter');
            if (!meter) { return; }
            input.addEventListener('input', function () {
                var v = input.value, score = 0;
                if (v.length >= 6) { score++; }
                if (v.length >= 10) { score++; }
                if (/[A-Z]/.test(v) && /[a-z]/.test(v)) { score++; }
                if (/\d/.test(v)) { score++; }
                if (/[^A-Za-z0-9]/.test(v)) { score++; }
                var level = v.length === 0 ? 0 : (score <= 1 ? 1 : score <= 3 ? 2 : 3);
                meter.className = 'strength-meter level-' + level;
                meter.setAttribute('data-label', ['', 'Weak', 'Okay', 'Strong'][level]);
            });
        });
    }

    /* ---------- 4. Drag-and-drop file picker with preview ------------- */
    function initDropzones() {
        document.querySelectorAll('[data-dropzone]').forEach(function (zone) {
            var input = zone.querySelector('input[type="file"]');
            var maxMb = parseFloat(zone.getAttribute('data-max-mb') || '0');
            var errorBox = zone.querySelector('.dz-error');

            function show(file) {
                errorBox.textContent = '';
                zone.classList.remove('has-error');
                if (!file) { zone.classList.remove('has-file'); return; }

                if (maxMb && file.size > maxMb * 1024 * 1024) {
                    zone.classList.add('has-error');
                    zone.classList.remove('has-file');
                    errorBox.textContent = 'That file is ' + (file.size / 1048576).toFixed(1) + 'MB. Please choose one under ' + maxMb + 'MB.';
                    input.value = '';
                    return;
                }

                zone.classList.add('has-file');
                zone.querySelector('.dz-name').textContent = file.name;
                zone.querySelector('.dz-size').textContent = (file.size / 1024 < 1024)
                    ? Math.round(file.size / 1024) + ' KB'
                    : (file.size / 1048576).toFixed(1) + ' MB';

                var img = zone.querySelector('.dz-preview');
                if (/^image\//.test(file.type) && window.URL) {
                    img.src = URL.createObjectURL(file);
                    zone.classList.add('is-image');
                } else {
                    img.removeAttribute('src');
                    zone.classList.remove('is-image');
                }
            }

            input.addEventListener('change', function () { show(input.files[0]); });

            ['dragenter', 'dragover'].forEach(function (ev) {
                zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('is-dragging'); });
            });
            ['dragleave', 'drop'].forEach(function (ev) {
                zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('is-dragging'); });
            });
            zone.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    show(input.files[0]);
                }
            });
        });
    }

    /* ---------- 5. Toast-style flash messages ------------------------- */
    function initToasts() {
        document.querySelectorAll('.flash').forEach(function (el) {
            var close = el.querySelector('.flash-close');
            var dismiss = function () {
                el.classList.add('is-leaving');
                setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 300);
            };
            if (close) { close.addEventListener('click', dismiss); }
            if (el.classList.contains('flash-success')) { setTimeout(dismiss, 5000); }
        });
    }

    ready(function () {
        initPasswordFields();
        initDropzones();
        initToasts();

        // Forms: confirm first (if asked), then lock the button. Listens on the
        // document, so forms added later (e.g. the live invigilation table) work too.
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (e.defaultPrevented || !form || (form.getAttribute('method') || '').toLowerCase() !== 'post') { return; }
            if (form.hasAttribute('data-confirm') && !form.dataset.confirmed) {
                e.preventDefault();
                askConfirm(form.getAttribute('data-confirm'), form.getAttribute('data-confirm-ok'),
                    form.hasAttribute('data-confirm-danger'), function () {
                        form.dataset.confirmed = '1';
                        setBusy(form);
                        form.submit();
                    });
                return;
            }
            if (form.checkValidity && !form.checkValidity()) { return; }
            setBusy(form);
        });

        // Links that need a confirm (e.g. Apply, Remove lecturer).
        document.querySelectorAll('a[data-confirm]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                askConfirm(link.getAttribute('data-confirm'), link.getAttribute('data-confirm-ok'),
                    link.hasAttribute('data-confirm-danger'), function () { window.location = link.href; });
            });
        });
    });
})();

/* ==========================================================================
   v3: theme switching, collapsible sidebar, Ctrl+K quick search
   ========================================================================== */
(function () {
    'use strict';

    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    /* ---------- Theme ---------- */
    function getPref() {
        try { return localStorage.getItem('tc-theme') || 'system'; } catch (e) { return 'system'; }
    }

    function applyTheme(pref, animate) {
        var dark = pref === 'dark' || (pref === 'system' && media && media.matches);
        if (animate) {
            root.classList.add('theme-transition');
            setTimeout(function () { root.classList.remove('theme-transition'); }, 350);
        }
        root.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        var meta = document.getElementById('themeColorMeta');
        if (meta) { meta.setAttribute('content', dark ? '#0A1020' : '#F5F7FB'); }

        document.querySelectorAll('[data-theme-choice]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-theme-choice') === pref);
            btn.setAttribute('aria-pressed', btn.getAttribute('data-theme-choice') === pref ? 'true' : 'false');
        });
    }

    function setPref(pref) {
        try { localStorage.setItem('tc-theme', pref); } catch (e) {}
        applyTheme(pref, true);
    }

    function toggleTheme() {
        setPref(root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');
    }

    // Follow the phone/laptop setting live when on "Auto".
    if (media) {
        var onChange = function () { if (getPref() === 'system') { applyTheme('system', true); } };
        if (media.addEventListener) { media.addEventListener('change', onChange); } else if (media.addListener) { media.addListener(onChange); }
    }

    /* ---------- Sidebar collapse ---------- */
    function toggleSidebar() {
        var collapsed = root.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('tc-sidebar', collapsed ? 'collapsed' : 'open'); } catch (e) {}
    }

    /* ---------- Quick search palette ---------- */
    var palette, input, items, groups, emptyMsg, selected = -1;

    function visibleItems() {
        return items.filter(function (el) { return el.parentNode.style.display !== 'none'; });
    }

    function select(index) {
        var vis = visibleItems();
        items.forEach(function (el) { el.classList.remove('selected'); });
        if (!vis.length) { selected = -1; return; }
        selected = (index + vis.length) % vis.length;
        vis[selected].classList.add('selected');
        vis[selected].scrollIntoView({ block: 'nearest' });
    }

    function filter() {
        var q = input.value.trim().toLowerCase();
        var shownGroups = {};
        items.forEach(function (el) {
            var text = el.textContent.toLowerCase();
            var match = !q || q.split(/\s+/).every(function (w) { return text.indexOf(w) !== -1; });
            el.parentNode.style.display = match ? '' : 'none';
            if (match) { shownGroups[el.getAttribute('data-group')] = true; }
        });
        groups.forEach(function (g) { g.style.display = shownGroups[g.getAttribute('data-group')] ? '' : 'none'; });
        emptyMsg.classList.toggle('d-none', Object.keys(shownGroups).length > 0);
        select(0);
    }

    function openPalette() {
        if (!palette) { return; }
        palette.classList.add('open');
        palette.setAttribute('aria-hidden', 'false');
        input.value = '';
        filter();
        setTimeout(function () { input.focus(); }, 10);
    }

    function closePalette() {
        if (!palette) { return; }
        palette.classList.remove('open');
        palette.setAttribute('aria-hidden', 'true');
    }

    function runItem(el) {
        if (el.getAttribute('href') === '#theme') { closePalette(); toggleTheme(); return; }
        window.location = el.href;
    }

    function initPalette() {
        palette = document.getElementById('palette');
        if (!palette) { return; }
        input = palette.querySelector('.palette-input');
        items = Array.prototype.slice.call(palette.querySelectorAll('.palette-item'));
        groups = Array.prototype.slice.call(palette.querySelectorAll('.palette-group'));
        emptyMsg = palette.querySelector('.palette-empty');

        input.addEventListener('input', filter);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); select(selected + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); select(selected - 1); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                var vis = visibleItems();
                if (vis[selected]) { runItem(vis[selected]); }
            }
        });
        palette.addEventListener('click', function (e) {
            var item = e.target.closest('.palette-item');
            if (item) { e.preventDefault(); runItem(item); return; }
            if (e.target === palette) { closePalette(); }
        });
        items.forEach(function (el) {
            el.addEventListener('mousemove', function () {
                var i = visibleItems().indexOf(el);
                if (i !== selected) { select(i); }
            });
        });
    }

    /* ---------- Wire up ---------- */
    function init() {
        applyTheme(getPref(), false);
        initPalette();

        document.addEventListener('click', function (e) {
            var t = e.target.closest('[data-theme-toggle]');
            if (t) { e.preventDefault(); toggleTheme(); return; }
            var c = e.target.closest('[data-theme-choice]');
            if (c) { e.preventDefault(); e.stopPropagation(); setPref(c.getAttribute('data-theme-choice')); return; }
            if (e.target.closest('[data-sidebar-toggle]')) { toggleSidebar(); return; }
            if (e.target.closest('[data-palette-open]')) { e.preventDefault(); openPalette(); }
        });

        document.addEventListener('keydown', function (e) {
            var typing = /^(INPUT|TEXTAREA|SELECT)$/.test((e.target.tagName || '')) || e.target.isContentEditable;
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                if (palette && palette.classList.contains('open')) { closePalette(); } else { openPalette(); }
            } else if (e.key === '/' && !typing) {
                e.preventDefault(); openPalette();
            } else if (e.key === 'Escape' && palette && palette.classList.contains('open')) {
                closePalette();
            }
        });
    }

    if (document.readyState !== 'loading') { init(); } else { document.addEventListener('DOMContentLoaded', init); }
})();

/* ==========================================================================
   v4: error capture, report-a-problem, offline banner, page progress,
       caps-lock warning, copy buttons, table search, announcement dismiss
   ========================================================================== */
(function () {
    'use strict';
    var TC = window.TC || { base: '/', loggedIn: false };

    /* ---------- 1. Send browser (JavaScript) errors to Error Reports ---------- */
    var sent = 0;
    function reportJsError(payload) {
        if (sent >= 5) { return; }             // max 5 per page view
        sent++;
        try {
            var body = JSON.stringify(payload);
            var url = TC.base + 'support/js';
            if (navigator.sendBeacon) {
                navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
            } else {
                var x = new XMLHttpRequest(); x.open('POST', url, true);
                x.setRequestHeader('Content-Type', 'application/json'); x.send(body);
            }
        } catch (e) { /* never let error reporting cause errors */ }
    }
    window.addEventListener('error', function (e) {
        if (!e.message) { return; }
        reportJsError({ message: e.message, source: e.filename || '', line: e.lineno || 0,
            stack: e.error && e.error.stack ? String(e.error.stack) : '', url: location.href });
    });
    window.addEventListener('unhandledrejection', function (e) {
        var r = e.reason || {};
        reportJsError({ message: 'Unhandled promise: ' + (r.message || String(r)), source: '', line: 0,
            stack: r.stack ? String(r.stack) : '', url: location.href });
    });

    function ready(fn) { if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }

    ready(function () {

        /* ---------- 2. Report a problem modal (AJAX, with reference number) ---------- */
        var reportEl = document.getElementById('reportModal');
        document.addEventListener('click', function (e) {
            if (!e.target.closest('[data-report-open]')) { return; }
            e.preventDefault();
            if (!reportEl || !window.bootstrap) { window.location = TC.base + 'support/report?from=' + encodeURIComponent(location.pathname); return; }
            var form = reportEl.querySelector('form');
            form.reset();
            form.querySelector('[name="page_url"]').value = location.href;
            form.querySelector('.report-result').className = 'report-result d-none';
            form.querySelectorAll('.modal-body > :not(.report-result), .modal-footer').forEach(function (el) { el.style.display = ''; });
            bootstrap.Modal.getOrCreateInstance(reportEl).show();
        });
        if (reportEl) {
            var form = reportEl.querySelector('form');
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!form.checkValidity()) { form.reportValidity(); return; }
                var btn = form.querySelector('[type="submit"]');
                btn.disabled = true;
                var x = new XMLHttpRequest();
                x.open('POST', form.action, true);
                x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                x.onload = function () {
                    btn.disabled = false;
                    var res = {};
                    try { res = JSON.parse(x.responseText); } catch (err) { res = { ok: false, message: 'Something went wrong sending your report.' }; }
                    var box = form.querySelector('.report-result');
                    box.className = 'report-result flash ' + (res.ok ? 'flash-success' : 'flash-error') + ' mt-3 mb-0';
                    box.textContent = res.message;
                    if (res.ok) {
                        form.querySelectorAll('.modal-body > :not(.report-result)').forEach(function (el) { el.style.display = 'none'; });
                        form.querySelector('.modal-footer').style.display = 'none';
                        setTimeout(function () { bootstrap.Modal.getInstance(reportEl).hide(); }, 3500);
                    }
                };
                x.onerror = function () { btn.disabled = false; alert('Could not send - check your connection and try again.'); };
                x.send(new FormData(form));
            });
        }

        /* ---------- 3. Offline banner ---------- */
        var banner = document.getElementById('offlineBanner');
        function onlineState() { if (banner) { banner.hidden = navigator.onLine !== false; } }
        window.addEventListener('online', onlineState);
        window.addEventListener('offline', onlineState);
        onlineState();

        /* ---------- 4. Thin progress bar while the next page loads ---------- */
        var bar = document.getElementById('topProgress');
        function startProgress() { if (bar) { bar.className = 'top-progress is-loading'; } }
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]');
            if (!a || e.defaultPrevented || e.ctrlKey || e.metaKey || e.shiftKey || a.target === '_blank' || a.hasAttribute('download')) { return; }
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0 || a.hasAttribute('data-confirm') || a.hasAttribute('data-palette-open')) { return; }
            if (a.host && a.host !== location.host) { return; }
            if (/\/(view_proof|download|export)\b/.test(a.pathname)) { return; } // file downloads don't navigate
            startProgress();
        });
        document.addEventListener('submit', function (e) { if (!e.defaultPrevented && !e.target.hasAttribute('data-ajax-report')) { startProgress(); } });
        window.addEventListener('pageshow', function () { if (bar) { bar.className = 'top-progress'; } });

        /* ---------- 5. Caps Lock warning on password fields ---------- */
        document.querySelectorAll('input[type="password"]').forEach(function (input) {
            var note = document.createElement('div');
            note.className = 'caps-warning';
            note.textContent = 'Caps Lock is on';
            note.hidden = true;
            (input.closest('.password-field') || input).insertAdjacentElement('afterend', note);
            function check(e) { if (e.getModifierState) { note.hidden = !e.getModifierState('CapsLock'); } }
            input.addEventListener('keyup', check);
            input.addEventListener('keydown', check);
            input.addEventListener('blur', function () { note.hidden = true; });
        });

        /* ---------- 6. Copy buttons ---------- */
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-copy]');
            if (!btn) { return; }
            var text = btn.getAttribute('data-copy');
            var done = function () {
                var old = btn.innerHTML;
                btn.classList.add('copied');
                btn.innerHTML = btn.classList.contains('icon-btn') ? '✓' : 'Copied ✓';
                setTimeout(function () { btn.innerHTML = old; btn.classList.remove('copied'); }, 1600);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done);
            } else {
                var ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); done(); } catch (err) {}
                document.body.removeChild(ta);
            }
        });

        /* ---------- 7. Instant table search ---------- */
        document.querySelectorAll('[data-table-filter]').forEach(function (input) {
            var table = document.querySelector(input.getAttribute('data-table-filter'));
            if (!table) { return; }
            var rows = table.querySelectorAll('tbody tr');
            var empty = table.parentNode.querySelector('[data-filter-empty]');
            input.addEventListener('input', function () {
                var q = input.value.trim().toLowerCase(), shown = 0;
                rows.forEach(function (tr) {
                    var match = !q || tr.textContent.toLowerCase().indexOf(q) !== -1;
                    tr.style.display = match ? '' : 'none';
                    if (match) { shown++; }
                });
                if (empty) { empty.classList.toggle('d-none', shown > 0); }
            });
        });

        /* ---------- 8. Dismissible announcements (remembered on this device) ---------- */
        var dismissed = [];
        try { dismissed = JSON.parse(localStorage.getItem('tc-dismissed') || '[]'); } catch (e) {}
        document.querySelectorAll('[data-announcement]').forEach(function (el) {
            var id = el.getAttribute('data-announcement');
            if (dismissed.indexOf(id) !== -1) { el.remove(); return; }
            var close = el.querySelector('[data-dismiss-announcement]');
            if (close) {
                close.addEventListener('click', function () {
                    dismissed.push(id);
                    try { localStorage.setItem('tc-dismissed', JSON.stringify(dismissed.slice(-50))); } catch (e) {}
                    el.classList.add('is-leaving');
                    setTimeout(function () { el.remove(); }, 250);
                });
            }
        });
    });
})();

/* ==========================================================================
   v4.1: profile photo picker - crop to a square and shrink on the device
   before uploading (a 4MB phone photo becomes ~80KB), with live preview.
   ========================================================================== */
(function () {
    'use strict';
    function ready(fn) { if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }

    ready(function () {
        document.querySelectorAll('[data-photo-form]').forEach(function (form) {
            var input = form.querySelector('[data-photo-input]');
            var save = form.querySelector('[data-photo-save]');
            var preview = form.querySelector('.photo-picker-preview');
            if (!input) { return; }

            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file) { return; }
                if (!/^image\//.test(file.type)) { alert('Please choose a photo.'); input.value = ''; return; }

                var img = new Image();
                var url = URL.createObjectURL(file);
                img.onload = function () {
                    var side = Math.min(img.naturalWidth, img.naturalHeight);
                    var sx = (img.naturalWidth - side) / 2;
                    var sy = (img.naturalHeight - side) / 3;       // faces sit in the upper third
                    var canvas = document.createElement('canvas');
                    canvas.width = canvas.height = 512;
                    var ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, 512, 512);
                    ctx.drawImage(img, sx, sy, side, side, 0, 0, 512, 512);
                    URL.revokeObjectURL(url);

                    var dataUrl = canvas.toDataURL('image/jpeg', 0.86);
                    preview.innerHTML = '<img class="avatar avatar-img avatar-xl" alt="Preview" src="' + dataUrl + '">';

                    // Swap the big original for the small square version, where the browser allows it.
                    if (canvas.toBlob && window.DataTransfer) {
                        canvas.toBlob(function (blob) {
                            try {
                                var dt = new DataTransfer();
                                dt.items.add(new File([blob], 'photo.jpg', { type: 'image/jpeg' }));
                                input.files = dt.files;
                            } catch (e) { /* older browser: the original is uploaded and resized on the server */ }
                            if (save) { save.disabled = false; save.focus(); }
                        }, 'image/jpeg', 0.86);
                    } else if (save) {
                        save.disabled = false;
                    }
                };
                img.onerror = function () { URL.revokeObjectURL(url); alert('That photo could not be read. Please try a JPG or PNG.'); };
                img.src = url;
            });
        });
    });
})();

(function () {
    'use strict';
    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-print-card]')) { return; }
        document.body.classList.add('print-card');
        window.print();
    });
    window.addEventListener('afterprint', function () { document.body.classList.remove('print-card'); });
})();

/* ID card: tap / click / Enter to flip between front and back */
(function () {
    'use strict';
    document.addEventListener('click', function (e) {
        var card = e.target.closest('[data-id-flip]');
        if (card) { card.classList.toggle('is-flipped'); }
    });
    document.addEventListener('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.matches && e.target.matches('[data-id-flip]')) {
            e.preventDefault();
            e.target.classList.toggle('is-flipped');
        }
    });
})();
