/* Ezra chat page: sends the question with fetch, shows a typing indicator,
   and adds Ezra's answer (already formatted and escaped by the server). */
(function () {
    'use strict';
    var form = document.querySelector('[data-ezra-form]');
    var log = document.getElementById('ezra-log');
    if (!form || !log) { return; }
    var input = form.querySelector('[data-ezra-input]');
    var send = form.querySelector('.ezra-send');
    var busy = false;

    function scrollDown() { log.scrollTop = log.scrollHeight; }
    function grow() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 160) + 'px'; }

    function addMessage(cls, html) {
        var intro = log.querySelector('[data-ezra-intro]');
        if (intro) { intro.remove(); }
        var newBtn = document.querySelector('[data-ezra-new]');
        if (newBtn) { newBtn.classList.remove('d-none'); }
        var row = document.createElement('div');
        row.className = 'ezra-msg ' + cls;
        row.innerHTML = (cls.indexOf('is-ezra') === 0 ? '<span class="ezra-avatar" aria-hidden="true">E</span>' : '') + '<div class="ezra-bubble"></div>';
        row.querySelector('.ezra-bubble').innerHTML = html;
        log.appendChild(row);
        scrollDown();
        return row;
    }
    function escapeText(t) {
        var d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML.replace(/\n/g, '<br>');
    }

    function ask(question) {
        if (busy || !question.trim()) { return; }
        busy = true;
        send.disabled = true;
        addMessage('is-user', escapeText(question));
        input.value = '';
        grow();
        var typing = addMessage('is-ezra is-typing', '<span class="ezra-dots"><i></i><i></i><i></i></span><span class="visually-hidden">Ezra is typing</span>');

        var body = new FormData();
        body.append('question', question);
        fetch(form.action, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Something went wrong. Please try again.' }; }); })
            .then(function (res) {
                typing.remove();
                if (res.html) {
                    addMessage('is-ezra' + (res.ok ? '' : ' is-problem'), res.html);
                } else {
                    addMessage('is-ezra is-problem', escapeText(res.error || 'Something went wrong. Please try again.'));
                    if (!res.paused) { input.value = question; grow(); }
                }
                var left = document.querySelector('[data-ezra-left]');
                if (left && typeof res.left === 'number') {
                    left.textContent = res.left + ' question' + (res.left === 1 ? '' : 's') + ' left today';
                }
                if (res.paused) { setTimeout(function () { window.location.reload(); }, 2500); }
            })
            .catch(function () {
                typing.remove();
                addMessage('is-ezra is-problem', 'You seem to be offline. Your question wasn\'t sent; press send again when you have a signal.');
                input.value = question;
                grow();
            })
            .then(function () {
                busy = false;
                send.disabled = false;
                if (window.matchMedia('(pointer: fine)').matches) { input.focus(); }
            });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        ask(input.value);
    });
    // Enter sends on a computer; Shift+Enter (and Enter on phones) makes a new line.
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing && window.matchMedia('(pointer: fine)').matches) {
            e.preventDefault();
            ask(input.value);
        }
    });
    input.addEventListener('input', grow);
    document.querySelectorAll('[data-ezra-suggest]').forEach(function (chip) {
        chip.addEventListener('click', function () { ask(chip.textContent); });
    });
    if (!log.querySelector('[data-ezra-intro]')) { scrollDown(); }
})();
