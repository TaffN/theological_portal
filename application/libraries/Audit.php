<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Audit trail. Usage anywhere:
 *     $this->audit->log('payment.approved', 'payment', $id, 'Approved $50 from Danai', ['amount' => 50]);
 *
 * Never throws: an audit failure must not break the action being audited.
 */
class Audit
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function log($action, $entityType = null, $entityId = null, $description = '', array $meta = [], $userOverride = null)
    {
        try {
            if (! $this->CI->db->table_exists('audit_log')) {
                return false;
            }

            $session = isset($this->CI->session) ? $this->CI->session : null;
            $user = $userOverride ?: [
                'id'   => $session ? $session->userdata('user_id') : null,
                'name' => $session ? $session->userdata('name') : null,
                'role' => $session ? $session->userdata('role') : null,
            ];

            return $this->CI->db->insert('audit_log', [
                'user_id'     => $user['id'] ?: null,
                'user_name'   => $user['name'] ? mb_substr($user['name'], 0, 150) : null,
                'role'        => $user['role'] ?: null,
                'action'      => mb_substr($action, 0, 60),
                'entity_type' => $entityType,
                'entity_id'   => $entityId ? (int) $entityId : null,
                'description' => mb_substr((string) $description, 0, 500),
                'meta'        => $meta ? json_encode($meta) : null,
                'ip_address'  => $this->CI->input->ip_address(),
                'user_agent'  => mb_substr((string) $this->CI->input->user_agent(), 0, 255),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Audit log failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Failed logins for an email in the last $minutes - used for lockout.
     */
    public function recent_failed_logins($email, $minutes = 15)
    {
        if (! $this->CI->db->table_exists('audit_log')) {
            return 0;
        }
        return $this->CI->db
            ->where('action', 'auth.login_failed')
            ->where('entity_type', 'email:' . mb_substr(strtolower(trim($email)), 0, 34))
            ->where('created_at >=', date('Y-m-d H:i:s', time() - $minutes * 60))
            ->count_all_results('audit_log');
    }
}
