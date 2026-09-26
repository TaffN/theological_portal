<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stage 5 - Online exams.
 *
 *  exams           - one timed exam in a course, with an open/close window
 *  exam_questions  - multiple choice (auto-marked) or short answer (lecturer marks)
 *  exam_attempts   - one per student per exam: which questions they got (and in
 *                    what order), the server-side deadline, the device lock and
 *                    the score
 *  exam_answers    - one row per question answered, saved as the student goes
 *  exam_events     - the "invigilator's notebook": leaving the exam, pasting,
 *                    being blocked on a second device, network changes
 */
class Migration_Create_exams extends CI_Migration
{
    public function up()
    {
        $id = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true];
        $ts = ['type' => 'DATETIME', 'null' => true];

        // ---- exams
        $this->dbforge->add_field([
            'id'               => $id + ['auto_increment' => true],
            'course_id'        => $id,
            'lecturer_id'      => $id + ['comment' => 'the lecturer who created it'],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'instructions'     => ['type' => 'TEXT', 'null' => true],
            'opens_at'         => ['type' => 'DATETIME'],
            'closes_at'        => ['type' => 'DATETIME'],
            'duration_minutes' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true],
            'question_count'   => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'null' => true, 'comment' => 'questions drawn per student from the pool; NULL = all'],
            'shuffle'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => '1 = question and option order differs per student'],
            'status'           => ['type' => 'ENUM', 'constraint' => ['draft', 'published'], 'default' => 'draft'],
            'published_at'     => $ts,
            'results_released' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'released_at'      => $ts,
            'created_at'       => $ts,
            'updated_at'       => $ts,
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('course_id');
        $this->dbforge->create_table('exams');
        $this->db->query('ALTER TABLE exams ADD FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE exams ADD FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE');

        // ---- exam_questions
        $this->dbforge->add_field([
            'id'             => $id + ['auto_increment' => true],
            'exam_id'        => $id,
            'position'       => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'type'           => ['type' => 'ENUM', 'constraint' => ['mcq', 'short'], 'default' => 'mcq'],
            'prompt'         => ['type' => 'TEXT'],
            'options'        => ['type' => 'TEXT', 'null' => true, 'comment' => 'JSON array of option texts (mcq)'],
            'correct_option' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => true, 'comment' => 'index into options (mcq)'],
            'marks'          => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '1.00'],
            'created_at'     => $ts,
            'updated_at'     => $ts,
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('exam_id');
        $this->dbforge->create_table('exam_questions');
        $this->db->query('ALTER TABLE exam_questions ADD FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE ON UPDATE CASCADE');

        // ---- exam_attempts
        $this->dbforge->add_field([
            'id'            => $id + ['auto_increment' => true],
            'exam_id'       => $id,
            'student_id'    => $id,
            'question_ids'  => ['type' => 'TEXT', 'comment' => 'JSON: this student\'s questions, in their order'],
            'option_orders' => ['type' => 'TEXT', 'null' => true, 'comment' => 'JSON: {question_id: [option indexes in display order]}'],
            'session_token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'comment' => 'device lock; NULL = next device to open it takes over'],
            'pledge_at'     => $ts,
            'started_at'    => ['type' => 'DATETIME'],
            'deadline_at'   => ['type' => 'DATETIME', 'comment' => 'min(start + duration, closes_at): the only clock that counts'],
            'submitted_at'  => $ts,
            'submit_reason' => ['type' => 'ENUM', 'constraint' => ['student', 'time_up'], 'null' => true],
            'max_score'     => ['type' => 'DECIMAL', 'constraint' => '7,2', 'default' => '0.00'],
            'auto_score'    => ['type' => 'DECIMAL', 'constraint' => '7,2', 'null' => true, 'comment' => 'multiple-choice marks'],
            'total_score'   => ['type' => 'DECIMAL', 'constraint' => '7,2', 'null' => true, 'comment' => 'set once everything is marked'],
            'feedback'      => ['type' => 'TEXT', 'null' => true],
            'graded_by'     => $id + ['null' => true],
            'graded_at'     => $ts,
            'flag_count'    => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'away_seconds'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'last_seen_at'  => $ts,
            'created_at'    => $ts,
            'updated_at'    => $ts,
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('exam_attempts');
        $this->db->query('ALTER TABLE exam_attempts ADD UNIQUE KEY exam_student (exam_id, student_id)');
        $this->db->query('ALTER TABLE exam_attempts ADD FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE exam_attempts ADD FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE exam_attempts ADD FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');

        // ---- exam_answers
        $this->dbforge->add_field([
            'id'            => $id + ['auto_increment' => true],
            'attempt_id'    => $id,
            'question_id'   => $id,
            'answer'        => ['type' => 'TEXT', 'null' => true, 'comment' => 'option index (mcq) or text (short)'],
            'is_correct'    => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'marks_awarded' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'saved_at'      => $ts,
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('exam_answers');
        $this->db->query('ALTER TABLE exam_answers ADD UNIQUE KEY attempt_question (attempt_id, question_id)');
        $this->db->query('ALTER TABLE exam_answers ADD FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE exam_answers ADD FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE ON UPDATE CASCADE');

        // ---- exam_events
        $this->dbforge->add_field([
            'id'         => $id + ['auto_increment' => true],
            'attempt_id' => $id,
            'type'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'detail'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'seconds'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('attempt_id');
        $this->dbforge->create_table('exam_events');
        $this->db->query('ALTER TABLE exam_events ADD FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE ON UPDATE CASCADE');

        // Students should be told that exams are monitored.
        if ($this->db->table_exists('settings')) {
            $row  = $this->db->where('setting_key', 'privacy_notice')->get('settings')->row_array();
            $text = $row ? (string) $row['setting_value'] : '';
            if ($row && stripos($text, 'online exam') === false) {
                $text = rtrim($text) . "\n\nDuring online exams the portal records when you start and hand in, your answers as you type them, your internet address, and when you leave the exam page or paste text. This record is only used by your lecturers and administrators to make sure exams are fair.";
                $this->db->where('setting_key', 'privacy_notice')->update('settings', ['setting_value' => $text, 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('exam_events');
        $this->dbforge->drop_table('exam_answers');
        $this->dbforge->drop_table('exam_attempts');
        $this->dbforge->drop_table('exam_questions');
        $this->dbforge->drop_table('exams');
    }
}
