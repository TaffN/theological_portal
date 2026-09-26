<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Open to everyone (logged in or not):
 *   /support/report   - "Report a problem" (form page + AJAX from the modal)
 *   /support/js       - silent JavaScript-error beacon from app.js
 *   /support/forgot   - forgot password request (admins reset it)
 */
class Support extends CI_Controller
{
    private $categories = [
        'bug'     => ['Something isn\'t working', 'high'],
        'payment' => ['Payment or fees', 'high'],
        'access'  => ['Can\'t see a course or material', 'medium'],
        'account' => ['Login or account', 'medium'],
        'idea'    => ['Suggestion', 'low'],
        'other'   => ['Something else', 'low'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Error_model');
        $this->load->helper('ui');
    }

    /** Help & Contact: the Center's details, a WhatsApp button and FAQs. */
    public function help()
    {
        $this->load->view('templates/header', ['title' => 'Help & contact']);
        $this->load->view('support/help', ['loggedIn' => (bool) $this->session->userdata('user_id'), 'role' => $this->session->userdata('role')]);
        $this->load->view('templates/footer');
    }

    public function report()
    {
        $userId = $this->session->userdata('user_id');

        if ($this->input->method() === 'post') {
            $desc     = trim((string) $this->input->post('description'));
            $category = (string) $this->input->post('category');
            $pageUrl  = trim((string) $this->input->post('page_url'));
            $email    = trim((string) $this->input->post('email'));
            $relRef   = trim((string) $this->input->post('related_ref'));
            $isAjax   = $this->input->is_ajax_request();

            if (mb_strlen($desc) < 5 || ! isset($this->categories[$category])) {
                return $this->_respond($isAjax, false, 'Please choose a category and describe the problem in a few words.', 'support/report');
            }

            list($catLabel, $severity) = $this->categories[$category];
            $who = $userId
                ? $this->session->userdata('name') . ' (' . $this->session->userdata('role') . ')'
                : ($email !== '' ? 'Guest: ' . $email : 'Guest (no email given)');

            $ref = $this->Error_model->record([
                'source'     => 'user',
                'severity'   => $severity,
                'title'      => $catLabel . ': ' . mb_substr(preg_replace('/\s+/', ' ', $desc), 0, 180),
                'details'    => "Reported by: {$who}\nCategory: {$catLabel}\n" . ($relRef ? "Related error: {$relRef}\n" : '') . "\n{$desc}",
                'url'        => $pageUrl ?: null,
                'user_id'    => $userId ?: null,
                'user_agent' => $this->input->user_agent(),
                'ip_address' => $this->input->ip_address(),
            ]);

            $this->_notify_admins('New problem report ' . $ref . ': ' . $catLabel, base_url('admin_errors'));
            $this->audit->log('support.reported', 'error_report', null, 'Reported a problem (' . $catLabel . ') - ' . $ref);

            return $this->_respond($isAjax, true, 'Thanks! Your report ' . $ref . ' has been sent to the team.', $userId ? 'dashboard' : 'auth/login', $ref);
        }

        $this->load->view('templates/header', ['title' => 'Report a problem']);
        $this->load->view('support/report', [
            'categories' => $this->categories,
            'from'       => (string) $this->input->get('from'),
            'ref'        => preg_replace('/[^A-Z0-9-]/', '', (string) $this->input->get('ref')),
            'guest'      => ! $userId,
        ]);
        $this->load->view('templates/footer');
    }

    /**
     * Receives window.onerror reports. Silent, tiny, and capped per session so
     * a looping script can't flood the table.
     */
    public function js()
    {
        $count = (int) $this->session->userdata('js_err_count');
        if ($this->input->method() !== 'post' || $count >= 20) {
            return $this->output->set_status_header(204);
        }
        $this->session->set_userdata('js_err_count', $count + 1);

        $raw  = json_decode((string) $this->input->raw_input_stream, true);
        $data = is_array($raw) ? $raw : $this->input->post();

        $msg = isset($data['message']) ? mb_substr((string) $data['message'], 0, 250) : 'Unknown script error';
        $src = isset($data['source']) ? (string) $data['source'] : '';
        $line = isset($data['line']) ? (int) $data['line'] : 0;

        // Browser extensions and cross-origin scripts produce useless "Script error."
        if ($msg === 'Script error.' || stripos($src, 'extension://') !== false) {
            return $this->output->set_status_header(204);
        }

        $this->Error_model->record([
            'source'     => 'javascript',
            'severity'   => 'medium',
            'title'      => $msg,
            'details'    => "Script: {$src}:{$line}\n\n" . (isset($data['stack']) ? mb_substr((string) $data['stack'], 0, 5000) : ''),
            'url'        => isset($data['url']) ? (string) $data['url'] : null,
            'group_key'  => basename(parse_url($src, PHP_URL_PATH) ?: '') . ':' . $line,
            'user_id'    => $this->session->userdata('user_id') ?: null,
            'user_agent' => $this->input->user_agent(),
            'ip_address' => $this->input->ip_address(),
        ]);

        return $this->output->set_status_header(204);
    }

    public function forgot()
    {
        if ($this->input->method() === 'post') {
            $email = strtolower(trim((string) $this->input->post('email')));

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->load->model('User_model');
                $user = $this->User_model->find_by_email($email);

                if ($user && $user['status'] === 'active') {
                    $this->db->where('id', $user['id'])->update('users', ['reset_requested_at' => date('Y-m-d H:i:s')]);
                    $this->_notify_admins($user['name'] . ' asked for a password reset.', base_url('admin_users/students?reset=1'));
                    $this->audit->log('auth.reset_requested', 'user', $user['id'], $user['name'] . ' requested a password reset', [],
                        ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]);
                }
            }

            // Same answer whether or not the email exists, so nobody can use
            // this form to discover who has an account.
            $this->session->set_flashdata('success', 'If that email has an account, the administrator has been notified and will send you a new password on WhatsApp or by phone.');
            return redirect('login');
        }

        $this->load->view('templates/header', ['title' => 'Forgot password']);
        $this->load->view('support/forgot');
        $this->load->view('templates/footer');
    }

    /* ------------------------------------------------------------ */

    private function _notify_admins($message, $link)
    {
        $this->load->library('notifier');
        $admins = $this->db->select('id')->where('role', 'admin')->where('status', 'active')->get('users')->result_array();
        foreach ($admins as $a) {
            $this->notifier->notify_user($a['id'], $message, $link);
        }
    }

    private function _respond($isAjax, $ok, $message, $redirect, $ref = null)
    {
        if ($isAjax) {
            return $this->output->set_content_type('application/json')
                ->set_status_header($ok ? 200 : 422)
                ->set_output(json_encode(['ok' => $ok, 'message' => $message, 'reference' => $ref]));
        }
        $this->session->set_flashdata($ok ? 'success' : 'error', $message);
        return redirect($redirect);
    }
}
