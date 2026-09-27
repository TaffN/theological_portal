<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The calendar: events staff add (calendar_events) plus, read live from their
 * own tables, assignment due dates and exam windows. Everything comes back as
 * one list of "items", one per day it falls on.
 */
class Calendar_model extends CI_Model
{
    public static $types = [
        'class'    => 'Class',
        'event'    => 'Event',
        'holiday'  => 'Holiday / closed',
        'deadline' => 'Deadline',
        'other'    => 'Other',
    ];

    public function find($id)
    {
        return $this->db->where('id', (int) $id)->get('calendar_events')->row_array();
    }

    public function save(array $data, $id = null)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($id) {
            $this->db->where('id', (int) $id)->update('calendar_events', $data);
            return (int) $id;
        }
        $data['created_at'] = $data['updated_at'];
        $this->db->insert('calendar_events', $data);
        return $this->db->insert_id();
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id)->delete('calendar_events');
    }

    /**
     * Every item between two dates (inclusive, 'Y-m-d') for these courses,
     * sorted by day and time. $withCollege adds college-wide events.
     */
    public function items($from, $to, array $courseIds, $withCollege = true)
    {
        $items = [];
        $fromDt = $from . ' 00:00:00';
        $toDt   = $to . ' 23:59:59';

        // 1. Events staff added
        $this->db->select('e.*, c.name AS course_name')->from('calendar_events e')->join('courses c', 'c.id = e.course_id', 'left')
            ->where('e.starts_at <=', $toDt)->where('COALESCE(e.ends_at, e.starts_at) >=', $fromDt);
        $this->db->group_start();
        if ($withCollege) {
            $this->db->where('e.course_id IS NULL', null, false);
        } else {
            $this->db->where('1 = 0', null, false);
        }
        if ($courseIds) {
            $this->db->or_where_in('e.course_id', $courseIds);
        }
        $this->db->group_end();
        foreach ($this->db->get()->result_array() as $e) {
            $start = strtotime($e['starts_at']);
            $end   = $e['ends_at'] ? strtotime($e['ends_at']) : $start;
            // One item per day the event covers (a holiday week shows on every day).
            for ($d = strtotime(date('Y-m-d', $start)); $d <= $end; $d = strtotime('+1 day', $d)) {
                $day = date('Y-m-d', $d);
                if ($day < $from || $day > $to) {
                    continue;
                }
                $first = $day === date('Y-m-d', $start);
                $items[] = [
                    'date'   => $day,
                    'time'   => $e['all_day'] || ! $first ? null : date('H:i', $start),
                    'end'    => $e['ends_at'] && ! $e['all_day'] && date('Y-m-d', $end) === $day ? date('H:i', $end) : null,
                    'title'  => $e['title'],
                    'kind'   => $e['event_type'],
                    'course' => $e['course_name'],
                    'where'  => $e['location'],
                    'link'   => $e['meeting_link'],
                    'notes'  => $e['description'],
                    'id'     => (int) $e['id'],
                    'course_id' => $e['course_id'] ? (int) $e['course_id'] : null,
                    'url'    => null,
                ];
            }
        }

        if (! $courseIds) {
            return $this->sort($items);
        }

        // 2. Assignment due dates
        if ($this->db->table_exists('assignments')) {
            $rows = $this->db->select('a.id, a.title, a.due_at, c.name AS course_name')->from('assignments a')->join('courses c', 'c.id = a.course_id')
                ->where_in('a.course_id', $courseIds)->where('a.due_at >=', $fromDt)->where('a.due_at <=', $toDt)->get()->result_array();
            $role = $this->session->userdata('role');
            foreach ($rows as $a) {
                $items[] = [
                    'date' => date('Y-m-d', strtotime($a['due_at'])), 'time' => date('H:i', strtotime($a['due_at'])), 'end' => null,
                    'title' => 'Due: ' . $a['title'], 'kind' => 'assignment', 'course' => $a['course_name'], 'where' => null, 'link' => null, 'notes' => null,
                    'id' => null, 'course_id' => null,
                    'url' => $role === 'admin' ? null : base_url(($role === 'student' ? 'student_assignments/view/' : 'lecturer_assignments/view/') . $a['id']),
                ];
            }
        }

        // 3. Exam windows (published exams only)
        if ($this->db->table_exists('exams')) {
            $rows = $this->db->select('x.id, x.title, x.opens_at, x.closes_at, x.duration_minutes, c.name AS course_name')->from('exams x')->join('courses c', 'c.id = x.course_id')
                ->where_in('x.course_id', $courseIds)->where('x.status', 'published')
                ->where('x.opens_at <=', $toDt)->where('x.closes_at >=', $fromDt)->get()->result_array();
            $role = $this->session->userdata('role');
            foreach ($rows as $x) {
                $url = $role === 'admin' ? null : base_url(($role === 'student' ? 'student_exams/view/' : 'lecturer_exams/view/') . $x['id']);
                $openDay  = date('Y-m-d', strtotime($x['opens_at']));
                $closeDay = date('Y-m-d', strtotime($x['closes_at']));
                if ($openDay >= $from && $openDay <= $to) {
                    $items[] = [
                        'date' => $openDay, 'time' => date('H:i', strtotime($x['opens_at'])), 'end' => $openDay === $closeDay ? date('H:i', strtotime($x['closes_at'])) : null,
                        'title' => 'Exam: ' . $x['title'] . ' (' . (int) $x['duration_minutes'] . ' min)', 'kind' => 'exam', 'course' => $x['course_name'],
                        'where' => null, 'link' => null, 'notes' => null, 'id' => null, 'course_id' => null, 'url' => $url,
                    ];
                }
                if ($closeDay !== $openDay && $closeDay >= $from && $closeDay <= $to) {
                    $items[] = [
                        'date' => $closeDay, 'time' => date('H:i', strtotime($x['closes_at'])), 'end' => null,
                        'title' => 'Exam closes: ' . $x['title'], 'kind' => 'exam', 'course' => $x['course_name'],
                        'where' => null, 'link' => null, 'notes' => null, 'id' => null, 'course_id' => null, 'url' => $url,
                    ];
                }
            }
        }

        return $this->sort($items);
    }

    /** Upcoming items from today, e.g. for dashboards and Ezra. */
    public function upcoming(array $courseIds, $days = 14, $limit = 8)
    {
        return array_slice($this->items(date('Y-m-d'), date('Y-m-d', strtotime('+' . (int) $days . ' days')), $courseIds), 0, $limit);
    }

    private function sort(array $items)
    {
        usort($items, function ($a, $b) {
            $ka = $a['date'] . ($a['time'] === null ? '00:00' : $a['time']);
            $kb = $b['date'] . ($b['time'] === null ? '00:00' : $b['time']);
            return strcmp($ka, $kb);
        });
        return $items;
    }
}
