<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public page opened by scanning the QR code on a statement of results:
 *   /results/verify/{token}
 * Shows the results the Center has actually published for that student, so
 * anyone (an employer, a church) can check a printed statement wasn't altered.
 */
class Result_verify extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Result_model');
        $this->load->helper('ui');
    }

    public function index($token = null)
    {
        if (! $this->db->field_exists('results_token', 'users')) {
            show_404();
        }
        $user    = $this->Result_model->find_by_token($token);
        $results = $user ? $this->Result_model->for_student($user['id']) : [];

        if ($user) {
            $viewer = $this->session->userdata('role');
            $this->audit->log('result.verified', 'user', $user['id'], 'Statement of results of ' . $user['name'] . ' (' . $user['id_number'] . ') was checked'
                . ($viewer ? ' by a signed-in ' . $viewer : ''));
        }
        $profile = null;
        if ($user && $this->db->table_exists('user_profiles')) {
            $profile = $this->db->select('title')->where('user_id', $user['id'])->get('user_profiles')->row_array();
        }

        $this->load->view('verify/results', [
            'u'       => $user,
            'name'    => $user ? trim((! empty($profile['title']) ? $profile['title'] . ' ' : '') . $user['name']) : '',
            'results' => $results,
        ]);
    }
}
