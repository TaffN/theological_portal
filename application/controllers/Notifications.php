<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Notification_model');
        $this->load->helper('ui');
    }

    public function index()
    {
        $notifications = $this->Notification_model->for_user($this->current_user_id);
        $this->Notification_model->mark_all_read($this->current_user_id);

        $this->load->view('templates/header', ['title' => 'Notifications']);
        $this->load->view('notifications/index', ['notifications' => $notifications]);
        $this->load->view('templates/footer');
    }

    /**
     * Click-through from a single notification straight to what it's about.
     */
    public function open($id)
    {
        $this->Notification_model->mark_read($id, $this->current_user_id);

        $this->load->database();
        $row = $this->db->where('id', $id)->where('user_id', $this->current_user_id)->get('notifications')->row_array();

        if ($row && $row['link']) {
            return redirect($row['link']);
        }

        redirect('notifications');
    }
}
