<?php
// ONE-TIME admin password reset. DELETE THIS FILE straight after using it.
if (! in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true)) { exit('Only works on this computer.'); }

$pdo  = new PDO('mysql:host=localhost;dbname=theological_portal;charset=utf8', 'root', '');
$pass = 'Recover-2026!';
$hash = password_hash($pass, PASSWORD_DEFAULT);

$upd = $pdo->prepare("UPDATE users SET password_hash = ?, status = 'active' WHERE email = 'admin@example.com'");
$upd->execute([$hash]);

// Clear the brute-force lockout for this email
$pdo->exec("DELETE FROM audit_log WHERE action IN ('auth.login_failed','auth.locked_out')
            AND entity_type = 'email:admin@example.com'");

$row = $pdo->query("SELECT password_hash FROM users WHERE email = 'admin@example.com'")->fetch();
echo 'Accounts updated: ' . $upd->rowCount() . '<br>';
echo 'Password check: ' . ($row && password_verify($pass, $row['password_hash']) ? 'OK - log in now' : 'FAILED') . '<br>';
echo '<strong>Now delete reset_admin.php</strong>';