<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material_model extends CI_Model
{
    protected $table = 'materials';

    public function __construct()
    {
        parent::__construct();
    }

    public function find($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function for_module($moduleId)
    {
        return $this->db
            ->where('module_id', $moduleId)
            ->order_by('created_at', 'DESC')
            ->get($this->table)
            ->result_array();
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->delete($this->table);
    }
}
