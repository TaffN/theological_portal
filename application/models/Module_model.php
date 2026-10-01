<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Module_model extends CI_Model
{
    protected $table = 'modules';

    public function __construct()
    {
        parent::__construct();
    }

    /** One module with its program's name and slug. */
    public function find($id)
    {
        return $this->with_program()->where('modules.id', (int) $id)->get()->row_array();
    }

    /** Every module, grouped by program then in the program's own order. */
    public function all()
    {
        return $this->with_program()->order_by('programs.name')->order_by('modules.sort_order')->order_by('modules.name')->get()->result_array();
    }

    /** Modules students may apply for: the module and its program are both open. */
    public function active_modules()
    {
        return $this->with_program()->where('modules.status', 'active')->where('programs.status', 'active')
            ->order_by('programs.name')->order_by('modules.sort_order')->get()->result_array();
    }

    /** A program's modules in their set order. */
    public function for_program($programId, $onlyActive = false)
    {
        $this->with_program()->where('modules.program_id', (int) $programId);
        if ($onlyActive) {
            $this->db->where('modules.status', 'active');
        }
        return $this->db->order_by('modules.sort_order')->order_by('modules.name')->get()->result_array();
    }

    /** May students apply for this module right now? (module and program both open) */
    public function is_open_for_applications(array $module)
    {
        return $module['status'] === 'active' && (! isset($module['program_status']) || $module['program_status'] === 'active');
    }

    public function create($data)
    {
        $data['sort_order'] = isset($data['sort_order']) ? $data['sort_order'] : $this->next_sort_order($data['program_id']);
        $data['code'] = isset($data['code']) && $data['code'] !== '' ? $data['code'] : $this->next_code();
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        // Moving a module to another program puts it last there.
        if (isset($data['program_id'])) {
            $current = $this->db->where('id', (int) $id)->get($this->table)->row_array();
            if ($current && (int) $current['program_id'] !== (int) $data['program_id'] && ! isset($data['sort_order'])) {
                $data['sort_order'] = $this->next_sort_order($data['program_id']);
            }
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', (int) $id)->update($this->table, $data);
    }

    /** Swaps a module with its neighbour in the program's order. */
    public function move($id, $direction)
    {
        $m = $this->db->where('id', (int) $id)->get($this->table)->row_array();
        if (! $m) {
            return false;
        }
        $siblings = $this->db->select('id')->where('program_id', $m['program_id'])
            ->order_by('sort_order')->order_by('name')->get($this->table)->result_array();
        $ids = array_map('intval', array_column($siblings, 'id'));
        $i = array_search((int) $id, $ids, true);
        $j = $direction === 'up' ? $i - 1 : $i + 1;
        if ($i === false || $j < 0 || $j >= count($ids)) {
            return false;
        }
        list($ids[$i], $ids[$j]) = [$ids[$j], $ids[$i]];
        foreach ($ids as $pos => $mid) {              // renumber 1..n so ties can never happen
            $this->db->where('id', $mid)->update($this->table, ['sort_order' => $pos + 1]);
        }
        return true;
    }

    /** What would be lost if this module were deleted (only an empty module can be). */
    public function usage($id)
    {
        $id = (int) $id;
        $out = [];
        foreach (['enrollments' => 'enrolments', 'materials' => 'materials', 'assignments' => 'assignments', 'exams' => 'exams',
                  'discussions' => 'discussion topics', 'attendance_sessions' => 'registers', 'calendar_events' => 'calendar events'] as $table => $label) {
            if ($this->db->table_exists($table)) {
                $n = $this->db->where('module_id', $id)->count_all_results($table);
                if ($n > 0) {
                    $out[$label] = $n;
                }
            }
        }
        return $out;
    }

    public function delete($id)
    {
        return ! $this->usage($id) && $this->db->where('id', (int) $id)->delete($this->table);
    }

    public function next_sort_order($programId)
    {
        $row = $this->db->select_max('sort_order', 'm')->where('program_id', (int) $programId)->get($this->table)->row_array();
        return (int) $row['m'] + 1;
    }

    /** MOD-001, MOD-002 ... (one more than the highest number used so far). */
    public function next_code()
    {
        $row = $this->db->select_max('id', 'm')->get($this->table)->row_array();
        $n = (int) $row['m'] + 1;
        do {
            $code = sprintf('MOD-%03d', $n++);
        } while ($this->db->where('code', $code)->count_all_results($this->table) > 0);
        return $code;
    }

    public function code_taken($code, $ignoreId = null)
    {
        $this->db->where('code', $code);
        if ($ignoreId) {
            $this->db->where('id !=', (int) $ignoreId);
        }
        return $this->db->count_all_results($this->table) > 0;
    }

    /** The base query: modules.* plus the program's name, slug and status. */
    protected function with_program()
    {
        return $this->db->select('modules.*, programs.name AS program_name, programs.slug AS program_slug, programs.status AS program_status')
            ->from('modules')->join('programs', 'programs.id = modules.program_id');
    }

    /**
     * Lecturers assigned to a module, via the module_lecturers pivot.
     */
    public function lecturers($moduleId)
    {
        return $this->db
            ->select('users.id, users.name, users.email')
            ->from('module_lecturers cl')
            ->join('users', 'users.id = cl.user_id')
            ->where('cl.module_id', $moduleId)
            ->get()
            ->result_array();
    }

    /**
     * The modules a person can see in the shared campus pages (discussions,
     * calendar, library, attendance): a student's paid-up modules, a
     * lecturer's assigned modules, or every module for an administrator.
     */
    public function for_user($userId, $role)
    {
        if ($role === 'admin') {
            return $this->all();
        }
        if ($role === 'lecturer') {
            return $this->with_program()->join('module_lecturers cl', 'cl.module_id = modules.id')
                ->where('cl.user_id', $userId)->order_by('programs.name')->order_by('modules.sort_order')->get()->result_array();
        }
        return $this->with_program()->join('enrollments e', 'e.module_id = modules.id')
            ->where('e.user_id', $userId)->where('e.status', 'active')->order_by('programs.name')->order_by('modules.sort_order')->get()->result_array();
    }

    /** Just the ids of for_user(). */
    public function ids_for_user($userId, $role)
    {
        return array_map('intval', array_column($this->for_user($userId, $role), 'id'));
    }

    /** May this person see this module in the shared campus pages? */
    public function user_can_see($moduleId, $userId, $role)
    {
        return in_array((int) $moduleId, $this->ids_for_user($userId, $role), true);
    }

    /** May this person run this module (add events, take registers, moderate)? Lecturers on it and admins. */
    public function user_can_manage($moduleId, $userId, $role)
    {
        if ($role === 'admin') {
            return true;
        }
        return $role === 'lecturer' && $this->db->where('module_id', $moduleId)->where('user_id', $userId)->count_all_results('module_lecturers') > 0;
    }
}
