<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller {
    public function index()
    {
        $this->load->library('migration');
        if ($this->migration->latest() === FALSE)
        {
            show_error($this->migration->error_string());
        }
        else
        {
            $this->load->config('migration');
            $ver = $this->config->item('migration_version');
            echo "Migrations up to date. Current version: $ver <br>";
            echo "Admin login: admin@example.com / ChangeMe123!";
        }
    }
}