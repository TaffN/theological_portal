<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Exams and their questions (Stage 5). Everything about a student actually
 * sitting an exam lives in Exam_attempt_model.
 */
class Exam_model extends CI_Model
{
    protected $table = 'exams';
    protected $qs    = 'exam_questions';

    /* ------------------------------------------------------------ EXAMS */

    public function find($id)
    {
        return $this->db->select('exams.*, modules.name AS module_name')
            ->from($this->table)
            ->join('modules', 'modules.id = exams.module_id')
            ->where('exams.id', $id)
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

    /** Every exam in the lecturer's modules, with counts for the list page. */
    public function for_lecturer($lecturerId)
    {
        return $this->db
            ->select("exams.*, modules.name AS module_name,
                (SELECT COUNT(*) FROM exam_questions q WHERE q.exam_id = exams.id) AS question_total,
                (SELECT COUNT(*) FROM exam_attempts a WHERE a.exam_id = exams.id) AS attempts,
                (SELECT COUNT(*) FROM exam_attempts a WHERE a.exam_id = exams.id AND a.submitted_at IS NOT NULL AND a.graded_at IS NULL) AS to_mark", false)
            ->from($this->table)
            ->join('module_lecturers cl', 'cl.module_id = exams.module_id')
            ->join('modules', 'modules.id = exams.module_id')
            ->where('cl.user_id', $lecturerId)
            ->order_by('exams.opens_at', 'DESC')
            ->get()->result_array();
    }

    /** Handed-in scripts with short answers still to mark, across a lecturer's modules. */
    public function to_mark_count($lecturerId)
    {
        return $this->db->from('exam_attempts a')
            ->join('exams', 'exams.id = a.exam_id')
            ->join('module_lecturers cl', 'cl.module_id = exams.module_id')
            ->where('cl.user_id', $lecturerId)
            ->where('a.submitted_at IS NOT NULL', null, false)
            ->where('a.graded_at IS NULL', null, false)
            ->count_all_results();
    }

    /**
     * Published exams in every module the student has active (paid) access to,
     * with the student's own attempt fields (att_*) if they have started.
     */
    public function for_student($studentId)
    {
        return $this->db
            ->select('exams.*, modules.name AS module_name,
                a.id AS att_id, a.started_at AS att_started_at, a.deadline_at AS att_deadline_at,
                a.submitted_at AS att_submitted_at, a.total_score AS att_total_score,
                a.max_score AS att_max_score, a.graded_at AS att_graded_at')
            ->from($this->table)
            ->join('enrollments e', "e.module_id = exams.module_id AND e.status = 'active'", 'inner', false)
            ->join('modules', 'modules.id = exams.module_id')
            ->join('exam_attempts a', 'a.exam_id = exams.id AND a.student_id = ' . (int) $studentId, 'left', false)
            ->where('e.user_id', $studentId)
            ->where('exams.status', 'published')
            ->order_by('exams.opens_at', 'ASC')
            ->get()->result_array();
    }

    /**
     * Where an exam is in its life:
     *   draft     - being written, students can't see it
     *   scheduled - published, not open yet
     *   open      - students can start it now
     *   closed    - the window has passed
     */
    public static function phase(array $exam)
    {
        if ($exam['status'] !== 'published') {
            return 'draft';
        }
        $now = time();
        if ($now < strtotime($exam['opens_at'])) {
            return 'scheduled';
        }
        return $now <= strtotime($exam['closes_at']) ? 'open' : 'closed';
    }

    /**
     * Where a student stands on an exam (row from for_student()):
     *   scheduled - not open yet          open    - can start now
     *   writing   - started, time left    missed  - closed, never started
     *   waiting   - handed in, no result  result  - result released
     */
    public static function student_state(array $e)
    {
        $phase = self::phase($e);
        if ($e['att_id']) {
            if (! $e['att_submitted_at']) {
                return 'writing';
            }
            return $e['results_released'] && $e['att_graded_at'] ? 'result' : 'waiting';
        }
        if ($phase === 'scheduled') {
            return 'scheduled';
        }
        return $phase === 'open' ? 'open' : 'missed';
    }

    /**
     * What stops this exam being published, in plain words. Empty = ready.
     */
    public function publish_problems(array $exam)
    {
        $problems  = [];
        $questions = $this->questions($exam['id']);

        if (! $questions) {
            $problems[] = 'Add at least one question.';
        }
        foreach ($questions as $i => $q) {
            if ($q['type'] === 'mcq') {
                $opts = self::options($q);
                if (count($opts) < 2 || $q['correct_option'] === null || ! isset($opts[(int) $q['correct_option']])) {
                    $problems[] = 'Question ' . ($i + 1) . ' needs at least two choices and a correct answer.';
                }
            }
        }
        if (strtotime($exam['closes_at']) <= strtotime($exam['opens_at'])) {
            $problems[] = 'The closing time must be after the opening time.';
        }
        if (strtotime($exam['closes_at']) <= time()) {
            $problems[] = 'The closing time has already passed. Move it into the future.';
        }
        if ($exam['question_count'] && $questions && $exam['question_count'] > count($questions)) {
            $problems[] = 'Each student is set to get ' . (int) $exam['question_count'] . ' questions, but there are only ' . count($questions) . ' in the pool.';
        }
        return $problems;
    }

    /* -------------------------------------------------------- QUESTIONS */

    public function questions($examId)
    {
        return $this->db->where('exam_id', $examId)
            ->order_by('position', 'ASC')->order_by('id', 'ASC')
            ->get($this->qs)->result_array();
    }

    public function find_question($id)
    {
        return $this->db->where('id', $id)->get($this->qs)->row_array();
    }

    public function add_question($examId, array $data)
    {
        $max = $this->db->select_max('position')->where('exam_id', $examId)->get($this->qs)->row()->position;
        $data['exam_id']    = $examId;
        $data['position']   = (int) $max + 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->qs, $data);
        return $this->db->insert_id();
    }

    public function update_question($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->qs, $data);
    }

    public function delete_question($id)
    {
        return $this->db->where('id', $id)->delete($this->qs);
    }

    /** Swap a question with its neighbour ($direction -1 = up, 1 = down). */
    public function move_question(array $question, $direction)
    {
        $list = $this->questions($question['exam_id']);
        $ids  = array_column($list, 'id');
        $pos  = array_search($question['id'], $ids);
        $swap = $pos + ($direction < 0 ? -1 : 1);
        if ($pos === false || ! isset($ids[$swap])) {
            return;
        }
        list($ids[$pos], $ids[$swap]) = [$ids[$swap], $ids[$pos]];
        foreach ($ids as $i => $qid) {   // renumber 1..n so positions stay tidy
            $this->db->where('id', $qid)->update($this->qs, ['position' => $i + 1]);
        }
    }

    /** Total marks if a student got every question (the whole pool). */
    public function pool_marks(array $questions)
    {
        return array_sum(array_map(function ($q) { return (float) $q['marks']; }, $questions));
    }

    /** A multiple-choice question's options as an array of strings. */
    public static function options(array $question)
    {
        $opts = json_decode((string) $question['options'], true);
        return is_array($opts) ? array_values($opts) : [];
    }
}
