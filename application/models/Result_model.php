<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stage 6: overall course results.
 *
 * How a result is worked out (explained the same way on the lecturer's page):
 *  - Assignment %: the average of the student's percentage on every assignment
 *    whose due date has passed (not handed in = 0%). Work handed in but not yet
 *    marked holds the result back.
 *  - Exam %: the average of the student's percentage on every published exam
 *    that has closed (not sat = 0%). An exam whose results aren't released yet
 *    holds the result back.
 *  - Final % = assignment % x assignment weight + exam % x exam weight. If a
 *    course has only assignments or only exams, that part counts 100%.
 *  - Grade from the boundaries in Settings (Distinction / Merit / Pass / Fail).
 *
 * Publishing stores a snapshot (course_results), so a published result only
 * changes when the lecturer publishes again.
 */
class Result_model extends CI_Model
{
    /* ------------------------------------------------------- WEIGHTING */

    /** [assignment weight, exam weight], percentages adding up to 100. */
    public function weights($courseId)
    {
        $row = $this->db->where('course_id', $courseId)->get('course_grading')->row_array();
        return $row ? [(int) $row['assignment_weight'], (int) $row['exam_weight']] : [40, 60];
    }

    public function save_weights($courseId, $assignmentWeight, $userId)
    {
        $a = max(0, min(100, (int) $assignmentWeight));
        $this->db->query('INSERT INTO course_grading (course_id, assignment_weight, exam_weight, updated_by, updated_at) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE assignment_weight = VALUES(assignment_weight), exam_weight = VALUES(exam_weight),
            updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)', [$courseId, $a, 100 - $a, $userId, date('Y-m-d H:i:s')]);
        return [$a, 100 - $a];
    }

    /* ---------------------------------------------------------- GRADES */

    /** Grade boundaries from Settings, highest first: [[name, minimum %], ...]. */
    public function bands()
    {
        $CI =& get_instance();
        return [
            ['Distinction', (float) $CI->settings->get('grade_distinction', '75')],
            ['Merit',       (float) $CI->settings->get('grade_merit', '60')],
            ['Pass',        (float) $CI->settings->get('grade_pass', '50')],
            ['Fail',        0.0],
        ];
    }

    public function grade_for($pct)
    {
        foreach ($this->bands() as $b) {
            if ($pct >= $b[1]) {
                return $b[0];
            }
        }
        return 'Fail';
    }

    /* ------------------------------------------------------ CALCULATION */

    /**
     * Works out the current result for every student on the course (active
     * enrolments, plus anyone who already has a published result).
     * Each row: student fields, enrollment_id, assignment_pct, exam_pct,
     * final_pct, grade, items (breakdown), blockers (why it can't be
     * published yet), published (the stored result, if any).
     */
    public function compute_for_course(array $course)
    {
        list($aw, $ew) = $this->weights($course['id']);
        $now = date('Y-m-d H:i:s');

        $students = $this->db
            ->select('users.id AS student_id, users.name, users.id_number, users.photo_path, users.photo_updated_at, e.id AS enrollment_id, e.status AS enrollment_status')
            ->from('enrollments e')
            ->join('users', 'users.id = e.user_id')
            ->where('e.course_id', $course['id'])
            ->group_start()
                ->where('e.status', 'active')
                ->or_where('e.id IN (SELECT enrollment_id FROM course_results)', null, false)
            ->group_end()
            ->order_by('users.name', 'ASC')
            ->get()->result_array();

        // Assignments that count: due date passed.
        $assignments = $this->db->table_exists('assignments')
            ? $this->db->where('course_id', $course['id'])->where('due_at <=', $now)->order_by('due_at', 'ASC')->get('assignments')->result_array()
            : [];
        $subs = [];
        if ($assignments) {
            foreach ($this->db->where_in('assignment_id', array_column($assignments, 'id'))->get('assignment_submissions')->result_array() as $s) {
                $subs[$s['assignment_id']][$s['student_id']] = $s;
            }
        }

        // Exams that count: published and closed.
        $exams = $this->db->table_exists('exams')
            ? $this->db->where('course_id', $course['id'])->where('status', 'published')->where('closes_at <=', $now)->order_by('opens_at', 'ASC')->get('exams')->result_array()
            : [];
        $atts = [];
        if ($exams) {
            $CI =& get_instance();
            $CI->load->model('Exam_attempt_model');
            foreach ($exams as $x) {
                $CI->Exam_attempt_model->finalize_expired($x['id']);
            }
            foreach ($this->db->where_in('exam_id', array_column($exams, 'id'))->get('exam_attempts')->result_array() as $a) {
                $atts[$a['exam_id']][$a['student_id']] = $a;
            }
        }

        $published = [];
        foreach ($this->db->where('course_id', $course['id'])->get('course_results')->result_array() as $r) {
            $published[$r['enrollment_id']] = $r;
        }

        $rows = [];
        foreach ($students as $st) {
            $sid      = $st['student_id'];
            $items    = [];
            $blockers = [];
            $aPcts    = [];
            $ePcts    = [];

            foreach ($assignments as $a) {
                $sub = isset($subs[$a['id']][$sid]) ? $subs[$a['id']][$sid] : null;
                if ($sub && $sub['graded_at'] === null) {
                    $blockers[] = '"' . $a['title'] . '" is handed in but not marked yet';
                    $items[] = ['type' => 'assignment', 'title' => $a['title'], 'score' => null, 'max' => (float) $a['max_score'], 'pct' => null, 'note' => 'waiting to be marked'];
                    continue;
                }
                $score = $sub ? (float) $sub['score'] : 0.0;
                $pct   = $a['max_score'] > 0 ? $score / $a['max_score'] * 100 : 0;
                $aPcts[] = $pct;
                $items[] = ['type' => 'assignment', 'title' => $a['title'], 'score' => $score, 'max' => (float) $a['max_score'], 'pct' => round($pct, 2), 'note' => $sub ? '' : 'not handed in'];
            }

            foreach ($exams as $x) {
                $att = isset($atts[$x['id']][$sid]) ? $atts[$x['id']][$sid] : null;
                if ($att && (! $att['graded_at'] || ! $x['results_released'])) {
                    $blockers[] = '"' . $x['title'] . '" ' . (! $att['graded_at'] ? 'is not fully marked yet' : 'results are not released yet');
                    $items[] = ['type' => 'exam', 'title' => $x['title'], 'score' => null, 'max' => null, 'pct' => null, 'note' => ! $att['graded_at'] ? 'waiting to be marked' : 'results not released'];
                    continue;
                }
                if ($att) {
                    $pct = $att['max_score'] > 0 ? $att['total_score'] / $att['max_score'] * 100 : 0;
                    $items[] = ['type' => 'exam', 'title' => $x['title'], 'score' => (float) $att['total_score'], 'max' => (float) $att['max_score'], 'pct' => round($pct, 2), 'note' => ''];
                } else {
                    $pct = 0;
                    $items[] = ['type' => 'exam', 'title' => $x['title'], 'score' => 0, 'max' => null, 'pct' => 0, 'note' => 'not sat'];
                }
                $ePcts[] = $pct;
            }

            $aPct = $aPcts ? array_sum($aPcts) / count($aPcts) : null;
            $ePct = $ePcts ? array_sum($ePcts) / count($ePcts) : null;
            if ($aPct === null && $ePct === null) {
                $final = null;
                if (! $blockers) {
                    $blockers[] = 'No assignments or exams have finished yet';
                }
            } elseif ($aPct === null) {
                $final = $ePct;
            } elseif ($ePct === null) {
                $final = $aPct;
            } else {
                $final = ($aPct * $aw + $ePct * $ew) / 100;
            }

            $rows[] = $st + [
                'assignment_pct' => $aPct === null ? null : round($aPct, 2),
                'exam_pct'       => $ePct === null ? null : round($ePct, 2),
                'final_pct'      => $final === null ? null : round($final, 2),
                'grade'          => $final === null ? null : $this->grade_for(round($final, 2)),
                'items'          => $items,
                'blockers'       => $blockers,
                'published'      => isset($published[$st['enrollment_id']]) ? $published[$st['enrollment_id']] : null,
            ];
        }

        return [
            'weights'     => [$aw, $ew],
            'rows'        => $rows,
            'assignments' => count($assignments),
            'exams'       => count($exams),
        ];
    }

    /* ------------------------------------------------------ PUBLISHING */

    /** Stores (or replaces) a student's published result from a computed row. */
    public function publish(array $course, array $row, $remarks, $publisherId, array $weights)
    {
        $now  = date('Y-m-d H:i:s');
        $data = [
            'course_id'         => $course['id'],
            'student_id'        => $row['student_id'],
            'assignment_pct'    => $row['assignment_pct'],
            'exam_pct'          => $row['exam_pct'],
            'assignment_weight' => $weights[0],
            'exam_weight'       => $weights[1],
            'final_pct'         => $row['final_pct'],
            'grade'             => $row['grade'],
            'remarks'           => $remarks !== '' ? $remarks : null,
            'breakdown'         => json_encode($row['items'], JSON_UNESCAPED_UNICODE),
            'status'            => 'published',
            'published_by'      => $publisherId,
            'published_at'      => $now,
            'updated_at'        => $now,
        ];

        if ($row['published']) {
            $this->db->where('id', $row['published']['id'])->update('course_results', $data);
        } else {
            $data['enrollment_id'] = $row['enrollment_id'];
            $data['created_at']    = $now;
            $this->db->insert('course_results', $data);
        }
        $this->ensure_token($row['student_id']);
    }

    public function withdraw($resultId)
    {
        return $this->db->where('id', $resultId)->update('course_results', ['status' => 'withdrawn', 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /** The statement QR code: created the first time a student gets a result. */
    public function ensure_token($studentId)
    {
        $u = $this->db->select('results_token')->where('id', $studentId)->get('users')->row_array();
        if ($u && $u['results_token']) {
            return $u['results_token'];
        }
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $t = '';
            for ($i = 0; $i < 20; $i++) {
                $t .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $debug = $this->db->db_debug;
            $this->db->db_debug = false;
            $ok = $this->db->where('id', $studentId)->update('users', ['results_token' => $t]);
            $this->db->db_debug = $debug;
            if ($ok) {
                return $t;
            }
        }
        return null;
    }

    /* ---------------------------------------------------------- READING */

    /** A student's published results, newest first, with course names. */
    public function for_student($studentId)
    {
        return $this->db->select('course_results.*, courses.name AS course_name, courses.duration_text')
            ->from('course_results')
            ->join('courses', 'courses.id = course_results.course_id')
            ->where('course_results.student_id', $studentId)
            ->where('course_results.status', 'published')
            ->order_by('course_results.published_at', 'DESC')
            ->get()->result_array();
    }

    public function find_by_token($token)
    {
        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9]{20}$/', $token)) {
            return null;
        }
        return $this->db->where('results_token', $token)->where('role', 'student')->get('users')->row_array();
    }

    /** Per course: students with access, results published, average final %. */
    public function overview()
    {
        return $this->db->query("SELECT c.id, c.name, c.status,
                (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.status = 'active') AS students,
                (SELECT COUNT(*) FROM course_results r WHERE r.course_id = c.id AND r.status = 'published') AS published,
                (SELECT AVG(r.final_pct) FROM course_results r WHERE r.course_id = c.id AND r.status = 'published') AS average,
                (SELECT MAX(r.published_at) FROM course_results r WHERE r.course_id = c.id AND r.status = 'published') AS last_published
            FROM courses c ORDER BY c.name")->result_array();
    }

    /** Published results for one course (or all), for the admin CSV. */
    public function published_rows($courseId = null)
    {
        $this->db->select('course_results.*, courses.name AS course_name, users.name AS student_name, users.id_number, p.name AS publisher_name')
            ->from('course_results')
            ->join('courses', 'courses.id = course_results.course_id')
            ->join('users', 'users.id = course_results.student_id')
            ->join('users p', 'p.id = course_results.published_by', 'left')
            ->where('course_results.status', 'published');
        if ($courseId) {
            $this->db->where('course_results.course_id', $courseId);
        }
        return $this->db->order_by('courses.name', 'ASC')->order_by('users.name', 'ASC')->get()->result_array();
    }
}
