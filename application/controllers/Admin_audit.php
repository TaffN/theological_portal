<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_audit extends Admin_Controller
{
    const PER_PAGE = 50;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('ui');
    }

    public function index()
    {
        $f     = $this->_filters();
        $page  = max(1, (int) $this->input->get('page'));
        $total = $this->_query($f)->count_all_results('audit_log');
        $rows  = $this->_query($f)
            ->order_by('id', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
            ->get('audit_log')->result_array();

        $groups = $this->db->select("DISTINCT SUBSTRING_INDEX(action, '.', 1) AS g", false)
            ->order_by('g')->get('audit_log')->result_array();

        $this->load->view('templates/header', ['title' => 'Audit trail']);
        $this->load->view('admin/audit', [
            'rows'   => $rows,
            'total'  => $total,
            'page'   => $page,
            'pages'  => max(1, (int) ceil($total / self::PER_PAGE)),
            'f'      => $f,
            'groups' => array_column($groups, 'g'),
        ]);
        $this->load->view('templates/footer');
    }

    /** Download the current filtered view as a spreadsheet-friendly CSV. */
    public function export()
    {
        $rows = $this->_query($this->_filters())->order_by('id', 'DESC')->limit(20000)->get('audit_log')->result_array();

        $this->audit->log('audit.exported', null, null, 'Exported ' . count($rows) . ' audit entries to CSV');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit-trail-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // so Excel opens UTF-8 correctly
        fputcsv($out, ['When', 'User', 'Role', 'Action', 'Description', 'Record', 'IP address']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['created_at'], $r['user_name'], $r['role'], $r['action'], $r['description'],
                $r['entity_type'] && strpos($r['entity_type'], 'email:') !== 0 ? $r['entity_type'] . ' #' . $r['entity_id'] : '',
                $r['ip_address'],
            ]);
        }
        fclose($out);
        exit;
    }

    private function _filters()
    {
        return [
            'q'     => trim((string) $this->input->get('q')),
            'group' => preg_replace('/[^a-z_]/', '', (string) $this->input->get('group')),
            'from'  => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $this->input->get('from')) ? $this->input->get('from') : '',
            'to'    => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $this->input->get('to')) ? $this->input->get('to') : '',
        ];
    }

    private function _query(array $f)
    {
        if ($f['q'] !== '') {
            $this->db->group_start()
                ->like('description', $f['q'])
                ->or_like('user_name', $f['q'])
                ->or_like('ip_address', $f['q'])
                ->group_end();
        }
        if ($f['group'] !== '') {
            $this->db->like('action', $f['group'] . '.', 'after');
        }
        if ($f['from'] !== '') {
            $this->db->where('created_at >=', $f['from'] . ' 00:00:00');
        }
        if ($f['to'] !== '') {
            $this->db->where('created_at <=', $f['to'] . ' 23:59:59');
        }
        return $this->db;
    }
}
