<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['session', 'form_validation']);
        $this->load->model('User_model');
    }

    public function login()
    {
        if ($this->session->userdata('user_id')) {
            return $this->_redirect_to_dashboard($this->session->userdata('role'));
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
            $this->form_validation->set_rules('password', 'Password', 'required');

            if ($this->form_validation->run()) {
                $email = strtolower(trim($this->input->post('email')));

                // Brute-force protection: 5 wrong passwords in 15 minutes locks the email for 15 minutes.
                if ($this->audit->recent_failed_logins($email, 15) >= 5) {
                    $this->audit->log('auth.locked_out', 'email:' . mb_substr($email, 0, 34), null, 'Login blocked for ' . $email . ' (too many failed attempts)');
                    $this->session->set_flashdata('error', 'Too many wrong passwords. For your security this account is locked for 15 minutes. Use "Forgot password" if you\'re stuck.');
                    $this->session->set_flashdata('old_email', $email);
                    return redirect('login');
                }

                $user = $this->User_model->verify_password($email, $this->input->post('password'));

                if ($user && $user['status'] !== 'active') {
                    $this->audit->log('auth.login_blocked', 'user', $user['id'], $user['name'] . ' tried to log in to a deactivated account', [],
                        ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]);
                    $this->session->set_flashdata('error', 'This account has been deactivated. Please contact the administrator.');
                    return redirect('login');
                }

                if ($user) {
                    $this->session->sess_regenerate(true);   // new session id on login (prevents session fixation)
                    $this->session->set_userdata([
                        'user_id'   => $user['id'],
                        'name'      => $user['name'],
                        'role'      => $user['role'],
                        'id_number' => isset($user['id_number']) ? $user['id_number'] : null,
                        'photo'     => ! empty($user['photo_path']) ? strtotime($user['photo_updated_at']) : null,
                    ]);
                    $this->db->where('id', $user['id'])->update('users', ['last_login_at' => date('Y-m-d H:i:s')]);
                    $this->audit->log('auth.login', 'user', $user['id'], $user['name'] . ' logged in');

                    // Send them back to the page they were trying to open, if any.
                    $next = $this->session->userdata('after_login');
                    if ($next) {
                        $this->session->unset_userdata('after_login');
                        return redirect($next);
                    }
                    return $this->_redirect_to_dashboard($user['role']);
                }

                $this->audit->log('auth.login_failed', 'email:' . mb_substr($email, 0, 34), null, 'Wrong password for ' . $email);
                $left = 5 - $this->audit->recent_failed_logins($email, 15);
                $this->session->set_flashdata('error', 'Incorrect email or password.' . ($left > 0 && $left <= 2 ? ' ' . $left . ' attempt' . ($left == 1 ? '' : 's') . ' left before a 15-minute lock.' : ''));
                $this->session->set_flashdata('old_email', $this->input->post('email'));
                return redirect('login');
            }
        }

        $this->load->view('templates/header', ['title' => 'Log In']);
        $this->load->view('auth/login');
        $this->load->view('templates/footer');
    }

    public function register()
    {
        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('name', 'Name', 'required|min_length[2]');
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[users.email]');
            $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
            $this->form_validation->set_rules('password_confirm', 'Confirm password', 'required|matches[password]');
            $this->form_validation->set_message('is_unique', 'An account with that email already exists. Try logging in instead.');
            $this->form_validation->set_message('matches', 'The two passwords do not match.');

            if ($this->form_validation->run()) {
                $userId = $this->User_model->create([
                    'name'          => $this->input->post('name'),
                    'email'         => $this->input->post('email'),
                    'phone'         => $this->input->post('phone'),
                    'password_hash' => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
                    'role'          => 'student',
                    'status'        => 'active',
                ]);

                $this->audit->log('auth.registered', 'user', $userId, $this->input->post('name') . ' created a student account', [],
                    ['id' => $userId, 'name' => $this->input->post('name'), 'role' => 'student']);
                $this->db->where('id', $userId)->update('users', ['last_login_at' => date('Y-m-d H:i:s')]);
                $this->session->sess_regenerate(true);
                $newUser = $this->User_model->find($userId);
                $this->session->set_userdata([
                    'user_id'   => $userId,
                    'name'      => $this->input->post('name'),
                    'role'      => 'student',
                    'id_number' => isset($newUser['id_number']) ? $newUser['id_number'] : null,
                    'photo'     => null,
                ]);
                $this->session->set_flashdata('success', 'Welcome! Your account has been created' . (! empty($newUser['id_number']) ? '. Your student number is ' . $newUser['id_number'] . '.' : '.'));
                return redirect('courses');
            }
        }

        $this->load->view('templates/header', ['title' => 'Register']);
        $this->load->view('auth/register');
        $this->load->view('templates/footer');
    }

    public function logout()
    {
        if ($this->session->userdata('user_id')) {
            $this->audit->log('auth.logout', 'user', $this->session->userdata('user_id'), $this->session->userdata('name') . ' logged out');
        }
        $this->session->sess_destroy();
        redirect('login');
    }

    private function _redirect_to_dashboard($role)
    {
        // Every role has its own dashboard at the same URL.
        return redirect('dashboard');
    }
}
