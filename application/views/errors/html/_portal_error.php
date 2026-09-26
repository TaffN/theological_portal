<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * Shared, self-contained error page (no Bootstrap, no database, no layout)
 * so it still renders when the thing that broke is the app itself.
 * Expects: $code, $title, $text, optional $techDetails.
 */
$base = rtrim((string) config_item('base_url'), '/') . '/';
$ref  = class_exists('MY_Exceptions', false) ? MY_Exceptions::$last_reference : null;
$isDev = defined('ENVIRONMENT') && ENVIRONMENT === 'development';
?><!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> &middot; Theological Center Portal</title>
<script>(function(){try{var p=localStorage.getItem('tc-theme')||'system';var d=p==='dark'||(p==='system'&&matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.setAttribute('data-bs-theme',d?'dark':'light');}catch(e){}})();</script>
<style>
:root{--g1:#1F3864;--g2:#4A78D6;--bg:#F5F7FB;--card:#fff;--t1:#0F172A;--t2:#475569;--t3:#64748B;--b:#E4E8F0;--accent:#1F3864;--gold:#C9A227;--soft:#EDF1F7}
[data-bs-theme=dark]{--g1:#8DB0F2;--g2:#DDB940;--bg:#0A1020;--card:#101829;--t1:#E7ECF5;--t2:#B3BDD0;--t3:#8491A9;--b:#1F2C45;--accent:#4A78D6;--soft:#1B2740}
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:var(--bg);color:var(--t1);font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
.wrap{width:100%;max-width:520px;text-align:center}
.mark{width:44px;height:44px;border-radius:12px;display:inline-grid;place-items:center;background:linear-gradient(135deg,#DDB940,#C9A227);color:#13243F;font-weight:800;text-decoration:none;margin-bottom:28px;box-shadow:0 4px 12px rgba(201,162,39,.35)}
.code{font-size:96px;font-weight:800;letter-spacing:-.04em;line-height:1;margin:0;background:linear-gradient(135deg,var(--g1),var(--g2));-webkit-background-clip:text;background-clip:text;color:transparent}
h1{font-size:24px;margin:14px 0 8px;letter-spacing:-.02em}p{color:var(--t2);margin:0 0 24px;line-height:1.55}
.btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:6px;padding:11px 18px;border-radius:10px;font-weight:600;text-decoration:none;font-size:15px;border:1px solid var(--b);color:var(--t1);background:var(--card);cursor:pointer}
.btn.primary{background:var(--accent);border-color:var(--accent);color:#fff}
.ref{margin-top:26px;display:inline-block;padding:10px 14px;border-radius:12px;background:var(--soft);color:var(--t2);font-size:13px}
.ref strong{color:var(--t1);font-family:ui-monospace,Menlo,Consolas,monospace;letter-spacing:.04em}
details{margin-top:22px;text-align:left;background:var(--card);border:1px solid var(--b);border-radius:12px;padding:12px 14px;font-size:13px;color:var(--t2)}
details pre{white-space:pre-wrap;word-break:break-word;margin:10px 0 0;font-size:12px}
</style>
</head>
<body>
<main class="wrap">
    <a class="mark" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>" aria-label="Home">TC</a>
    <p class="code"><?= htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8') ?></p>
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= $text ?></p>
    <div class="btns">
        <a class="btn primary" href="<?= htmlspecialchars($base . 'dashboard', ENT_QUOTES, 'UTF-8') ?>">Go to my dashboard</a>
        <a class="btn" href="javascript:history.back()">Go back</a>
        <a class="btn" href="<?= htmlspecialchars($base . 'support/report?from=' . rawurlencode(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '') . ($ref ? '&ref=' . $ref : ''), ENT_QUOTES, 'UTF-8') ?>">Report this</a>
    </div>
    <?php if ($ref): ?>
        <div class="ref">Error reference <strong><?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></strong> &middot; our team has been notified</div>
    <?php endif; ?>
    <?php if ($isDev && ! empty($techDetails)): ?>
        <details><summary>Technical details (only shown on your development machine)</summary><pre><?= htmlspecialchars(strip_tags($techDetails), ENT_QUOTES, 'UTF-8') ?></pre></details>
    <?php endif; ?>
</main>
</body>
</html>
