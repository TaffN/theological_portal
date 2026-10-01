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

        $this->_require_current_database();
    }

    /**
     * New code on an old database (the files were updated but /migrate has not been run yet) would
     * crash every page with a 500. Say what to do instead. Checked on the table the latest
     * migration creates; /migrate itself is a separate controller, so it always works.
     */
    protected function _require_current_database()
    {
        if ($this->db->table_exists('modules') && $this->db->table_exists('programs') && $this->db->table_exists('program_enrollments')) {
            return;
        }
        $this->output->set_status_header(503);
        $code  = 'Update';
        $title = 'The portal needs a database update';
        $text  = 'The program files have been updated, but the database has not been updated to match yet. '
               . '<strong>First back up the database</strong> (phpMyAdmin, then Export, then Go). '
               . 'Then open <a href="' . base_url('migrate') . '">' . base_url('migrate') . '</a>. When it says "Migrations up to date", come back here.';
        $techDetails = '';
        $customButtons = '<a class="btn primary" href="' . base_url('migrate') . '">Open the update page</a> <a class="btn" href="' . base_url('logout') . '">Log out</a>';
        include APPPATH . 'views/errors/html/_portal_error.php';
        exit;
    }

    /**
     * Streams a private upload (e.g. uploads/submissions/...) to the browser
     * under a friendly name. Call it only AFTER checking the user may see it.
     * Images and PDFs open in the browser; everything else downloads.
     */
    protected function _send_file($relativePath, $downloadName = null)
    {
        $fullPath = FCPATH . $relativePath;
        if (! $relativePath || ! is_file($fullPath)) {
            show_404();
        }

        $name = $downloadName ? $downloadName : basename($fullPath);
        $name = trim(preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/', '-', $name), ' -.');   // characters Windows won't allow in file names
        $mime = function_exists('mime_content_type') ? mime_content_type($fullPath) : 'application/octet-stream';
        $inline = in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true);

        header('Content-Type: ' . ($mime ? $mime : 'application/octet-stream'));
        header('Content-Length: ' . filesize($fullPath));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0');
        readfile($fullPath);
        exit;
    }

    /**
     * Saves an optional uploaded file into uploads/$folder/ under a random name.
     * Returns ['path' => ..., 'name' => original name], null when no file was
     * chosen, or false on failure (the reason is in $error).
     */
    protected function _store_upload($field, $folder, $allowedTypes, $maxKb, &$error)
    {
        $error = '';
        if (empty($_FILES[$field]['name'])) {
            return null;
        }

        $dir = FCPATH . 'uploads/' . $folder . '/';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $config = [
            'upload_path'   => $dir,
            'allowed_types' => $allowedTypes,
            'max_size'      => $maxKb,
            'encrypt_name'  => true,
        ];
        $this->load->library('upload');
        $this->upload->initialize($config, true);

        if (! $this->upload->do_upload($field)) {
            $error = $this->upload->display_errors('', '');
            return false;
        }

        $data = $this->upload->data();
        return [
            'path' => 'uploads/' . $folder . '/' . $data['file_name'],
            'name' => mb_substr($data['client_name'], 0, 250),
        ];
    }
}

/**
 * Extend this in any controller that only Admins should reach,
 * e.g.: class Modules extends Admin_Controller { ... }
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
