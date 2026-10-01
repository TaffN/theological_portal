<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sends an in-app notification (always) and an email (best-effort) to every
 * actively-enrolled student in a module. Used now for "new material posted",
 * and reused in later stages for assignments/exams/results.
 *
 * Email sending failures are logged, not thrown - a broken mail server
 * should never stop the in-app notification or the rest of the request.
 */
class Notifier
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(['Enrollment_model', 'Notification_model']);
    }

    public function notify_module($moduleId, $message, $link = null)
    {
        $students = $this->CI->Enrollment_model->active_students_for_module($moduleId);

        foreach ($students as $student) {
            $this->CI->Notification_model->create($student['id'], $message, $link);
            $this->_send_email($student['email'], $student['name'], $message, $link);
        }

        return count($students);
    }

    public function notify_user($userId, $message, $link = null)
    {
        $this->CI->Notification_model->create($userId, $message, $link);
    }

    private function _send_email($toEmail, $toName, $message, $link = null)
    {
        // Only attempt to send if SMTP has actually been configured -
        // see application/config/email.php. Otherwise, skip quietly.
        $this->CI->config->load('email');

        if (! $this->CI->config->item('smtp_configured')) {
            return;
        }

        $this->CI->load->library('email');
        $this->CI->email->clear(true);
        $this->CI->email->from(
            $this->CI->config->item('from_email'),
            $this->CI->config->item('from_name')
        );
        $this->CI->email->to($toEmail);
        $this->CI->email->subject('Theological Center Portal Notification');

        $body = 'Hi ' . $toName . ',<br><br>' . $message;
        if ($link) {
            $body .= '<br><br><a href="' . $link . '">View in the portal</a>';
        }
        $this->CI->email->message($body);

        if (! $this->CI->email->send()) {
            log_message('error', 'Notification email failed for ' . $toEmail . ': ' . $this->CI->email->print_debugger(['headers']));
        }
    }
}
