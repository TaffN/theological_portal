<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hooks CodeIgniter's error handling into the Error Reports module.
 * Every path is wrapped so that recording an error can never itself cause
 * a new error (e.g. when the database is the thing that's broken).
 */
class MY_Exceptions extends CI_Exceptions
{
    /** Reference code of the last recorded error, shown on error pages. */
    public static $last_reference = null;

    private static $recording = false;

    public function log_exception($severity, $message, $filepath, $line, $exception = null)
    {
        parent::log_exception($severity, $message, $filepath, $line);

        // Skip noise that isn't a real fault.
        if (is_int($severity) && in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED, E_STRICT], true)) {
            return;
        }

        $isException = ($severity === 'error');
        $level = $isException ? 'critical' : $this->_level_for($severity);

        $details = $message . "\n" . 'File: ' . $filepath . ' (line ' . $line . ')';
        if ($exception instanceof Throwable) {
            $details .= "\n\n" . $exception->getTraceAsString();
        }

        $this->_record([
            'source'    => $isException ? 'exception' : 'php',
            'severity'  => $level,
            'title'     => $this->_short(preg_replace('/^Exception: /', '', $message)),
            'details'   => $details,
            'group_key' => basename($filepath) . ':' . $line,
        ]);
    }

    public function show_404($page = '', $log_error = true)
    {
        // Only record 404s that look like a broken link inside the portal
        // (came from one of our own pages) - not random bots probing URLs.
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $host    = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        if ($referer !== '' && $host !== '' && parse_url($referer, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST)) {
            $this->_record([
                'source'    => 'not_found',
                'severity'  => 'low',
                'title'     => 'Broken link: ' . $this->_short($page ?: $this->_current_path(), 200),
                'details'   => 'Linked from: ' . $referer,
                'group_key' => $page,
            ]);
        }

        parent::show_404($page, $log_error);
    }

    public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        if ($template === 'error_db') {
            $text = is_array($message) ? implode("\n", $message) : (string) $message;
            $this->_record([
                'source'    => 'database',
                'severity'  => 'critical',
                'title'     => $this->_short(strip_tags(is_array($message) ? (isset($message[0]) ? $message[0] : $heading) : $heading)),
                'details'   => strip_tags($text),
                'group_key' => md5($text),
            ]);
        }

        return parent::show_error($heading, $message, $template, $status_code);
    }

    /** Friendly 500 page used on live servers (display_errors off). */
    public function show_friendly_500()
    {
        $heading = 'Something went wrong';
        $message = 'We hit an unexpected problem loading this page.';
        echo parent::show_error($heading, $message, 'error_general', 500);
    }

    /* ------------------------------------------------------------ */

    private function _record(array $e)
    {
        if (self::$recording) {
            return;
        }
        self::$recording = true;

        try {
            if (! function_exists('get_instance') || ! class_exists('CI_Controller', false)) {
                return;
            }
            $CI =& get_instance();
            if (! $CI || ! isset($CI->db) || ! is_object($CI->db) || ! $CI->db->conn_id) {
                return;
            }

            $debug = $CI->db->db_debug;
            $CI->db->db_debug = false;            // never let this insert throw a DB error page

            if (! isset($CI->Error_model)) {
                $CI->load->model('Error_model');
            }

            $session = isset($CI->session) ? $CI->session : null;
            $e['url']        = $this->_current_url();
            $e['user_id']    = $session ? $session->userdata('user_id') : null;
            $e['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
            $e['ip_address'] = isset($CI->input) ? $CI->input->ip_address() : null;

            $ref = $CI->Error_model->record($e);
            if ($ref) {
                self::$last_reference = $ref;
            }

            $CI->db->db_debug = $debug;
        } catch (Throwable $t) {
            // swallow - recording must never break the page
        } finally {
            self::$recording = false;
        }
    }

    private function _level_for($severity)
    {
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_PARSE, E_RECOVERABLE_ERROR], true)) {
            return 'critical';
        }
        if (in_array($severity, [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING], true)) {
            return 'high';
        }
        return 'low'; // notices
    }

    private function _short($text, $max = 250)
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) $text));
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1) . '…' : $text;
    }

    private function _current_path()
    {
        return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    }

    private function _current_url()
    {
        if (empty($_SERVER['HTTP_HOST'])) {
            return null;
        }
        $https = ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $this->_current_path();
    }
}
