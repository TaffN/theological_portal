<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * A student sitting an exam (Stage 5): starting, the device lock, saving
 * answers as they type, the server-side clock, auto-marking, the activity
 * log the lecturer sees, and marking short answers.
 *
 * The server's deadline_at is the only clock that counts. Phones' clocks,
 * refreshes and lost connections can't give anyone extra time.
 */
class Exam_attempt_model extends CI_Model
{
    protected $table = 'exam_attempts';

    /** Answers arriving this long after the deadline are still accepted (slow networks). */
    const GRACE_SECONDS = 30;

    /** Events that count as a warning sign. The rest are shown for context only. */
    public static $flag_types = ['left', 'paste', 'bulk_insert', 'device_blocked'];

    /* ------------------------------------------------------------ BASICS */

    public function find($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function for_student($examId, $studentId)
    {
        return $this->db->where('exam_id', $examId)->where('student_id', $studentId)->get($this->table)->row_array();
    }

    public function count_for_exam($examId)
    {
        return $this->db->where('exam_id', $examId)->count_all_results($this->table);
    }

    /* -------------------------------------------------------- STARTING */

    /**
     * Creates the student's attempt: draws their questions (a random subset if
     * the exam uses a pool), shuffles question and option order if the exam
     * says so, sets the deadline and locks it to this device.
     * Returns the attempt row. If they already started, returns that one.
     */
    public function start(array $exam, $studentId, $sessionToken, $ip, $userAgent)
    {
        $existing = $this->for_student($exam['id'], $studentId);
        if ($existing) {
            return $existing;
        }

        $CI =& get_instance();
        $questions = $CI->Exam_model->questions($exam['id']);

        if ($exam['question_count'] && $exam['question_count'] < count($questions)) {
            shuffle($questions);
            $questions = array_slice($questions, 0, (int) $exam['question_count']);
            if (! $exam['shuffle']) {   // pool but no shuffling: keep the lecturer's order
                usort($questions, function ($a, $b) { return ($a['position'] - $b['position']) ?: ($a['id'] - $b['id']); });
            }
        } elseif ($exam['shuffle']) {
            shuffle($questions);
        }

        $optionOrders = [];
        foreach ($questions as $q) {
            if ($q['type'] === 'mcq') {
                $order = array_keys(Exam_model::options($q));
                if ($exam['shuffle']) {
                    shuffle($order);
                }
                $optionOrders[$q['id']] = $order;
            }
        }

        $now      = time();
        $deadline = min($now + (int) $exam['duration_minutes'] * 60, strtotime($exam['closes_at']));

        $data = [
            'exam_id'       => $exam['id'],
            'student_id'    => $studentId,
            'question_ids'  => json_encode(array_map('intval', array_column($questions, 'id'))),
            'option_orders' => json_encode($optionOrders),
            'session_token' => $sessionToken,
            'pledge_at'     => date('Y-m-d H:i:s', $now),
            'started_at'    => date('Y-m-d H:i:s', $now),
            'deadline_at'   => date('Y-m-d H:i:s', $deadline),
            'max_score'     => $CI->Exam_model->pool_marks($questions),
            'ip_address'    => $ip,
            'user_agent'    => mb_substr((string) $userAgent, 0, 255),
            'last_seen_at'  => date('Y-m-d H:i:s', $now),
            'created_at'    => date('Y-m-d H:i:s', $now),
            'updated_at'    => date('Y-m-d H:i:s', $now),
        ];

        // Two taps on "Start" at once: the unique key lets only one through.
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $ok = $this->db->insert($this->table, $data);
        $this->db->db_debug = $debug;

        $attempt = $this->for_student($exam['id'], $studentId);
        if ($ok && $attempt) {
            $this->log_event($attempt, 'started', 'Pledge accepted');
        }
        return $attempt;
    }

    /* ----------------------------------------------------------- CLOCK */

    public static function seconds_left(array $attempt)
    {
        return max(0, strtotime($attempt['deadline_at']) - time());
    }

    /** Still accepting answers? (not handed in, and within the deadline + grace) */
    public static function is_open(array $attempt)
    {
        return empty($attempt['submitted_at']) && time() <= strtotime($attempt['deadline_at']) + self::GRACE_SECONDS;
    }

    /* -------------------------------------------------- QUESTIONS/ANSWERS */

    /**
     * This student's questions, in their order, each with 'display_options'
     * = [original index => text] in the order this student sees them.
     * Correct answers are removed unless $withAnswers.
     */
    public function questions_for(array $attempt, $withAnswers = false)
    {
        $ids = json_decode($attempt['question_ids'], true);
        if (! $ids) {
            return [];
        }
        $orders = json_decode((string) $attempt['option_orders'], true) ?: [];

        $rows = $this->db->where_in('id', $ids)->get('exam_questions')->result_array();
        $byId = [];
        foreach ($rows as $r) {
            $byId[$r['id']] = $r;
        }

        $out = [];
        foreach ($ids as $qid) {
            if (! isset($byId[$qid])) {
                continue;   // question deleted after the attempt started (shouldn't happen: they're locked)
            }
            $q = $byId[$qid];
            $q['display_options'] = [];
            if ($q['type'] === 'mcq') {
                $opts  = Exam_model::options($q);
                $order = isset($orders[$qid]) ? $orders[$qid] : array_keys($opts);
                foreach ($order as $idx) {
                    if (isset($opts[$idx])) {
                        $q['display_options'][$idx] = $opts[$idx];
                    }
                }
            }
            if (! $withAnswers) {
                unset($q['correct_option']);
            }
            $out[] = $q;
        }
        return $out;
    }

    /** The attempt's answers keyed by question id. */
    public function answers($attemptId)
    {
        $out = [];
        foreach ($this->db->where('attempt_id', $attemptId)->get('exam_answers')->result_array() as $a) {
            $out[$a['question_id']] = $a;
        }
        return $out;
    }

    public function answered_count($attemptId)
    {
        return $this->db->where('attempt_id', $attemptId)->where("answer IS NOT NULL AND answer <> ''", null, false)
            ->count_all_results('exam_answers');
    }

    /**
     * Saves one answer (insert or update). Returns false if the question isn't
     * one of this student's, or the answer isn't valid for the question type.
     */
    public function save_answer(array $attempt, $questionId, $answer)
    {
        $ids = json_decode($attempt['question_ids'], true) ?: [];
        if (! in_array((int) $questionId, $ids, true)) {
            return false;
        }
        $q = $this->db->where('id', $questionId)->get('exam_questions')->row_array();
        if (! $q) {
            return false;
        }

        $answer = (string) $answer;
        if ($q['type'] === 'mcq') {
            $opts = Exam_model::options($q);
            if ($answer !== '' && (! ctype_digit($answer) || ! isset($opts[(int) $answer]))) {
                return false;
            }
        } else {
            $answer = mb_substr($answer, 0, 20000);   // generous, but bounded
        }

        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO exam_answers (attempt_id, question_id, answer, saved_at) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE answer = VALUES(answer), saved_at = VALUES(saved_at)';
        $this->db->query($sql, [$attempt['id'], $questionId, $answer === '' ? null : $answer, $now]);
        return true;
    }

    /* ------------------------------------------------------- FINISHING */

    /**
     * Hands the attempt in and marks the multiple-choice questions. Safe to
     * call twice: only the first call does anything. If there are no short
     * answers the attempt is fully marked straight away.
     */
    public function finalize($attemptId, $reason = 'student')
    {
        $attempt = $this->find($attemptId);
        if (! $attempt || $attempt['submitted_at']) {
            return false;
        }

        $submittedAt = $reason === 'time_up' ? min(time(), strtotime($attempt['deadline_at'])) : time();
        $this->db->where('id', $attemptId)->where('submitted_at IS NULL', null, false)
            ->update($this->table, [
                'submitted_at'  => date('Y-m-d H:i:s', $submittedAt),
                'submit_reason' => $reason,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        if ($this->db->affected_rows() !== 1) {
            return false;   // someone else finalized it a moment ago
        }

        $answers  = $this->answers($attemptId);
        $auto     = 0.0;
        $hasShort = false;
        foreach ($this->questions_for($attempt, true) as $q) {
            if ($q['type'] !== 'mcq') {
                $hasShort = true;
                continue;
            }
            $given   = isset($answers[$q['id']]) ? $answers[$q['id']]['answer'] : null;
            $correct = $given !== null && $given !== '' && (int) $given === (int) $q['correct_option'];
            $marks   = $correct ? (float) $q['marks'] : 0.0;
            $auto   += $marks;
            $this->db->query('INSERT INTO exam_answers (attempt_id, question_id, answer, is_correct, marks_awarded, saved_at) VALUES (?, ?, NULL, ?, ?, ?)
                ON DUPLICATE KEY UPDATE is_correct = VALUES(is_correct), marks_awarded = VALUES(marks_awarded)',
                [$attemptId, $q['id'], $correct ? 1 : 0, $marks, date('Y-m-d H:i:s')]);
        }

        $update = ['auto_score' => $auto];
        if (! $hasShort) {
            $update['total_score'] = $auto;
            $update['graded_at']   = date('Y-m-d H:i:s');
        }
        $this->db->where('id', $attemptId)->update($this->table, $update);
        $this->log_event($attempt, 'submitted', $reason === 'time_up' ? 'Time ran out; answers handed in automatically' : 'Handed in by the student');
        return true;
    }

    /**
     * Hands in every attempt whose time ran out while the student was away
     * (phone died, tab closed). Called whenever an exam's pages are opened,
     * so nothing waits on a scheduled job.
     */
    public function finalize_expired($examId = null, $studentId = null)
    {
        $this->db->select('id')->from($this->table)
            ->where('submitted_at IS NULL', null, false)
            ->where('deadline_at <', date('Y-m-d H:i:s', time() - self::GRACE_SECONDS));
        if ($examId) {
            $this->db->where('exam_id', $examId);
        }
        if ($studentId) {
            $this->db->where('student_id', $studentId);
        }
        foreach ($this->db->get()->result_array() as $row) {
            $this->finalize($row['id'], 'time_up');
        }
    }

    /* ------------------------------------------------------- ACTIVITY */

    /**
     * Records something that happened during the attempt. Warning signs
     * (see $flag_types) raise the attempt's flag count; 'returned' adds the
     * time spent away. Capped so a misbehaving page can't flood the table.
     */
    public function log_event(array $attempt, $type, $detail = null, $seconds = null)
    {
        if ($this->db->where('attempt_id', $attempt['id'])->count_all_results('exam_events') >= 500) {
            return;
        }
        $this->db->insert('exam_events', [
            'attempt_id' => $attempt['id'],
            'type'       => mb_substr($type, 0, 30),
            'detail'     => $detail !== null ? mb_substr((string) $detail, 0, 255) : null,
            'seconds'    => $seconds !== null ? (int) $seconds : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (in_array($type, self::$flag_types, true)) {
            $this->db->set('flag_count', 'flag_count + 1', false)->where('id', $attempt['id'])->update($this->table);
        }
        if ($type === 'returned' && $seconds) {
            $this->db->set('away_seconds', 'away_seconds + ' . (int) $seconds, false)->where('id', $attempt['id'])->update($this->table);
        }
    }

    public function events($attemptId)
    {
        return $this->db->where('attempt_id', $attemptId)->order_by('id', 'ASC')->get('exam_events')->result_array();
    }

    /** Heartbeat: remember the student is still here, and note a change of network. */
    public function touch(array $attempt, $ip)
    {
        $this->db->where('id', $attempt['id'])->update($this->table, ['last_seen_at' => date('Y-m-d H:i:s')]);
        if ($ip && $attempt['ip_address'] && $ip !== $attempt['ip_address']) {
            $this->log_event($attempt, 'network_changed', $attempt['ip_address'] . ' → ' . $ip);
            $this->db->where('id', $attempt['id'])->update($this->table, ['ip_address' => $ip]);
        }
    }

    /** Lecturer lets the student carry on from a different phone. */
    public function reset_device(array $attempt)
    {
        $this->db->where('id', $attempt['id'])->update($this->table, ['session_token' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->log_event($attempt, 'device_reset', 'Lecturer allowed a new device');
    }

    /** First device to open an attempt after reset_device() takes it over. */
    public function claim_device(array $attempt, $token)
    {
        $this->db->where('id', $attempt['id'])->where('session_token IS NULL', null, false)
            ->update($this->table, ['session_token' => $token]);
        return $this->db->affected_rows() === 1;
    }

    /* -------------------------------------------------------- MARKING */

    /**
     * Saves the lecturer's marks for short answers ($marks = [question_id => mark])
     * and the overall feedback. Once every short answer has a mark the attempt
     * is complete and gets its total. Returns [ok, error message].
     */
    public function mark(array $attempt, array $marks, $feedback, $graderId)
    {
        $questions = $this->questions_for($attempt, true);
        $answers   = $this->answers($attempt['id']);
        $total     = (float) $attempt['auto_score'];
        $complete  = true;
        $now       = date('Y-m-d H:i:s');

        foreach ($questions as $q) {
            if ($q['type'] !== 'short') {
                continue;
            }
            $raw = isset($marks[$q['id']]) ? trim((string) $marks[$q['id']]) : '';
            if ($raw === '') {
                $complete = false;
                continue;
            }
            if (! is_numeric($raw) || $raw < 0 || $raw > $q['marks']) {
                return [false, 'Each mark must be between 0 and the marks for that question.'];
            }
            $m = round((float) $raw, 2);
            $total += $m;
            $this->db->query('INSERT INTO exam_answers (attempt_id, question_id, answer, marks_awarded, saved_at) VALUES (?, ?, NULL, ?, ?)
                ON DUPLICATE KEY UPDATE marks_awarded = VALUES(marks_awarded)', [$attempt['id'], $q['id'], $m, $now]);
        }

        $update = ['feedback' => $feedback !== '' ? $feedback : null, 'updated_at' => $now];
        if ($complete) {
            $update['total_score'] = $total;
            $update['graded_at']   = $now;
            $update['graded_by']   = $graderId;
        } else {
            $update['total_score'] = null;
            $update['graded_at']   = null;
        }
        $this->db->where('id', $attempt['id'])->update($this->table, $update);
        return [true, $complete ? null : 'Saved. Some short answers still need a mark.'];
    }

    /* -------------------------------------------------------- LECTURER */

    /**
     * Everyone who can sit the exam (active students on the module) plus anyone
     * who started it and has since lost access, with their attempt (att_*)
     * and how many questions they have answered.
     */
    public function roster(array $exam)
    {
        return $this->db
            ->select('users.id AS student_id, users.name, users.id_number, users.phone, users.photo_path, users.photo_updated_at,
                a.id AS att_id, a.started_at, a.deadline_at, a.submitted_at, a.submit_reason, a.last_seen_at,
                a.flag_count, a.away_seconds, a.max_score, a.auto_score, a.total_score, a.graded_at, a.session_token,
                a.question_ids,
                (SELECT COUNT(*) FROM exam_answers x WHERE x.attempt_id = a.id AND x.answer IS NOT NULL AND x.answer <> \'\') AS answered', false)
            ->from('users')
            ->join('exam_attempts a', 'a.student_id = users.id AND a.exam_id = ' . (int) $exam['id'], 'left', false)
            ->join('enrollments e', 'e.user_id = users.id AND e.module_id = ' . (int) $exam['module_id'] . " AND e.status = 'active'", 'left', false)
            ->group_start()->where('e.id IS NOT NULL', null, false)->or_where('a.id IS NOT NULL', null, false)->group_end()
            ->order_by('users.name', 'ASC')
            ->get()->result_array();
    }

    /** Short labels for the invigilation screen and the event timeline. */
    public static function event_label($type)
    {
        $labels = [
            'started'         => 'Started the exam',
            'left'            => 'Left the exam page',
            'returned'        => 'Came back',
            'paste'           => 'Tried to paste text',
            'bulk_insert'     => 'Text appeared without typing (e.g. "Force paste")',
            'copy'            => 'Copied text',
            'device_blocked'  => 'Tried to open the exam on another device',
            'device_reset'    => 'New device allowed',
            'network_changed' => 'Network changed',
            'offline'         => 'Connection lost, then restored',
            'submitted'       => 'Handed in',
        ];
        return isset($labels[$type]) ? $labels[$type] : ucfirst(str_replace('_', ' ', $type));
    }
}
