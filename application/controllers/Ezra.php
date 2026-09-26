<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ezra, the AI study assistant: the chat page and the calls it makes.
 *   ezra                 - the conversation (and the reason if Ezra is paused)
 *   ezra/ask             - POST, JSON: asks one question, returns the answer as HTML
 *   ezra/new_thread      - POST: starts a new conversation
 * Who gets Ezra is set by the administrator (Settings on the Ezra page).
 */
class Ezra extends Auth_Controller
{
    protected $user;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('ui');
        $this->load->library('ezra_ai');
        $this->load->model('User_model');
        if (! $this->db->table_exists('ezra_messages')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'Ezra needs a database update first. An administrator should run the latest update (/migrate).');
            redirect('dashboard');
        }
        if (! $this->ezra_ai->offered_to($this->current_role)) {
            $this->session->set_flashdata('error', 'Ezra is not available for your account.');
            redirect('dashboard');
        }
        $this->user = $this->User_model->find($this->current_user_id);
    }

    public function index()
    {
        $this->ezra_ai->maybe_purge();
        list($canAsk, $reason) = $this->ezra_ai->availability($this->user);
        $thread = $this->ezra_ai->active_thread($this->user['id']);
        $limit  = (int) $this->ezra_ai->setting('daily_limit', '25');

        $this->load->view('templates/header', ['title' => 'Ask Ezra']);
        $this->load->view('ezra/index', [
            'messages'  => $this->ezra_ai->messages($this->user['id'], $thread),
            'canAsk'    => $canAsk,
            'reason'    => $reason,
            'left'      => $limit > 0 ? max(0, $limit - $this->ezra_ai->questions_today($this->user['id'])) : null,
            'firstName' => display_first_name($this->user['name'], $this->current_role),
            'maxLength' => Ezra_ai::MAX_QUESTION,
        ]);
        $this->load->view('templates/footer');
    }

    public function ask()
    {
        if ($this->input->method() !== 'post') {
            return redirect('ezra');
        }
        $question = trim((string) $this->input->post('question'));
        if ($question === '') {
            return $this->_json(['ok' => false, 'error' => 'Please type a question.'], 422);
        }
        list($canAsk, $reason) = $this->ezra_ai->availability($this->user);
        if (! $canAsk) {
            return $this->_json(['ok' => false, 'error' => $reason, 'paused' => true], 403);
        }
        // Only one question at a time per person (a double tap would pay twice).
        if ((int) $this->session->userdata('ezra_busy') > time() - 120) {
            return $this->_json(['ok' => false, 'error' => 'Ezra is still answering your last question.'], 429);
        }
        $this->session->set_userdata('ezra_busy', time());
        session_write_close();   // don't hold the session lock for the whole API call (other tabs keep working)

        @set_time_limit(150);
        $result = $this->ezra_ai->ask($this->user, $question);

        $this->_reopen_session();
        $this->session->unset_userdata('ezra_busy');

        $limit = (int) $this->ezra_ai->setting('daily_limit', '25');
        $this->_json([
            'ok'     => $result['ok'],
            'status' => $result['status'],
            'html'   => ezra_format($result['answer']),
            'left'   => $limit > 0 ? max(0, $limit - $this->ezra_ai->questions_today($this->user['id'])) : null,
        ]);
    }

    public function new_thread()
    {
        if ($this->input->method() === 'post') {
            $this->ezra_ai->new_thread($this->user['id']);
        }
        redirect('ezra');
    }

    private function _reopen_session()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    private function _json(array $data, $status = 200)
    {
        $this->output->set_status_header($status)
            ->set_header('Cache-Control: no-store')
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}
