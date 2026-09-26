<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Seed_admin extends CI_Migration
{
    public function up()
    {
        $exists = $this->db->where('email', 'admin@example.com')->get('users')->row();

        if (! $exists) {
            $this->db->insert('users', [
                'name'          => 'Site Administrator',
                'email'         => 'admin@example.com',
                'password_hash' => password_hash('ChangeMe123!', PASSWORD_DEFAULT),
                'role'          => 'admin',
                'status'        => 'active',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        $this->db->where('email', 'admin@example.com')->delete('users');
    }
}
