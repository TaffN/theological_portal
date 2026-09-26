<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_announcements extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'body'       => ['type' => 'TEXT'],
            'audience'   => ['type' => 'ENUM', 'constraint' => ['all', 'student', 'lecturer'], 'default' => 'all'],
            'tone'       => ['type' => 'ENUM', 'constraint' => ['info', 'success', 'warning'], 'default' => 'info'],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('announcements');
    }

    public function down()
    {
        $this->dbforge->drop_table('announcements');
    }
}
