<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stage 4 - Assignments.
 *
 *  assignments             - set by a lecturer for one course, with a due date
 *  assignment_submissions  - one row per student per assignment (a resubmission
 *                            replaces the earlier one), plus the lecturer's mark
 */
class Migration_Create_assignments extends CI_Migration
{
    public function up()
    {
        $id = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true];

        // ---- assignments
        $this->dbforge->add_field([
            'id'              => $id + ['auto_increment' => true],
            'course_id'       => $id,
            'lecturer_id'     => $id + ['comment' => 'the lecturer who set it'],
            'title'           => ['type' => 'VARCHAR', 'constraint' => 200],
            'instructions'    => ['type' => 'TEXT', 'null' => true],
            'attachment_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => 'optional question paper / brief'],
            'attachment_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'due_at'          => ['type' => 'DATETIME'],
            'max_score'       => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 100],
            'allow_late'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => '1 = first submissions still accepted after the due date, marked late'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('course_id');
        $this->dbforge->create_table('assignments');
        $this->db->query('ALTER TABLE assignments ADD FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE assignments ADD FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE');

        // ---- assignment_submissions
        $this->dbforge->add_field([
            'id'            => $id + ['auto_increment' => true],
            'assignment_id' => $id,
            'student_id'    => $id,
            'file_path'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'answer_text'   => ['type' => 'TEXT', 'null' => true, 'comment' => 'typed answer, for students without a document'],
            'submitted_at'  => ['type' => 'DATETIME'],
            'is_late'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'attempts'      => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 1],
            'score'         => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'feedback'      => ['type' => 'TEXT', 'null' => true],
            'graded_by'     => $id + ['null' => true],
            'graded_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('assignment_submissions');
        $this->db->query('ALTER TABLE assignment_submissions ADD UNIQUE KEY assignment_student (assignment_id, student_id)');
        $this->db->query('ALTER TABLE assignment_submissions ADD FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE assignment_submissions ADD FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE assignment_submissions ADD FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->dbforge->drop_table('assignment_submissions');
        $this->dbforge->drop_table('assignments');
    }
}
