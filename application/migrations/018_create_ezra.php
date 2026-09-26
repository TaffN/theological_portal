<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ezra - the portal's AI assistant (students first).
 *
 *  ezra_messages - every question and answer, per user, grouped into
 *                  conversations (thread_no), with the tokens used and what
 *                  it cost, so the monthly spending limit can be enforced
 *  settings      - on/off, spending limit, daily limit, model, the statement
 *                  of faith Ezra follows (editable by administrators)
 */
class Migration_Create_ezra extends CI_Migration
{
    public function up()
    {
        $id = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true];

        $this->dbforge->add_field([
            'id'                 => $id + ['auto_increment' => true],
            'user_id'            => $id,
            'thread_no'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
            'role'               => ['type' => 'ENUM', 'constraint' => ['user', 'assistant'], 'default' => 'user'],
            'content'            => ['type' => 'TEXT'],
            'status'             => ['type' => 'ENUM', 'constraint' => ['ok', 'refused', 'error'], 'default' => 'ok'],
            'model'              => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'input_tokens'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'cache_write_tokens' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'cache_read_tokens'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'output_tokens'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'cost_usd'           => ['type' => 'DECIMAL', 'constraint' => '10,6', 'default' => '0.000000'],
            'created_at'         => ['type' => 'DATETIME'],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key(['user_id', 'thread_no']);
        $this->dbforge->add_key('created_at');
        $this->dbforge->create_table('ezra_messages');
        $this->db->query('ALTER TABLE ezra_messages ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE');

        $defaults = [
            'ezra_enabled'         => '1',
            'ezra_roles'           => 'student',
            'ezra_monthly_cap_usd' => '50',
            'ezra_daily_limit'     => '25',
            'ezra_model'           => 'claude-opus-5',
            'ezra_effort'          => 'low',
            'ezra_bible_version'   => 'NKJV',
            'ezra_retention_days'  => '365',
            'ezra_warned_month'    => '',
            'ezra_statement_of_faith' => self::statement_of_faith(),
        ];
        foreach ($defaults as $k => $v) {
            if (! $this->db->where('setting_key', $k)->count_all_results('settings')) {
                $this->db->insert('settings', ['setting_key' => $k, 'setting_value' => $v, 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }

        // Tell students what happens to what they type to Ezra.
        $row = $this->db->where('setting_key', 'privacy_notice')->get('settings')->row_array();
        if ($row && stripos((string) $row['setting_value'], 'Ezra') === false) {
            $text = rtrim((string) $row['setting_value']) . "\n\nIf you use Ezra, the portal's AI assistant, your questions and a summary of your own course information (courses, due dates, published marks) are sent to Anthropic, the company that provides Ezra's AI, only to produce the answer. Your conversations are kept in the portal so you can see them again, and are deleted after a period set by the Center.";
            $this->db->where('setting_key', 'privacy_notice')->update('settings', ['setting_value' => $text, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('ezra_messages');
        $this->db->like('setting_key', 'ezra_', 'after')->delete('settings');
    }

    /**
     * Starting point, based on the Assemblies of God Statement of Fundamental
     * Truths. The Center should check it against its own statement and edit it
     * on the admin Ezra page.
     */
    public static function statement_of_faith()
    {
        return "We are a Pentecostal Bible college in the Assemblies of God tradition. We believe:\n"
            . "1. The Scriptures are inspired by God, the infallible and authoritative rule of faith and conduct.\n"
            . "2. There is one true God, eternally existing as Father, Son and Holy Spirit.\n"
            . "3. The Lord Jesus Christ is fully God and fully man: born of a virgin, sinless, crucified for our sins, bodily risen and ascended.\n"
            . "4. Humanity was created good but fell by voluntary sin, and all people need salvation.\n"
            . "5. Salvation is by grace through faith in Jesus Christ, through repentance toward God; the new birth is evidenced by a holy life.\n"
            . "6. The ordinances of the church are water baptism by immersion and the Lord's Supper.\n"
            . "7. All believers are entitled to, and should earnestly seek, the baptism in the Holy Spirit, an experience distinct from and subsequent to the new birth, which empowers for life and service.\n"
            . "8. The initial physical evidence of the baptism in the Holy Spirit is speaking in other tongues as the Spirit gives utterance.\n"
            . "9. Sanctification is an act of separation from evil and dedication to God, progressively realised by the believer through the Spirit and the Word.\n"
            . "10. The church is the body of Christ, called to worship God, evangelise the world and build up believers.\n"
            . "11. God calls and equips people for ministry to lead the church in these purposes.\n"
            . "12. Divine healing is provided for in the atonement and is the privilege of all believers.\n"
            . "13. The blessed hope: the resurrection of those who have died in Christ and their catching up, together with those alive at his coming, to meet the Lord.\n"
            . "14. The second coming of Christ, followed by his millennial reign on earth.\n"
            . "15. There will be a final judgment, and eternal punishment for those not found in the book of life.\n"
            . "16. We look for new heavens and a new earth in which righteousness dwells.";
    }
}
