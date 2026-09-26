<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ezra for administrators: what it costs this month against the limit, who
 * uses it (counts only; conversations stay private), and its settings,
 * including the statement of faith it follows.
 */
class Admin_ezra extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['ui', 'chart']);
        if (! $this->db->table_exists('ezra_messages')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'Ezra needs a database update first. Run the latest update (/migrate).');
            redirect('dashboard');
        }
        $this->load->library('ezra_ai');
    }

    public function index()
    {
        $this->load->view('templates/header', ['title' => 'Ezra (AI assistant)']);
        $this->load->view('admin/ezra', [
            'usage'      => $this->ezra_ai->usage_summary(),
            'configured' => $this->ezra_ai->is_configured(),
            'cap'        => (float) $this->ezra_ai->setting('monthly_cap_usd', '50'),
            's'          => [
                'enabled'   => $this->ezra_ai->setting('enabled', '1'),
                'roles'     => array_map('trim', explode(',', $this->ezra_ai->setting('roles', 'student'))),
                'cap'       => $this->ezra_ai->setting('monthly_cap_usd', '50'),
                'daily'     => $this->ezra_ai->setting('daily_limit', '25'),
                'model'     => $this->ezra_ai->model(),
                'effort'    => $this->ezra_ai->setting('effort', 'low'),
                'bible'     => $this->ezra_ai->setting('bible_version', 'NKJV'),
                'retention' => $this->ezra_ai->setting('retention_days', '365'),
                'faith'     => $this->ezra_ai->setting('statement_of_faith', ''),
            ],
            'models'     => Ezra_ai::$models,
        ]);
        $this->load->view('templates/footer');
    }

    public function save()
    {
        if ($this->input->method() !== 'post') {
            return redirect('admin_ezra');
        }
        $roles = array_values(array_intersect((array) $this->input->post('roles'), ['student', 'lecturer']));
        $cap   = $this->input->post('monthly_cap_usd');
        $daily = $this->input->post('daily_limit');
        $keep  = $this->input->post('retention_days');
        $faith = trim((string) $this->input->post('statement_of_faith'));
        $model = (string) $this->input->post('model');
        $effort = (string) $this->input->post('effort');

        $problems = [];
        if (! is_numeric($cap) || $cap < 0 || $cap > 10000) { $problems[] = 'the monthly limit must be a number of dollars (0 to 10000)'; }
        if (! ctype_digit((string) $daily) || $daily > 500) { $problems[] = 'the daily limit must be a whole number (0 to 500)'; }
        if (! ctype_digit((string) $keep) || ($keep > 0 && $keep < 31)) { $problems[] = 'keep conversations for at least 31 days (or 0 = forever)'; }
        if (mb_strlen($faith) < 20) { $problems[] = 'the statement of faith can\'t be empty'; }
        if (! isset(Ezra_ai::$models[$model])) { $problems[] = 'choose a model from the list'; }
        if (! in_array($effort, ['low', 'medium', 'high'], true)) { $problems[] = 'choose an answer depth from the list'; }
        if ($problems) {
            $this->session->set_flashdata('error', 'Not saved: ' . implode('; ', $problems) . '.');
            return redirect('admin_ezra#settings');
        }

        $before = $this->ezra_ai->setting('monthly_cap_usd', '50');
        $this->settings->save([
            'ezra_enabled'            => $this->input->post('enabled') ? '1' : '0',
            'ezra_roles'              => implode(',', $roles),
            'ezra_monthly_cap_usd'    => (string) round((float) $cap, 2),
            'ezra_daily_limit'        => (string) (int) $daily,
            'ezra_model'              => $model,
            'ezra_effort'             => $effort,
            'ezra_bible_version'      => mb_substr(trim((string) $this->input->post('bible_version')), 0, 40) ?: 'NKJV',
            'ezra_retention_days'     => (string) (int) $keep,
            'ezra_statement_of_faith' => mb_substr($faith, 0, 8000),
        ]);
        if ((float) $cap > (float) $before) {
            $this->settings->save(['ezra_warned_month' => '']);   // a raised limit can warn again at 80%
        }
        $this->audit->log('ezra.settings_saved', null, null, 'Changed Ezra settings (' . ($this->input->post('enabled') ? 'on' : 'off') . ', for ' . ($roles ? implode(' + ', $roles) . 's' : 'nobody')
            . ', limit $' . round((float) $cap, 2) . '/month, ' . (int) $daily . ' questions a day, ' . $model . ')');
        $this->session->set_flashdata('success', 'Ezra settings saved.');
        redirect('admin_ezra');
    }

    /** Sends a tiny question to check the API key (costs a fraction of a cent). */
    public function test()
    {
        if ($this->input->method() !== 'post') {
            return redirect('admin_ezra');
        }
        if ($this->ezra_ai->api_key() === '') {
            $this->session->set_flashdata('error', 'There is no API key yet. Create application/config/ezra.php (see the steps on this page).');
            return redirect('admin_ezra');
        }
        list($ok, $message) = $this->ezra_ai->test_connection();
        $this->audit->log('ezra.tested', null, null, 'Tested the Ezra connection: ' . ($ok ? 'working' : 'failed'));
        $this->session->set_flashdata($ok ? 'success' : 'error', $message);
        redirect('admin_ezra');
    }

    /** Wipes the text of conversations older than the retention period now (it also happens daily on its own). */
    public function purge()
    {
        if ($this->input->method() !== 'post') {
            return redirect('admin_ezra');
        }
        $n = $this->ezra_ai->purge_old();
        $this->audit->log('ezra.purged', null, null, 'Removed the text of ' . $n . ' old Ezra messages');
        $this->session->set_flashdata('success', $n ? 'Removed the text of ' . $n . ' old message' . ($n == 1 ? '' : 's') . '.' : 'Nothing to remove: no conversations are older than the retention period.');
        redirect('admin_ezra');
    }
}
