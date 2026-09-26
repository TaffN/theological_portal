<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Students + lecturers: list, search, create lecturers, reset passwords,
 * activate / deactivate. Admin accounts are never touched from here.
 */
class Admin_users extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->helper('ui');
    }

    public function students()
    {
        $students = $this->db
            ->select("users.*,
                SUM(CASE WHEN enrollments.status = 'active' THEN 1 ELSE 0 END) AS active_courses,
                SUM(CASE WHEN enrollments.status = 'pending_payment' THEN 1 ELSE 0 END) AS awaiting,
                COUNT(enrollments.id) AS total_courses", false)
            ->from('users')
            ->join('enrollments', 'enrollments.user_id = users.id', 'left')
            ->where('users.role', 'student')
            ->group_by('users.id')
            ->order_by('users.reset_requested_at IS NULL', '', false)   // reset requests first
            ->order_by('users.created_at', 'DESC')
            ->get()->result_array();

        $this->load->view('templates/header', ['title' => 'Students']);
        $this->load->view('admin/students', ['students' => $students]);
        $this->load->view('templates/footer');
    }

    public function lecturers()
    {
        $lecturers = $this->db
            ->select('users.*, COUNT(course_lecturers.id) AS course_count', false)
            ->from('users')
            ->join('course_lecturers', 'course_lecturers.user_id = users.id', 'left')
            ->where('users.role', 'lecturer')
            ->group_by('users.id')
            ->order_by('users.name', 'ASC')
            ->get()->result_array();

        $this->load->view('templates/header', ['title' => 'Lecturers']);
        $this->load->view('admin/lecturers', ['lecturers' => $lecturers]);
        $this->load->view('templates/footer');
    }

    /** A student's or lecturer's ID card, with the option to set their photo. */
    public function card($id)
    {
        $user = $this->_managed_user($id);
        $this->load->view('templates/header', ['title' => $user['name']]);
        $this->load->view('admin/user_card', [
            'u'           => $user,
            'cardCourses' => $this->User_model->card_courses($user),
        ]);
        $this->load->view('templates/footer');
    }

    public function create_lecturer()
    {
        $this->form_validation->set_rules('name', 'Name', 'required|min_length[2]|max_length[150]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[users.email]');
        $this->form_validation->set_message('is_unique', 'An account with that email already exists.');

        if (! $this->form_validation->run()) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
            return redirect('admin_users/lecturers');
        }

        $temp = $this->_temp_password();
        $id = $this->User_model->create([
            'name'          => $this->input->post('name'),
            'email'         => strtolower(trim($this->input->post('email'))),
            'phone'         => $this->input->post('phone'),
            'password_hash' => password_hash($temp, PASSWORD_DEFAULT),
            'role'          => 'lecturer',
            'status'        => 'active',
        ]);

        $this->audit->log('user.lecturer_created', 'user', $id, 'Created lecturer account for ' . $this->input->post('name'));
        $this->_flash_credentials($id, $temp, 'Lecturer account created.');
        redirect('admin_users/lecturers');
    }

    public function reset_password($id)
    {
        $user = $this->_managed_user($id);
        $temp = $this->_temp_password();

        $this->User_model->update($id, [
            'password_hash'      => password_hash($temp, PASSWORD_DEFAULT),
            'reset_requested_at' => null,
        ]);

        $this->audit->log('user.password_reset', 'user', $id, 'Reset password for ' . $user['name']);
        $this->_flash_credentials($id, $temp, 'Password reset for ' . $user['name'] . '.');
        redirect($user['role'] === 'lecturer' ? 'admin_users/lecturers' : 'admin_users/students');
    }

    public function toggle_status($id)
    {
        $user   = $this->_managed_user($id);
        $status = $user['status'] === 'active' ? 'inactive' : 'active';

        $this->User_model->update($id, ['status' => $status]);
        $this->audit->log('user.' . ($status === 'active' ? 'activated' : 'deactivated'), 'user', $id,
            ($status === 'active' ? 'Re-activated ' : 'Deactivated ') . $user['name']);

        $this->session->set_flashdata('success', $user['name'] . ($status === 'active' ? ' can log in again.' : ' can no longer log in.'));
        redirect($user['role'] === 'lecturer' ? 'admin_users/lecturers' : 'admin_users/students');
    }

    /* ------------------------------------------------------------ */

    private function _managed_user($id)
    {
        $user = $this->User_model->find($id);
        if (! $user || ! in_array($user['role'], ['student', 'lecturer'], true)) {
            show_404();
        }
        return $user;
    }

    /** Easy to read out over the phone: no 0/O, 1/l/I. e.g. "Kite-4827" */
    private function _temp_password()
    {
        $words = ['Grace', 'Faith', 'Hope', 'Light', 'Peace', 'Kite', 'River', 'Cedar', 'Olive', 'Stone', 'Shield', 'Crown'];
        return $words[random_int(0, count($words) - 1)] . '-' . random_int(2000, 9899);
    }

    /**
     * Shows the new password once (it's never stored in plain text), with a
     * ready-made WhatsApp message.
     */
    private function _flash_credentials($userId, $password, $headline)
    {
        $u = $this->User_model->find($userId);
        $this->session->set_flashdata('credentials', [
            'headline' => $headline,
            'name'     => $u['name'],
            'email'    => $u['email'],
            'id_number'=> $u['id_number'],
            'phone'    => $u['phone'],
            'password' => $password,
            'login'    => base_url('auth/login'),
        ]);
    }
}
