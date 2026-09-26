<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stores error reports. record() groups repeats of the same problem into one
 * row (by fingerprint) and bumps `occurrences`, so a broken page hit 500
 * times shows up once, not 500 times.
 */
class Error_model extends CI_Model
{
    protected $table = 'error_reports';

    public function record(array $e)
    {
        if (! $this->db->table_exists($this->table)) {
            return null;
        }

        $now   = date('Y-m-d H:i:s');
        $title = mb_substr(trim((string) $e['title']), 0, 255);
        $url   = isset($e['url']) ? mb_substr((string) $e['url'], 0, 500) : null;

        // User reports are always their own row; automatic ones are grouped.
        $fingerprint = $e['source'] === 'user'
            ? sha1(uniqid('u', true))
            : sha1($e['source'] . '|' . $title . '|' . (isset($e['group_key']) ? $e['group_key'] : $url));

        $existing = $this->db->where('fingerprint', $fingerprint)
            ->where_in('status', ['open', 'resolved'])
            ->get($this->table)->row_array();

        if ($existing) {
            $this->db->set('occurrences', 'occurrences + 1', false)
                ->set('last_seen', $now)
                // A resolved error that happens again is re-opened automatically.
                ->set('status', 'open')
                ->where('id', $existing['id'])
                ->update($this->table);
            return $existing['reference'];
        }

        $reference = $this->_new_reference();
        $this->db->insert($this->table, [
            'reference'   => $reference,
            'fingerprint' => $fingerprint,
            'source'      => $e['source'],
            'severity'    => isset($e['severity']) ? $e['severity'] : 'medium',
            'title'       => $title,
            'details'     => isset($e['details']) ? mb_substr((string) $e['details'], 0, 20000) : null,
            'url'         => $url,
            'user_id'     => isset($e['user_id']) ? $e['user_id'] : null,
            'user_agent'  => isset($e['user_agent']) ? mb_substr((string) $e['user_agent'], 0, 255) : null,
            'ip_address'  => isset($e['ip_address']) ? $e['ip_address'] : null,
            'status'      => 'open',
            'first_seen'  => $now,
            'last_seen'   => $now,
        ]);

        return $reference;
    }

    public function find($id)
    {
        return $this->db->select('error_reports.*, users.name AS user_name, users.email AS user_email, r.name AS resolver_name')
            ->from($this->table)
            ->join('users', 'users.id = error_reports.user_id', 'left')
            ->join('users r', 'r.id = error_reports.resolved_by', 'left')
            ->where('error_reports.id', $id)
            ->get()->row_array();
    }

    public function listing($status = 'open', $source = null, $limit = 100)
    {
        $this->db->select('error_reports.*, users.name AS user_name')
            ->from($this->table)
            ->join('users', 'users.id = error_reports.user_id', 'left')
            ->where('error_reports.status', $status);
        if ($source) {
            $this->db->where('error_reports.source', $source);
        }
        return $this->db
            ->order_by("FIELD(error_reports.severity, 'critical','high','medium','low')", '', false)
            ->order_by('error_reports.last_seen', 'DESC')
            ->limit($limit)
            ->get()->result_array();
    }

    public function counts()
    {
        $out = ['open' => 0, 'resolved' => 0, 'ignored' => 0, 'critical_open' => 0];
        if (! $this->db->table_exists($this->table)) {
            return $out;
        }
        foreach ($this->db->select('status, COUNT(*) AS n', false)->group_by('status')->get($this->table)->result_array() as $r) {
            $out[$r['status']] = (int) $r['n'];
        }
        $out['critical_open'] = $this->db->where('status', 'open')->where_in('severity', ['critical', 'high'])->count_all_results($this->table);
        return $out;
    }

    public function set_status($id, $status, $adminId, $note = null)
    {
        return $this->db->where('id', $id)->update($this->table, [
            'status'      => $status,
            'admin_note'  => $note,
            'resolved_by' => $status === 'open' ? null : $adminId,
            'resolved_at' => $status === 'open' ? null : date('Y-m-d H:i:s'),
        ]);
    }

    private function _new_reference()
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I confusion
        do {
            $code = 'ER-';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while ($this->db->where('reference', $code)->count_all_results($this->table) > 0);
        return $code;
    }
}
