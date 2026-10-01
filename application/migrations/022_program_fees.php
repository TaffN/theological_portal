<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * v14: fees and enrolment belong to the PROGRAM.
 *
 * A student applies to a program (e.g. "Certificate in Christian Ministry", 6 modules, $300), pays once,
 * and gets every module in it. Modules no longer have a price of their own.
 *
 *  programs.fee_amount            - what the whole program costs
 *  program_enrollments            - one row per student per program (pending_payment / active / ...)
 *  enrollments.program_enrollment_id - which program enrolment gave the student this module
 *                                   (enrollments stays one row per student per module: it is what every
 *                                   access check, register and result already uses)
 *  payments.program_enrollment_id - a payment is for a program enrolment (enrollment_id stays for old
 *                                   payments and may now be NULL)
 *
 * Existing data (nothing is deleted or changed in what a student can open):
 *  - each program's fee starts as the total of its modules' fees (edit it on the Programs page);
 *  - every student who already had modules in a program gets a program enrolment. Their modules and
 *    payments are linked to it. If they hold only some of the program's modules, the enrolment is marked
 *    full_access = 0: they keep exactly what they paid for and are not given new modules automatically;
 *  - old links to payments/upload/{enrollment id} in notifications are pointed at the new enrolment.
 *
 * Re-runnable if interrupted. BACK UP THE DATABASE FIRST.
 */
class Migration_Program_fees extends CI_Migration
{
    public function up()
    {
        // 1. programs.fee_amount -------------------------------------------------
        if (! $this->db->field_exists('fee_amount', 'programs')) {
            $this->db->query("ALTER TABLE programs ADD fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER duration_text");
            // The old per-module prices add up to a sensible starting price for the whole program.
            $this->db->query("UPDATE programs p SET fee_amount = COALESCE((SELECT SUM(m.fee_amount) FROM modules m WHERE m.program_id = p.id), 0)");
        }

        // 2. program_enrollments -------------------------------------------------
        if (! $this->db->table_exists('program_enrollments')) {
            $this->db->query("CREATE TABLE program_enrollments (
                id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT(11) UNSIGNED NOT NULL,
                program_id INT(11) UNSIGNED NOT NULL,
                status ENUM('pending_payment','active','completed','suspended') NOT NULL DEFAULT 'pending_payment',
                full_access TINYINT(1) NOT NULL DEFAULT 1,
                enrolled_at DATETIME NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY program_enrollments_pair (user_id, program_id),
                KEY program_enrollments_program (program_id, status),
                CONSTRAINT program_enrollments_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT program_enrollments_program_fk FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }

        // 3. enrollments.program_enrollment_id -------------------------------------
        if (! $this->db->field_exists('program_enrollment_id', 'enrollments')) {
            $this->db->query("ALTER TABLE enrollments ADD program_enrollment_id INT(11) UNSIGNED NULL AFTER module_id");
            $this->db->query("ALTER TABLE enrollments ADD CONSTRAINT enrollments_program_enrollment_fk FOREIGN KEY (program_enrollment_id) REFERENCES program_enrollments(id) ON DELETE SET NULL ON UPDATE CASCADE");
        }

        // 4. payments.program_enrollment_id; enrollment_id may be empty -------------
        if (! $this->db->field_exists('program_enrollment_id', 'payments')) {
            foreach ($this->foreign_keys('payments', 'enrollments') as $fk) {
                $this->db->query("ALTER TABLE payments DROP FOREIGN KEY `$fk`");
            }
            $this->db->query("ALTER TABLE payments MODIFY enrollment_id INT(11) UNSIGNED NULL");
            $this->db->query("ALTER TABLE payments ADD CONSTRAINT payments_enrollment_fk FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE ON UPDATE CASCADE");
            $this->db->query("ALTER TABLE payments ADD program_enrollment_id INT(11) UNSIGNED NULL AFTER enrollment_id");
            $this->db->query("ALTER TABLE payments ADD CONSTRAINT payments_program_enrollment_fk FOREIGN KEY (program_enrollment_id) REFERENCES program_enrollments(id) ON DELETE CASCADE ON UPDATE CASCADE");
        }

        // 5. Existing students: one program enrolment per program they already have modules in ----
        $now = date('Y-m-d H:i:s');
        $map = [];                                            // old enrollment id => program enrolment id
        $rows = $this->db->query("SELECT e.id, e.user_id, e.status, e.enrolled_at, m.program_id
                FROM enrollments e JOIN modules m ON m.id = e.module_id
                WHERE e.program_enrollment_id IS NULL ORDER BY e.user_id, m.program_id, e.id")->result_array();
        $groups = [];
        foreach ($rows as $r) {
            $groups[$r['user_id'] . '-' . $r['program_id']][] = $r;
        }
        foreach ($groups as $list) {
            $userId = (int) $list[0]['user_id'];
            $programId = (int) $list[0]['program_id'];
            $paidUp = 0; $suspended = 0; $first = null;
            foreach ($list as $r) {
                if (in_array($r['status'], ['active', 'completed'], true)) { $paidUp++; }
                if ($r['status'] === 'suspended') { $suspended++; }
                if ($r['enrolled_at'] && ($first === null || $r['enrolled_at'] < $first)) { $first = $r['enrolled_at']; }
            }
            $status = $paidUp > 0 ? 'active' : ($suspended > 0 ? 'suspended' : 'pending_payment');
            $total = (int) $this->db->where('program_id', $programId)->count_all_results('modules');
            // Waiting to pay: nothing granted yet, so the whole program will be on offer. Otherwise keep exactly what they hold.
            $full = $status === 'pending_payment' || $paidUp >= $total ? 1 : 0;

            $existing = $this->db->where(['user_id' => $userId, 'program_id' => $programId])->get('program_enrollments')->row_array();
            if ($existing) {
                $peId = (int) $existing['id'];
            } else {
                $this->db->insert('program_enrollments', [
                    'user_id' => $userId, 'program_id' => $programId, 'status' => $status, 'full_access' => $full,
                    'enrolled_at' => $first, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $peId = (int) $this->db->insert_id();
            }
            foreach ($list as $r) {
                $this->db->where('id', $r['id'])->update('enrollments', ['program_enrollment_id' => $peId]);
                $this->db->where('enrollment_id', $r['id'])->update('payments', ['program_enrollment_id' => $peId]);
                $map[(int) $r['id']] = $peId;
            }
        }

        // 6. Old "pay for this" links in notifications ------------------------------
        if ($map && $this->db->table_exists('notifications')) {
            foreach ($this->db->like('link', 'payments/upload/')->get('notifications')->result_array() as $n) {
                if (preg_match('#payments/upload/(\d+)#', $n['link'], $m) && isset($map[(int) $m[1]])) {
                    $this->db->where('id', $n['id'])->update('notifications', ['link' => preg_replace('#payments/upload/\d+#', 'payments/upload/' . $map[(int) $m[1]], $n['link'])]);
                }
            }
        }
    }

    /** Reverses the structure. Refuses if program payments exist that the old structure cannot hold. */
    public function down()
    {
        if ($this->db->field_exists('program_enrollment_id', 'payments')
            && $this->db->where('enrollment_id IS NULL', null, false)->count_all_results('payments') > 0) {
            throw new RuntimeException('Cannot go back: some payments belong to a program, not a module.');
        }
        foreach ([['payments', 'program_enrollment_id'], ['enrollments', 'program_enrollment_id']] as $c) {
            if ($this->db->field_exists($c[1], $c[0])) {
                foreach ($this->foreign_keys($c[0], 'program_enrollments') as $fk) {
                    $this->db->query("ALTER TABLE `{$c[0]}` DROP FOREIGN KEY `$fk`");
                }
                $this->db->query("ALTER TABLE `{$c[0]}` DROP COLUMN `{$c[1]}`");
            }
        }
        if ($this->db->field_exists('enrollment_id', 'payments')) {
            foreach ($this->foreign_keys('payments', 'enrollments') as $fk) {
                $this->db->query("ALTER TABLE payments DROP FOREIGN KEY `$fk`");
            }
            $this->db->query("ALTER TABLE payments MODIFY enrollment_id INT(11) UNSIGNED NOT NULL");
            $this->db->query("ALTER TABLE payments ADD FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE ON UPDATE CASCADE");
        }
        $this->dbforge->drop_table('program_enrollments', true);
        if ($this->db->field_exists('fee_amount', 'programs')) {
            $this->db->query('ALTER TABLE programs DROP COLUMN fee_amount');
        }
    }

    private function foreign_keys($table, $referenced)
    {
        return array_unique(array_column($this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME = ?", [$table, $referenced])->result_array(), 'CONSTRAINT_NAME'));
    }
}
