<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * A student's enrolment in a PROGRAM: they apply to the program, pay its fee once, and get every module in it.
 *
 * The access checks everywhere else still look at `enrollments` (one row per student per module), so
 * activating a program enrolment writes those rows (grant_modules). full_access = 0 marks students who
 * were enrolled module by module before programs had a fee: they keep exactly the modules they hold and
 * are not given new ones automatically.
 */
class Program_enrollment_model extends CI_Model
{
    protected $table = 'program_enrollments';

    /** One enrolment with its program's name, slug, fee and status. */
    public function find($id)
    {
        return $this->with_program()->where('pe.id', (int) $id)->get()->row_array();
    }

    public function find_for($userId, $programId)
    {
        return $this->with_program()->where('pe.user_id', (int) $userId)->where('pe.program_id', (int) $programId)->get()->row_array();
    }

    /** All of a student's program enrolments, newest first. */
    public function for_student($userId)
    {
        return $this->with_program()->where('pe.user_id', (int) $userId)->order_by('pe.created_at', 'DESC')->get()->result_array();
    }

    public function create_pending($userId, $programId)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->insert($this->table, [
            'user_id' => (int) $userId, 'program_id' => (int) $programId, 'status' => 'pending_payment',
            'full_access' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        return $this->db->insert_id();
    }

    /**
     * Payment approved (or a free program): the enrolment becomes active and the student gets every
     * module in the program. Returns how many modules were opened.
     */
    public function activate($id)
    {
        $pe = $this->find($id);
        if (! $pe) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->update($this->table, [
            'status' => 'active', 'enrolled_at' => $pe['enrolled_at'] ?: $now, 'updated_at' => $now,
        ]);
        return count($this->grant_modules($id));
    }

    /**
     * Makes sure the student has an active module enrolment for every module of the program.
     * Returns the ids of the modules that were newly opened for them.
     */
    public function grant_modules($id)
    {
        $pe = $this->find($id);
        if (! $pe) {
            return [];
        }
        $now = date('Y-m-d H:i:s');
        $opened = [];
        foreach ($this->db->select('id')->where('program_id', $pe['program_id'])->get('modules')->result_array() as $m) {
            $row = $this->db->where(['user_id' => $pe['user_id'], 'module_id' => $m['id']])->get('enrollments')->row_array();
            if (! $row) {
                $this->db->insert('enrollments', [
                    'user_id' => $pe['user_id'], 'module_id' => $m['id'], 'program_enrollment_id' => $pe['id'],
                    'status' => 'active', 'enrolled_at' => $now, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $opened[] = (int) $m['id'];
            } elseif ($row['status'] !== 'active' && $row['status'] !== 'completed') {
                $this->db->where('id', $row['id'])->update('enrollments', [
                    'status' => 'active', 'program_enrollment_id' => $pe['id'], 'enrolled_at' => $row['enrolled_at'] ?: $now, 'updated_at' => $now,
                ]);
                $opened[] = (int) $m['id'];
            } elseif (empty($row['program_enrollment_id'])) {
                $this->db->where('id', $row['id'])->update('enrollments', ['program_enrollment_id' => $pe['id']]);
            }
        }
        return $opened;
    }

    /**
     * A module was added to (or moved into) a program: open it for everyone with an active, full-access
     * enrolment. Returns [user_id => [module ids newly opened]] so the caller can tell them.
     */
    public function sync_program($programId)
    {
        $out = [];
        $rows = $this->db->select('id, user_id')->where(['program_id' => (int) $programId, 'status' => 'active', 'full_access' => 1])
            ->get($this->table)->result_array();
        foreach ($rows as $pe) {
            $opened = $this->grant_modules($pe['id']);
            if ($opened) {
                $out[(int) $pe['user_id']] = $opened;
            }
        }
        return $out;
    }

    /** Students with an active enrolment in a program, with how many modules they hold. */
    public function active_count($programId)
    {
        return (int) $this->db->where(['program_id' => (int) $programId, 'status' => 'active'])->count_all_results($this->table);
    }

    protected function with_program()
    {
        return $this->db->select('pe.*, programs.name AS program_name, programs.slug AS program_slug, programs.fee_amount AS fee_amount,
                programs.duration_text AS program_duration, programs.status AS program_status')
            ->from($this->table . ' pe')->join('programs', 'programs.id = pe.program_id');
    }
}
