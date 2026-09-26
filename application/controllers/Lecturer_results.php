<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stage 6: lecturers set how assignments and exams are weighted in each of
 * their courses, check everyone's calculated result, add remarks and publish.
 * Students are notified; a published result is a snapshot until published again.
 */
class Lecturer_results extends Lecturer_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('notifier');
        $this->load->model(['Result_model', 'Course_lecturer_model', 'Course_model']);
        $this->load->helper('ui');
        if (! $this->db->table_exists('course_results')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'Results need a database update first. An administrator should run the latest update (/migrate).');
            redirect('dashboard');
        }
    }

    public function index()
    {
        $courses = $this->Course_lecturer_model->courses_for_lecturer($this->current_user_id);
        foreach ($courses as &$c) {
            $c['students']  = $this->db->where('course_id', $c['id'])->where('status', 'active')->count_all_results('enrollments');
            $c['published'] = $this->db->where('course_id', $c['id'])->where('status', 'published')->count_all_results('course_results');
            $c['weights']   = $this->Result_model->weights($c['id']);
        }
        unset($c);

        $this->load->view('templates/header', ['title' => 'Results']);
        $this->load->view('lecturer/results_index', ['courses' => $courses]);
        $this->load->view('templates/footer');
    }

    /** One course: weighting, everyone's calculated result, publish. */
    public function course($courseId)
    {
        $course = $this->_my_course($courseId);
        $calc   = $this->Result_model->compute_for_course($course);

        $this->load->view('templates/header', ['title' => 'Results: ' . $course['name']]);
        $this->load->view('lecturer/results_course', [
            'course' => $course,
            'calc'   => $calc,
            'bands'  => $this->Result_model->bands(),
        ]);
        $this->load->view('templates/footer');
    }

    public function weights($courseId)
    {
        $course = $this->_my_course($courseId, true);
        $old = $this->Result_model->weights($courseId);
        $new = $this->Result_model->save_weights($courseId, $this->input->post('assignment_weight'), $this->current_user_id);
        if ($old !== $new) {
            $this->audit->log('grading.updated', 'course', $courseId, 'Set weighting for ' . $course['name'] . ': assignments ' . $new[0] . '%, exams ' . $new[1] . '% (was ' . $old[0] . '/' . $old[1] . ')');
        }
        $this->session->set_flashdata('success', 'Weighting saved: assignments ' . $new[0] . '%, exams ' . $new[1] . '%. The results below have been recalculated.'
            . ($this->db->where('course_id', $courseId)->where('status', 'published')->count_all_results('course_results') ? ' Published results only change when you publish again.' : ''));
        redirect('lecturer_results/course/' . $courseId);
    }

    /** Publishes the ticked students (skipping anyone whose result isn't ready). */
    public function publish($courseId)
    {
        $course   = $this->_my_course($courseId, true);
        $calc     = $this->Result_model->compute_for_course($course);
        $selected = array_map('intval', (array) $this->input->post('students'));
        $remarks  = (array) $this->input->post('remarks');

        $done = 0; $updated = 0; $skipped = [];
        foreach ($calc['rows'] as $row) {
            if (! in_array((int) $row['student_id'], $selected, true)) {
                continue;
            }
            if ($row['blockers'] || $row['final_pct'] === null) {
                $skipped[] = $row['name'];
                continue;
            }
            $remark = isset($remarks[$row['student_id']]) ? mb_substr(trim((string) $remarks[$row['student_id']]), 0, 1000) : '';
            $wasPublished = $row['published'] && $row['published']['status'] === 'published';
            $this->Result_model->publish($course, $row, $remark, $this->current_user_id, $calc['weights']);

            $this->notifier->notify_user($row['student_id'],
                ($wasPublished ? 'Your result for ' . $course['name'] . ' was updated: ' : 'Your final result for ' . $course['name'] . ' is out: ')
                    . $row['grade'] . ' (' . score_fmt(round($row['final_pct'], 1)) . '%)',
                base_url('student_results'));
            $this->audit->log('result.published', 'result', $row['enrollment_id'], ($wasPublished ? 'Re-published ' : 'Published ') . 'result for ' . $row['name']
                . ' in ' . $course['name'] . ': ' . $row['grade'] . ' ' . score_fmt(round($row['final_pct'], 1)) . '%'
                . ($wasPublished ? ' (was ' . $row['published']['grade'] . ' ' . score_fmt(round($row['published']['final_pct'], 1)) . '%)' : ''));
            $wasPublished ? $updated++ : $done++;
        }

        $msg = [];
        if ($done)    { $msg[] = $done . ' result' . ($done == 1 ? '' : 's') . ' published'; }
        if ($updated) { $msg[] = $updated . ' updated'; }
        if ($msg) {
            $this->session->set_flashdata('success', implode(', ', $msg) . '. Students have been notified.');
        }
        if ($skipped) {
            $this->session->set_flashdata('error', 'Not published (not ready yet): ' . implode(', ', $skipped) . '.');
        }
        if (! $msg && ! $skipped) {
            $this->session->set_flashdata('error', 'Tick at least one student to publish.');
        }
        redirect('lecturer_results/course/' . $courseId);
    }

    /** Takes a published result back (e.g. published by mistake). */
    public function withdraw($resultId)
    {
        $r = $this->db->where('id', $resultId)->get('course_results')->row_array();
        if (! $r) {
            show_404();
        }
        $course = $this->_my_course($r['course_id'], true);
        $this->Result_model->withdraw($resultId);
        $student = $this->db->select('name')->where('id', $r['student_id'])->get('users')->row_array();
        $this->audit->log('result.withdrawn', 'result', $r['enrollment_id'], 'Withdrew result for ' . $student['name'] . ' in ' . $course['name'] . ' (' . $r['grade'] . ')');
        $this->notifier->notify_user($r['student_id'], 'Your result for ' . $course['name'] . ' has been withdrawn for review. You will be notified when it is published again.', base_url('student_results'));
        $this->session->set_flashdata('success', 'Result withdrawn. ' . $student['name'] . ' no longer sees it and has been told it is under review.');
        redirect('lecturer_results/course/' . $course['id']);
    }

    /* ------------------------------------------------------------ */

    /** $postOnly: changes only through a button (POST), never a plain link. */
    private function _my_course($courseId, $postOnly = false)
    {
        $course = $this->Course_model->find($courseId);
        if (! $course || ! $this->Course_lecturer_model->is_assigned($courseId, $this->current_user_id)
            || ($postOnly && $this->input->method() !== 'post')) {
            show_404();
        }
        return $course;
    }
}
