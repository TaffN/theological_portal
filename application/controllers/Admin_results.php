<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stage 6: administrators see how far each module is with results, read any
 * published result, and export them to Excel. Publishing is the lecturer's job.
 */
class Admin_results extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Result_model', 'Module_model']);
        $this->load->helper('ui');
        if (! $this->db->table_exists('module_results')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'Results need a database update first. An administrator should run the latest update (/migrate).');
            redirect('dashboard');
        }
    }

    public function index()
    {
        $this->load->view('templates/header', ['title' => 'Results']);
        $this->load->view('admin/results', [
            'modules' => $this->Result_model->overview(),
            'bands'   => $this->Result_model->bands(),
        ]);
        $this->load->view('templates/footer');
    }

    public function module($moduleId)
    {
        $module = $this->Module_model->find($moduleId);
        if (! $module) {
            show_404();
        }
        $this->load->view('templates/header', ['title' => 'Results: ' . $module['name']]);
        $this->load->view('admin/results_module', [
            'module' => $module,
            'rows'   => $this->Result_model->published_rows($moduleId),
        ]);
        $this->load->view('templates/footer');
    }

    /** All published results (or one module's) for Excel. */
    public function export($moduleId = null)
    {
        $rows = $this->Result_model->published_rows($moduleId ? (int) $moduleId : null);
        $this->audit->log('result.exported', null, null, 'Exported ' . count($rows) . ' published results to CSV');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="results-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Module', 'Student no.', 'Student', 'Assignments %', 'Exams %', 'Assignment weight %', 'Exam weight %', 'Final %', 'Grade', 'Remarks', 'Published', 'Published by']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['module_name'], $r['id_number'], $r['student_name'], $r['assignment_pct'], $r['exam_pct'], $r['assignment_weight'], $r['exam_weight'],
                $r['final_pct'], $r['grade'], $r['remarks'], $r['published_at'], $r['publisher_name']]);
        }
        fclose($out);
        exit;
    }
}
