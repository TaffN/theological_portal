<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Assignments and students' submissions (Stage 4).
 *
 * The rules for when a student may hand in live here, in can_submit(), so
 * the page and the upload handler can never disagree.
 */
class Assignment_model extends CI_Model
{
    protected $table = 'assignments';
    protected $subs  = 'assignment_submissions';

    /* ------------------------------------------------------ ASSIGNMENTS */

    public function find($id)
    {
        return $this->db->select('assignments.*, courses.name AS course_name')
            ->from($this->table)
            ->join('courses', 'courses.id = assignments.course_id')
            ->where('assignments.id', $id)
            ->get()->row_array();
    }

    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->delete($this->table);
    }

    /**
     * Every assignment in the lecturer's courses (not only ones they set,
     * so co-lecturers share the marking), with submission counts.
     */
    public function for_lecturer($lecturerId)
    {
        return $this->db
            ->select("assignments.*, courses.name AS course_name,
                COUNT(s.id) AS submitted,
                SUM(CASE WHEN s.id IS NOT NULL AND s.graded_at IS NULL THEN 1 ELSE 0 END) AS to_mark", false)
            ->from($this->table)
            ->join('course_lecturers cl', 'cl.course_id = assignments.course_id')
            ->join('courses', 'courses.id = assignments.course_id')
            ->join($this->subs . ' s', 's.assignment_id = assignments.id', 'left')
            ->where('cl.user_id', $lecturerId)
            ->group_by('assignments.id')
            ->order_by('assignments.due_at', 'DESC')
            ->get()->result_array();
    }

    /** Submissions waiting for a mark, across all of a lecturer's courses. */
    public function to_mark_count($lecturerId)
    {
        return $this->db->from($this->subs . ' s')
            ->join('assignments', 'assignments.id = s.assignment_id')
            ->join('course_lecturers cl', 'cl.course_id = assignments.course_id')
            ->where('cl.user_id', $lecturerId)
            ->where('s.graded_at IS NULL', null, false)
            ->count_all_results();
    }

    /**
     * Assignments in every course the student has active (paid) access to,
     * each with the student's own submission fields (sub_*) if any.
     */
    public function for_student($studentId)
    {
        return $this->db
            ->select('assignments.*, courses.name AS course_name,
                s.id AS sub_id, s.submitted_at AS sub_submitted_at, s.is_late AS sub_is_late,
                s.score AS sub_score, s.graded_at AS sub_graded_at')
            ->from($this->table)
            ->join('enrollments e', "e.course_id = assignments.course_id AND e.status = 'active'", 'inner', false)
            ->join('courses', 'courses.id = assignments.course_id')
            ->join($this->subs . ' s', 's.assignment_id = assignments.id AND s.student_id = ' . (int) $studentId, 'left', false)
            ->where('e.user_id', $studentId)
            ->order_by('assignments.due_at', 'ASC')
            ->get()->result_array();
    }

    /** Things a student still has to hand in (for the badge and dashboard). */
    public function student_outstanding($studentId)
    {
        $out = [];
        foreach ($this->for_student($studentId) as $a) {
            $state = self::student_state($a, $a['sub_id'] ? ['graded_at' => $a['sub_graded_at']] : null);
            if ($state === 'todo' || $state === 'overdue') {
                $a['state'] = $state;
                $out[] = $a;
            }
        }
        return $out;
    }

    /* ------------------------------------------------------ SUBMISSIONS */

    public function find_submission($id)
    {
        return $this->db->where('id', $id)->get($this->subs)->row_array();
    }

    public function submission_for($assignmentId, $studentId)
    {
        return $this->db->where('assignment_id', $assignmentId)->where('student_id', $studentId)
            ->get($this->subs)->row_array();
    }

    public function count_submissions($assignmentId)
    {
        return $this->db->where('assignment_id', $assignmentId)->count_all_results($this->subs);
    }

    /**
     * Saves a student's work. A resubmission replaces the earlier one (and
     * its file) and counts the attempt. Returns [submission_id, old_file].
     */
    public function save_submission(array $assignment, $studentId, $filePath, $originalName, $answerText)
    {
        $now      = date('Y-m-d H:i:s');
        $existing = $this->submission_for($assignment['id'], $studentId);
        $data = [
            'file_path'     => $filePath,
            'original_name' => $originalName,
            'answer_text'   => $answerText !== '' ? $answerText : null,
            'submitted_at'  => $now,
            'is_late'       => strtotime($now) > strtotime($assignment['due_at']) ? 1 : 0,
            'updated_at'    => $now,
        ];

        if ($existing) {
            $data['attempts'] = (int) $existing['attempts'] + 1;
            $this->db->where('id', $existing['id'])->update($this->subs, $data);
            return [(int) $existing['id'], $existing['file_path']];
        }

        $data['assignment_id'] = $assignment['id'];
        $data['student_id']    = $studentId;
        $data['created_at']    = $now;
        $this->db->insert($this->subs, $data);
        return [(int) $this->db->insert_id(), null];
    }

    public function grade($submissionId, $score, $feedback, $graderId)
    {
        return $this->db->where('id', $submissionId)->update($this->subs, [
            'score'      => $score,
            'feedback'   => $feedback !== '' ? $feedback : null,
            'graded_by'  => $graderId,
            'graded_at'  => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * The marking sheet for one assignment: every student with active access
     * to the course, plus anyone who handed in and has since lost access
     * (their work still needs a mark). Students who haven't submitted have
     * sub_id = null. Not-yet-marked work comes first.
     */
    public function roster($assignment)
    {
        $rows = $this->db
            ->select('users.id AS student_id, users.name, users.id_number, users.phone, users.photo_path, users.photo_updated_at,
                s.id AS sub_id, s.file_path, s.original_name, s.answer_text, s.submitted_at, s.is_late, s.attempts,
                s.score, s.feedback, s.graded_at')
            ->from('users')
            ->join($this->subs . ' s', 's.student_id = users.id AND s.assignment_id = ' . (int) $assignment['id'], 'left', false)
            ->join('enrollments e', "e.user_id = users.id AND e.course_id = " . (int) $assignment['course_id'] . " AND e.status = 'active'", 'left', false)
            ->group_start()->where('e.id IS NOT NULL', null, false)->or_where('s.id IS NOT NULL', null, false)->group_end()
            ->order_by('(s.id IS NOT NULL AND s.graded_at IS NULL)', 'DESC', false)
            ->order_by('(s.id IS NULL)', 'ASC', false)
            ->order_by('users.name', 'ASC')
            ->get()->result_array();

        return $rows;
    }

    /* ----------------------------------------------------------- RULES */

    /**
     * Where a student stands on one assignment:
     *   graded    - marked by the lecturer
     *   submitted - handed in, waiting for a mark
     *   todo      - not handed in, still before the due date
     *   overdue   - past the due date, late work still accepted
     *   missed    - past the due date, late work not accepted
     */
    public static function student_state(array $assignment, $submission)
    {
        if ($submission) {
            return ! empty($submission['graded_at']) ? 'graded' : 'submitted';
        }
        if (time() <= strtotime($assignment['due_at'])) {
            return 'todo';
        }
        return ! empty($assignment['allow_late']) ? 'overdue' : 'missed';
    }

    /**
     * May the student hand in (or replace) their work right now?
     *  - never once it has been marked
     *  - resubmitting is allowed until the due date
     *  - after the due date, only a first, late submission (if the lecturer allows late work)
     */
    public static function can_submit(array $assignment, $submission)
    {
        if ($submission && ! empty($submission['graded_at'])) {
            return false;
        }
        if (time() <= strtotime($assignment['due_at'])) {
            return true;
        }
        return ! $submission && ! empty($assignment['allow_late']);
    }
}
