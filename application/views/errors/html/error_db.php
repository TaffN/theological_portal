<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$code  = 500;
$title = 'We\'re having trouble reaching our records';
$text  = 'This is on our side, not yours. Please try again in a few minutes.';
$techDetails = $heading . "\n" . (is_array($message) ? implode("\n", $message) : $message);
include __DIR__ . '/_portal_error.php';
