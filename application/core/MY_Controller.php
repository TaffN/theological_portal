<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller: just requires the user to be logged in.
 * Assumes a simple session-based login sets 'user_id', 'name' and 'role'
 * in session once we build the auth/login screens in a later step.
 */
class Auth_Controller extends CI_Controller
{
    protected $current_user_id;
    protected $current_role;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');

        if (! $this->session->userdata('user_id')) {
            if ($this->input->method() === 'get' && ! $this->input->is_ajax_request()) {
                $this->session->set_userdata('after_login', $this->uri->uri_string());
            }
            $this->session->set_flashdata('error', 'Please log in to continue.');
            redirect('login');
        }

        $this->current_user_id = $this->session->userdata('user_id');
        $this->current_role    = $this->session->userdata('role');
    }
}

/**
 * Extend this in any controller that only Admins should reach,
 * e.g.: class Courses extends Admin_Controller { ... }
 */
class Admin_Controller extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();

        if ($this->current_role !== 'admin') {
            show_error('You do not have access to that area.', 403);
        }
    }
}

/**
 * Extend this in any controller that only Lecturers should reach.
 */
class Lecturer_Controller extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();

        if ($this->current_role !== 'lecturer') {
            show_error('You do not have access to that area.', 403);
        }
    }
}

/**
 * Extend this in any controller that only Students should reach.
 */
class Student_Controller extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();

        if ($this->current_role !== 'student') {
            show_error('You do not have access to that area.', 403);
        }
    }
}
