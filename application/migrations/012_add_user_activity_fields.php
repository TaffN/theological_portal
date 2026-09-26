<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * last_login_at      - "last seen" on the admin Students page
 * reset_requested_at - set when a user clicks "Forgot password"
 */
class Migration_Add_user_activity_fields extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('users', [
            'last_login_at'      => ['type' => 'DATETIME', 'null' => true, 'after' => 'status'],
            'reset_requested_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'last_login_at'],
        ]);
    }

    public function down()
    {
        $this->dbforge->drop_column('users', 'last_login_at');
        $this->dbforge->drop_column('users', 'reset_requested_at');
    }
}
