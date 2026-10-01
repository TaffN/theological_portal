<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public page opened by scanning the QR code on an ID card:
 *   /verify/{token}
 * Confirms the card is genuine and whether the holder is currently active.
 * Deliberately shows very little to the public (name, ID, role, status);
 * logged-in staff also see the photo and a link to the full record.
 */
class Verify extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->helper('ui');
    }

    public function index($token = null)
    {
        $user = $token ? $this->User_model->find_by_token($token) : null;
        $viewerRole = $this->session->userdata('role');
        $isStaff = in_array($viewerRole, ['admin', 'lecturer'], true);

        if ($user) {
            $this->audit->log('id.verified', 'user', $user['id'], 'ID card of ' . $user['name'] . ' (' . $user['id_number'] . ') was scanned'
                . ($viewerRole ? ' by a signed-in ' . $viewerRole : ''));
        }

        $issued   = $user && $user['photo_updated_at'] ? $user['photo_updated_at'] : ($user ? $user['created_at'] : null);
        $this->load->view('verify/index', [
            'u'         => $user,
            'isStaff'   => $isStaff,
            'isAdmin'   => $viewerRole === 'admin',
            'modules'   => $user ? $this->User_model->card_modules($user) : [],
            'profile'   => $user ? $this->User_model->get_profile($user['id']) : null,
        ]);
    }
}
