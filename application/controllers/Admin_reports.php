<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reports for administrators: charts + tables, each printable and exportable to Excel (CSV).
 *   admin_reports                         - overview with headline figures
 *   admin_reports/pass_rates?program=&module=&year=&by=province   - pass rates per province (pie + bars)
 *   admin_reports/grades?module=&year=    - grade spread and per-module pass rates
 *   admin_reports/students?by=&module=&scope=  - who our students are (province, gender, ...)
 *   admin_reports/attendance              - attendance per module
 *   admin_reports/fees?year=              - fees collected
 *   admin_reports/documents               - document verification
 *   admin_reports/export/{report}?...     - the same figures as CSV
 */
class Admin_reports extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Report_model', 'Module_model', 'Program_model']);
        $this->load->helper(['ui', 'chart']);
    }

    public function index()
    {
        $this->page('index', 'Reports', ['h' => $this->Report_model->headline()]);
    }

    public function pass_rates()
    {
        if (! $this->needs('module_results', 'Results')) { return; }
        list($moduleId, $year) = $this->filters();
        $by = $this->grouping();
        $this->page('pass_rates', 'Pass rates by ' . strtolower(Report_model::$groupings[$by]), [
            'data' => $this->Report_model->pass_rates($moduleId, $year, $by, $this->programId()), 'by' => $by,
        ]);
    }

    public function grades()
    {
        if (! $this->needs('module_results', 'Results')) { return; }
        list($moduleId, $year) = $this->filters();
        $this->page('grades', 'Grades', ['data' => $this->Report_model->grades($moduleId, $year, $this->programId())]);
    }

    public function students()
    {
        $by = $this->grouping();
        $scope = $this->input->get('scope') === 'all' ? 'all' : 'enrolled';
        list($moduleId) = $this->filters();
        $this->page('students', 'Students by ' . strtolower(Report_model::$groupings[$by]), [
            'rows' => $this->Report_model->students_by($by, $moduleId, $scope, $this->programId()), 'by' => $by, 'scope' => $scope,
        ]);
    }

    public function attendance()
    {
        if (! $this->needs('attendance', 'Attendance')) { return; }
        $this->load->model('Attendance_model');
        $this->page('attendance', 'Attendance', ['rows' => $this->Attendance_model->overview()]);
    }

    public function fees()
    {
        list(, $year) = $this->filters();
        $this->page('fees', 'Fees collected', ['data' => $this->Report_model->fees($year)]);
    }

    public function documents()
    {
        if (! $this->needs('user_documents', 'Documents')) { return; }
        $this->load->model('Document_model');
        $this->page('documents', 'Documents', ['data' => $this->Report_model->documents(), 'types' => Document_model::$types]);
    }

    /** CSV of any report, with the same filters as on screen. */
    public function export($report = '')
    {
        list($moduleId, $year) = $this->filters();
        $by = $this->grouping();
        $label = isset(Report_model::$groupings[$by]) ? Report_model::$groupings[$by] : 'Group';
        switch ($report) {
            case 'pass_rates':
                $d = $this->Report_model->pass_rates($moduleId, $year, $by, $this->programId());
                $head = [$label, 'Results', 'Passed', 'Failed', 'Pass rate %', 'Average %'];
                $rows = array_map(function ($r) { return [$r['group'], $r['results'], $r['passed'], $r['failed'], $r['rate'], $r['average']]; }, $d['rows']);
                break;
            case 'grades':
                $d = $this->Report_model->grades($moduleId, $year, $this->programId());
                $head = ['Module', 'Results', 'Distinction', 'Merit', 'Pass', 'Fail', 'Pass rate %', 'Average %'];
                $rows = array_map(function ($c) { return [$c['module'], $c['results'], $c['Distinction'], $c['Merit'], $c['Pass'], $c['Fail'], $c['rate'], $c['average']]; }, $d['modules']);
                break;
            case 'students':
                $head = [$label, 'Students'];
                $rows = array_map(function ($r) { return [$r['group'], $r['students']]; }, $this->Report_model->students_by($by, $moduleId, $this->input->get('scope') === 'all' ? 'all' : 'enrolled', $this->programId()));
                break;
            case 'attendance':
                $this->load->model('Attendance_model');
                $head = ['Module', 'Students', 'Registers', 'Last register', 'Attendance rate %'];
                $rows = array_map(function ($r) { return [$r['name'], $r['students'], $r['sessions'], $r['last_date'], $r['rate']]; }, $this->Attendance_model->overview());
                break;
            case 'fees':
                $d = $this->Report_model->fees($year);
                $head = ['Module', 'Approved payments', 'Total'];
                $rows = array_map(function ($c) { return [$c['name'], $c['payments'], $c['total']]; }, $d['modules']);
                break;
            default:
                show_404();
                return;
        }
        $this->audit->log('report.exported', null, null, 'Exported the ' . str_replace('_', ' ', $report) . ' report to CSV');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="report-' . $report . '-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $head);
        foreach ($rows as $r) {
            fputcsv($out, $r);
        }
        fclose($out);
    }

    /* ------------------------------------------------------------------ */

    private function page($view, $title, array $data)
    {
        list($moduleId, $year) = $this->filters();
        $this->load->view('templates/header', ['title' => $title]);
        $this->load->view('admin/reports/_nav', ['current' => $view, 'title' => $title]);
        $this->load->view('admin/reports/' . $view, $data + [
            'modules'  => $this->Module_model->all(),
            'years'    => $this->Report_model->result_years(),
            'moduleId' => $moduleId,
            'programs' => $this->Program_model->all(),
            'programId' => $this->programId(),
            'year'     => $year,
            'groupings' => Report_model::$groupings,
        ]);
        $this->load->view('templates/footer');
    }

    private function filters()
    {
        $moduleId = (int) $this->input->get('module');
        $year = (int) $this->input->get('year');
        return [$moduleId ?: null, $year >= 2000 && $year <= 2100 ? $year : null];
    }

    /** The ?program= filter (a program id), or null for all programs. */
    private function programId()
    {
        return (int) $this->input->get('program') ?: null;
    }

    private function grouping()
    {
        $by = (string) $this->input->get('by');
        return isset(Report_model::$groupings[$by]) ? $by : 'province';
    }

    private function needs($table, $what)
    {
        if ($this->db->table_exists($table)) {
            return true;
        }
        $this->session->set_flashdata('error', $what . ' need a database update first. Run the latest update (/migrate).');
        redirect('admin_reports');
        return false;
    }
}
