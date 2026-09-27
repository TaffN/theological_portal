<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reports for administrators: charts + tables, each printable and exportable to Excel (CSV).
 *   admin_reports                         - overview with headline figures
 *   admin_reports/pass_rates?course=&year=&by=province   - pass rates per province (pie + bars)
 *   admin_reports/grades?course=&year=    - grade spread and per-course pass rates
 *   admin_reports/students?by=&course=&scope=  - who our students are (province, gender, ...)
 *   admin_reports/attendance              - attendance per course
 *   admin_reports/fees?year=              - fees collected
 *   admin_reports/documents               - document verification
 *   admin_reports/export/{report}?...     - the same figures as CSV
 */
class Admin_reports extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Report_model', 'Course_model']);
        $this->load->helper(['ui', 'chart']);
    }

    public function index()
    {
        $this->page('index', 'Reports', ['h' => $this->Report_model->headline()]);
    }

    public function pass_rates()
    {
        if (! $this->needs('course_results', 'Results')) { return; }
        list($courseId, $year) = $this->filters();
        $by = $this->grouping();
        $this->page('pass_rates', 'Pass rates by ' . strtolower(Report_model::$groupings[$by]), [
            'data' => $this->Report_model->pass_rates($courseId, $year, $by), 'by' => $by,
        ]);
    }

    public function grades()
    {
        if (! $this->needs('course_results', 'Results')) { return; }
        list($courseId, $year) = $this->filters();
        $this->page('grades', 'Grades', ['data' => $this->Report_model->grades($courseId, $year)]);
    }

    public function students()
    {
        $by = $this->grouping();
        $scope = $this->input->get('scope') === 'all' ? 'all' : 'enrolled';
        list($courseId) = $this->filters();
        $this->page('students', 'Students by ' . strtolower(Report_model::$groupings[$by]), [
            'rows' => $this->Report_model->students_by($by, $courseId, $scope), 'by' => $by, 'scope' => $scope,
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
        list($courseId, $year) = $this->filters();
        $by = $this->grouping();
        $label = isset(Report_model::$groupings[$by]) ? Report_model::$groupings[$by] : 'Group';
        switch ($report) {
            case 'pass_rates':
                $d = $this->Report_model->pass_rates($courseId, $year, $by);
                $head = [$label, 'Results', 'Passed', 'Failed', 'Pass rate %', 'Average %'];
                $rows = array_map(function ($r) { return [$r['group'], $r['results'], $r['passed'], $r['failed'], $r['rate'], $r['average']]; }, $d['rows']);
                break;
            case 'grades':
                $d = $this->Report_model->grades($courseId, $year);
                $head = ['Course', 'Results', 'Distinction', 'Merit', 'Pass', 'Fail', 'Pass rate %', 'Average %'];
                $rows = array_map(function ($c) { return [$c['course'], $c['results'], $c['Distinction'], $c['Merit'], $c['Pass'], $c['Fail'], $c['rate'], $c['average']]; }, $d['courses']);
                break;
            case 'students':
                $head = [$label, 'Students'];
                $rows = array_map(function ($r) { return [$r['group'], $r['students']]; }, $this->Report_model->students_by($by, $courseId, $this->input->get('scope') === 'all' ? 'all' : 'enrolled'));
                break;
            case 'attendance':
                $this->load->model('Attendance_model');
                $head = ['Course', 'Students', 'Registers', 'Last register', 'Attendance rate %'];
                $rows = array_map(function ($r) { return [$r['name'], $r['students'], $r['sessions'], $r['last_date'], $r['rate']]; }, $this->Attendance_model->overview());
                break;
            case 'fees':
                $d = $this->Report_model->fees($year);
                $head = ['Course', 'Approved payments', 'Total'];
                $rows = array_map(function ($c) { return [$c['name'], $c['payments'], $c['total']]; }, $d['courses']);
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
        list($courseId, $year) = $this->filters();
        $this->load->view('templates/header', ['title' => $title]);
        $this->load->view('admin/reports/_nav', ['current' => $view, 'title' => $title]);
        $this->load->view('admin/reports/' . $view, $data + [
            'courses'  => $this->Course_model->all(),
            'years'    => $this->Report_model->result_years(),
            'courseId' => $courseId,
            'year'     => $year,
            'groupings' => Report_model::$groupings,
        ]);
        $this->load->view('templates/footer');
    }

    private function filters()
    {
        $courseId = (int) $this->input->get('course');
        $year = (int) $this->input->get('year');
        return [$courseId ?: null, $year >= 2000 && $year <= 2100 ? $year : null];
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
