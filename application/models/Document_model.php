<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Personal documents: identity copies, qualifications and certificates that
 * students and lecturers upload and administrators verify. Files are private
 * (uploads/documents, served by the Documents controller to the owner and admins).
 */
class Document_model extends CI_Model
{
    public static $types = [
        'national_id'       => 'National ID',
        'passport'          => 'Passport',
        'birth_certificate' => 'Birth certificate',
        'qualification'     => 'Qualification / certificate',
        'transcript'        => 'Academic transcript',
        'ordination'        => 'Ordination / ministry certificate',
        'reference'         => 'Reference letter',
        'other'             => 'Other',
    ];

    public static function type_label($type)
    {
        return isset(self::$types[$type]) ? self::$types[$type] : ucfirst(str_replace('_', ' ', $type));
    }

    public function find($id)
    {
        return $this->db->select('d.*, u.name AS owner_name, u.role AS owner_role, u.id_number, u.phone, r.name AS reviewer_name')
            ->from('user_documents d')->join('users u', 'u.id = d.user_id')->join('users r', 'r.id = d.reviewed_by', 'left')
            ->where('d.id', (int) $id)->get()->row_array();
    }

    public function for_user($userId)
    {
        return $this->db->select('d.*, r.name AS reviewer_name')->from('user_documents d')->join('users r', 'r.id = d.reviewed_by', 'left')
            ->where('d.user_id', (int) $userId)->order_by('d.uploaded_at', 'DESC')->get()->result_array();
    }

    public function create(array $data)
    {
        $data['uploaded_at'] = date('Y-m-d H:i:s');
        $data['status'] = 'pending';
        $this->db->insert('user_documents', $data);
        return $this->db->insert_id();
    }

    public function review($id, $status, $note, $adminId)
    {
        $this->db->where('id', (int) $id)->update('user_documents', [
            'status' => $status, 'review_note' => $note !== '' ? $note : null, 'reviewed_by' => $adminId, 'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete($doc)
    {
        $this->db->where('id', (int) $doc['id'])->delete('user_documents');
        if ($doc['file_path'] && is_file(FCPATH . $doc['file_path'])) {
            @unlink(FCPATH . $doc['file_path']);
        }
    }

    /** Documents in a review state, newest first ($role = student|lecturer|''). */
    public function queue($status, $role = '', $search = '', $limit = 200)
    {
        $this->db->select('d.*, u.name AS owner_name, u.role AS owner_role, u.id_number, u.photo_path, u.photo_updated_at')
            ->from('user_documents d')->join('users u', 'u.id = d.user_id')->where('d.status', $status);
        if ($role) {
            $this->db->where('u.role', $role);
        }
        if ($search !== '') {
            $this->db->group_start()->like('u.name', $search)->or_like('u.id_number', $search)->or_like('d.title', $search)->group_end();
        }
        return $this->db->order_by($status === 'pending' ? 'd.uploaded_at' : 'd.reviewed_at', $status === 'pending' ? 'ASC' : 'DESC')->limit($limit)->get()->result_array();
    }

    public function counts()
    {
        $out = ['pending' => 0, 'verified' => 0, 'rejected' => 0];
        foreach ($this->db->select('status, COUNT(*) AS n', false)->group_by('status')->get('user_documents')->result_array() as $r) {
            $out[$r['status']] = (int) $r['n'];
        }
        return $out;
    }

    /** Document types a role must provide (from Settings). */
    public function required_for($role)
    {
        $CI =& get_instance();
        $raw = $CI->settings->get('docs_required_' . $role, '');
        return array_values(array_filter(array_map('trim', explode(',', $raw)), function ($t) { return isset(Document_model::$types[$t]); }));
    }

    /**
     * For each required type: verified | pending | rejected | missing (best state wins).
     * Returns [type => state].
     */
    public function checklist($userId, $role)
    {
        $rank = ['verified' => 3, 'pending' => 2, 'rejected' => 1];
        $best = [];
        foreach ($this->db->select('doc_type, status')->where('user_id', (int) $userId)->get('user_documents')->result_array() as $d) {
            if (! isset($best[$d['doc_type']]) || $rank[$d['status']] > $rank[$best[$d['doc_type']]]) {
                $best[$d['doc_type']] = $d['status'];
            }
        }
        $out = [];
        foreach ($this->required_for($role) as $t) {
            $out[$t] = isset($best[$t]) ? $best[$t] : 'missing';
        }
        return $out;
    }

    /** Active students/lecturers who haven't uploaded (or only have rejected copies of) a required document. */
    public function missing($role = '')
    {
        $out = [];
        foreach ($role ? [$role] : ['student', 'lecturer'] as $r) {
            $required = $this->required_for($r);
            if (! $required) {
                continue;
            }
            $users = $this->db->select('id, name, id_number, phone, role, photo_path, photo_updated_at')->where('role', $r)->where('status', 'active')->order_by('name')->get('users')->result_array();
            foreach ($users as $u) {
                $lacking = array_keys(array_filter($this->checklist($u['id'], $r), function ($s) { return $s === 'missing' || $s === 'rejected'; }));
                if ($lacking) {
                    $u['lacking'] = $lacking;
                    $out[] = $u;
                }
            }
        }
        return $out;
    }
}
