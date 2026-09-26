<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_announcements extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('ui');
    }

    public function index()
    {
        $rows = $this->db->select('announcements.*, users.name AS author')
            ->from('announcements')
            ->join('users', 'users.id = announcements.created_by', 'left')
            ->order_by('announcements.id', 'DESC')
            ->get()->result_array();

        $this->load->view('templates/header', ['title' => 'Announcements']);
        $this->load->view('admin/announcements', ['rows' => $rows]);
        $this->load->view('templates/footer');
    }

    public function create()
    {
        $this->form_validation->set_rules('title', 'Title', 'required|max_length[150]');
        $this->form_validation->set_rules('body', 'Message', 'required|max_length[2000]');
        $this->form_validation->set_rules('audience', 'Audience', 'required|in_list[all,student,lecturer]');
        $this->form_validation->set_rules('tone', 'Style', 'required|in_list[info,success,warning]');

        if (! $this->form_validation->run()) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
            return redirect('admin_announcements');
        }

        $days = (int) $this->input->post('days');
        $this->db->insert('announcements', [
            'title'      => $this->input->post('title'),
            'body'       => $this->input->post('body'),
            'audience'   => $this->input->post('audience'),
            'tone'       => $this->input->post('tone'),
            'is_active'  => 1,
            'expires_at' => $days > 0 ? date('Y-m-d 23:59:59', strtotime('+' . $days . ' days')) : null,
            'created_by' => $this->current_user_id,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = $this->db->insert_id();

        $this->audit->log('announcement.posted', 'announcement', $id, 'Posted announcement "' . $this->input->post('title') . '" to ' . $this->input->post('audience'));
        $this->session->set_flashdata('success', 'Announcement posted. It now shows on dashboards.');
        redirect('admin_announcements');
    }

    public function toggle($id)
    {
        $row = $this->db->where('id', $id)->get('announcements')->row_array();
        if (! $row) {
            show_404();
        }
        $this->db->where('id', $id)->update('announcements', ['is_active' => $row['is_active'] ? 0 : 1]);
        $this->audit->log('announcement.' . ($row['is_active'] ? 'hidden' : 'shown'), 'announcement', $id, ($row['is_active'] ? 'Hid' : 'Re-published') . ' announcement "' . $row['title'] . '"');
        redirect('admin_announcements');
    }

    public function delete($id)
    {
        $row = $this->db->where('id', $id)->get('announcements')->row_array();
        if ($row) {
            $this->db->where('id', $id)->delete('announcements');
            $this->audit->log('announcement.deleted', 'announcement', $id, 'Deleted announcement "' . $row['title'] . '"');
            $this->session->set_flashdata('success', 'Announcement deleted.');
        }
        redirect('admin_announcements');
    }
}
