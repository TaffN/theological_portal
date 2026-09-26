<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stage 4: lecturers set assignments for their courses, see who has handed
 * in, and mark the work with a score and feedback.
 *
 * Every action checks Course_lecturer_model::is_assigned(), so a lecturer
 * can only ever see or change assignments in courses they teach.
 */
class Lecturer_assignments extends Lecturer_Controller
{
    const ATTACHMENT_TYPES = 'pdf|doc|docx|ppt|pptx|xls|xlsx|jpg|jpeg|png|zip|txt';
    const ATTACHMENT_MAX_KB = 20480;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['form_validation', 'notifier']);
        $this->load->model(['Assignment_model', 'Course_lecturer_model', 'Course_model']);
        $this->load->helper('ui');
    }

    /** All assignments across the lecturer's courses, grouped by course. */
    public function index()
    {
        $courses = $this->Course_lecturer_model->courses_for_lecturer($this->current_user_id);
        $byCourse = [];
        foreach ($courses as $c) {
            $byCourse[$c['id']] = ['course' => $c, 'assignments' => []];
        }
        foreach ($this->Assignment_model->for_lecturer($this->current_user_id) as $a) {
            if (isset($byCourse[$a['course_id']])) {
                $byCourse[$a['course_id']]['assignments'][] = $a;
            }
        }

        $this->load->view('templates/header', ['title' => 'Assignments']);
        $this->load->view('lecturer/assignments_index', [
            'groups'  => $byCourse,
            'to_mark' => $this->Assignment_model->to_mark_count($this->current_user_id),
        ]);
        $this->load->view('templates/footer');
    }

    public function create($courseId)
    {
        $course = $this->_my_course($courseId);
        $uploadError = null;

        if ($this->input->method() === 'post' && $this->_validate()) {
            $upload = $this->_store_upload('attachment', 'assignments', self::ATTACHMENT_TYPES, self::ATTACHMENT_MAX_KB, $error);
            if ($upload === false) {
                $uploadError = 'The file was not attached: ' . $error;   // shown on the form below (flashdata would only appear on the NEXT page)
            } else {
                $data = $this->_form_data();
                $data['course_id']   = $course['id'];
                $data['lecturer_id'] = $this->current_user_id;
                if ($upload) {
                    $data['attachment_path'] = $upload['path'];
                    $data['attachment_name'] = $upload['name'];
                }
                $id = $this->Assignment_model->create($data);

                $this->audit->log('assignment.created', 'assignment', $id, 'Set assignment "' . $data['title'] . '" in ' . $course['name'] . ', due ' . date('j M Y H:i', strtotime($data['due_at'])));
                $sent = $this->notifier->notify_course(
                    $course['id'],
                    'New assignment in ' . $course['name'] . ': ' . $data['title'] . ' (due ' . date('D j M, H:i', strtotime($data['due_at'])) . ')',
                    base_url('student_assignments/view/' . $id)
                );

                $this->session->set_flashdata('success', 'Assignment set. ' . $sent . ' student' . ($sent == 1 ? ' was' : 's were') . ' notified.');
                return redirect('lecturer_assignments/view/' . $id);
            }
        }

        $this->load->view('templates/header', ['title' => 'New assignment']);
        $this->load->view('lecturer/assignment_form', ['course' => $course, 'a' => null, 'uploadError' => $uploadError]);
        $this->load->view('templates/footer');
    }

    public function edit($id)
    {
        $a = $this->_my_assignment($id);
        $uploadError = null;

        if ($this->input->method() === 'post' && $this->_validate()) {
            $upload = $this->_store_upload('attachment', 'assignments', self::ATTACHMENT_TYPES, self::ATTACHMENT_MAX_KB, $error);
            if ($upload === false) {
                $uploadError = 'The file was not attached: ' . $error;   // shown on the form below (flashdata would only appear on the NEXT page)
            } else {
                $data = $this->_form_data();
                $oldFile = null;
                if ($upload || $this->input->post('remove_attachment')) {
                    $oldFile = $a['attachment_path'];
                    $data['attachment_path'] = $upload ? $upload['path'] : null;
                    $data['attachment_name'] = $upload ? $upload['name'] : null;
                }
                $this->Assignment_model->update($id, $data);
                $this->_delete_file($oldFile);

                $dueChanged = strtotime($data['due_at']) !== strtotime($a['due_at']);
                $this->audit->log('assignment.updated', 'assignment', $id, 'Edited assignment "' . $data['title'] . '" in ' . $a['course_name']
                    . ($dueChanged ? ', due date ' . date('j M H:i', strtotime($a['due_at'])) . ' -> ' . date('j M H:i', strtotime($data['due_at'])) : ''));
                if ($dueChanged) {
                    $this->notifier->notify_course(
                        $a['course_id'],
                        'Due date changed for "' . $data['title'] . '" (' . $a['course_name'] . '): now ' . date('D j M, H:i', strtotime($data['due_at'])),
                        base_url('student_assignments/view/' . $id)
                    );
                }

                $this->session->set_flashdata('success', 'Assignment updated.' . ($dueChanged ? ' Students were told about the new due date.' : ''));
                return redirect('lecturer_assignments/view/' . $id);
            }
        }

        $this->load->view('templates/header', ['title' => 'Edit assignment']);
        $this->load->view('lecturer/assignment_form', [
            'course' => ['id' => $a['course_id'], 'name' => $a['course_name']],
            'a'      => $a,
            'uploadError' => $uploadError,
        ]);
        $this->load->view('templates/footer');
    }

    /** Only while nobody has handed in, so marked work is never lost. */
    public function delete($id)
    {
        $a = $this->_my_assignment($id);
        if ($this->input->method() !== 'post') {
            show_404();
        }

        if ($this->Assignment_model->count_submissions($id) > 0) {
            $this->session->set_flashdata('error', 'Students have already handed in work for this assignment, so it can\'t be deleted. You can change its details instead.');
            return redirect('lecturer_assignments/view/' . $id);
        }

        $this->Assignment_model->delete($id);
        $this->_delete_file($a['attachment_path']);
        $this->audit->log('assignment.deleted', 'assignment', $id, 'Deleted assignment "' . $a['title'] . '" from ' . $a['course_name']);
        $this->session->set_flashdata('success', 'Assignment deleted.');
        redirect('lecturer_assignments');
    }

    /** One assignment: details, who has handed in, and the marking forms. */
    public function view($id)
    {
        $a      = $this->_my_assignment($id);
        $roster = $this->Assignment_model->roster($a);

        $submitted = 0;
        $marked    = 0;
        $scores    = [];
        foreach ($roster as $r) {
            if ($r['sub_id']) {
                $submitted++;
                if ($r['graded_at']) {
                    $marked++;
                    $scores[] = (float) $r['score'];
                }
            }
        }

        $this->load->view('templates/header', ['title' => $a['title']]);
        $this->load->view('lecturer/assignment_view', [
            'a'         => $a,
            'roster'    => $roster,
            'submitted' => $submitted,
            'marked'    => $marked,
            'average'   => $scores ? array_sum($scores) / count($scores) : null,
        ]);
        $this->load->view('templates/footer');
    }

    public function grade($submissionId)
    {
        $sub = $this->Assignment_model->find_submission($submissionId);
        if (! $sub || $this->input->method() !== 'post') {
            show_404();
        }
        $a = $this->_my_assignment($sub['assignment_id']);

        $score = trim((string) $this->input->post('score'));
        $feedback = trim((string) $this->input->post('feedback'));

        if ($score === '' || ! is_numeric($score) || $score < 0 || $score > $a['max_score']) {
            $this->session->set_flashdata('error', 'Enter a mark between 0 and ' . (int) $a['max_score'] . '.');
            return redirect('lecturer_assignments/view/' . $a['id'] . '#sub-' . $sub['id']);
        }
        $score = round((float) $score, 2);

        $this->Assignment_model->grade($sub['id'], $score, $feedback, $this->current_user_id);

        $this->load->model('User_model');
        $student = $this->User_model->find($sub['student_id']);
        $remark  = ! empty($sub['graded_at']);
        $this->audit->log('submission.graded', 'submission', $sub['id'], ($remark ? 'Changed mark for ' : 'Marked ') . $student['name'] . '\'s "' . $a['title'] . '": '
            . score_fmt($score) . '/' . (int) $a['max_score'] . ($remark ? ' (was ' . score_fmt($sub['score']) . ')' : ''));
        $this->notifier->notify_user(
            $sub['student_id'],
            ($remark ? 'Your mark was updated for "' : 'Your assignment "') . $a['title'] . ($remark ? '": ' : '" has been marked: ')
                . score_fmt($score) . '/' . (int) $a['max_score'],
            base_url('student_assignments/view/' . $a['id'])
        );

        $this->session->set_flashdata('success', 'Mark saved for ' . $student['name'] . '. They have been notified.');
        redirect('lecturer_assignments/view/' . $a['id'] . '#sub-' . $sub['id']);
    }

    /** A student's uploaded work. */
    public function submission_file($submissionId)
    {
        $sub = $this->Assignment_model->find_submission($submissionId);
        if (! $sub || ! $sub['file_path']) {
            show_404();
        }
        $a = $this->_my_assignment($sub['assignment_id']);

        $this->load->model('User_model');
        $student = $this->User_model->find($sub['student_id']);
        $ext = pathinfo($sub['file_path'], PATHINFO_EXTENSION);
        // "Tendai Moyo - Essay 1.pdf" sorts nicely in the lecturer's Downloads folder.
        $this->_send_file($sub['file_path'], $student['name'] . ' - ' . mb_substr($a['title'], 0, 60) . '.' . $ext);
    }

    public function attachment($id)
    {
        $a = $this->_my_assignment($id);
        $this->_send_file($a['attachment_path'], $a['attachment_name']);
    }

    /* ------------------------------------------------------------ */

    private function _my_course($courseId)
    {
        $course = $this->Course_model->find($courseId);
        if (! $course || ! $this->Course_lecturer_model->is_assigned($courseId, $this->current_user_id)) {
            show_error('You are not assigned to that course.', 403);
        }
        return $course;
    }

    private function _my_assignment($id)
    {
        $a = $this->Assignment_model->find($id);
        if (! $a || ! $this->Course_lecturer_model->is_assigned($a['course_id'], $this->current_user_id)) {
            show_404();
        }
        return $a;
    }

    private function _validate()
    {
        $this->form_validation->set_rules('title', 'Title', 'required|max_length[200]');
        $this->form_validation->set_rules('due_at', 'Due date', 'required|callback__valid_datetime');
        $this->form_validation->set_rules('max_score', 'Marks out of', 'required|is_natural_no_zero|less_than_equal_to[1000]');
        return $this->form_validation->run();
    }

    public function _valid_datetime($value)
    {
        if (strtotime((string) $value) === false) {
            $this->form_validation->set_message('_valid_datetime', 'Please pick a valid due date and time.');
            return false;
        }
        return true;
    }

    private function _form_data()
    {
        return [
            'title'        => trim($this->input->post('title')),
            'instructions' => trim((string) $this->input->post('instructions')),
            'due_at'       => date('Y-m-d H:i:s', strtotime($this->input->post('due_at'))),
            'max_score'    => (int) $this->input->post('max_score'),
            'allow_late'   => $this->input->post('allow_late') ? 1 : 0,
        ];
    }

    private function _delete_file($relativePath)
    {
        if ($relativePath && strpos($relativePath, 'uploads/') === 0 && is_file(FCPATH . $relativePath)) {
            @unlink(FCPATH . $relativePath);
        }
    }
}
