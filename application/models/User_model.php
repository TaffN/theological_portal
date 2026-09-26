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
        if ($id && $this->db->field_exists('verify_token', $this->table)) {
            $this->new_verify_token($id);
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

    /* ------------------------------------------------ extended profile */

    /** Fields a user (or an admin) may fill in on the profile form. */
    public static $profile_fields = [
        'title', 'date_of_birth', 'gender', 'national_id', 'alt_phone',
        'address_line1', 'address_line2', 'city', 'province', 'country', 'postal_code',
        'emergency_name', 'emergency_relationship', 'emergency_phone',
        'church_name', 'denomination', 'ministry_role', 'education_level', 'occupation', 'referral_source',
        'qualifications', 'bio',
    ];

    public function get_profile($userId)
    {
        if (! $this->db->table_exists('user_profiles')) {
            return array_fill_keys(self::$profile_fields, null);
        }
        $row = $this->db->where('user_id', $userId)->get('user_profiles')->row_array();
        return $row ?: array_fill_keys(self::$profile_fields, null);
    }

    public function save_profile($userId, array $input)
    {
        $data = [];
        foreach (self::$profile_fields as $f) {
            if (array_key_exists($f, $input)) {
                $v = trim((string) $input[$f]);
                $data[$f] = $v === '' ? null : mb_substr($v, 0, $f === 'bio' || $f === 'qualifications' ? 2000 : 150);
            }
        }
        if (isset($data['date_of_birth']) && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_of_birth'])) {
            $data['date_of_birth'] = null;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($this->db->where('user_id', $userId)->count_all_results('user_profiles') > 0) {
            return $this->db->where('user_id', $userId)->update('user_profiles', $data);
        }
        $data['user_id'] = $userId;
        return $this->db->insert('user_profiles', $data);
    }

    /**
     * How complete someone's details are, as a percentage, plus what's missing.
     * Only counts the fields that matter for their role.
     */
    public function completeness(array $user, array $profile)
    {
        $checks = [
            'Phone number'      => $user['phone'],
            'Profile photo'     => $user['photo_path'],
            'Date of birth'     => $profile['date_of_birth'],
            'Gender'            => $profile['gender'],
            'Home address'      => $profile['address_line1'],
            'City / town'       => $profile['city'],
        ];
        if ($user['role'] === 'student') {
            $checks += [
                'National ID'       => $profile['national_id'],
                'Emergency contact' => $profile['emergency_phone'],
                'Church'            => $profile['church_name'],
                'Education level'   => $profile['education_level'],
            ];
        } elseif ($user['role'] === 'lecturer') {
            $checks += [
                'Title'          => $profile['title'],
                'Qualifications' => $profile['qualifications'],
                'Short bio'      => $profile['bio'],
            ];
        }
        $missing = array_keys(array_filter($checks, function ($v) { return $v === null || $v === ''; }));
        $pct = (int) round((count($checks) - count($missing)) / count($checks) * 100);
        return ['percent' => $pct, 'missing' => $missing];
    }

    /** New secret for the ID card QR code; the old card's QR stops working. */
    public function new_verify_token($userId)
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $t = '';
            for ($i = 0; $i < 20; $i++) {
                $t .= $chars[random_int(0, strlen($chars) - 1)];
            }
            if ($this->db->where('verify_token', $t)->count_all_results($this->table) === 0) {
                $this->db->where('id', $userId)->update($this->table, ['verify_token' => $t]);
                return $t;
            }
        }
        return null;
    }

    public function find_by_token($token)
    {
        if (! preg_match('/^[A-Za-z0-9]{10,32}$/', (string) $token)) {
            return null;
        }
        return $this->db->where('verify_token', $token)->get($this->table)->row_array();
    }

    /** Records that the user agreed to the privacy notice (at registration). */
    public function record_consent($userId)
    {
        if (! $this->db->table_exists('user_profiles')) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        if ($this->db->where('user_id', $userId)->count_all_results('user_profiles') > 0) {
            return $this->db->where('user_id', $userId)->update('user_profiles', ['privacy_consent_at' => $now]);
        }
        return $this->db->insert('user_profiles', ['user_id' => $userId, 'privacy_consent_at' => $now, 'updated_at' => $now]);
    }
}
