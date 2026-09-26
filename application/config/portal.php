<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Payment details shown to students on the "Submit proof of payment" page,
| so they know exactly where to send money. Leave a value empty ('') to
| hide that line.
*/
$config['pay_ecocash_number'] = '';          // e.g. '+263 77 123 4567'
$config['pay_ecocash_name']   = '';          // e.g. 'Theological Center'
$config['pay_bank_name']      = '';          // e.g. 'CBZ Bank'
$config['pay_bank_account']   = '';          // e.g. '0123456789'
$config['pay_bank_holder']    = '';          // e.g. 'Theological Center Trust'
$config['pay_bank_branch']    = '';          // e.g. 'Bulawayo Main'
$config['pay_reference_hint'] = 'Use your full name as the payment reference.';
