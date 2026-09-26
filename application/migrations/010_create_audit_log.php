<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Append-only record of who did what, when and from where.
 * Nothing in the app ever updates or deletes rows here.
 */
class Migration_Create_audit_log extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'user_name'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'comment' => 'copied so the log still reads right if the user is renamed/deleted'],
            'role'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'action'      => ['type' => 'VARCHAR', 'constraint' => 60, 'comment' => 'e.g. payment.approved, auth.login_failed'],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'entity_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 500],
            'meta'        => ['type' => 'TEXT', 'null' => true, 'comment' => 'JSON extra detail'],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME'],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('action');
        $this->dbforge->add_key('created_at');
        $this->dbforge->create_table('audit_log');
    }

    public function down()
    {
        $this->dbforge->drop_table('audit_log');
    }
}
