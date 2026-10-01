<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * v13: Program > Module structure.
 *
 *  programs  - a qualification such as "Diploma in Theology" (name, slug, duration, thumbnail, status)
 *  modules   - the old "courses": each belongs to one program and has its own lecturers, students,
 *              materials, assignments, exams, fee and duration. Same ids as the courses they came from,
 *              so nothing that pointed at a course has to be re-numbered.
 *
 * What this does to existing data (nothing is deleted):
 *   1. Creates `programs` and `modules`.
 *   2. Creates one default program ("General Program") and copies every course into `modules` under it,
 *      keeping the same ids, names, fees, durations and status.
 *   3. In the 11 tables that pointed at courses (enrollments, materials, assignments, exams, grading,
 *      results, lecturers, discussions, calendar, library, attendance) it renames course_id to module_id
 *      and points the foreign key at modules. The rows themselves are not touched.
 *   4. Renames course_lecturers, course_grading and course_results to module_*.
 *   5. Keeps the original table as `courses_legacy` (safe to drop by hand once you are happy).
 *   6. Rewrites the old /course/ links stored in notifications.
 *
 * MariaDB cannot roll DDL back, so every step checks whether it has already been done: if the run is
 * interrupted, visiting /migrate again carries on where it stopped. BACK UP THE DATABASE FIRST.
 */
class Migration_Create_programs_modules extends CI_Migration
{
    /** [table, new table name or null, ON DELETE rule of its foreign key] */
    protected $children = [
        ['enrollments',          null,                'CASCADE'],
        ['materials',            null,                'CASCADE'],
        ['assignments',          null,                'CASCADE'],
        ['exams',                null,                'CASCADE'],
        ['course_grading',       'module_grading',    'CASCADE'],
        ['course_results',       'module_results',    'CASCADE'],
        ['course_lecturers',     'module_lecturers',  'CASCADE'],
        ['discussions',          null,                'CASCADE'],
        ['calendar_events',      null,                'CASCADE'],
        ['library_files',        null,                'SET NULL'],
        ['attendance_sessions',  null,                'CASCADE'],
    ];

    public function up()
    {
        // 1. programs ----------------------------------------------------------
        if (! $this->db->table_exists('programs')) {
            $this->db->query("CREATE TABLE programs (
                id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(200) NOT NULL,
                slug VARCHAR(160) NOT NULL,
                description TEXT NULL,
                duration_text VARCHAR(100) NULL,
                thumbnail_path VARCHAR(255) NULL,
                status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY programs_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }

        // 2. modules -----------------------------------------------------------
        if (! $this->db->table_exists('modules')) {
            $this->db->query("CREATE TABLE modules (
                id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                program_id INT(11) UNSIGNED NOT NULL,
                name VARCHAR(200) NOT NULL,
                code VARCHAR(30) NOT NULL,
                description TEXT NULL,
                fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                duration_text VARCHAR(100) NULL,
                sort_order INT(11) NOT NULL DEFAULT 0,
                credits INT(11) NOT NULL DEFAULT 0,
                status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY modules_code (code),
                KEY modules_program (program_id, sort_order),
                CONSTRAINT modules_program_fk FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }

        // Copy the courses (same ids) under a default program. Only when modules is still empty.
        $source = $this->db->table_exists('courses') ? 'courses' : ($this->db->table_exists('courses_legacy') ? 'courses_legacy' : null);
        if ($source && $this->db->count_all('modules') === 0) {
            $courses = $this->db->order_by('id')->get($source)->result_array();
            if ($courses) {
                $now = date('Y-m-d H:i:s');
                $programId = $this->default_program_id($now);
                $order = 0;
                foreach ($courses as $c) {
                    $this->db->insert('modules', [
                        'program_id'    => $programId,
                        'id'            => (int) $c['id'],
                        'name'          => $c['name'],
                        'code'          => sprintf('MOD-%03d', $c['id']),
                        'description'   => $c['description'],
                        'fee_amount'    => $c['fee_amount'],
                        'duration_text' => $c['duration_text'],
                        'sort_order'    => ++$order,
                        'credits'       => 0,
                        'status'        => $c['status'],
                        'created_at'    => $c['created_at'] ?: $now,
                        'updated_at'    => $c['updated_at'] ?: $now,
                    ]);
                }
                $next = (int) $this->db->select_max('id')->get('modules')->row()->id + 1;
                $this->db->query('ALTER TABLE modules AUTO_INCREMENT = ' . $next);
            }
        }

        // 3 + 4. Re-point every table that referenced a course ------------------
        foreach ($this->children as $c) {
            list($table, $newName, $onDelete) = $c;
            $current = $this->db->table_exists($table) ? $table : $newName;
            if ($current === null || ! $this->db->table_exists($current)) {
                continue;                                   // table not there at all (should not happen)
            }
            if ($this->db->field_exists('course_id', $current)) {
                $this->drop_foreign_keys_to($current, 'courses');
                $col = $this->db->query("SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'course_id'", [$current])->row_array();
                $def = $col['COLUMN_TYPE'] . ($col['IS_NULLABLE'] === 'YES' ? ' NULL' : ' NOT NULL');
                $this->db->query("ALTER TABLE `$current` CHANGE `course_id` `module_id` $def");
            }
            if ($this->db->field_exists('module_id', $current) && ! $this->has_foreign_key_to($current, 'modules')) {
                $this->db->query("ALTER TABLE `$current` ADD CONSTRAINT `{$current}_module_fk` FOREIGN KEY (`module_id`) REFERENCES modules(id) ON DELETE $onDelete ON UPDATE CASCADE");
            }
            if ($newName !== null && $current === $table) {
                $this->db->query("RENAME TABLE `$table` TO `$newName`");
            }
        }

        // 5. Keep the old table, under a name that says what it is.
        if ($this->db->table_exists('courses') && ! $this->db->table_exists('courses_legacy')) {
            $this->db->query('RENAME TABLE courses TO courses_legacy');
        }

        // 6. Notification links stored before the move.
        if ($this->db->table_exists('notifications')) {
            foreach (['student_materials', 'lecturer_materials', 'lecturer_results', 'lecturer_attendance', 'admin_results', 'admin_attendance'] as $c) {
                $this->db->query("UPDATE notifications SET link = REPLACE(link, '$c/course/', '$c/module/') WHERE link LIKE '%$c/course/%'");
            }
        }
    }

    /**
     * Puts things back as they were before: modules become courses again (new modules are copied back,
     * so nothing created after the migration is lost), then programs and modules are dropped.
     * Not reachable from the /migrate page (it only goes up); kept so the change can be reversed by a developer.
     */
    public function down()
    {
        if (! $this->db->table_exists('courses_legacy') && ! $this->db->table_exists('courses')) {
            return;
        }
        if (! $this->db->table_exists('courses')) {
            $this->db->query('RENAME TABLE courses_legacy TO courses');
        }
        foreach ($this->db->get('modules')->result_array() as $m) {
            $row = [
                'name' => $m['name'], 'description' => $m['description'], 'fee_amount' => $m['fee_amount'],
                'duration_text' => $m['duration_text'], 'status' => $m['status'],
                'created_at' => $m['created_at'], 'updated_at' => $m['updated_at'],
            ];
            if ($this->db->where('id', $m['id'])->count_all_results('courses')) {
                $this->db->where('id', $m['id'])->update('courses', $row);
            } else {
                $this->db->insert('courses', ['id' => $m['id']] + $row);
            }
        }
        foreach (array_reverse($this->children) as $c) {
            list($table, $newName) = $c;
            $current = $newName !== null && $this->db->table_exists($newName) ? $newName : $table;
            if ($this->db->field_exists('module_id', $current)) {
                $this->drop_foreign_keys_to($current, 'modules');
                $col = $this->db->query("SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'module_id'", [$current])->row_array();
                $this->db->query("ALTER TABLE `$current` CHANGE `module_id` `course_id` " . $col['COLUMN_TYPE'] . ($col['IS_NULLABLE'] === 'YES' ? ' NULL' : ' NOT NULL'));
                $onDelete = $c[2];
                $this->db->query("ALTER TABLE `$current` ADD FOREIGN KEY (`course_id`) REFERENCES courses(id) ON DELETE $onDelete ON UPDATE CASCADE");
            }
            if ($newName !== null && $current === $newName) {
                $this->db->query("RENAME TABLE `$newName` TO `$table`");
            }
        }
        $this->dbforge->drop_table('modules', true);
        $this->dbforge->drop_table('programs', true);
        if ($this->db->table_exists('notifications')) {
            foreach (['student_materials', 'lecturer_materials', 'lecturer_results', 'lecturer_attendance', 'admin_results', 'admin_attendance'] as $c) {
                $this->db->query("UPDATE notifications SET link = REPLACE(link, '$c/module/', '$c/course/') WHERE link LIKE '%$c/module/%'");
            }
        }
    }

    /* ------------------------------------------------------------------ */

    private function default_program_id($now)
    {
        $row = $this->db->where('slug', 'general-program')->get('programs')->row_array();
        if ($row) {
            return (int) $row['id'];
        }
        $this->db->insert('programs', [
            'name'        => 'General Program',
            'slug'        => 'general-program',
            'description' => 'Created automatically when programs were introduced. It holds the courses the Center already had, now called modules. Rename it, or move the modules into your own programs.',
            'status'      => 'active',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        return (int) $this->db->insert_id();
    }

    private function foreign_keys_to($table, $referenced)
    {
        return array_column($this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME = ?", [$table, $referenced])->result_array(), 'CONSTRAINT_NAME');
    }

    private function has_foreign_key_to($table, $referenced)
    {
        return count($this->foreign_keys_to($table, $referenced)) > 0;
    }

    private function drop_foreign_keys_to($table, $referenced)
    {
        foreach (array_unique($this->foreign_keys_to($table, $referenced)) as $name) {
            $this->db->query("ALTER TABLE `$table` DROP FOREIGN KEY `$name`");
        }
    }
}
