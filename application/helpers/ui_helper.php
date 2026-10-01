<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Small inline SVG icons (Feather-style, MIT). Inline so they work offline
 * with no icon font to download.
 */
if (! function_exists('icon')) {
    function icon($name, $size = 20)
    {
        $paths = [
            'grid'    => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
            'book'    => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
            'folder'  => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
            'bell'    => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
            'card'    => '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
            'users'   => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'layers'  => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
            'logout'  => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
            'file'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
            'dollar'  => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
            'clock'   => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            'check'   => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'edit'    => '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>',
            'award'   => '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
            'plus'    => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
            'arrow'   => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
            'user'    => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'eye'     => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off' => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>',
            'upload'  => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
            'x'       => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
            'alert'   => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
            'lock'    => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            'sun'     => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
            'moon'    => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
            'monitor' => '<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
            'search'  => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
            'sidebar' => '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/>',
            'phone'   => '<rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
            'camera'  => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
            'id-card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="12" r="2.5"/><line x1="13" y1="10" x2="19" y2="10"/><line x1="13" y1="14" x2="17" y2="14"/>',
            'shield'  => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'image'   => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
            'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
            'send'    => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
            'bulb'    => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.2 1 2V17h6v-.3c0-.8.4-1.5 1-2A7 7 0 0 0 12 2z"/>',
            'arrow-up' => '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>',
            'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
            'library' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
            'chat'    => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
            'check-square' => '<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
            'pin'     => '<line x1="12" y1="17" x2="12" y2="22"/><path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24z"/>',
            'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
            'link'    => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
            'map-pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
            'headphones' => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>',
            'chart'   => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            'video'   => '<polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/>',
        ];

        $inner = isset($paths[$name]) ? $paths[$name] : $paths['grid'];

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . (int) $size . '" height="' . (int) $size
            . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
            . ' stroke-linejoin="round" class="icon" aria-hidden="true">' . $inner . '</svg>';
    }
}

/**
 * Menu items per role. key = first URL segment, used to highlight the
 * active item. 'soon' items show greyed out in the sidebar only, as a
 * preview of Stages 4-6.
 */
if (! function_exists('nav_items')) {
    function nav_items($role)
    {
        // section: sidebar group heading. mobile: shown in the phone tab bar.
        // exact: only highlight on this exact URL (for pages sharing a controller).
        $dashboard = ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'grid', 'section' => 'Menu', 'mobile' => true];
        $help      = ['key' => 'support', 'label' => 'Help & contact', 'url' => 'support/help', 'icon' => 'phone', 'section' => 'Support', 'exact' => true];
        $alerts    = ['key' => 'notifications', 'label' => 'Alerts', 'url' => 'notifications', 'icon' => 'bell', 'badge' => 'notifications', 'section' => 'Menu', 'mobile' => true];

        $items = _nav_items_for($role, $dashboard, $help, $alerts);
        $ezra  = ezra_offered($role);
        return array_values(array_filter($items, function ($i) use ($ezra) { return empty($i['ezra']) || $ezra; }));
    }
}

if (! function_exists('_nav_items_for')) {
    function _nav_items_for($role, $dashboard, $help, $alerts)
    {
        // Shared by all three roles (v10).
        $calendar    = ['key' => 'calendar', 'label' => 'Calendar', 'url' => 'calendar', 'icon' => 'calendar', 'section' => 'Campus'];
        $discussions = ['key' => 'discussions', 'label' => 'Discussions', 'url' => 'discussions', 'icon' => 'chat', 'section' => 'Campus'];
        $library     = ['key' => 'library', 'label' => 'Library', 'url' => 'library', 'icon' => 'library', 'section' => 'Campus'];

        switch ($role) {
            case 'admin':
                return [
                    $dashboard,
                    ['key' => 'admin_payments', 'label' => 'Payments', 'url' => 'admin_payments', 'icon' => 'card', 'badge' => 'payments', 'section' => 'Menu', 'mobile' => true],
                    ['key' => 'admin_users', 'label' => 'Students', 'url' => 'admin_users/students', 'icon' => 'users', 'badge' => 'resets', 'section' => 'Menu', 'mobile' => true, 'exact' => true],
                    ['key' => 'admin_programs', 'label' => 'Programs', 'url' => 'admin_programs', 'icon' => 'layers', 'section' => 'Menu', 'also' => ['admin_modules']],
                    ['key' => 'admin_users', 'label' => 'Lecturers', 'url' => 'admin_users/lecturers', 'icon' => 'user', 'section' => 'Menu', 'exact' => true],
                    ['key' => 'admin_results', 'label' => 'Results', 'url' => 'admin_results', 'icon' => 'award', 'section' => 'Menu'],
                    ['key' => 'admin_attendance', 'label' => 'Attendance', 'url' => 'admin_attendance', 'icon' => 'check-square', 'section' => 'Menu'],
                    ['key' => 'admin_documents', 'label' => 'Documents', 'url' => 'admin_documents', 'icon' => 'file', 'badge' => 'documents', 'section' => 'Menu'],
                    ['key' => 'admin_reports', 'label' => 'Reports', 'url' => 'admin_reports', 'icon' => 'chart', 'section' => 'Menu'],
                    ['key' => 'admin_announcements', 'label' => 'Announcements', 'url' => 'admin_announcements', 'icon' => 'bell', 'section' => 'Menu'],
                    $alerts,
                    $calendar, $discussions, $library,
                    ['key' => 'admin_errors', 'label' => 'Error reports', 'url' => 'admin_errors', 'icon' => 'alert', 'badge' => 'errors', 'section' => 'System'],
                    ['key' => 'admin_audit', 'label' => 'Audit trail', 'url' => 'admin_audit', 'icon' => 'shield', 'section' => 'System'],
                    ['key' => 'admin_settings', 'label' => 'Settings', 'url' => 'admin_settings', 'icon' => 'layers', 'section' => 'System'],
                    ['key' => 'admin_users', 'label' => 'Administrators', 'url' => 'admin_users/admins', 'icon' => 'lock', 'section' => 'System', 'exact' => true],
                    $help,
                ];

            case 'lecturer':
                return [
                    $dashboard,
                    ['key' => 'lecturer_materials', 'label' => 'My Modules', 'url' => 'lecturer_materials', 'icon' => 'book', 'section' => 'Menu', 'mobile' => true],
                    ['key' => 'lecturer_assignments', 'label' => 'Assignments', 'url' => 'lecturer_assignments', 'icon' => 'edit', 'badge' => 'marking', 'section' => 'Menu', 'mobile' => true],
                    ['key' => 'lecturer_exams', 'label' => 'Exams', 'url' => 'lecturer_exams', 'icon' => 'clock', 'badge' => 'exam_marking', 'section' => 'Menu', 'mobile' => true],
                    ['key' => 'lecturer_attendance', 'label' => 'Attendance', 'url' => 'lecturer_attendance', 'icon' => 'check-square', 'section' => 'Menu'],
                    ['key' => 'lecturer_results', 'label' => 'Results', 'url' => 'lecturer_results', 'icon' => 'award', 'section' => 'Menu'],
                    ['key' => 'documents', 'label' => 'My documents', 'url' => 'documents', 'icon' => 'file', 'badge' => 'docs_todo', 'section' => 'Menu'],
                    $alerts,
                    $calendar, $discussions, $library,
                    $help,
                ];

            default: // student
                return [
                    $dashboard,
                    ['key' => 'programs', 'label' => 'Programs', 'url' => 'programs', 'icon' => 'book', 'section' => 'Menu', 'also' => ['modules']],
                    ['key' => 'student_assignments', 'label' => 'Assignments', 'url' => 'student_assignments', 'icon' => 'edit', 'badge' => 'assignments', 'section' => 'Menu', 'mobile' => true],
                    ['key' => 'student_exams', 'label' => 'Exams', 'url' => 'student_exams', 'icon' => 'clock', 'badge' => 'exams', 'section' => 'Menu', 'mobile' => true],
                    ['key' => 'student_materials', 'label' => 'Materials', 'url' => 'student_materials', 'icon' => 'folder', 'section' => 'Menu', 'mobile' => true],
                    ['key' => 'student_results', 'label' => 'Results', 'url' => 'student_results', 'icon' => 'award', 'section' => 'Menu'],
                    ['key' => 'student_attendance', 'label' => 'Attendance', 'url' => 'student_attendance', 'icon' => 'check-square', 'section' => 'Menu'],
                    ['key' => 'payments', 'label' => 'Payments', 'url' => 'payments', 'icon' => 'card', 'section' => 'Menu'],
                    ['key' => 'documents', 'label' => 'My documents', 'url' => 'documents', 'icon' => 'file', 'badge' => 'docs_todo', 'section' => 'Menu'],
                    $alerts,
                    $calendar, $discussions, $library,
                    $help,
                ];
        }
    }
}

if (! function_exists('nav_is_active')) {
    function nav_is_active(array $item, $uri, $segment)
    {
        if (! empty($item['exact'])) {
            return $uri === $item['url'] || strpos($uri, $item['url'] . '/') === 0;
        }
        return $segment === $item['key'] || (! empty($item['also']) && in_array($segment, $item['also'], true));
    }
}

/**
 * Breadcrumb trail: crumbs([['Programs', 'programs'], ['Diploma in Theology', 'programs/diploma'], ['Church History']]).
 * An item with no address is the current page.
 */
if (! function_exists('crumbs')) {
    function crumbs(array $items)
    {
        $parts = [];
        foreach ($items as $i => $it) {
            $label = html_escape($it[0]);
            $parts[] = isset($it[1]) && $it[1] !== null
                ? '<a href="' . base_url($it[1]) . '">' . $label . '</a>'
                : '<span aria-current="page">' . $label . '</span>';
        }
        return '<nav class="crumbs" aria-label="Breadcrumb">' . implode('<span class="crumb-sep" aria-hidden="true">&rsaquo;</span>', $parts) . '</nav>';
    }
}

/**
 * Program > Module trail for a module's own pages, linked to what this role can open:
 * students to the program page, administrators to the program's module list, lecturers see plain text.
 * $leaf = an optional last item (the current sub-page, e.g. "Materials").
 */
if (! function_exists('module_crumbs')) {
    function module_crumbs(array $module, $leaf = null)
    {
        if (empty($module['program_name'])) {
            return '';
        }
        $CI =& get_instance();
        $role = $CI->session->userdata('role');
        $items = [];
        if ($role === 'student') {
            $items[] = ['Programs', 'programs'];
            $items[] = [$module['program_name'], 'programs/' . $module['program_slug']];
        } elseif ($role === 'admin') {
            $items[] = ['Programs', 'admin_programs'];
            $items[] = [$module['program_name'], 'admin_programs/' . (int) $module['program_id'] . '/modules'];
        } else {
            $items[] = [$module['program_name']];
        }
        $items[] = $leaf === null ? [$module['name']] : [$module['name'], null];
        if ($leaf !== null) {
            $items[] = [$leaf];
        }
        return crumbs($items);
    }
}

/** "Module name" with its program, for pickers: "Church History (General Program)". */
if (! function_exists('module_label')) {
    function module_label(array $m)
    {
        return $m['name'] . (! empty($m['program_name']) ? ' (' . $m['program_name'] . ')' : '');
    }
}

/**
 * Consistent coloured pill for any enrollment/payment status.
 */
if (! function_exists('status_badge')) {
    function status_badge($status)
    {
        $map = [
            'pending_payment' => ['Awaiting payment', 'pill-warning'],
            'active'          => ['Active', 'pill-success'],
            'completed'       => ['Completed', 'pill-muted'],
            'suspended'       => ['Suspended', 'pill-danger'],
            'pending'         => ['Pending', 'pill-warning'],
            'approved'        => ['Approved', 'pill-success'],
            'rejected'        => ['Rejected', 'pill-danger'],
            'verified'        => ['Verified', 'pill-success'],
            'missing'         => ['Missing', 'pill-danger'],
            // assignments (Assignment_model::student_state)
            'todo'            => ['To do', 'pill-warning'],
            'overdue'         => ['Overdue', 'pill-danger'],
            'missed'          => ['Missed', 'pill-danger'],
            'submitted'       => ['Handed in', 'pill-muted'],
            'graded'          => ['Marked', 'pill-success'],
            'late'            => ['Late', 'pill-warning'],
            'to_mark'         => ['To mark', 'pill-warning'],
            // exams (Exam_model::phase / student_state, invigilation)
            'exam_draft'       => ['Draft', 'pill-muted'],
            'exam_scheduled'   => ['Scheduled', 'pill-warning'],
            'exam_open'        => ['Open now', 'pill-success'],
            'exam_closed'      => ['Closed', 'pill-muted'],
            'exam_not_started' => ['Not started', 'pill-muted'],
            'exam_absent'      => ['Didn\'t sit', 'pill-danger'],
            'exam_writing'     => ['Writing', 'pill-warning'],
            'exam_handed_in'   => ['Handed in', 'pill-success'],
            'exam_waiting'     => ['Awaiting result', 'pill-muted'],
            'exam_missed'      => ['Missed', 'pill-danger'],
            'exam_result'      => ['Result out', 'pill-success'],
        ];

        list($label, $class) = isset($map[$status]) ? $map[$status] : [ucfirst((string) $status), 'pill-muted'];

        return '<span class="pill ' . $class . '">' . html_escape($label) . '</span>';
    }
}

if (! function_exists('initials')) {
    function initials($name)
    {
        $parts = preg_split('/\s+/', trim((string) $name));
        $out   = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $out .= strtoupper(substr($p, 0, 1));
        }
        return $out !== '' ? $out : '?';
    }
}

if (! function_exists('money')) {
    function money($amount)
    {
        return '$' . number_format((float) $amount, 2);
    }
}

/**
 * Everything the layout (header + footer) needs: current user, their menu,
 * which item is active, and badge counts. Cached so the queries run once
 * per page even though both header and footer call it.
 */
if (! function_exists('layout_context')) {
    function layout_context()
    {
        static $ctx = null;
        if ($ctx !== null) {
            return $ctx;
        }

        $CI =& get_instance();

        $userId = $CI->session->userdata('user_id');
        $role   = $CI->session->userdata('role');

        $badges = ['notifications' => 0, 'payments' => 0, 'errors' => 0, 'resets' => 0, 'marking' => 0, 'assignments' => 0, 'exam_marking' => 0, 'exams' => 0, 'documents' => 0, 'docs_todo' => 0];
        if ($userId) {
            if ($CI->db->table_exists('notifications')) {
                $badges['notifications'] = $CI->db->where('user_id', $userId)->where('is_read', 0)->count_all_results('notifications');
            }
            if ($role !== 'admin' && $CI->db->table_exists('assignments')) {
                $CI->load->model('Assignment_model');
                if ($role === 'lecturer') {
                    $badges['marking'] = $CI->Assignment_model->to_mark_count($userId);
                } else {
                    $badges['assignments'] = count($CI->Assignment_model->student_outstanding($userId));
                }
            }
            if ($role !== 'admin' && $CI->db->table_exists('exams')) {
                $CI->load->model('Exam_model');
                if ($role === 'lecturer') {
                    $badges['exam_marking'] = $CI->Exam_model->to_mark_count($userId);
                } else {
                    foreach ($CI->Exam_model->for_student($userId) as $e) {   // open to start, or started and not handed in
                        if (in_array(Exam_model::student_state($e), ['open', 'writing'], true)) {
                            $badges['exams']++;
                        }
                    }
                }
            }
            if ($CI->db->table_exists('user_documents')) {
                if ($role === 'admin') {
                    $badges['documents'] = $CI->db->where('status', 'pending')->count_all_results('user_documents');
                } else {
                    $CI->load->model('Document_model');
                    $badges['docs_todo'] = count(array_filter($CI->Document_model->checklist($userId, $role), function ($s) { return $s === 'missing' || $s === 'rejected'; }));
                }
            }
            if ($role === 'admin') {
                $badges['payments'] = $CI->db->where('status', 'pending')->count_all_results('payments');
                if ($CI->db->table_exists('error_reports')) {
                    $badges['errors'] = $CI->db->where('status', 'open')->where_in('severity', ['high', 'critical'])->count_all_results('error_reports');
                }
                if ($CI->db->field_exists('reset_requested_at', 'users')) {
                    $badges['resets'] = $CI->db->where('role !=', 'admin')->where('reset_requested_at IS NOT NULL', null, false)->count_all_results('users');
                }
            }
        }

        $ctx = [
            'userId'   => $userId,
            'role'     => $role,
            'userName' => (string) $CI->session->userdata('name'),
            'photoVer' => $CI->session->userdata('photo'),
            'idNumber' => $CI->session->userdata('id_number'),
            'segment'  => $CI->uri->segment(1) ?: 'dashboard',
            'uri'      => trim($CI->uri->uri_string(), '/'),
            'badges'   => $badges,
            'navItems' => $userId ? nav_items($role) : [],
        ];

        return $ctx;
    }
}


/**
 * "Good morning" / "Good afternoon" / "Good evening" by server time.
 */
if (! function_exists('greeting')) {
    function greeting()
    {
        $h = (int) date('G');
        if ($h < 12) {
            return 'Good morning';
        }
        return $h < 17 ? 'Good afternoon' : 'Good evening';
    }
}

/**
 * First name, skipping titles like "Site" in "Site Administrator" isn't
 * possible to guess, so admins without a real name get "Administrator".
 */
if (! function_exists('display_first_name')) {
    function display_first_name($name, $role = null)
    {
        $name = trim((string) $name);
        if ($role === 'admin' && stripos($name, 'administrator') !== false) {
            return 'Administrator';
        }
        $first = strtok($name, ' ');
        return $first !== false ? $first : $name;
    }
}

/**
 * "just now", "5 min ago", "3 hrs ago", "yesterday", "4 days ago", "12 Sep".
 */
if (! function_exists('time_ago')) {
    function time_ago($datetime)
    {
        $ts = is_numeric($datetime) ? (int) $datetime : strtotime((string) $datetime);
        if (! $ts) {
            return '';
        }
        $diff = time() - $ts;

        if ($diff < 60)     return 'just now';
        if ($diff < 3600)   return floor($diff / 60) . ' min ago';
        if ($diff < 86400)  { $h = floor($diff / 3600); return $h . ' hr' . ($h == 1 ? '' : 's') . ' ago'; }
        if ($diff < 172800) return 'yesterday';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';

        return date('j M', $ts) . (date('Y', $ts) !== date('Y') ? ' ' . date('Y', $ts) : '');
    }
}


/**
 * Entries for the Ctrl+K quick-search palette: every page in the user's
 * menu plus a few handy shortcuts. Returned as [group, label, url, icon, hint].
 */
if (! function_exists('palette_items')) {
    function palette_items($role)
    {
        $items = [];
        foreach (nav_items($role) as $n) {
            if (empty($n['soon'])) {
                $items[] = ['Go to', $n['label'], base_url($n['url']), $n['icon'], ''];
            }
        }

        switch ($role) {
            case 'admin':
                $items[] = ['Actions', 'Review pending payments', base_url('admin_payments'), 'card', 'Payments'];
                $items[] = ['Actions', 'Payment history', base_url('admin_payments/history'), 'clock', 'Payments'];
                $items[] = ['Actions', 'Add a program', base_url('admin_programs'), 'plus', 'Programs'];
                $items[] = ['Actions', 'Add a module to a program', base_url('admin_programs'), 'plus', 'Programs'];
                $items[] = ['Actions', 'Assign a lecturer to a module', base_url('admin_programs'), 'users', 'Programs'];
                $items[] = ['Actions', 'Create a lecturer account', base_url('admin_users/lecturers'), 'users', 'Lecturers'];
                $items[] = ['Actions', 'Reset a student\'s password', base_url('admin_users/students'), 'lock', 'Students'];
                $items[] = ['Actions', 'Post an announcement', base_url('admin_announcements'), 'bell', 'Announcements'];
                $items[] = ['Actions', 'Export audit trail (CSV)', base_url('admin_audit/export'), 'upload', 'Audit trail'];
                $items[] = ['Actions', 'Organisation settings', base_url('admin_settings'), 'layers', 'Settings'];
                $items[] = ['Actions', 'Payment details shown to students', base_url('admin_settings') . '#set-payments', 'card', 'Settings'];
                $items[] = ['Actions', 'Add another administrator', base_url('admin_users/admins'), 'lock', 'Administrators'];
                $items[] = ['Actions', 'Change my login email', base_url('admin_users/admins'), 'edit', 'Administrators'];
                $items[] = ['Actions', 'Export all results (CSV)', base_url('admin_results/export'), 'upload', 'Results'];
                $items[] = ['Actions', 'Grade boundaries', base_url('admin_settings') . '#set-results', 'award', 'Settings'];
                $items[] = ['Actions', 'Export all student details (CSV)', base_url('admin_users/export_students'), 'upload', 'Students'];
                $items[] = ['Actions', 'Add a college event or holiday', base_url('calendar/add'), 'calendar', 'Calendar'];
                $items[] = ['Actions', 'Add a book to the library', base_url('library'), 'library', 'Library'];
                $items[] = ['Actions', 'Attendance per module (CSV)', base_url('admin_attendance'), 'check-square', 'Attendance'];
                $items[] = ['Actions', 'Verify uploaded documents', base_url('admin_documents'), 'file', 'Documents'];
                $items[] = ['Actions', 'Who is missing a document', base_url('admin_documents?tab=missing'), 'file', 'Documents'];
                $items[] = ['Actions', 'Pass rates by province (pie chart)', base_url('admin_reports/pass_rates'), 'chart', 'Reports'];
                $items[] = ['Actions', 'Students by province / gender', base_url('admin_reports/students'), 'chart', 'Reports'];
                $items[] = ['Actions', 'Grades report', base_url('admin_reports/grades'), 'chart', 'Reports'];
                $items[] = ['Actions', 'Fees report', base_url('admin_reports/fees'), 'chart', 'Reports'];
                break;
            case 'lecturer':
                $items[] = ['Actions', 'Post a new material', base_url('lecturer_materials'), 'plus', 'My Modules'];
                $items[] = ['Actions', 'Set a new assignment', base_url('lecturer_assignments'), 'plus', 'Assignments'];
                $items[] = ['Actions', 'Mark handed-in work', base_url('lecturer_assignments'), 'check', 'Assignments'];
                $items[] = ['Actions', 'Create an exam', base_url('lecturer_exams'), 'plus', 'Exams'];
                $items[] = ['Actions', 'Invigilate a running exam', base_url('lecturer_exams'), 'eye', 'Exams'];
                $items[] = ['Actions', 'Mark exam scripts / release results', base_url('lecturer_exams'), 'award', 'Exams'];
                $items[] = ['Actions', 'Publish module results', base_url('lecturer_results'), 'award', 'Results'];
                $items[] = ['Actions', 'Change assignment / exam weighting', base_url('lecturer_results'), 'layers', 'Results'];
                $items[] = ['Actions', 'Take the register', base_url('lecturer_attendance'), 'check-square', 'Attendance'];
                $items[] = ['Actions', 'Upload my ID or qualifications', base_url('documents'), 'file', 'My documents'];
                $items[] = ['Actions', 'Add a class or event to the calendar', base_url('calendar/add'), 'calendar', 'Calendar'];
                $items[] = ['Actions', 'Start a discussion', base_url('discussions'), 'chat', 'Discussions'];
                $items[] = ['Actions', 'Add a book to the library', base_url('library'), 'library', 'Library'];
                break;
            default:
                $items[] = ['Actions', 'Apply for a module', base_url('programs'), 'book', 'Programs'];
                $items[] = ['Actions', 'Upload proof of payment', base_url('programs'), 'upload', 'Programs'];
                $items[] = ['Actions', 'Open my materials', base_url('student_materials'), 'folder', 'Materials'];
                $items[] = ['Actions', 'Hand in an assignment', base_url('student_assignments'), 'upload', 'Assignments'];
                $items[] = ['Actions', 'See my marks and feedback', base_url('student_assignments'), 'award', 'Assignments'];
                $items[] = ['Actions', 'Start or continue an exam', base_url('student_exams'), 'clock', 'Exams'];
                $items[] = ['Actions', 'See my exam results', base_url('student_exams'), 'award', 'Exams'];
                $items[] = ['Actions', 'My module results', base_url('student_results'), 'award', 'Results'];
                $items[] = ['Actions', 'Print my statement of results', base_url('student_results/statement'), 'file', 'Results'];
                $items[] = ['Actions', 'Download a payment receipt', base_url('payments'), 'file', 'Payments'];
                $items[] = ['Actions', 'See my attendance', base_url('student_attendance'), 'check-square', 'Attendance'];
                $items[] = ['Actions', 'Upload my National ID', base_url('documents'), 'file', 'My documents'];
                $items[] = ['Actions', 'What\'s on this month', base_url('calendar'), 'calendar', 'Calendar'];
                $items[] = ['Actions', 'Ask a question in Discussions', base_url('discussions'), 'chat', 'Discussions'];
                $items[] = ['Actions', 'Find a book in the library', base_url('library'), 'library', 'Library'];
        }

        $items[] = ['Account', 'My profile', base_url('profile'), 'user', ''];
        $items[] = ['Account', 'My ID card', base_url('profile') . '#id-card', 'id-card', ''];
        $items[] = ['Account', 'Change my photo', base_url('profile') . '#photo', 'camera', ''];
        $items[] = ['Account', 'Change password', base_url('profile') . '#password', 'lock', ''];
        $items[] = ['Account', 'Toggle dark mode', '#theme', 'moon', 'Appearance'];
        $items[] = ['Account', 'Help & contact the office', base_url('support/help'), 'phone', 'Help'];
        $items[] = ['Account', 'Report a problem', base_url('support/report'), 'alert', 'Help'];
        $items[] = ['Account', 'Log out', base_url('auth/logout'), 'logout', ''];

        return $items;
    }
}

/** Coloured pill for an overall grade (Stage 6). */
if (! function_exists('grade_badge')) {
    function grade_badge($grade)
    {
        $class = ['Distinction' => 'pill-success', 'Merit' => 'pill-success', 'Pass' => 'pill-warning', 'Fail' => 'pill-danger'];
        return '<span class="pill ' . (isset($class[$grade]) ? $class[$grade] : 'pill-muted') . '">' . html_escape($grade) . '</span>';
    }
}

/** 67.5 -> "67.5%", null -> "—". */
if (! function_exists('pct')) {
    function pct($value)
    {
        return $value === null || $value === '' ? '—' : score_fmt(round((float) $value, 1)) . '%';
    }
}

/** A mark without pointless decimals: 15, 15.5, 15.25. */
if (! function_exists('score_fmt')) {
    function score_fmt($score)
    {
        return rtrim(rtrim(number_format((float) $score, 2, '.', ''), '0'), '.');
    }
}

/**
 * Due date in words, from the reader's point of view:
 * "in 3 days", "in 5 hrs", "today 17:00", "2 days ago".
 */
if (! function_exists('due_in')) {
    function due_in($datetime)
    {
        $ts = strtotime((string) $datetime);
        if (! $ts) {
            return '';
        }
        $diff = $ts - time();
        if ($diff < 0) {
            return time_ago($ts);
        }
        if ($diff < 3600)  return 'in ' . max(1, floor($diff / 60)) . ' min';
        if (date('Y-m-d', $ts) === date('Y-m-d')) return 'today ' . date('H:i', $ts);
        if ($diff < 86400) return 'in ' . floor($diff / 3600) . ' hrs';
        if (date('Y-m-d', $ts) === date('Y-m-d', strtotime('+1 day'))) return 'tomorrow ' . date('H:i', $ts);
        $days = (int) ceil($diff / 86400);
        return 'in ' . $days . ' day' . ($days == 1 ? '' : 's');
    }
}

if (! function_exists('receipt_no')) {
    function receipt_no($paymentId)
    {
        return 'TC-' . str_pad((int) $paymentId, 6, '0', STR_PAD_LEFT);
    }
}

/**
 * A person's avatar: their photo if they have one, otherwise initials.
 *   $photoVersion - photo_updated_at (string or timestamp), null for none
 *   $class        - extra classes, e.g. 'avatar-sm', 'avatar-xl'
 */
if (! function_exists('avatar_html')) {
    function avatar_html($name, $userId, $photoVersion = null, $class = '')
    {
        if ($photoVersion) {
            $v = is_numeric($photoVersion) ? (int) $photoVersion : strtotime((string) $photoVersion);
            return '<img class="avatar avatar-img ' . $class . '" src="' . base_url('photo/view/' . (int) $userId) . '?v=' . $v
                 . '" alt="' . html_escape($name) . '" loading="lazy">';
        }
        return '<span class="avatar ' . $class . '">' . html_escape(initials($name)) . '</span>';
    }
}

if (! function_exists('setting')) {
    function setting($key, $default = '')
    {
        $CI =& get_instance();
        if (! isset($CI->settings)) {
            $CI->load->library('settings');
        }
        return $CI->settings->get($key, $default);
    }
}

/** Digits-only WhatsApp link from a local or international number. */
if (! function_exists('wa_link')) {
    function wa_link($phone, $text = '')
    {
        $d = preg_replace('/\D+/', '', (string) $phone);
        if ($d === '') {
            return '';
        }
        if (strpos($d, '0') === 0) {
            $d = '263' . substr($d, 1);
        }
        return 'https://wa.me/' . $d . ($text !== '' ? '?text=' . rawurlencode($text) : '');
    }
}

if (! function_exists('tel_link')) {
    function tel_link($phone)
    {
        return 'tel:' . preg_replace('/[^\d+]/', '', (string) $phone);
    }
}

/**
 * Field layout for the "About you" form (My Profile and the admin's
 * user card). Keys match user_profiles columns / User_model::$profile_fields.
 * Each field: [label, type, options-or-placeholder, column class]
 */
if (! function_exists('profile_field_groups')) {
    function profile_field_groups($role)
    {
        $groups = [
            'Personal' => ['user', [
                'title'         => ['Title', 'select', ['', 'Mr', 'Mrs', 'Miss', 'Ms', 'Pastor', 'Rev.', 'Evangelist', 'Bishop', 'Elder', 'Deacon', 'Dr.', 'Prof.'], 'col-md-3'],
                'gender'        => ['Gender', 'select', ['', 'Female', 'Male', 'Prefer not to say'], 'col-md-3'],
                'date_of_birth' => ['Date of birth', 'date', '', 'col-md-3'],
                'national_id'   => ['National ID / passport', 'text', 'e.g. 08-123456-X-08', 'col-md-3'],
            ]],
            'Contact & address' => ['phone', [
                'alt_phone'     => ['Alternative phone', 'tel', '+263 7...', 'col-md-4'],
                'address_line1' => ['Street address', 'text', 'House number and street', 'col-md-8'],
                'address_line2' => ['Suburb / area', 'text', '', 'col-md-4'],
                'city'          => ['City / town', 'text', 'e.g. Bulawayo', 'col-md-4'],
                'province'      => ['Province', 'select', ['', 'Bulawayo', 'Harare', 'Manicaland', 'Mashonaland Central', 'Mashonaland East', 'Mashonaland West', 'Masvingo', 'Matabeleland North', 'Matabeleland South', 'Midlands', 'Outside Zimbabwe'], 'col-md-4'],
                'country'       => ['Country', 'text', 'Zimbabwe', 'col-md-6'],
                'postal_code'   => ['Postal code / P.O. Box', 'text', '', 'col-md-6'],
            ]],
            'Church & ministry' => ['book', [
                'church_name'   => ['Home church / congregation', 'text', '', 'col-md-5'],
                'denomination'  => ['Denomination', 'text', '', 'col-md-4'],
                'ministry_role' => ['Role in ministry', 'text', 'e.g. Youth leader', 'col-md-3'],
            ]],
            'Education & work' => ['award', [
                'education_level' => ['Highest education', 'select', ['', 'Primary', 'O Level', 'A Level', 'Certificate', 'Diploma', 'Bachelor\'s degree', 'Master\'s degree', 'Doctorate'], 'col-md-6'],
                'occupation'      => ['Occupation', 'text', '', 'col-md-6'],
            ]],
            'Emergency contact' => ['alert', [
                'emergency_name'         => ['Full name', 'text', '', 'col-md-5'],
                'emergency_relationship' => ['Relationship', 'select', ['', 'Spouse', 'Parent', 'Sibling', 'Child', 'Relative', 'Friend', 'Pastor', 'Other'], 'col-md-3'],
                'emergency_phone'        => ['Phone', 'tel', '+263 7...', 'col-md-4'],
            ]],
        ];

        if ($role === 'lecturer') {
            $groups['Education & work'][1] = [
                'education_level' => ['Highest education', 'select', ['', 'Diploma', 'Bachelor\'s degree', 'Master\'s degree', 'Doctorate', 'Other'], 'col-md-4'],
                'qualifications'  => ['Qualifications', 'text', 'e.g. MTh (Biblical Studies), BA Theology', 'col-md-8'],
                'bio'             => ['Short bio (shown to your students)', 'textarea', 'A few lines about your background and what you teach', 'col-12'],
            ];
        } else {
            $groups['Other'] = ['grid', [
                'referral_source' => ['How did you hear about us?', 'select', ['', 'Church', 'Friend or family', 'WhatsApp', 'Facebook', 'Radio', 'Website', 'Other'], 'col-md-6'],
            ]];
        }
        return $groups;
    }
}

/**
 * Is Ezra (the AI assistant) switched on for this role? Cheap: settings are
 * already loaded for the page. Whether it can answer right now (API key,
 * limits, exams) is Ezra::availability().
 */
if (! function_exists('ezra_offered')) {
    function ezra_offered($role)
    {
        static $cache = [];
        if (! isset($cache[$role])) {
            $CI =& get_instance();
            $cache[$role] = $CI->db->table_exists('ezra_messages') && setting('ezra_enabled', '1') === '1'
                && in_array($role, array_map('trim', explode(',', setting('ezra_roles', 'student'))), true);
        }
        return $cache[$role];
    }
}

/**
 * Ezra's answer as safe HTML: everything is escaped first, then a little of
 * the Markdown the model uses is turned into formatting (headings, bold,
 * italics, bullet and numbered lists, paragraphs).
 */
if (! function_exists('ezra_format')) {
    function ezra_format($text)
    {
        $lines = preg_split('/\r\n|\r|\n/', html_escape(trim((string) $text)));
        $html = ''; $list = null; $para = [];
        $inline = function ($t) {
            $t = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $t);
            $t = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/s', '<em>$1</em>', $t);
            return preg_replace('/`([^`]+)`/', '<code>$1</code>', $t);
        };
        $flushPara = function () use (&$para, &$html, $inline) {
            if ($para) { $html .= '<p>' . $inline(implode('<br>', $para)) . '</p>'; $para = []; }
        };
        $flushList = function () use (&$list, &$html) {
            if ($list) { $html .= '<' . $list[0] . '>' . implode('', $list[1]) . '</' . $list[0] . '>'; $list = null; }
        };
        foreach ($lines as $line) {
            $t = trim($line);
            $n = [];
            if ($t === '' || preg_match('/^(-{3,}|\*{3,})$/', $t)) {
                $flushPara(); $flushList();
            } elseif (preg_match('/^#{1,6}\s+(.+)$/', $t, $m)) {
                $flushPara(); $flushList();
                $html .= '<p class="ezra-h">' . $inline(trim($m[1], '* ')) . '</p>';
            } elseif (preg_match('/^(?:[-*\x{2022}])\s+(.+)$/u', $t, $m) || preg_match('/^(\d+)[.)]\s+(.+)$/', $t, $n)) {
                $flushPara();
                $type = empty($n) ? 'ul' : 'ol';
                if (! $list || $list[0] !== $type) { $flushList(); $list = [$type, []]; }
                $list[1][] = '<li>' . $inline(empty($n) ? $m[1] : $n[2]) . '</li>';
            } else {
                $flushList();
                $para[] = $t;
            }
        }
        $flushPara(); $flushList();
        return $html;
    }
}

/** A forum post or event note as safe HTML: escaped, links clickable, line breaks kept. */
if (! function_exists('post_format')) {
    function post_format($text)
    {
        $html = html_escape(trim((string) $text));
        $html = preg_replace('~(https?://[^\s<]+[^\s<.,;:!?)\]\'"])~i', '<a href="$1" target="_blank" rel="noopener nofollow">$1</a>', $html);
        return nl2br($html, false);
    }
}

/** 1536 -> "1.5 KB". */
if (! function_exists('bytes_fmt')) {
    function bytes_fmt($bytes)
    {
        $bytes = (int) $bytes;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        return max(1, round($bytes / 1024)) . ' KB';
    }
}

/** CSS tone for an attendance rate: good (80%+), fair (60-79%), low, or none. */
if (! function_exists('rate_tone')) {
    function rate_tone($rate)
    {
        if ($rate === null) {
            return 'tone-none';
        }
        return $rate >= 80 ? 'tone-good' : ($rate >= 60 ? 'tone-fair' : 'tone-low');
    }
}
