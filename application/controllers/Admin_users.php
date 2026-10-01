<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Students + lecturers: list, search, create lecturers, reset passwords,
 * activate / deactivate. Administrators have their own page (admins()),
 * where nobody can lock themselves out or deactivate the last admin.
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
                SUM(CASE WHEN enrollments.status = 'active' THEN 1 ELSE 0 END) AS active_modules,
                SUM(CASE WHEN enrollments.status = 'pending_payment' THEN 1 ELSE 0 END) AS awaiting,
                COUNT(enrollments.id) AS total_modules", false)
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
            ->select('users.*, COUNT(module_lecturers.id) AS module_count', false)
            ->from('users')
            ->join('module_lecturers', 'module_lecturers.user_id = users.id', 'left')
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
        $profile = $this->User_model->get_profile($user['id']);
        $this->load->view('admin/user_card', [
            'u'           => $user,
            'profile'     => $profile,
            'cardModules' => $this->User_model->card_modules($user),
            'complete'    => $this->User_model->completeness($user, $profile),
        ]);
        $this->load->view('templates/footer');
    }

    public function update_account($id)
    {
        $user = $this->_managed_user($id);
        $this->form_validation->set_rules('name', 'Name', 'required|min_length[2]|max_length[150]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $email = strtolower(trim((string) $this->input->post('email')));

        if (! $this->form_validation->run()) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
        } elseif ($email !== $user['email'] && $this->db->where('email', $email)->where('id !=', $id)->count_all_results('users') > 0) {
            $this->session->set_flashdata('error', 'Another account already uses ' . $email . '.');
        } else {
            $this->User_model->update($id, ['name' => trim($this->input->post('name')), 'email' => $email, 'phone' => trim((string) $this->input->post('phone'))]);
            $this->audit->log('user.account_updated', 'user', $id, 'Updated account details of ' . $user['name'] . ' (' . $user['id_number'] . ')'
                . ($email !== $user['email'] ? ', email ' . $user['email'] . ' -> ' . $email : ''));
            $this->session->set_flashdata('success', 'Account details saved.');
        }
        redirect('admin_users/card/' . $id);
    }

    public function save_profile($id)
    {
        $user = $this->_managed_user($id);
        $this->User_model->save_profile($id, $this->input->post());
        $this->audit->log('user.profile_updated', 'user', $id, 'Updated personal details of ' . $user['name'] . ' (' . $user['id_number'] . ')');
        $this->session->set_flashdata('success', 'Details saved.');
        redirect('admin_users/card/' . $id . '#details');
    }

    /** Lost card: issue a new QR code so the old card no longer verifies. */
    public function reissue_card($id)
    {
        $user = $this->_managed_user($id);
        $this->User_model->new_verify_token($id);
        $this->audit->log('user.card_reissued', 'user', $id, 'Re-issued ID card for ' . $user['name'] . ' (' . $user['id_number'] . '); old QR code cancelled');
        $this->session->set_flashdata('success', 'New ID card issued. The old card\'s QR code now shows "Card not recognised". Print the new one.');
        redirect('admin_users/card/' . $id);
    }

    /** Everything about every student, for Excel. */
    public function export_students()
    {
        $rows = $this->db->select('users.id_number, users.name, users.email, users.phone, users.status, users.created_at, users.last_login_at, user_profiles.*')
            ->from('users')->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('users.role', 'student')->order_by('users.id_number', 'ASC')->get()->result_array();

        $this->audit->log('user.students_exported', null, null, 'Exported ' . count($rows) . ' student records to CSV');

        $cols = ['id_number' => 'Student no.', 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'alt_phone' => 'Alt. phone',
            'status' => 'Status', 'date_of_birth' => 'Date of birth', 'gender' => 'Gender', 'national_id' => 'National ID',
            'address_line1' => 'Address', 'address_line2' => 'Address 2', 'city' => 'City', 'province' => 'Province', 'country' => 'Country', 'postal_code' => 'Postal code',
            'emergency_name' => 'Emergency contact', 'emergency_relationship' => 'Relationship', 'emergency_phone' => 'Emergency phone',
            'church_name' => 'Church', 'denomination' => 'Denomination', 'ministry_role' => 'Ministry role',
            'education_level' => 'Education', 'occupation' => 'Occupation', 'referral_source' => 'Heard about us via',
            'created_at' => 'Registered', 'last_login_at' => 'Last login'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="students-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_values($cols));
        foreach ($rows as $r) {
            $line = [];
            foreach (array_keys($cols) as $k) {
                $line[] = isset($r[$k]) ? $r[$k] : '';
            }
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
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

    /* ------------------------------------------------------ ADMINS */

    public function admins()
    {
        $admins = $this->db->where('role', 'admin')->order_by('name', 'ASC')->get('users')->result_array();

        $this->load->view('templates/header', ['title' => 'Administrators']);
        $this->load->view('admin/admins', ['admins' => $admins, 'me' => (int) $this->current_user_id]);
        $this->load->view('templates/footer');
    }

    public function create_admin()
    {
        $this->form_validation->set_rules('name', 'Name', 'required|min_length[2]|max_length[150]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[users.email]');
        $this->form_validation->set_message('is_unique', 'An account with that email already exists.');

        if (! $this->form_validation->run()) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
            return redirect('admin_users/admins');
        }

        $temp = $this->_temp_password();
        $id = $this->User_model->create([
            'name'          => trim($this->input->post('name')),
            'email'         => strtolower(trim($this->input->post('email'))),
            'phone'         => trim((string) $this->input->post('phone')),
            'password_hash' => password_hash($temp, PASSWORD_DEFAULT),
            'role'          => 'admin',
            'status'        => 'active',
        ]);

        $this->audit->log('user.admin_created', 'user', $id, 'Created administrator account for ' . trim($this->input->post('name')));
        $this->_flash_credentials($id, $temp, 'Administrator account created.');
        redirect('admin_users/admins');
    }

    /** Name, email and phone of any admin, including yourself. */
    public function update_admin($id)
    {
        $admin = $this->_admin_account($id, true);
        $this->form_validation->set_rules('name', 'Name', 'required|min_length[2]|max_length[150]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $email = strtolower(trim((string) $this->input->post('email')));

        if (! $this->form_validation->run()) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
        } elseif ($email !== $admin['email'] && $this->db->where('email', $email)->where('id !=', $id)->count_all_results('users') > 0) {
            $this->session->set_flashdata('error', 'Another account already uses ' . $email . '.');
        } else {
            $name = trim($this->input->post('name'));
            $this->User_model->update($id, ['name' => $name, 'email' => $email, 'phone' => trim((string) $this->input->post('phone'))]);
            if ((int) $id === (int) $this->current_user_id) {
                $this->session->set_userdata('name', $name);   // top bar shows the new name straight away
            }
            $this->audit->log('user.account_updated', 'user', $id, 'Updated administrator details of ' . $admin['name']
                . ($email !== $admin['email'] ? ', email ' . $admin['email'] . ' -> ' . $email : ''));
            $this->session->set_flashdata('success', 'Details saved.' . ($email !== $admin['email'] ? ' Log in with ' . $email . ' from now on.' : ''));
        }
        redirect('admin_users/admins');
    }

    public function reset_admin_password($id)
    {
        $admin = $this->_admin_account($id);
        $temp  = $this->_temp_password();

        $this->User_model->update($id, ['password_hash' => password_hash($temp, PASSWORD_DEFAULT), 'reset_requested_at' => null]);
        $this->audit->log('user.password_reset', 'user', $id, 'Reset password for administrator ' . $admin['name']);
        $this->_flash_credentials($id, $temp, 'Password reset for ' . $admin['name'] . '.');
        redirect('admin_users/admins');
    }

    public function toggle_admin_status($id)
    {
        $admin  = $this->_admin_account($id);
        $status = $admin['status'] === 'active' ? 'inactive' : 'active';

        $this->User_model->update($id, ['status' => $status]);
        $this->audit->log('user.' . ($status === 'active' ? 'activated' : 'deactivated'), 'user', $id,
            ($status === 'active' ? 'Re-activated administrator ' : 'Deactivated administrator ') . $admin['name']);

        $this->session->set_flashdata('success', $admin['name'] . ($status === 'active' ? ' can log in again.' : ' can no longer log in.'));
        redirect('admin_users/admins');
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

    /**
     * An administrator account. Passwords and status can only be changed for
     * *other* admins (your own password is changed on My Profile), so you
     * can never lock yourself out, and there is always one admin left.
     */
    private function _admin_account($id, $allowSelf = false)
    {
        $user = $this->User_model->find($id);
        if (! $user || $user['role'] !== 'admin' || $this->input->method() !== 'post') {
            show_404();   // admin accounts only change through the buttons on the page (POST), never a plain link
        }
        if (! $allowSelf && (int) $user['id'] === (int) $this->current_user_id) {
            $this->session->set_flashdata('error', 'You can\'t do that to your own account. Change your password on My Profile.');
            redirect('admin_users/admins');   // redirect() stops the request here
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
