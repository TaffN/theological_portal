<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Replaces CodeIgniter's default uncaught-exception handler (CI only defines
 * its own if one doesn't exist yet). Differences from the stock one:
 *  - the crash is recorded in Error Reports with a reference code
 *  - on a live server (display_errors off) users see a friendly page with
 *    that reference instead of a blank white screen.
 */
if (! function_exists('_exception_handler')) {
    function _exception_handler($exception)
    {
        $_error =& load_class('Exceptions', 'core');
        $_error->log_exception('error', 'Exception: ' . $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception);

        is_cli() OR set_status_header(500);

        if (str_ireplace(['off', 'none', 'no', 'false', 'null'], '', ini_get('display_errors'))) {
            $_error->show_exception($exception);
        } elseif (method_exists($_error, 'show_friendly_500')) {
            $_error->show_friendly_500();
        }

        exit(1);
    }
}
