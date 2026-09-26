<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only queries that feed the three dashboards. Kept in one place so
 * the dashboards never slow down or clutter the main models.
 */
class Dashboard_model extends CI_Model
{
    /* ------------------------------------------------------------ ADMIN */

    public function admin_stats()
    {
        $feesTotal = $this->db->select_sum('amount')->where('status', 'approved')->get('payments')->row()->amount;
        $feesMonth = $this->db->select_sum('amount')
            ->where('status', 'approved')
            ->where('reviewed_at >=', date('Y-m-01 00:00:00'))
            ->get('payments')->row()->amount;

        return [
            'students'           => $this->db->where('role', 'student')->count_all_results('users'),
            'lecturers'          => $this->db->where('role', 'lecturer')->count_all_results('users'),
            'courses'            => $this->db->where('status', 'active')->count_all_results('courses'),
            'active_enrollments' => $this->db->where('status', 'active')->count_all_results('enrollments'),
            'pending_payments'   => $this->db->where('status', 'pending')->count_all_results('payments'),
            'fees_total'         => (float) $feesTotal,
            'fees_month'         => (float) $feesMonth,
        ];
    }

    /**
     * Approved fees per month for the last $months months, oldest first,
     * with zero-filled months so the chart never has gaps.
     */
    public function fees_by_month($months = 6)
    {
        $firstOfThisMonth = strtotime(date('Y-m-01'));
        $start            = strtotime('-' . ($months - 1) . ' months', $firstOfThisMonth);

        $rows = $this->db
            ->select("DATE_FORMAT(reviewed_at, '%Y-%m') AS ym, SUM(amount) AS total", false)
            ->from('payments')
            ->where('status', 'approved')
            ->where('reviewed_at >=', date('Y-m-d H:i:s', $start))
            ->group_by('ym')
            ->get()->result_array();

        $byMonth = [];
        foreach ($rows as $r) {
            $byMonth[$r['ym']] = (float) $r['total'];
        }

        $labels = [];
        $values = [];
        for ($i = 0; $i < $months; $i++) {
            $ts       = strtotime('+' . $i . ' months', $start);
            $key      = date('Y-m', $ts);
            $labels[] = date('M', $ts);
            $values[] = isset($byMonth[$key]) ? $byMonth[$key] : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Paid-up (active or completed) enrollments per active course.
     */
    public function enrollments_by_course()
    {
        $rows = $this->db
            ->select('courses.name, COUNT(enrollments.id) AS total', false)
            ->from('courses')
            ->join('enrollments', "enrollments.course_id = courses.id AND enrollments.status IN ('active','completed')", 'left', false)
            ->where('courses.status', 'active')
            ->group_by('courses.id')
            ->order_by('total', 'DESC')
            ->get()->result_array();

        return [
            'labels' => array_column($rows, 'name'),
            'values' => array_map('intval', array_column($rows, 'total')),
        ];
    }

    public function enrollment_status_counts()
    {
        $counts = ['pending_payment' => 0, 'active' => 0, 'completed' => 0, 'suspended' => 0];

        $rows = $this->db->select('status, COUNT(*) AS total', false)
            ->from('enrollments')->group_by('status')->get()->result_array();

        foreach ($rows as $r) {
            $counts[$r['status']] = (int) $r['total'];
        }

        return $counts;
    }

    public function recent_payments($limit = 6)
    {
        return $this->db
            ->select('payments.id, payments.amount, payments.method, payments.status, payments.submitted_at, users.name AS student_name, courses.name AS course_name')
            ->from('payments')
            ->join('enrollments', 'enrollments.id = payments.enrollment_id')
            ->join('users', 'users.id = enrollments.user_id')
            ->join('courses', 'courses.id = enrollments.course_id')
            ->order_by('payments.submitted_at', 'DESC')
            ->limit($limit)
            ->get()->result_array();
    }

    public function recent_students($limit = 5)
    {
        return $this->db->select('name, email, created_at')
            ->where('role', 'student')
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('users')->result_array();
    }

    /* ---------------------------------------------------------- STUDENT */

    public function student_pending_payments($userId)
    {
        return $this->db->from('payments')
            ->join('enrollments', 'enrollments.id = payments.enrollment_id')
            ->where('enrollments.user_id', $userId)
            ->where('payments.status', 'pending')
            ->count_all_results();
    }

    /**
     * Enrollment ids that already have a proof of payment waiting for
     * review, so the dashboard doesn't nag students to upload twice.
     */
    public function student_enrollments_with_pending_proof($userId)
    {
        $rows = $this->db->select('payments.enrollment_id')
            ->from('payments')
            ->join('enrollments', 'enrollments.id = payments.enrollment_id')
            ->where('enrollments.user_id', $userId)
            ->where('payments.status', 'pending')
            ->get()->result_array();

        return array_map('intval', array_column($rows, 'enrollment_id'));
    }

    public function student_recent_materials($userId, $limit = 5)
    {
        if (! $this->db->table_exists('materials')) {
            return [];
        }

        return $this->db
            ->select('materials.id, materials.title, materials.created_at, materials.course_id, courses.name AS course_name')
            ->from('materials')
            ->join('enrollments', 'enrollments.course_id = materials.course_id')
            ->join('courses', 'courses.id = materials.course_id')
            ->where('enrollments.user_id', $userId)
            ->where('enrollments.status', 'active')
            ->order_by('materials.created_at', 'DESC')
            ->limit($limit)
            ->get()->result_array();
    }

    public function student_new_materials_count($userId, $days = 7)
    {
        if (! $this->db->table_exists('materials')) {
            return 0;
        }

        return $this->db->from('materials')
            ->join('enrollments', 'enrollments.course_id = materials.course_id')
            ->where('enrollments.user_id', $userId)
            ->where('enrollments.status', 'active')
            ->where('materials.created_at >=', date('Y-m-d H:i:s', strtotime('-' . (int) $days . ' days')))
            ->count_all_results();
    }

    /* --------------------------------------------------------- LECTURER */

    public function lecturer_courses_with_counts($userId)
    {
        return $this->db
            ->select('courses.id, courses.name, COUNT(enrollments.id) AS students', false)
            ->from('course_lecturers')
            ->join('courses', 'courses.id = course_lecturers.course_id')
            ->join('enrollments', "enrollments.course_id = courses.id AND enrollments.status = 'active'", 'left', false)
            ->where('course_lecturers.user_id', $userId)
            ->group_by('courses.id')
            ->order_by('courses.name', 'ASC')
            ->get()->result_array();
    }

    public function lecturer_materials_count($userId)
    {
        if (! $this->db->table_exists('materials')) {
            return 0;
        }
        return $this->db->where('lecturer_id', $userId)->count_all_results('materials');
    }

    public function lecturer_recent_materials($userId, $limit = 5)
    {
        if (! $this->db->table_exists('materials')) {
            return [];
        }

        return $this->db
            ->select('materials.id, materials.title, materials.created_at, materials.course_id, courses.name AS course_name')
            ->from('materials')
            ->join('courses', 'courses.id = materials.course_id')
            ->where('materials.lecturer_id', $userId)
            ->order_by('materials.created_at', 'DESC')
            ->limit($limit)
            ->get()->result_array();
    }

    /* ----------------------------------------------------------- SHARED */

    public function unread_notifications($userId)
    {
        if (! $this->db->table_exists('notifications')) {
            return 0;
        }
        return $this->db->where('user_id', $userId)->where('is_read', 0)->count_all_results('notifications');
    }

    /* ------------------------------------------------- v4 additions */

    /** Live announcements for a role, newest first. */
    public function announcements_for($role)
    {
        if (! $this->db->table_exists('announcements')) {
            return [];
        }
        $this->db->where('is_active', 1)
            ->group_start()->where('expires_at IS NULL', null, false)->or_where('expires_at >=', date('Y-m-d H:i:s'))->group_end();
        if ($role !== 'admin') {
            $this->db->where_in('audience', ['all', $role]);
        }
        return $this->db->order_by('id', 'DESC')->limit(3)->get('announcements')->result_array();
    }

    public function recent_activity($limit = 8)
    {
        if (! $this->db->table_exists('audit_log')) {
            return [];
        }
        return $this->db->where_not_in('action', ['auth.logout'])
            ->order_by('id', 'DESC')->limit($limit)->get('audit_log')->result_array();
    }

    public function logins_today()
    {
        if (! $this->db->table_exists('audit_log')) {
            return 0;
        }
        return (int) $this->db->select('COUNT(DISTINCT user_id) AS n', false)
            ->where('action', 'auth.login')->where('created_at >=', date('Y-m-d 00:00:00'))
            ->get('audit_log')->row()->n;
    }

    public function failed_logins_today()
    {
        if (! $this->db->table_exists('audit_log')) {
            return 0;
        }
        return $this->db->where_in('action', ['auth.login_failed', 'auth.locked_out'])
            ->where('created_at >=', date('Y-m-d 00:00:00'))->count_all_results('audit_log');
    }

    /** Setup steps for a brand-new install, each [label, done, url, hint]. */
    public function admin_checklist($adminId)
    {
        $admin  = $this->db->where('id', $adminId)->get('users')->row_array();
        $CI     =& get_instance();
        $payConfigured = $CI->settings->get('pay_ecocash_number') !== '' || $CI->settings->get('pay_bank_account') !== '';
        $orgConfigured = $CI->settings->get('org_phone') !== '' && $CI->settings->get('org_address') !== '';

        return [
            ['Change the default admin password', ! password_verify('ChangeMe123!', $admin['password_hash']), base_url('profile') . '#password', 'Still using ChangeMe123!'],
            ['Use your real email to log in', $admin['email'] !== 'admin@example.com', base_url('admin_users/admins'), 'Still admin@example.com'],
            ['Add a second administrator', $this->db->where('role', 'admin')->where('status', 'active')->count_all_results('users') > 1, base_url('admin_users/admins'), 'So someone can reset your password if you forget it'],
            ['Add the Center\'s contact details', $orgConfigured, base_url('admin_settings') . '#set-contact', 'Shown on receipts, ID cards and the Help page'],
            ['Add your payment details', $payConfigured, base_url('admin_settings') . '#set-payments', 'EcoCash number and bank account'],
            ['Create your first course', $this->db->count_all('courses') > 0, base_url('admin_courses'), ''],
            ['Add a lecturer', $this->db->where('role', 'lecturer')->count_all_results('users') > 0, base_url('admin_users/lecturers'), ''],
            ['Assign a lecturer to a course', $this->db->count_all('course_lecturers') > 0, base_url('admin_courses'), ''],
            ['Post a welcome announcement', $this->db->table_exists('announcements') && $this->db->count_all('announcements') > 0, base_url('admin_announcements'), ''],
        ];
    }

    public function student_checklist($userId, array $courses)
    {
        $user = $this->db->where('id', $userId)->get('users')->row_array();
        $CI =& get_instance();
        $CI->load->model('User_model');
        $CI_complete = $CI->User_model->completeness($user, $CI->User_model->get_profile($userId))['percent'];
        $hasPayment = $this->db->from('payments')->join('enrollments', 'enrollments.id = payments.enrollment_id')
            ->where('enrollments.user_id', $userId)->count_all_results() > 0;
        $active = count(array_filter($courses, function ($c) { return $c['enrollment_status'] === 'active'; }));

        return [
            ['Create your account', true, null, ''],
            ['Add your WhatsApp number', ! empty($user['phone']), base_url('profile'), 'So the Center can reach you'],
            ['Add a profile photo', ! empty($user['photo_path']), base_url('profile') . '#photo', 'Used on your student ID card'],
            ['Complete your personal details', $CI_complete >= 80, base_url('profile') . '#details', 'Address, emergency contact, church'],
            ['Apply for a course', count($courses) > 0, base_url('courses'), ''],
            ['Upload your proof of payment', $hasPayment, base_url('courses'), ''],
            ['Get approved and start studying', $active > 0, base_url('student_materials'), 'Usually within a day'],
        ];
    }
}
