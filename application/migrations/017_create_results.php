<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stage 6 - Results.
 *
 *  course_grading  - how much assignments and exams count in each course
 *  course_results  - a student's published overall result for a course: a
 *                    snapshot taken when the lecturer publishes, so later mark
 *                    changes never silently alter a published result
 *  users.results_token - secret code in the QR on a student's statement of results
 *  settings        - grade boundaries and the note printed on statements
 */
class Migration_Create_results extends CI_Migration
{
    public function up()
    {
        $id = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true];
        $ts = ['type' => 'DATETIME', 'null' => true];

        // ---- course_grading
        $this->dbforge->add_field([
            'course_id'         => $id,
            'assignment_weight' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 40],
            'exam_weight'       => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 60],
            'updated_by'        => $id + ['null' => true],
            'updated_at'        => $ts,
        ]);
        $this->dbforge->add_key('course_id', true);
        $this->dbforge->create_table('course_grading');
        $this->db->query('ALTER TABLE course_grading ADD FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE');

        // ---- course_results
        $this->dbforge->add_field([
            'id'                => $id + ['auto_increment' => true],
            'enrollment_id'     => $id,
            'course_id'         => $id,
            'student_id'        => $id,
            'assignment_pct'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'exam_pct'          => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'assignment_weight' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true],
            'exam_weight'       => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true],
            'final_pct'         => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'grade'             => ['type' => 'VARCHAR', 'constraint' => 30],
            'remarks'           => ['type' => 'TEXT', 'null' => true],
            'breakdown'         => ['type' => 'TEXT', 'null' => true, 'comment' => 'JSON: every assignment and exam that counted, with its mark, at publish time'],
            'status'            => ['type' => 'ENUM', 'constraint' => ['published', 'withdrawn'], 'default' => 'published'],
            'published_by'      => $id + ['null' => true],
            'published_at'      => $ts,
            'created_at'        => $ts,
            'updated_at'        => $ts,
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('course_id');
        $this->dbforge->create_table('course_results');
        $this->db->query('ALTER TABLE course_results ADD UNIQUE KEY enrollment (enrollment_id)');
        $this->db->query('ALTER TABLE course_results ADD FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE course_results ADD FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE course_results ADD FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE course_results ADD FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');

        // ---- QR secret for statements of results
        $this->dbforge->add_column('users', [
            'results_token' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true, 'after' => 'verify_token'],
        ]);
        $this->db->query('ALTER TABLE users ADD UNIQUE KEY results_token (results_token)');

        // ---- grade boundaries (editable under Settings)
        $defaults = [
            'grade_distinction' => '75',
            'grade_merit'       => '60',
            'grade_pass'        => '50',
            'statement_note'    => 'This statement lists results published by the Center. Scan the QR code to confirm it is genuine.',
        ];
        foreach ($defaults as $k => $v) {
            if (! $this->db->where('setting_key', $k)->count_all_results('settings')) {
                $this->db->insert('settings', ['setting_key' => $k, 'setting_value' => $v, 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
    }

    public function down()
    {
        $this->db->query('ALTER TABLE users DROP INDEX results_token');
        $this->dbforge->drop_column('users', 'results_token');
        $this->dbforge->drop_table('course_results');
        $this->dbforge->drop_table('course_grading');
        $this->db->where_in('setting_key', ['grade_distinction', 'grade_merit', 'grade_pass', 'statement_note'])->delete('settings');
    }
}
