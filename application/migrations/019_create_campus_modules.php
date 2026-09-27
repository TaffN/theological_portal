<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * v10: discussions, calendar, library and attendance.
 *
 *  discussions         - a topic on a course's board (course_id NULL = the college-wide "General" board)
 *  discussion_replies  - replies under a topic
 *  calendar_events     - classes, events, holidays added by staff (course_id NULL = whole college).
 *                        Assignment due dates and exam windows are shown too, read from their own tables.
 *  library_files       - the college library: books, articles, commentaries, sermons, audio, video
 *  attendance_sessions - one class meeting of a course (a register)
 *  attendance          - one student's mark in one register
 */
class Migration_Create_campus_modules extends CI_Migration
{
    public function up()
    {
        $id  = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true];
        $idn = $id + ['null' => true];

        // Discussions --------------------------------------------------------
        $this->dbforge->add_field([
            'id'               => $id + ['auto_increment' => true],
            'course_id'        => $idn,
            'user_id'          => $id,
            'title'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'body'             => ['type' => 'TEXT'],
            'is_pinned'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_locked'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'reply_count'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'last_activity_at' => ['type' => 'DATETIME'],
            'created_at'       => ['type' => 'DATETIME'],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key(['course_id', 'last_activity_at']);
        $this->dbforge->create_table('discussions');
        $this->db->query('ALTER TABLE discussions ADD FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE discussions ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE');

        $this->dbforge->add_field([
            'id'            => $id + ['auto_increment' => true],
            'discussion_id' => $id,
            'user_id'       => $id,
            'body'          => ['type' => 'TEXT'],
            'created_at'    => ['type' => 'DATETIME'],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('discussion_id');
        $this->dbforge->create_table('discussion_replies');
        $this->db->query('ALTER TABLE discussion_replies ADD FOREIGN KEY (discussion_id) REFERENCES discussions(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE discussion_replies ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE');

        // Calendar -----------------------------------------------------------
        $this->dbforge->add_field([
            'id'           => $id + ['auto_increment' => true],
            'course_id'    => $idn,
            'title'        => ['type' => 'VARCHAR', 'constraint' => 200],
            'description'  => ['type' => 'TEXT', 'null' => true],
            'event_type'   => ['type' => 'ENUM', 'constraint' => ['class', 'event', 'holiday', 'deadline', 'other'], 'default' => 'event'],
            'location'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'meeting_link' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'starts_at'    => ['type' => 'DATETIME'],
            'ends_at'      => ['type' => 'DATETIME', 'null' => true],
            'all_day'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_by'   => $idn,
            'created_at'   => ['type' => 'DATETIME'],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('starts_at');
        $this->dbforge->add_key('course_id');
        $this->dbforge->create_table('calendar_events');
        $this->db->query('ALTER TABLE calendar_events ADD FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE');

        // Library ------------------------------------------------------------
        $this->dbforge->add_field([
            'id'            => $id + ['auto_increment' => true],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 200],
            'author'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'category'      => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'Other'],
            'description'   => ['type' => 'TEXT', 'null' => true],
            'course_id'     => $idn,
            'file_path'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'file_size'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'external_link' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'downloads'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'uploaded_by'   => $idn,
            'created_at'    => ['type' => 'DATETIME'],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('category');
        $this->dbforge->create_table('library_files');
        $this->db->query('ALTER TABLE library_files ADD FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL ON UPDATE CASCADE');

        // Attendance ---------------------------------------------------------
        $this->dbforge->add_field([
            'id'           => $id + ['auto_increment' => true],
            'course_id'    => $id,
            'session_date' => ['type' => 'DATE'],
            'topic'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'taken_by'     => $idn,
            'created_at'   => ['type' => 'DATETIME'],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key(['course_id', 'session_date']);
        $this->dbforge->create_table('attendance_sessions');
        $this->db->query('ALTER TABLE attendance_sessions ADD FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE');

        $this->dbforge->add_field([
            'id'         => $id + ['auto_increment' => true],
            'session_id' => $id,
            'student_id' => $id,
            'status'     => ['type' => 'ENUM', 'constraint' => ['present', 'late', 'absent', 'excused'], 'default' => 'present'],
            'note'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'marked_at'  => ['type' => 'DATETIME'],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('student_id');
        $this->dbforge->create_table('attendance');
        $this->db->query('ALTER TABLE attendance ADD UNIQUE KEY session_student (session_id, student_id)');
        $this->db->query('ALTER TABLE attendance ADD FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE attendance ADD FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        foreach (['attendance', 'attendance_sessions', 'library_files', 'calendar_events', 'discussion_replies', 'discussions'] as $t) {
            $this->dbforge->drop_table($t, true);
        }
    }
}
