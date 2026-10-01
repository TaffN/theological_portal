<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class registers. A session is one class meeting of a module; each student
 * with access gets a mark: present, late, absent or excused.
 *
 * Attendance rate = (present + late) / (present + late + absent).
 * Excused absences don't count against a student, and a student who joined
 * after a class (no mark) isn't counted for it.
 */
class Attendance_model extends CI_Model
{
    public static $statuses = ['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'excused' => 'Excused'];

    public static function rate($present, $late, $absent)
    {
        $total = (int) $present + (int) $late + (int) $absent;
        return $total ? round(((int) $present + (int) $late) / $total * 100, 1) : null;
    }

    /** A module's sessions, newest first, with their counts. */
    public function sessions($moduleId)
    {
        return $this->db->select("s.*, u.name AS taken_by_name,
                SUM(a.status = 'present') AS present, SUM(a.status = 'late') AS late,
                SUM(a.status = 'absent') AS absent, SUM(a.status = 'excused') AS excused", false)
            ->from('attendance_sessions s')->join('attendance a', 'a.session_id = s.id', 'left')->join('users u', 'u.id = s.taken_by', 'left')
            ->where('s.module_id', (int) $moduleId)->group_by('s.id')
            ->order_by('s.session_date', 'DESC')->order_by('s.id', 'DESC')->get()->result_array();
    }

    public function find_session($id)
    {
        return $this->db->where('id', (int) $id)->get('attendance_sessions')->row_array();
    }

    /** [student_id => mark row] for one session. */
    public function marks($sessionId)
    {
        $out = [];
        foreach ($this->db->where('session_id', (int) $sessionId)->get('attendance')->result_array() as $m) {
            $out[(int) $m['student_id']] = $m;
        }
        return $out;
    }

    /**
     * Creates or updates a session and its marks.
     * $marks = [student_id => ['status' => ..., 'note' => ...]]
     */
    public function save_session($moduleId, $date, $topic, $takenBy, array $marks, $sessionId = null)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        if ($sessionId) {
            $this->db->where('id', (int) $sessionId)->update('attendance_sessions', ['session_date' => $date, 'topic' => $topic, 'updated_at' => $now]);
        } else {
            $this->db->insert('attendance_sessions', ['module_id' => $moduleId, 'session_date' => $date, 'topic' => $topic, 'taken_by' => $takenBy, 'created_at' => $now, 'updated_at' => $now]);
            $sessionId = $this->db->insert_id();
        }
        $existing = $this->marks($sessionId);
        foreach ($marks as $studentId => $m) {
            $row = ['status' => $m['status'], 'note' => $m['note'] !== '' ? $m['note'] : null];
            if (isset($existing[$studentId])) {
                if ($existing[$studentId]['status'] !== $row['status'] || (string) $existing[$studentId]['note'] !== (string) $row['note']) {
                    $this->db->where('id', $existing[$studentId]['id'])->update('attendance', $row + ['marked_at' => $now]);
                }
            } else {
                $this->db->insert('attendance', $row + ['session_id' => $sessionId, 'student_id' => $studentId, 'marked_at' => $now]);
            }
        }
        $this->db->trans_complete();
        return (int) $sessionId;
    }

    public function delete_session($id)
    {
        $this->db->where('id', (int) $id)->delete('attendance_sessions');   // marks go with it (foreign key cascade)
    }

    /** Every student with access to the module, with their totals and rate. */
    public function summary($moduleId)
    {
        $rows = $this->db->select("u.id, u.name, u.id_number, u.phone, u.photo_path, u.photo_updated_at,
                SUM(a.status = 'present') AS present, SUM(a.status = 'late') AS late,
                SUM(a.status = 'absent') AS absent, SUM(a.status = 'excused') AS excused", false)
            ->from('enrollments e')->join('users u', 'u.id = e.user_id')
            ->join('attendance_sessions s', 's.module_id = e.module_id', 'left')
            ->join('attendance a', 'a.session_id = s.id AND a.student_id = u.id', 'left')
            ->where('e.module_id', (int) $moduleId)->where('e.status', 'active')
            ->group_by('u.id')->order_by('u.name')->get()->result_array();
        foreach ($rows as &$r) {
            $r['rate'] = self::rate($r['present'], $r['late'], $r['absent']);
        }
        unset($r);
        return $rows;
    }

    /** A student's attendance in each of these modules, with their recent marks. */
    public function for_student($studentId, array $moduleIds, $recent = 8)
    {
        $out = [];
        foreach ($moduleIds as $cid) {
            $marks = $this->db->select('s.session_date, s.topic, a.status, a.note')->from('attendance a')
                ->join('attendance_sessions s', 's.id = a.session_id')
                ->where('a.student_id', (int) $studentId)->where('s.module_id', (int) $cid)
                ->order_by('s.session_date', 'DESC')->get()->result_array();
            $n = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
            foreach ($marks as $m) {
                $n[$m['status']]++;
            }
            $out[(int) $cid] = $n + [
                'sessions' => count($marks),
                'rate'     => self::rate($n['present'], $n['late'], $n['absent']),
                'recent'   => array_slice($marks, 0, $recent),
                'all'      => $marks,
            ];
        }
        return $out;
    }

    /** One line per module for the admin overview. */
    public function overview()
    {
        $rows = $this->db->select("c.id, c.name, COUNT(DISTINCT s.id) AS sessions, MAX(s.session_date) AS last_date,
                SUM(a.status = 'present') AS present, SUM(a.status = 'late') AS late, SUM(a.status = 'absent') AS absent", false)
            ->from('modules c')->join('attendance_sessions s', 's.module_id = c.id', 'left')->join('attendance a', 'a.session_id = s.id', 'left')
            ->group_by('c.id')->order_by('c.name')->get()->result_array();
        foreach ($rows as &$r) {
            $r['rate'] = self::rate($r['present'], $r['late'], $r['absent']);
            $r['students'] = $this->db->where('module_id', $r['id'])->where('status', 'active')->count_all_results('enrollments');
        }
        unset($r);
        return $rows;
    }
}
