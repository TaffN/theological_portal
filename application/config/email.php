<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Fill these in with your real SMTP details (Gmail, Zoho, your host's mail
| server, etc.) and flip SMTP_CONFIGURED to true below. Until then, the
| Notifier library skips sending email entirely and only creates in-app
| notifications - so the site works fine before you've set up email.
*/

$config['protocol']    = 'smtp';
$config['smtp_host']   = 'ssl://smtp.example.com';
$config['smtp_port']   = 465;
$config['smtp_user']   = 'you@example.com';
$config['smtp_pass']   = 'your-smtp-password';
$config['charset']     = 'utf-8';
$config['mailtype']    = 'html';
$config['newline']     = "\r\n";
$config['from_email']  = 'notifications@example.com';
$config['from_name']   = 'Theological Center Portal';

// Flip this to true once the settings above are real.
$config['smtp_configured'] = false;
