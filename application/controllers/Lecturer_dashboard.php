<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lecturer_dashboard extends Lecturer_Controller
{
    public function index()
    {
        $this->load->view('templates/header', ['title' => 'Lecturer Dashboard']);
        $this->load->view('lecturer/dashboard');
        $this->load->view('templates/footer');
    }
}
