<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Programs: a qualification such as "Diploma in Theology". A program holds modules
 * (the units students enrol in, pay for and are taught and marked in).
 */
class Program_model extends CI_Model
{
    protected $table = 'programs';

    public function find($id)
    {
        return $this->db->where('id', (int) $id)->get($this->table)->row_array();
    }

    public function find_by_slug($slug)
    {
        return $this->db->where('slug', (string) $slug)->get($this->table)->row_array();
    }

    public function all()
    {
        return $this->db->order_by('name')->get($this->table)->result_array();
    }

    public function active()
    {
        return $this->db->where('status', 'active')->order_by('name')->get($this->table)->result_array();
    }

    /**
     * Programs with their numbers: modules (and how many are open), students with access,
     * (the program's own fee is in p.fee_amount). $onlyActive hides closed programs (student view).
     */
    public function with_counts($onlyActive = false)
    {
        $this->db->select("p.*,
                COUNT(DISTINCT m.id) AS module_count,
                COUNT(DISTINCT CASE WHEN m.status = 'active' THEN m.id END) AS open_modules,
                COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.user_id END) AS students", false)
            ->from('programs p')
            ->join('modules m', 'm.program_id = p.id', 'left')
            ->join('enrollments e', 'e.module_id = m.id', 'left')
            ->group_by('p.id')->order_by('p.name');
        if ($onlyActive) {
            $this->db->where('p.status', 'active');
        }
        return $this->db->get()->result_array();
    }

    public function create(array $data)
    {
        $data['slug'] = $this->unique_slug(isset($data['slug']) && $data['slug'] !== '' ? $data['slug'] : $data['name']);
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /** The slug changes only when the name does (so shared links keep working otherwise). */
    public function update($id, array $data)
    {
        $current = $this->find($id);
        if ($current && isset($data['name']) && $data['name'] !== $current['name']) {
            $data['slug'] = $this->unique_slug($data['name'], $id);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', (int) $id)->update($this->table, $data);
    }

    /** A program can only be deleted while it has no modules (modules hold students' work). */
    public function can_delete($id)
    {
        return $this->db->where('program_id', (int) $id)->count_all_results('modules') === 0;
    }

    public function delete($id)
    {
        return $this->can_delete($id) && $this->db->where('id', (int) $id)->delete($this->table);
    }

    /** "Diploma in Theology" -> diploma-in-theology (-2, -3... if taken). */
    public function unique_slug($text, $ignoreId = null)
    {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', (string) $text), '-'));
        $base = $base !== '' ? substr($base, 0, 150) : 'program';
        $slug = $base;
        for ($n = 2; $this->slug_taken($slug, $ignoreId); $n++) {
            $slug = $base . '-' . $n;
        }
        return $slug;
    }

    private function slug_taken($slug, $ignoreId)
    {
        $this->db->where('slug', $slug);
        if ($ignoreId) {
            $this->db->where('id !=', (int) $ignoreId);
        }
        return $this->db->count_all_results($this->table) > 0;
    }
}
