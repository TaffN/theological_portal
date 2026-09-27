<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only figures for the administrators' Reports pages. Every method takes
 * the same optional filters: a course id and a year (of publishing / paying).
 * "Passed" means a published overall result whose grade isn't Fail.
 */
class Report_model extends CI_Model
{
    /** Student profile fields you can group students by. */
    public static $groupings = [
        'province'        => 'Province',
        'gender'          => 'Gender',
        'city'            => 'City / town',
        'denomination'    => 'Denomination',
        'education_level' => 'Education',
        'ministry_role'   => 'Ministry role',
    ];

    /** Years that have published results, newest first. */
    public function result_years()
    {
        if (! $this->db->table_exists('course_results')) {
            return [];
        }
        return array_column($this->db->select('DISTINCT YEAR(published_at) AS y', false)->where('status', 'published')
            ->where('published_at IS NOT NULL', null, false)->order_by('y', 'DESC')->get('course_results')->result_array(), 'y');
    }

    /** Published results with the student's province (or another profile field), filtered. */
    private function results_query($courseId, $year, $field = 'province')
    {
        $field = isset(self::$groupings[$field]) ? $field : 'province';
        $this->db->select("COALESCE(NULLIF(p.$field, ''), 'Not given') AS grp, cr.grade, cr.final_pct, cr.course_id, cr.student_id", false)
            ->from('course_results cr')
            ->join('user_profiles p', 'p.user_id = cr.student_id', 'left')
            ->where('cr.status', 'published');
        if ($courseId) {
            $this->db->where('cr.course_id', (int) $courseId);
        }
        if ($year) {
            $this->db->where('YEAR(cr.published_at) =', (int) $year, false);
        }
        return $this->db->get()->result_array();
    }

    /**
     * Pass rates grouped by a profile field (province by default).
     * Returns rows [group, results, passed, failed, rate, average] sorted by results, largest first,
     * plus totals.
     */
    public function pass_rates($courseId = null, $year = null, $field = 'province')
    {
        $groups = [];
        foreach ($this->results_query($courseId, $year, $field) as $r) {
            $g = $r['grp'];
            if (! isset($groups[$g])) {
                $groups[$g] = ['group' => $g, 'results' => 0, 'passed' => 0, 'sum' => 0.0];
            }
            $groups[$g]['results']++;
            $groups[$g]['sum'] += (float) $r['final_pct'];
            if ($r['grade'] !== 'Fail') {
                $groups[$g]['passed']++;
            }
        }
        $total = ['results' => 0, 'passed' => 0, 'sum' => 0.0];
        foreach ($groups as &$g) {
            $g['failed']  = $g['results'] - $g['passed'];
            $g['rate']    = round($g['passed'] / $g['results'] * 100, 1);
            $g['average'] = round($g['sum'] / $g['results'], 1);
            $total['results'] += $g['results'];
            $total['passed']  += $g['passed'];
            $total['sum']     += $g['sum'];
        }
        unset($g);
        usort($groups, function ($a, $b) { return $b['results'] - $a['results'] ?: strcmp($a['group'], $b['group']); });
        $total['rate']    = $total['results'] ? round($total['passed'] / $total['results'] * 100, 1) : null;
        $total['average'] = $total['results'] ? round($total['sum'] / $total['results'], 1) : null;
        return ['rows' => $groups, 'total' => $total];
    }

    /** How many of each grade, overall, and a line per course. */
    public function grades($courseId = null, $year = null)
    {
        $order = ['Distinction' => 0, 'Merit' => 0, 'Pass' => 0, 'Fail' => 0];
        $byCourse = [];
        $rows = $this->results_query($courseId, $year);
        $names = [];
        foreach ($this->db->select('id, name')->get('courses')->result_array() as $c) {
            $names[$c['id']] = $c['name'];
        }
        foreach ($rows as $r) {
            $grade = isset($order[$r['grade']]) ? $r['grade'] : 'Fail';
            $order[$grade]++;
            $cid = $r['course_id'];
            if (! isset($byCourse[$cid])) {
                $byCourse[$cid] = ['course' => isset($names[$cid]) ? $names[$cid] : '?', 'Distinction' => 0, 'Merit' => 0, 'Pass' => 0, 'Fail' => 0, 'results' => 0, 'sum' => 0.0];
            }
            $byCourse[$cid][$grade]++;
            $byCourse[$cid]['results']++;
            $byCourse[$cid]['sum'] += (float) $r['final_pct'];
        }
        foreach ($byCourse as &$c) {
            $c['rate'] = round(($c['results'] - $c['Fail']) / $c['results'] * 100, 1);
            $c['average'] = round($c['sum'] / $c['results'], 1);
        }
        unset($c);
        usort($byCourse, function ($a, $b) { return strcmp($a['course'], $b['course']); });
        return ['grades' => $order, 'courses' => $byCourse];
    }

    /**
     * Students grouped by a profile field. $scope: 'enrolled' = with at least
     * one paid-up course (optionally a given course), 'all' = every active student account.
     */
    public function students_by($field, $courseId = null, $scope = 'enrolled')
    {
        $field = isset(self::$groupings[$field]) ? $field : 'province';
        $this->db->select("COALESCE(NULLIF(p.$field, ''), 'Not given') AS grp, COUNT(DISTINCT u.id) AS n", false)
            ->from('users u')->join('user_profiles p', 'p.user_id = u.id', 'left')
            ->where('u.role', 'student')->where('u.status', 'active');
        if ($scope === 'enrolled' || $courseId) {
            $this->db->join('enrollments e', 'e.user_id = u.id AND e.status = \'active\'', 'inner', false);
            if ($courseId) {
                $this->db->where('e.course_id', (int) $courseId);
            }
        }
        $rows = $this->db->group_by('grp')->order_by('n', 'DESC')->get()->result_array();
        return array_map(function ($r) { return ['group' => $r['grp'], 'students' => (int) $r['n']]; }, $rows);
    }

    /** Approved fees per month for the last 12 months, and totals per course. */
    public function fees($year = null)
    {
        $months = [];
        if ($year) {
            for ($m = 1; $m <= 12; $m++) {
                $months[sprintf('%04d-%02d', $year, $m)] = 0.0;
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $months[date('Y-m', strtotime(date('Y-m-01') . " -$i months"))] = 0.0;
            }
        }
        $keys = array_keys($months);
        $rows = $this->db->select("DATE_FORMAT(COALESCE(reviewed_at, submitted_at), '%Y-%m') AS ym, SUM(amount) AS total", false)
            ->where('status', 'approved')->where('COALESCE(reviewed_at, submitted_at) >=', reset($keys) . '-01')
            ->where('COALESCE(reviewed_at, submitted_at) <', date('Y-m-d', strtotime(end($keys) . '-01 +1 month')))
            ->group_by('ym')->get('payments')->result_array();
        foreach ($rows as $r) {
            if (isset($months[$r['ym']])) {
                $months[$r['ym']] = (float) $r['total'];
            }
        }

        $this->db->select("c.name, COUNT(p.id) AS payments, SUM(p.amount) AS total", false)
            ->from('payments p')->join('enrollments e', 'e.id = p.enrollment_id')->join('courses c', 'c.id = e.course_id')->where('p.status', 'approved');
        if ($year) {
            $this->db->where('YEAR(COALESCE(p.reviewed_at, p.submitted_at)) =', (int) $year, false);
        }
        $courses = $this->db->group_by('c.id')->order_by('total', 'DESC')->get()->result_array();

        return [
            'months'  => $months,
            'courses' => $courses,
            'pending' => $this->db->where('status', 'pending')->count_all_results('payments'),
            'total'   => array_sum(array_map('floatval', array_column($courses, 'total'))),
        ];
    }

    /** Document verification at a glance. */
    public function documents()
    {
        if (! $this->db->table_exists('user_documents')) {
            return null;
        }
        $this->load->model('Document_model');
        $byType = $this->db->select("doc_type, SUM(status = 'verified') AS verified, SUM(status = 'pending') AS pending, SUM(status = 'rejected') AS rejected", false)
            ->group_by('doc_type')->get('user_documents')->result_array();
        return [
            'counts'  => $this->Document_model->counts(),
            'types'   => $byType,
            'missing' => count($this->Document_model->missing()),
        ];
    }

    /** Headline numbers for the Reports home page. */
    public function headline()
    {
        $pass = $this->db->table_exists('course_results') ? $this->pass_rates() : ['total' => ['results' => 0, 'rate' => null]];
        return [
            'students' => $this->db->where('role', 'student')->where('status', 'active')->count_all_results('users'),
            'enrolled' => $this->db->select('COUNT(DISTINCT user_id) AS n', false)->where('status', 'active')->get('enrollments')->row()->n,
            'results'  => $pass['total']['results'],
            'passrate' => $pass['total']['rate'],
            'fees'     => (float) $this->db->select_sum('amount')->where('status', 'approved')->where('YEAR(COALESCE(reviewed_at, submitted_at)) =', (int) date('Y'), false)->get('payments')->row()->amount,
        ];
    }
}
