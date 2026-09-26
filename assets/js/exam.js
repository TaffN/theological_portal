/* ==========================================================================
   Stage 5: online exams. Loaded only on the exam pages.
     1. Question editor: show the choices only for multiple choice
     2. Invigilation screen: refresh the table every few seconds
     3. Sitting an exam: countdown, autosave (with an offline queue kept on
        the phone), heartbeat, and activity reporting
   The server is always the judge: this file only reports and helps.
   ========================================================================== */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
    }

    function post(url, data, onDone, onFail) {
        var fd = new FormData();
        Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
        var x = new XMLHttpRequest();
        x.open('POST', url, true);
        x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        x.timeout = 15000;
        x.onload = function () {
            var res = null;
            try { res = JSON.parse(x.responseText); } catch (e) { res = null; }
            if (res) { onDone(res); } else if (onFail) { onFail(); }
        };
        x.onerror = x.ontimeout = function () { if (onFail) { onFail(); } };
        x.send(fd);
    }

    /* ---------- 1. Question editor --------------------------------------- */
    function initQuestionForm(form) {
        function sync() {
            var checked = form.querySelector('input[name="type"]:checked');
            var isShort = checked && checked.value === 'short';
            form.querySelectorAll('.mcq-only').forEach(function (el) { el.hidden = isShort; });
            form.querySelectorAll('.short-only').forEach(function (el) { el.hidden = !isShort; });
        }
        form.querySelectorAll('input[name="type"]').forEach(function (r) { r.addEventListener('change', sync); });
        sync();
    }

    /* ---------- 2. Invigilation ------------------------------------------ */
    function initInvigilate(box) {
        var url = box.getAttribute('data-live-url');
        var every = (parseInt(box.getAttribute('data-interval'), 10) || 10) * 1000;
        var dot = document.querySelector('[data-live-status]');

        function refresh() {
            // Don't swap the table under someone who is in the middle of clicking a confirm.
            if (document.querySelector('.modal.show') || document.hidden) { return; }
            var x = new XMLHttpRequest();
            x.open('GET', url + '?t=' + Date.now(), true);
            x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            x.onload = function () {
                if (x.status === 200 && x.responseURL && x.responseURL.indexOf('/live/') === -1) { location.reload(); return; } // logged out
                if (x.status === 200) {
                    box.innerHTML = x.responseText;
                    if (dot) { dot.className = 'live-dot'; dot.textContent = 'Live'; }
                } else if (dot) { dot.className = 'live-dot is-off'; dot.textContent = 'Reconnecting…'; }
            };
            x.onerror = function () { if (dot) { dot.className = 'live-dot is-off'; dot.textContent = 'Reconnecting…'; } };
            x.send();
        }
        setInterval(refresh, every);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) { refresh(); } });
    }

    /* ---------- 3. Sitting the exam -------------------------------------- */
    function initExam(root) {
        var attemptId = root.getAttribute('data-attempt');
        var saveUrl   = root.getAttribute('data-save-url');
        var pingUrl   = root.getAttribute('data-ping-url');
        var eventUrl  = root.getAttribute('data-event-url');
        var examUrl   = root.getAttribute('data-exam-url');
        var form      = document.getElementById('exam-form');
        var timerEl   = root.querySelector('[data-timer]');
        var statusEl  = root.querySelector('[data-save-status]');
        var answeredEl = root.querySelector('[data-answered]');
        var storeKey  = 'tc-exam-' + attemptId;

        // The phone's clock may be wrong, so count down from what the server said.
        var deadline  = Date.now() + parseInt(root.getAttribute('data-left'), 10) * 1000;
        var pending   = loadPending();
        var inFlight  = false, flushTimer = null, finished = false;
        var offlineSince = null, hiddenAt = null, lastCopy = 0;

        document.body.classList.add('exam-mode');   // hides the menus so nobody wanders off by accident

        /* -- pending answers survive a dead battery or a reload -- */
        function loadPending() {
            try { return JSON.parse(localStorage.getItem(storeKey)) || {}; } catch (e) { return {}; }
        }
        function storePending() {
            try { localStorage.setItem(storeKey, JSON.stringify(pending)); } catch (e) { /* private mode: memory only */ }
        }

        function fieldsFor(qid) { return form.querySelectorAll('[name="answers[' + qid + ']"]'); }
        function valueOf(qid) {
            var f = fieldsFor(qid);
            if (!f.length) { return ''; }
            if (f[0].type === 'radio') {
                for (var i = 0; i < f.length; i++) { if (f[i].checked) { return f[i].value; } }
                return '';
            }
            return f[0].value;
        }
        // Highlight the chosen option (for phones whose browser lacks CSS :has()).
        function markChosen() {
            form.querySelectorAll('.exam-option').forEach(function (l) {
                var r = l.querySelector('input');
                l.classList.toggle('is-checked', !!(r && r.checked));
            });
        }

        function countAnswered() {
            var n = 0;
            form.querySelectorAll('[data-question]').forEach(function (fs) {
                if (valueOf(fs.getAttribute('data-question')).trim() !== '') { n++; }
            });
            answeredEl.textContent = n;
        }
        function setStatus(text, cls) {
            statusEl.textContent = text;
            statusEl.className = cls || '';
        }

        // Put back anything typed while offline last time (newer than what the server has).
        Object.keys(pending).forEach(function (qid) {
            var f = fieldsFor(qid);
            if (!f.length) { delete pending[qid]; return; }
            if (f[0].type === 'radio') {
                f.forEach(function (r) { r.checked = r.value === pending[qid]; });
            } else {
                f[0].value = pending[qid];
            }
        });

        function queue(qid, delay) {
            pending[qid] = valueOf(qid);
            storePending();
            countAnswered();
            setStatus('Saving…');
            clearTimeout(flushTimer);
            flushTimer = setTimeout(flush, delay);
        }

        function flush() {
            if (finished || inFlight) { return; }
            var qids = Object.keys(pending);
            if (!qids.length) { setStatus('All answers saved', 'is-ok'); return; }
            var snapshot = {}, data = {};
            qids.forEach(function (q) { snapshot[q] = pending[q]; data['answers[' + q + ']'] = pending[q]; });
            inFlight = true;
            post(saveUrl, data, function (res) {
                inFlight = false;
                if (!res.ok) { return handleError(res.error); }
                backOnline();
                qids.forEach(function (q) { if (pending[q] === snapshot[q]) { delete pending[q]; } });
                storePending();
                if (typeof res.left === 'number') { deadline = Date.now() + res.left * 1000; }
                if (Object.keys(pending).length) { flush(); } else { setStatus('All answers saved', 'is-ok'); }
            }, function () {
                inFlight = false;
                wentOffline();
                clearTimeout(flushTimer);
                flushTimer = setTimeout(flush, 5000);
            });
        }

        function wentOffline() {
            if (offlineSince === null) { offlineSince = Date.now(); }
            setStatus('No connection: answers kept on this phone, retrying…', 'is-warn');
        }
        function backOnline() {
            if (offlineSince !== null) {
                var secs = Math.round((Date.now() - offlineSince) / 1000);
                offlineSince = null;
                if (secs >= 10) { report('offline', { seconds: secs }); }
            }
        }

        function handleError(err) {
            if (err === 'other_device') { finished = true; location.reload(); return; }
            // time_up / submitted / no_access: the server has closed the attempt.
            finished = true;
            try { localStorage.removeItem(storeKey); } catch (e) { /* ignore */ }
            location.href = examUrl;
        }

        function report(type, extra) {
            var data = { type: type };
            if (extra) { Object.keys(extra).forEach(function (k) { data[k] = extra[k]; }); }
            post(eventUrl, data, function () {}, function () {});
        }
        function beacon(url, data) {
            if (!navigator.sendBeacon) { post(url, data, function () {}, function () {}); return; }
            var fd = new FormData();
            Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
            navigator.sendBeacon(url, fd);
        }

        /* -- inputs -- */
        form.addEventListener('change', function (e) {
            var fs = e.target.closest('[data-question]');
            if (fs && e.target.type === 'radio') { markChosen(); queue(fs.getAttribute('data-question'), 0); }
        });
        form.addEventListener('input', function (e) {
            var fs = e.target.closest('[data-question]');
            if (fs && e.target.tagName === 'TEXTAREA') { queue(fs.getAttribute('data-question'), 1500); }
        });
        form.addEventListener('paste', function (e) {
            if (e.target.tagName !== 'TEXTAREA') { return; }
            e.preventDefault();
            var text = (e.clipboardData && e.clipboardData.getData('text')) || '';
            report('paste', { detail: text.length + ' characters' });
            setStatus('Pasting is turned off in exams. Please type your answer.', 'is-warn');
        });
        document.addEventListener('copy', function () {
            if (Date.now() - lastCopy > 10000) { lastCopy = Date.now(); report('copy'); }
        });

        /* -- leaving the page (switching apps, tabs, screen off) -- */
        document.addEventListener('visibilitychange', function () {
            if (finished) { return; }
            if (document.hidden) {
                hiddenAt = Date.now();
                beacon(eventUrl, { type: 'left' });
                if (Object.keys(pending).length) {   // save what's typed, in case they never come back
                    var data = {};
                    Object.keys(pending).forEach(function (q) { data['answers[' + q + ']'] = pending[q]; });
                    beacon(saveUrl, data);
                }
            } else {
                if (hiddenAt) { report('returned', { seconds: Math.round((Date.now() - hiddenAt) / 1000) }); }
                hiddenAt = null;
                ping();
                flush();
            }
        });

        /* -- heartbeat and timer -- */
        function ping() {
            post(pingUrl, {}, function (res) {
                if (!res.ok) { return handleError(res.error); }
                backOnline();
                if (typeof res.left === 'number') { deadline = Date.now() + res.left * 1000; }
                if (Object.keys(pending).length) { flush(); }
            }, wentOffline);
        }
        setInterval(ping, 30000);

        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function tick() {
            if (finished) { return; }
            var left = Math.max(0, Math.round((deadline - Date.now()) / 1000));
            var h = Math.floor(left / 3600), m = Math.floor((left % 3600) / 60), s = left % 60;
            timerEl.textContent = (h ? h + ':' + pad(m) : m) + ':' + pad(s);
            timerEl.className = 'exam-timer' + (left <= 60 ? ' is-danger' : (left <= 300 ? ' is-warn' : ''));
            if (left === 300) { setStatus('5 minutes left', 'is-warn'); }
            if (left <= 0) { handIn(); }
        }
        function handIn() {
            if (finished) { return; }
            finished = true;
            timerEl.textContent = '0:00';
            setStatus('Time is up: handing in…', 'is-warn');
            form.querySelector('[name="auto"]').value = '1';
            try { localStorage.removeItem(storeKey); } catch (e) { /* ignore */ }
            form.submit();   // carries every answer, even ones that never autosaved
        }
        form.addEventListener('submit', function () {
            // Manual hand-in: the form carries all answers, so the queue can go.
            try { localStorage.removeItem(storeKey); } catch (e) { /* ignore */ }
        });

        markChosen();
        countAnswered();
        tick();
        setInterval(tick, 1000);
        if (Object.keys(pending).length) { flush(); }
    }

    ready(function () {
        var qf = document.querySelector('[data-question-form]');
        if (qf) { initQuestionForm(qf); }
        var inv = document.querySelector('[data-invigilate]');
        if (inv) { initInvigilate(inv); }
        var exam = document.querySelector('[data-exam-take]');
        if (exam) { initExam(exam); }
    });
})();
