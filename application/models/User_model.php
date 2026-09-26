<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    protected $table = 'users';

    public function __construct()
    {
        parent::__construct();
    }

    public function find($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function find_by_email($email)
    {
        return $this->db->where('email', $email)->get($this->table)->row_array();
    }

    public function all()
    {
        return $this->db->get($this->table)->result_array();
    }

    public function find_students()
    {
        return $this->db->where('role', 'student')->get($this->table)->result_array();
    }

    public function find_lecturers()
    {
        return $this->db->where('role', 'lecturer')->get($this->table)->result_array();
    }

    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        $id = $this->db->insert_id();

        if ($id && isset($data['role']) && $this->db->field_exists('id_number', $this->table)) {
            $this->assign_id_number($id, $data['role']);
        }
        return $id;
    }

    /**
     * Gives a user the next free number for their role and year, e.g.
     * TCS-2026-0014. The unique index on id_number guarantees no duplicates;
     * if two people register in the same instant, the loser simply retries.
     */
    public function assign_id_number($userId, $role)
    {
        $prefix = ['student' => 'TCS', 'lecturer' => 'TCL', 'admin' => 'TCA'];
        if (! isset($prefix[$role])) {
            return null;
        }
        $base = $prefix[$role] . '-' . date('Y') . '-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $last = $this->db->select('id_number')->like('id_number', $base, 'after')
                ->order_by('id_number', 'DESC')->limit(1)->get($this->table)->row_array();
            $next = $last ? ((int) substr($last['id_number'], strlen($base))) + 1 : 1;
            $number = $base . str_pad($next + $attempt, 4, '0', STR_PAD_LEFT);

            $debug = $this->db->db_debug;
            $this->db->db_debug = false;
            $ok = $this->db->where('id', $userId)->update($this->table, ['id_number' => $number]);
            $this->db->db_debug = $debug;

            if ($ok) {
                return $number;
            }
        }
        return null;
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function set_password($userId, $plainPassword)
    {
        return $this->update($userId, [
            'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
        ]);
    }

    public function verify_password($email, $plainPassword)
    {
        $user = $this->find_by_email($email);

        if ($user && password_verify($plainPassword, $user['password_hash'])) {
            return $user;
        }

        return null;
    }

    /** Course names shown on someone's ID card. */
    public function card_courses(array $user)
    {
        if ($user['role'] === 'lecturer') {
            $rows = $this->db->select('courses.name')->from('course_lecturers')
                ->join('courses', 'courses.id = course_lecturers.course_id')
                ->where('course_lecturers.user_id', $user['id'])->get()->result_array();
        } elseif ($user['role'] === 'student') {
            $rows = $this->db->select('courses.name')->from('enrollments')
                ->join('courses', 'courses.id = enrollments.course_id')
                ->where('enrollments.user_id', $user['id'])->where('enrollments.status', 'active')->get()->result_array();
        } else {
            $rows = [];
        }
        return array_column($rows, 'name');
    }
}
