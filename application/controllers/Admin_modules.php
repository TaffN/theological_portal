<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Modules (admin), always inside a program.
 *   admin_programs/{id}/modules          - the program's modules + "Add a module" + lecturers
 *   admin_programs/{id}/modules/create   - POST: add a module to that program
 *   admin_modules/edit/{id}              - edit (also moves it to another program)
 *   admin_modules/toggle|move|delete/{id} - POST
 *   admin_modules/assign, unassign/{module}/{user} - lecturers (POST)
 */
class Admin_modules extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model(['Module_model', 'Module_lecturer_model', 'Program_model', 'User_model', 'Program_enrollment_model']);
        $this->load->library('notifier');
        $this->load->helper('ui');
    }

    /** Called as admin_programs/{id}/modules. Without a program, back to the list of programs. */
    public function index($programId = 0)
    {
        $program = $this->Program_model->find($programId);
        if (! $program) {
            return redirect('admin_programs');
        }

        $modules = $this->Module_model->for_program($program['id']);
        foreach ($modules as &$module) {
            $module['lecturers'] = $this->Module_model->lecturers($module['id']);
            $module['students'] = $this->db->where('module_id', $module['id'])->where('status', 'active')->count_all_results('enrollments');
        }
        unset($module);

        $this->load->view('templates/header', ['title' => $program['name'] . ': modules']);
        $this->load->view('admin/modules', [
            'program'   => $program,
            'modules'   => $modules,
            'lecturers' => $this->User_model->find_lecturers(),
        ]);
        $this->load->view('templates/footer');
    }

    public function create_module($programId = 0)
    {
        $program = $this->Program_model->find($programId);
        if (! $program) {
            show_404();
        }
        if ($this->input->method() !== 'post') {
            return redirect('admin_programs/' . $program['id'] . '/modules');
        }
        $data = $this->validated(null);
        if ($data === false) {
            $this->session->set_flashdata('error', $this->error);
        } else {
            $moduleId = $this->Module_model->create($data + ['program_id' => $program['id'], 'status' => 'active']);
            $this->audit->log('module.created', 'module', $moduleId, 'Created module "' . $data['name'] . '" in ' . $program['name']);
            $n = $this->open_for_enrolled($program);
            $this->session->set_flashdata('success', 'Module added to ' . $program['name'] . '.'
                . ($n ? ' It is now open to the ' . $n . ' student' . ($n === 1 ? '' : 's') . ' already enrolled in the program.' : ''));
        }
        redirect('admin_programs/' . $program['id'] . '/modules');
    }

    public function edit($id = 0)
    {
        $module = $this->Module_model->find($id);
        if (! $module) {
            show_404();
        }
        $error = '';
        if ($this->input->method() === 'post') {
            $data = $this->validated($module['id']);
            $programId = (int) $this->input->post('program_id');
            $program = $this->Program_model->find($programId);
            if ($data === false) {
                $error = $this->error;
            } elseif (! $program) {
                $error = 'Choose which program this module belongs to.';
            } else {
                $data['program_id'] = $program['id'];
                $this->Module_model->update($module['id'], $data);
                $this->audit->log('module.updated', 'module', $module['id'], 'Edited module "' . $data['name'] . '"'
                    . ((int) $module['program_id'] !== (int) $program['id'] ? ' (moved from ' . $module['program_name'] . ' to ' . $program['name'] . ')' : ''));
                if ((int) $module['program_id'] !== (int) $program['id']) {
                    $this->open_for_enrolled($program);
                }
                $this->session->set_flashdata('success', 'Module updated.');
                return redirect('admin_programs/' . $program['id'] . '/modules');
            }
        }
        $this->load->view('templates/header', ['title' => 'Edit module']);
        $this->load->view('admin/module_form', ['module' => $module, 'programs' => $this->Program_model->all(), 'error' => $error]);
        $this->load->view('templates/footer');
    }

    public function toggle($id = 0)
    {
        $module = $this->find_or_404($id);
        if ($this->input->method() === 'post') {
            $status = $module['status'] === 'active' ? 'inactive' : 'active';
            $this->Module_model->update($id, ['status' => $status]);
            $this->audit->log('module.' . ($status === 'active' ? 'opened' : 'closed'), 'module', $id,
                ($status === 'active' ? 'Re-opened' : 'Closed') . ' module "' . $module['name'] . '" for new applications');
            $this->session->set_flashdata('success', $status === 'active'
                ? $module['name'] . ' is open for applications again.'
                : $module['name'] . ' is closed to new applications. Existing students keep their access.');
        }
        redirect('admin_programs/' . $module['program_id'] . '/modules');
    }

    public function move($id = 0, $direction = '')
    {
        $module = $this->find_or_404($id);
        if ($this->input->method() === 'post' && in_array($direction, ['up', 'down'], true)) {
            $this->Module_model->move($module['id'], $direction);
        }
        redirect('admin_programs/' . $module['program_id'] . '/modules');
    }

    public function delete($id = 0)
    {
        $module = $this->find_or_404($id);
        if ($this->input->method() === 'post') {
            $usage = $this->Module_model->usage($module['id']);
            if ($usage) {
                $parts = [];
                foreach ($usage as $label => $n) {
                    $parts[] = $n . ' ' . $label;
                }
                $this->session->set_flashdata('error', 'This module can\'t be deleted because it has ' . implode(', ', $parts) . '. Close it instead: students keep their access and no one new can apply.');
            } elseif ($this->Module_model->delete($module['id'])) {
                $this->audit->log('module.deleted', 'module', $module['id'], 'Deleted the empty module "' . $module['name'] . '"');
                $this->session->set_flashdata('success', 'Module deleted.');
            }
        }
        redirect('admin_programs/' . $module['program_id'] . '/modules');
    }

    public function assign()
    {
        $moduleId = (int) $this->input->post('module_id');
        $userId   = (int) $this->input->post('user_id');
        $module   = $this->Module_model->find($moduleId);

        if ($module && $userId) {
            $this->Module_lecturer_model->assign($moduleId, $userId);
            $this->audit->log('module.lecturer_assigned', 'module', $moduleId, 'Assigned lecturer #' . $userId . ' to module "' . $module['name'] . '"');
            $this->session->set_flashdata('success', 'Lecturer assigned.');
        } else {
            $this->session->set_flashdata('error', 'Choose a lecturer.');
        }
        redirect($module ? 'admin_programs/' . $module['program_id'] . '/modules' : 'admin_programs');
    }

    public function unassign($moduleId = 0, $userId = 0)
    {
        $module = $this->find_or_404($moduleId);
        if ($this->input->method() === 'post') {
            $this->Module_lecturer_model->unassign($moduleId, $userId);
            $this->audit->log('module.lecturer_removed', 'module', $moduleId, 'Removed lecturer #' . (int) $userId . ' from module "' . $module['name'] . '"');
            $this->session->set_flashdata('success', 'Lecturer removed from the module.');
        }
        redirect('admin_programs/' . $module['program_id'] . '/modules');
    }

    /* ------------------------------------------------------------------ */

    protected $error = '';

    /** Students with full access to the program get its new module straight away, and are told. Returns how many. */
    private function open_for_enrolled($program)
    {
        $opened = $this->Program_enrollment_model->sync_program($program['id']);
        foreach ($opened as $userId => $moduleIds) {
            $this->notifier->notify_user($userId, 'A new module has been added to ' . $program['name'] . ' and is open to you.', base_url('programs/' . $program['slug']));
        }
        return count($opened);
    }

    private function find_or_404($id)
    {
        $module = $this->Module_model->find($id);
        if (! $module) {
            show_404();
        }
        return $module;
    }

    /** The posted module fields, or false with $this->error set. $ignoreId = the module being edited. */
    private function validated($ignoreId)
    {
        $this->form_validation->set_rules('name', 'Module name', 'required|max_length[200]');
        $this->form_validation->set_rules('credits', 'Credits', 'integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('code', 'Code', 'max_length[30]|regex_match[/^[A-Za-z0-9._-]*$/]');
        if (! $this->form_validation->run()) {
            $this->error = strip_tags(validation_errors());
            return false;
        }
        $code = strtoupper(trim($this->input->post('code')));
        if ($code !== '' && $this->Module_model->code_taken($code, $ignoreId)) {
            $this->error = 'The code ' . $code . ' is already used by another module.';
            return false;
        }
        $data = [
            'name'          => trim($this->input->post('name')),
            'description'   => trim($this->input->post('description')) ?: null,
            'duration_text' => trim($this->input->post('duration_text')) ?: null,
            'credits'       => (int) $this->input->post('credits'),
        ];
        if ($code !== '') {
            $data['code'] = $code;
        }
        return $data;
    }
}
