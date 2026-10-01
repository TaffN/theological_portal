<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Students no longer apply for single modules: fees and applications are per program (see Programs).
 * These addresses only send old bookmarks to the right place.
 */
class Modules extends Student_Controller
{
    public function index()
    {
        redirect('programs');
    }

    public function apply($moduleId = 0)
    {
        $this->load->model('Module_model');
        $module = $this->Module_model->find($moduleId);
        $this->session->set_flashdata('error', 'Applications are now made for a whole program. Open the program and choose Apply.');
        redirect($module ? 'programs/' . $module['program_slug'] : 'programs');
    }
}
