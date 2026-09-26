<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Problems reported by users + errors captured automatically (PHP errors,
 * uncaught exceptions, broken links, JavaScript errors). Repeats of the
 * same error are grouped by `fingerprint` and counted in `occurrences`.
 */
class Migration_Create_error_reports extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'reference'    => ['type' => 'VARCHAR', 'constraint' => 12, 'comment' => 'short code shown to users, e.g. ER-7F3K2A'],
            'fingerprint'  => ['type' => 'CHAR', 'constraint' => 40, 'comment' => 'sha1 used to group identical errors'],
            'source'       => ['type' => 'ENUM', 'constraint' => ['user', 'php', 'exception', 'not_found', 'javascript', 'database'], 'default' => 'user'],
            'severity'     => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'critical'], 'default' => 'medium'],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'details'      => ['type' => 'TEXT', 'null' => true],
            'url'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'user_agent'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'occurrences'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
            'status'       => ['type' => 'ENUM', 'constraint' => ['open', 'resolved', 'ignored'], 'default' => 'open'],
            'admin_note'   => ['type' => 'TEXT', 'null' => true],
            'resolved_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'resolved_at'  => ['type' => 'DATETIME', 'null' => true],
            'first_seen'   => ['type' => 'DATETIME', 'null' => true],
            'last_seen'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('fingerprint');
        $this->dbforge->add_key('status');
        $this->dbforge->create_table('error_reports');
        $this->db->query('ALTER TABLE error_reports ADD UNIQUE (reference)');
    }

    public function down()
    {
        $this->dbforge->drop_table('error_reports');
    }
}
