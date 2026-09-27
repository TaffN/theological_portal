<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The college library: books, articles, commentaries, sermons, theses,
 * audio and video that every paid-up student can use, whatever their course.
 * (Course materials stay with each course.)
 */
class Library_model extends CI_Model
{
    public static $categories = ['Books', 'Articles', 'Commentaries', 'Sermons', 'Theses', 'Audio', 'Video', 'Other'];

    public function search($q = '', $category = '', $courseId = null, $limit = 200)
    {
        $this->db->select('l.*, c.name AS course_name, u.name AS uploader_name')
            ->from('library_files l')->join('courses c', 'c.id = l.course_id', 'left')->join('users u', 'u.id = l.uploaded_by', 'left');
        if ($q !== '') {
            $this->db->group_start()->like('l.title', $q)->or_like('l.author', $q)->or_like('l.description', $q)->group_end();
        }
        if ($category !== '') {
            $this->db->where('l.category', $category);
        }
        if ($courseId) {
            $this->db->where('l.course_id', (int) $courseId);
        }
        return $this->db->order_by('l.created_at', 'DESC')->limit($limit)->get()->result_array();
    }

    public function counts()
    {
        $out = [];
        foreach ($this->db->select('category, COUNT(*) AS n', false)->group_by('category')->get('library_files')->result_array() as $r) {
            $out[$r['category']] = (int) $r['n'];
        }
        return $out;
    }

    public function find($id)
    {
        return $this->db->where('id', (int) $id)->get('library_files')->row_array();
    }

    public function create(array $data)
    {
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('library_files', $data);
        return $this->db->insert_id();
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id)->delete('library_files');
    }

    public function count_download($id)
    {
        $this->db->set('downloads', 'downloads + 1', false)->where('id', (int) $id)->update('library_files');
    }

    /** Short list for Ezra: what's in the library (newest first). */
    public function catalogue($limit = 60)
    {
        return $this->db->select('l.id, l.title, l.author, l.category, c.name AS course_name')->from('library_files l')
            ->join('courses c', 'c.id = l.course_id', 'left')->order_by('l.created_at', 'DESC')->limit($limit)->get()->result_array();
    }
}
