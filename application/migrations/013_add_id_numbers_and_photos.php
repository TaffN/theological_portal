<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Human-friendly ID numbers (TCS-2026-0001 / TCL-... / TCA-...) and profile
 * photos. Existing users are numbered in the order they joined.
 */
class Migration_Add_id_numbers_and_photos extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('users', [
            'id_number'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'id'],
            'photo_path'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'phone'],
            'photo_updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'photo_path'],
        ]);

        // Backfill: number everyone who already exists, oldest first.
        $prefix  = ['student' => 'TCS', 'lecturer' => 'TCL', 'admin' => 'TCA'];
        $counter = [];
        $users   = $this->db->select('id, role, created_at')->order_by('created_at', 'ASC')->order_by('id', 'ASC')->get('users')->result_array();

        foreach ($users as $u) {
            $year = $u['created_at'] ? date('Y', strtotime($u['created_at'])) : date('Y');
            $key  = $prefix[$u['role']] . '-' . $year;
            $counter[$key] = isset($counter[$key]) ? $counter[$key] + 1 : 1;
            $this->db->where('id', $u['id'])->update('users', [
                'id_number' => $key . '-' . str_pad($counter[$key], 4, '0', STR_PAD_LEFT),
            ]);
        }

        $this->db->query('ALTER TABLE users ADD UNIQUE KEY id_number (id_number)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE users DROP INDEX id_number');
        $this->dbforge->drop_column('users', 'id_number');
        $this->dbforge->drop_column('users', 'photo_path');
        $this->dbforge->drop_column('users', 'photo_updated_at');
    }
}
