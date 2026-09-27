<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * v11: personal documents (national ID copies, qualifications, certificates...)
 * that students and lecturers upload and administrators verify.
 */
class Migration_Create_user_documents extends CI_Migration
{
    public function up()
    {
        $id = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true];
        $this->dbforge->add_field([
            'id'            => $id + ['auto_increment' => true],
            'user_id'       => $id,
            'doc_type'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'file_path'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'file_size'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'status'        => ['type' => 'ENUM', 'constraint' => ['pending', 'verified', 'rejected'], 'default' => 'pending'],
            'review_note'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'reviewed_by'   => $id + ['null' => true],
            'reviewed_at'   => ['type' => 'DATETIME', 'null' => true],
            'uploaded_at'   => ['type' => 'DATETIME'],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key(['user_id', 'doc_type']);
        $this->dbforge->add_key('status');
        $this->dbforge->create_table('user_documents');
        $this->db->query('ALTER TABLE user_documents ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE');

        $defaults = [
            'docs_required_student'  => 'national_id',
            'docs_required_lecturer' => 'national_id,qualification',
        ];
        foreach ($defaults as $k => $v) {
            if (! $this->db->where('setting_key', $k)->count_all_results('settings')) {
                $this->db->insert('settings', ['setting_key' => $k, 'setting_value' => $v, 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }

        $row = $this->db->where('setting_key', 'privacy_notice')->get('settings')->row_array();
        if ($row && stripos((string) $row['setting_value'], 'identity documents') === false) {
            $text = rtrim((string) $row['setting_value']) . "\n\nCopies of identity documents and qualifications you upload are kept privately: only you and the Center's administrators can open them, and they are used only to confirm your identity and qualifications.";
            $this->db->where('setting_key', 'privacy_notice')->update('settings', ['setting_value' => $text, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('user_documents', true);
        $this->db->where_in('setting_key', ['docs_required_student', 'docs_required_lecturer'])->delete('settings');
    }
}
