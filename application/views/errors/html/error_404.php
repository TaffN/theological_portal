<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$code  = 404;
$title = 'We couldn\'t find that page';
$text  = 'The link may be old, or the page may have moved. Everything else is still where you left it.';
$techDetails = is_array($message) ? implode("\n", $message) : $message;
include __DIR__ . '/_portal_error.php';
