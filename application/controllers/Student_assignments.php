<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stage 4: students see assignments for their paid-up modules, hand in a
 * file and/or a typed answer, and read their mark and feedback.
 *
 * has_active_access() is checked on every page and every download, the
 * same gate used for module materials.
 */
class Student_assignments extends Student_Controller
{
    const SUBMISSION_TYPES = 'pdf|doc|docx|odt|rtf|txt|ppt|pptx|jpg|jpeg|png|zip';
    const SUBMISSION_MAX_KB = 10240;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Assignment_model', 'Enrollment_model']);
        $this->load->helper('ui');
    }

    public function index()
    {
        $groups = ['todo' => [], 'done' => []];
        foreach ($this->Assignment_model->for_student($this->current_user_id) as $a) {
            $a['state'] = Assignment_model::student_state($a, $a['sub_id'] ? ['graded_at' => $a['sub_graded_at']] : null);
            $groups[in_array($a['state'], ['todo', 'overdue'], true) ? 'todo' : 'done'][] = $a;
        }
        // Finished work: most recent first.
        $groups['done'] = array_reverse($groups['done']);

        $this->load->view('templates/header', ['title' => 'Assignments']);
        $this->load->view('student/assignments_index', [
            'groups'     => $groups,
            'hasModules' => $this->db->where('user_id', $this->current_user_id)->where('status', 'active')->count_all_results('enrollments') > 0,
        ]);
        $this->load->view('templates/footer');
    }

    public function view($id)
    {
        $a   = $this->_open_assignment($id);
        $sub = $this->Assignment_model->submission_for($id, $this->current_user_id);

        $this->load->view('templates/header', ['title' => $a['title']]);
        $this->load->view('student/assignment_view', [
            'a'         => $a,
            'sub'       => $sub,
            'state'     => Assignment_model::student_state($a, $sub),
            'canSubmit' => Assignment_model::can_submit($a, $sub),
        ]);
        $this->load->view('templates/footer');
    }

    public function submit($id)
    {
        $a = $this->_open_assignment($id);
        if ($this->input->method() !== 'post') {
            return redirect('student_assignments/view/' . $id);
        }

        $sub = $this->Assignment_model->submission_for($id, $this->current_user_id);
        if (! Assignment_model::can_submit($a, $sub)) {
            $this->session->set_flashdata('error', $sub && $sub['graded_at']
                ? 'This work has already been marked, so it can\'t be changed.'
                : 'The due date has passed, so this assignment is closed.');
            return redirect('student_assignments/view/' . $id);
        }

        $answer = trim((string) $this->input->post('answer_text'));
        $upload = $this->_store_upload('work', 'submissions', self::SUBMISSION_TYPES, self::SUBMISSION_MAX_KB, $error);

        if ($upload === false) {
            $this->session->set_flashdata('error', 'Your file was not uploaded: ' . $error);
            $this->session->set_flashdata('answer_draft', $answer);
            return redirect('student_assignments/view/' . $id . '#hand-in');
        }

        // Resubmitting without choosing a new file keeps the file already handed in.
        $keepOld = ! $upload && $sub && $sub['file_path'] && $this->input->post('keep_file');
        $filePath = $upload ? $upload['path'] : ($keepOld ? $sub['file_path'] : null);
        $fileName = $upload ? $upload['name'] : ($keepOld ? $sub['original_name'] : null);

        if (! $filePath && $answer === '') {
            $this->session->set_flashdata('error', 'Please attach your work or type your answer before handing in.');
            return redirect('student_assignments/view/' . $id . '#hand-in');
        }

        list($subId, $oldFile) = $this->Assignment_model->save_submission($a, $this->current_user_id, $filePath, $fileName, $answer);
        if ($oldFile && $oldFile !== $filePath && strpos($oldFile, 'uploads/submissions/') === 0 && is_file(FCPATH . $oldFile)) {
            @unlink(FCPATH . $oldFile);
        }

        $late = strtotime(date('Y-m-d H:i:s')) > strtotime($a['due_at']);
        $this->audit->log('submission.' . ($sub ? 'resubmitted' : 'submitted'), 'submission', $subId,
            ($sub ? 'Re-submitted' : 'Handed in') . ' "' . $a['title'] . '" (' . $a['module_name'] . ')' . ($late ? ', late' : ''));

        $this->session->set_flashdata('success', $late
            ? 'Handed in. It was after the due date, so your lecturer will see it as late.'
            : ($sub ? 'Your new version has replaced the earlier one.' : 'Handed in. You can replace it until the due date.'));
        redirect('student_assignments/view/' . $id);
    }

    /** The lecturer's question paper / brief. */
    public function attachment($id)
    {
        $a = $this->_open_assignment($id);
        $this->_send_file($a['attachment_path'], $a['attachment_name']);
    }

    /** The student's own handed-in file. */
    public function my_file($id)
    {
        $a   = $this->_open_assignment($id);
        $sub = $this->Assignment_model->submission_for($a['id'], $this->current_user_id);
        if (! $sub || ! $sub['file_path']) {
            show_404();
        }
        $this->_send_file($sub['file_path'], $sub['original_name']);
    }

    /* ------------------------------------------------------------ */

    private function _open_assignment($id)
    {
        $a = $this->Assignment_model->find($id);
        if (! $a) {
            show_404();
        }
        if (! $this->Enrollment_model->has_active_access($this->current_user_id, $a['module_id'])) {
            $this->session->set_flashdata('error', 'You do not have access to that module yet.');
            redirect('programs');
        }
        return $a;
    }
}
