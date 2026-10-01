<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Every role: update name/phone and change password.
 */
class Profile extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->helper('ui');
    }

    public function index()
    {
        $this->load->view('templates/header', ['title' => 'My Profile']);
        $user = $this->User_model->find($this->current_user_id);
        $profile = $this->User_model->get_profile($user['id']);
        $this->load->view('profile/index', [
            'user'        => $user,
            'u'           => $user,
            'profile'     => $profile,
            'cardModules' => $this->User_model->card_modules($user),
            'complete'    => $this->User_model->completeness($user, $profile),
        ]);
        $this->load->view('templates/footer');
    }

    public function update_details()
    {
        $this->form_validation->set_rules('name', 'Full name', 'required|min_length[2]|max_length[150]');
        $this->form_validation->set_rules('phone', 'Phone', 'max_length[30]');

        if (! $this->form_validation->run()) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
            return redirect('profile');
        }

        $name = trim($this->input->post('name'));
        $this->User_model->update($this->current_user_id, [
            'name'  => $name,
            'phone' => trim((string) $this->input->post('phone')),
        ]);
        $this->session->set_userdata('name', $name);
        $this->audit->log('profile.updated', 'user', $this->current_user_id, $name . ' updated their details');

        $this->session->set_flashdata('success', 'Your details have been updated.');
        redirect('profile');
    }

    public function save_more()
    {
        $input = $this->input->post();
        if ($this->current_role !== 'lecturer') {
            unset($input['qualifications'], $input['bio']);
        }
        $this->User_model->save_profile($this->current_user_id, $input);
        $this->audit->log('profile.details_updated', 'user', $this->current_user_id, $this->session->userdata('name') . ' updated their personal details');
        $this->session->set_flashdata('success', 'Your details have been saved.');
        redirect('profile#details');
    }

    public function change_password()
    {
        $user = $this->User_model->find($this->current_user_id);

        if (! password_verify((string) $this->input->post('current_password'), $user['password_hash'])) {
            $this->audit->log('profile.password_change_failed', 'user', $this->current_user_id, $user['name'] . ' entered a wrong current password');
            $this->session->set_flashdata('error', 'Your current password is not correct.');
            return redirect('profile');
        }

        $this->form_validation->set_rules('new_password', 'New password', 'required|min_length[6]');
        $this->form_validation->set_rules('new_password_confirm', 'Confirm new password', 'required|matches[new_password]');
        $this->form_validation->set_message('matches', 'The two new passwords do not match.');

        if (! $this->form_validation->run()) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
            return redirect('profile');
        }

        $this->User_model->set_password($this->current_user_id, $this->input->post('new_password'));
        $this->audit->log('profile.password_changed', 'user', $this->current_user_id, $user['name'] . ' changed their password');
        $this->session->set_flashdata('success', 'Password changed. Use your new password next time you log in.');
        redirect('profile');
    }
}
