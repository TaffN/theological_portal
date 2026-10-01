/*
 * Ezra floating chat widget (partials/ezra_widget.php).
 * Opens/closes the window, sends questions to api/ezra_chat, shows a typing
 * indicator, and remembers the chat in localStorage per user (this device only).
 */
(function () {
    'use strict';

    var root = document.getElementById('ezw');
    if (!root) { return; }

    var win      = document.getElementById('ezwWindow');
    var launcher = document.getElementById('ezwLauncher');
    var list     = document.getElementById('ezwMessages');
    var form     = document.getElementById('ezwForm');
    var input    = document.getElementById('ezwText');
    var endpoint = root.getAttribute('data-endpoint');
    var key      = 'ezra-chat-' + root.getAttribute('data-user');
    var MAX_KEEP = 40;
    var busy     = false;
    var history  = load();

    var greeting = {
        student:  "Hello! I'm **Ezra**, your study companion. Ask me about your modules, exams, payments or a Bible passage. I also understand Shona and Ndebele.",
        lecturer: "Hello! I'm **Ezra**. I can walk you through setting assignments and exams, taking the register, or help you prepare a lesson.",
        admin:    "Hello! I'm **Ezra**. Ask me how to approve payments, verify documents, set up modules and lecturers, or anything else in the portal."
    };

    /* ------------------------------------------------ storage */

    function load() {
        try {
            var v = JSON.parse(localStorage.getItem(key) || '[]');
            return Array.isArray(v) ? v : [];
        } catch (e) { return []; }
    }
    function save() {
        try { localStorage.setItem(key, JSON.stringify(history.slice(-MAX_KEEP))); } catch (e) {}
    }

    /* ------------------------------------------------ rendering */

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    // Only for the fixed greeting (server answers arrive as safe HTML already).
    function simpleFormat(s) {
        return '<p>' + esc(s).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>') + '</p>';
    }

    function bubble(role, html, extraClass) {
        var row = document.createElement('div');
        row.className = 'ezw-msg is-' + role + (extraClass ? ' ' + extraClass : '');
        var b = document.createElement('div');
        b.className = 'ezw-bubble';
        b.innerHTML = html;
        row.appendChild(b);
        list.appendChild(row);
        scrollDown();
        return row;
    }

    function render() {
        list.innerHTML = '';
        bubble('bot', simpleFormat(greeting[root.getAttribute('data-role')] || greeting.student));
        history.forEach(function (m) {
            bubble(m.role, m.role === 'user' ? '<p>' + esc(m.text) + '</p>' : m.html, m.status === 'guide' ? 'is-guide' : '');
        });
    }

    function scrollDown() {
        list.scrollTop = list.scrollHeight;
    }

    function typing() {
        return bubble('bot', '<span class="ezw-typing" aria-label="Ezra is typing"><i></i><i></i><i></i></span>');
    }

    /* ------------------------------------------------ sending */

    function send(text) {
        text = (text || '').trim();
        if (!text || busy) { return; }
        busy = true;
        form.classList.add('is-busy');
        input.value = '';

        var sentHistory = history.slice(-6).map(function (m) { return { role: m.role, text: m.text }; });
        history.push({ role: 'user', text: text });
        save();
        bubble('user', '<p>' + esc(text) + '</p>');
        var dots = typing();

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ message: text, history: sentHistory })
        }).then(function (r) {
            // A signed-out session redirects to the login page (HTML, not JSON).
            return r.json().catch(function () { return { reply: 'Please sign in again to talk to Ezra.', html: '<p>Please sign in again to talk to Ezra.</p>', status: 'error' }; });
        }).then(function (res) {
            dots.remove();
            var html = res.html || '<p>' + esc(res.reply || 'Sorry, something went wrong.') + '</p>';
            history.push({ role: 'bot', text: res.reply || '', html: html, status: res.status || 'ok' });
            save();
            bubble('bot', html, res.status === 'guide' ? 'is-guide' : '');
        }).catch(function () {
            dots.remove();
            bubble('bot', '<p>No connection right now. Please check your data and try again.</p>', 'is-problem');
        }).then(function () {
            busy = false;
            form.classList.remove('is-busy');
            input.focus();
        });
    }

    /* ------------------------------------------------ open / close */

    function setOpen(open) {
        root.classList.toggle('is-open', open);
        win.setAttribute('aria-hidden', open ? 'false' : 'true');
        launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
        try { sessionStorage.setItem('ezra-open', open ? '1' : '0'); } catch (e) {}
        if (open) {
            scrollDown();
            // Don't pop the phone keyboard up over the chat.
            if (window.matchMedia('(min-width: 768px)').matches) { input.focus(); }
        }
    }

    launcher.addEventListener('click', function () { setOpen(!root.classList.contains('is-open')); });
    root.querySelector('[data-ezw-close]').addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && root.classList.contains('is-open')) { setOpen(false); }
    });

    root.querySelector('[data-ezw-clear]').addEventListener('click', function () {
        if (busy) { return; }
        history = [];
        save();
        render();
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-ezw-ask]'), function (chip) {
        chip.addEventListener('click', function () {
            input.value = chip.getAttribute('data-ezw-ask');
            send(input.value);
        });
    });

    // Listening on the form itself runs before app.js's document-wide submit
    // handler, so preventDefault() stops its page-loading bar too.
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        send(input.value);
    });

    render();
    try { if (sessionStorage.getItem('ezra-open') === '1') { setOpen(true); } } catch (e) {}
})();
