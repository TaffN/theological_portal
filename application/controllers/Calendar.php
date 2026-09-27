<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The calendar, for every role.
 *   calendar?m=YYYY-MM    - month grid + that month's list
 *   calendar/add          - form (staff)       calendar/edit/{id} - form
 *   calendar/save[/{id}]  - POST               calendar/delete/{id} - POST
 *
 * Lecturers add events to their own courses; administrators to any course or
 * the whole college. Assignment due dates and exam windows appear by themselves.
 */
class Calendar extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Calendar_model', 'Course_model']);
        $this->load->library('notifier');
        $this->load->helper('ui');
        if (! $this->db->table_exists('calendar_events')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'The calendar needs a database update first. An administrator should run the latest update (/migrate).');
            redirect('dashboard');
        }
    }

    public function index()
    {
        $m = (string) $this->input->get('m');
        $month = preg_match('/^\d{4}-\d{2}$/', $m) && strtotime($m . '-01') ? $m : date('Y-m');
        $first = $month . '-01';
        $last  = date('Y-m-t', strtotime($first));

        // The grid starts on the Monday on or before the 1st and ends on the Sunday on or after the last day.
        $gridStart = date('Y-m-d', strtotime($first . ' -' . (date('N', strtotime($first)) - 1) . ' days'));
        $gridEnd   = date('Y-m-d', strtotime($last . ' +' . (7 - date('N', strtotime($last))) . ' days'));

        $courseIds = $this->Course_model->ids_for_user($this->current_user_id, $this->current_role);
        $items = $this->Calendar_model->items($gridStart, $gridEnd, $courseIds);
        $byDay = [];
        foreach ($items as $it) {
            $byDay[$it['date']][] = $it;
        }

        $this->load->view('templates/header', ['title' => 'Calendar']);
        $this->load->view('calendar/index', [
            'month'     => $month,
            'first'     => $first,
            'last'      => $last,
            'gridStart' => $gridStart,
            'gridEnd'   => $gridEnd,
            'byDay'     => $byDay,
            'canAdd'    => $this->current_role !== 'student',
            'manageable' => $this->manageable_ids(),
        ]);
        $this->load->view('templates/footer');
    }

    public function add()
    {
        $this->staff_only();
        $date = (string) $this->input->get('date');
        $this->form(null, preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d'));
    }

    public function edit($id = null)
    {
        $event = $this->find_manageable($id);
        $this->form($event, date('Y-m-d', strtotime($event['starts_at'])));
    }

    public function save($id = null)
    {
        $this->staff_only();
        if ($this->input->method() !== 'post') {
            return redirect('calendar');
        }
        $existing = $id ? $this->find_manageable($id) : null;

        $courseRaw = (string) $this->input->post('course_id');
        $courseId  = $courseRaw === '' ? null : (int) $courseRaw;
        if ($courseId === null ? $this->current_role !== 'admin' : ! $this->Course_model->user_can_manage($courseId, $this->current_user_id, $this->current_role)) {
            $this->session->set_flashdata('error', 'You can only add events to courses you teach.');
            return redirect('calendar');
        }

        $title   = trim((string) $this->input->post('title'));
        $type    = (string) $this->input->post('event_type');
        $date    = (string) $this->input->post('date');
        $endDate = (string) $this->input->post('end_date');
        $allDay  = (bool) $this->input->post('all_day');
        $start   = (string) $this->input->post('start_time');
        $end     = (string) $this->input->post('end_time');
        $link    = trim((string) $this->input->post('meeting_link'));

        $problems = [];
        if ($title === '' || mb_strlen($title) > 200) { $problems[] = 'a title (up to 200 characters)'; }
        if (! isset(Calendar_model::$types[$type])) { $problems[] = 'the kind of event'; }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! strtotime($date)) { $problems[] = 'a date'; }
        if (! $allDay && ! preg_match('/^\d{2}:\d{2}$/', $start)) { $problems[] = 'a start time (or tick "All day")'; }
        if ($link !== '' && ! filter_var($link, FILTER_VALIDATE_URL)) { $problems[] = 'a meeting link starting with https://'; }
        if ($problems) {
            $this->session->set_flashdata('error', 'Please give ' . implode(', ', $problems) . '.');
            return redirect($existing ? 'calendar/edit/' . $existing['id'] : 'calendar/add?date=' . rawurlencode($date));
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate) || $endDate < $date) {
            $endDate = $date;
        }
        $startsAt = $date . ' ' . ($allDay ? '00:00' : $start) . ':00';
        $endsAt = null;
        if ($allDay) {
            $endsAt = $endDate . ' 23:59:00';
        } elseif (preg_match('/^\d{2}:\d{2}$/', $end) || $endDate !== $date) {
            $endsAt = $endDate . ' ' . (preg_match('/^\d{2}:\d{2}$/', $end) ? $end : $start) . ':00';
            if ($endsAt <= $startsAt) {
                $endsAt = null;
            }
        }

        $data = [
            'course_id'    => $courseId,
            'title'        => $title,
            'event_type'   => $type,
            'description'  => trim((string) $this->input->post('description')) ?: null,
            'location'     => mb_substr(trim((string) $this->input->post('location')), 0, 200) ?: null,
            'meeting_link' => $link ?: null,
            'starts_at'    => $startsAt,
            'ends_at'      => $endsAt,
            'all_day'      => $allDay ? 1 : 0,
        ];
        if (! $existing) {
            $data['created_by'] = $this->current_user_id;
        }
        $eventId = $this->Calendar_model->save($data, $existing ? $existing['id'] : null);

        $when = date('D j M', strtotime($startsAt)) . ($allDay ? '' : ' at ' . date('H:i', strtotime($startsAt)));
        $this->audit->log($existing ? 'calendar.updated' : 'calendar.created', 'calendar', $eventId,
            ($existing ? 'Changed' : 'Added') . ' the ' . ($courseId ? 'course' : 'college') . ' event "' . $title . '" (' . $when . ')');

        // Students hear about new course events, and about changed times.
        if ($courseId && (! $existing || $existing['starts_at'] !== $startsAt)) {
            $course = $this->Course_model->find($courseId);
            $this->notifier->notify_course($courseId, ($existing ? 'Moved: ' : 'New in the calendar: ') . $title . ' (' . $course['name'] . '), ' . $when,
                base_url('calendar?m=' . substr($date, 0, 7)));
        }

        $this->session->set_flashdata('success', $existing ? 'Event updated.' : 'Event added to the calendar.');
        redirect('calendar?m=' . substr($date, 0, 7));
    }

    public function delete($id = null)
    {
        $event = $this->find_manageable($id);
        if ($this->input->method() === 'post') {
            $this->Calendar_model->delete($event['id']);
            $this->audit->log('calendar.deleted', 'calendar', $event['id'], 'Removed the event "' . $event['title'] . '" (' . date('D j M Y', strtotime($event['starts_at'])) . ')');
            $this->session->set_flashdata('success', 'Event removed.');
        }
        redirect('calendar?m=' . date('Y-m', strtotime($event['starts_at'])));
    }

    /* ------------------------------------------------------------------ */

    private function form($event, $date)
    {
        $courses = $this->current_role === 'admin'
            ? $this->Course_model->for_user($this->current_user_id, 'admin')
            : $this->Course_model->for_user($this->current_user_id, 'lecturer');
        if (! $courses && $this->current_role !== 'admin') {
            $this->session->set_flashdata('error', 'You aren\'t assigned to any course yet, so there\'s nothing to add events to.');
            return redirect('calendar');
        }
        $this->load->view('templates/header', ['title' => $event ? 'Edit event' : 'Add an event']);
        $this->load->view('calendar/form', [
            'e'       => $event,
            'date'    => $date,
            'courses' => $courses,
            'college' => $this->current_role === 'admin',
            'types'   => Calendar_model::$types,
        ]);
        $this->load->view('templates/footer');
    }

    private function staff_only()
    {
        if ($this->current_role === 'student') {
            show_404();
        }
    }

    private function find_manageable($id)
    {
        $this->staff_only();
        $event = $this->Calendar_model->find($id);
        if (! $event) {
            show_404();
        }
        $ok = $event['course_id'] === null ? $this->current_role === 'admin'
            : $this->Course_model->user_can_manage($event['course_id'], $this->current_user_id, $this->current_role);
        if (! $ok) {
            show_404();
        }
        return $event;
    }

    /** Course ids whose events this person may edit (null = all, for admins). */
    private function manageable_ids()
    {
        if ($this->current_role === 'admin') {
            return null;
        }
        return $this->current_role === 'lecturer' ? $this->Course_model->ids_for_user($this->current_user_id, 'lecturer') : [];
    }
}
