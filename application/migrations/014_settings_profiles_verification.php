<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 *  settings       - organisation details editable by admins (replaces config/portal.php)
 *  user_profiles  - extra personal details, one row per user (kept out of `users`
 *                   so the login table stays small)
 *  users.verify_token - secret code in each ID card's QR code
 */
class Migration_Settings_profiles_verification extends CI_Migration
{
    public function up()
    {
        // ---- settings
        $this->dbforge->add_field([
            'setting_key'   => ['type' => 'VARCHAR', 'constraint' => 60],
            'setting_value' => ['type' => 'TEXT', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->dbforge->add_key('setting_key', true);
        $this->dbforge->create_table('settings');

        // Carry over payment details already typed into config/portal.php.
        $this->config->load('portal', true, true);
        $old = (array) $this->config->item('portal');
        $defaults = [
            'org_name'            => 'Theological Center',
            'org_short_name'      => 'Theological Center',
            'org_tagline'         => 'Learning Portal',
            'org_initials'        => 'TC',
            'org_country'         => 'Zimbabwe',
            'office_hours'        => 'Mon - Fri, 8:00 - 16:30',
            'id_card_valid_months'=> '12',
            'receipt_footer'      => 'Thank you. Please keep this receipt for your records.',
            'id_card_note'        => 'This card remains the property of the institution. If found, please return it to the address above.',
            'privacy_notice'      => "We collect your personal details (name, contact details, address, date of birth, ID number and photo) so we can enrol you, contact you, issue your ID card and keep your academic records.\n\nYour details are only seen by our administrators and, where needed for teaching, your lecturers. We do not sell or share your information with anyone else, except where the law requires it.\n\nYou can ask the administrator at any time to see, correct or delete the information we hold about you.",
        ];
        foreach (['pay_ecocash_number', 'pay_ecocash_name', 'pay_bank_name', 'pay_bank_branch', 'pay_bank_account', 'pay_bank_holder', 'pay_reference_hint'] as $k) {
            $defaults[$k] = isset($old[$k]) ? (string) $old[$k] : '';
        }
        foreach ($defaults as $k => $v) {
            $this->db->insert('settings', ['setting_key' => $k, 'setting_value' => $v, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        // ---- user_profiles
        $v = function ($len) { return ['type' => 'VARCHAR', 'constraint' => $len, 'null' => true]; };
        $this->dbforge->add_field([
            'user_id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title'                  => $v(30),
            'date_of_birth'          => ['type' => 'DATE', 'null' => true],
            'gender'                 => $v(20),
            'national_id'            => $v(40),
            'alt_phone'              => $v(30),
            'address_line1'          => $v(150),
            'address_line2'          => $v(150),
            'city'                   => $v(80),
            'province'               => $v(80),
            'country'                => $v(80),
            'postal_code'            => $v(20),
            'emergency_name'         => $v(150),
            'emergency_relationship' => $v(60),
            'emergency_phone'        => $v(30),
            'church_name'            => $v(150),
            'denomination'           => $v(100),
            'ministry_role'          => $v(100),
            'education_level'        => $v(60),
            'occupation'             => $v(100),
            'referral_source'        => $v(60),
            'qualifications'         => ['type' => 'TEXT', 'null' => true],
            'bio'                    => ['type' => 'TEXT', 'null' => true],
            'privacy_consent_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->dbforge->add_key('user_id', true);
        $this->dbforge->create_table('user_profiles');
        $this->db->query('ALTER TABLE user_profiles ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE');

        // ---- verification tokens for ID-card QR codes
        $this->dbforge->add_column('users', [
            'verify_token' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true, 'after' => 'id_number'],
        ]);
        foreach ($this->db->select('id')->get('users')->result_array() as $u) {
            $this->db->where('id', $u['id'])->update('users', ['verify_token' => self::token()]);
        }
        $this->db->query('ALTER TABLE users ADD UNIQUE KEY verify_token (verify_token)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE users DROP INDEX verify_token');
        $this->dbforge->drop_column('users', 'verify_token');
        $this->dbforge->drop_table('user_profiles');
        $this->dbforge->drop_table('settings');
    }

    public static function token()
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $t = '';
        for ($i = 0; $i < 20; $i++) {
            $t .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $t;
    }
}
