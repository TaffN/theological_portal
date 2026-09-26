<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$code  = isset($status_code) ? $status_code : 500;
$title = ($code == 403) ? 'You don\'t have access to that' : 'Something went wrong';
$text  = ($code == 403)
    ? 'This area is for a different type of account. If you think that\'s a mistake, let the administrator know.'
    : 'We hit an unexpected problem. Please try again in a moment.';
$techDetails = is_array($message) ? implode("\n", $message) : $message;
include __DIR__ . '/_portal_error.php';
