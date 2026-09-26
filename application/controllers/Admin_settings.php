<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_settings extends Admin_Controller
{
    /** Every editable key, grouped for the form. [key => [label, placeholder, type]] */
    public static $groups = [
        'Organisation' => [
            'org_name'            => ['Full name', 'e.g. Bulawayo Theological Center', 'text'],
            'org_short_name'      => ['Short name (menu & ID cards)', 'e.g. Theological Center', 'text'],
            'org_tagline'         => ['Tagline', 'e.g. Learning Portal', 'text'],
            'org_initials'        => ['Logo initials (1-3 letters)', 'TC', 'text'],
            'org_registration_no' => ['Registration number', 'e.g. PVO 12/2019', 'text'],
        ],
        'Contact' => [
            'org_phone'    => ['Phone', '+263 29 ...', 'tel'],
            'org_whatsapp' => ['WhatsApp number', '+263 77 ...', 'tel'],
            'org_email'    => ['Email', 'info@...', 'email'],
            'org_website'  => ['Website', 'https://...', 'url'],
            'office_hours' => ['Office hours', 'Mon - Fri, 8:00 - 16:30', 'text'],
        ],
        'Address' => [
            'org_address'  => ['Street address', 'e.g. 12 Fort Street', 'text'],
            'org_city'     => ['City / town', 'e.g. Bulawayo', 'text'],
            'org_province' => ['Province', 'e.g. Bulawayo Metropolitan', 'text'],
            'org_country'  => ['Country', 'Zimbabwe', 'text'],
            'org_postal'   => ['Postal address', 'e.g. P.O. Box 123', 'text'],
        ],
        'Payments' => [
            'pay_ecocash_number' => ['EcoCash number', '+263 77 ...', 'text'],
            'pay_ecocash_name'   => ['EcoCash registered name', '', 'text'],
            'pay_bank_name'      => ['Bank', 'e.g. CBZ Bank', 'text'],
            'pay_bank_branch'    => ['Branch', '', 'text'],
            'pay_bank_account'   => ['Account number', '', 'text'],
            'pay_bank_holder'    => ['Account name', '', 'text'],
            'pay_reference_hint' => ['Payment reference instructions', 'Use your student number as the reference', 'text'],
        ],
        'Documents' => [
            'receipt_footer'       => ['Receipt footer note', '', 'text'],
            'id_card_valid_months' => ['ID cards valid for (months)', '12', 'number'],
            'id_card_note'         => ['Note on the back of ID cards', 'If found, please return to...', 'text'],
            'privacy_notice'       => ['Privacy notice (shown at registration and on the Help page)', '', 'textarea'],
        ],
        'Results' => [
            'grade_distinction' => ['Distinction from (%)', '75', 'number'],
            'grade_merit'       => ['Merit from (%)', '60', 'number'],
            'grade_pass'        => ['Pass mark (%): below this is a Fail', '50', 'number'],
            'statement_note'    => ['Note printed on statements of results', 'Scan the QR code to confirm this statement is genuine.', 'text'],
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('ui');
    }

    public function index()
    {
        $this->load->view('templates/header', ['title' => 'Organisation settings']);
        $this->load->view('admin/settings', ['groups' => self::$groups, 'values' => $this->settings->all()]);
        $this->load->view('templates/footer');
    }

    public function save()
    {
        $pairs = [];
        foreach (self::$groups as $fields) {
            foreach ($fields as $key => $meta) {
                $pairs[$key] = trim(mb_substr((string) $this->input->post($key), 0, $meta[2] === 'textarea' ? 5000 : 500));
            }
        }
        if ($pairs['org_email'] !== '' && ! filter_var($pairs['org_email'], FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'That email address doesn\'t look right.');
            return redirect('admin_settings');
        }
        $pairs['id_card_valid_months'] = (string) max(1, min(120, (int) $pairs['id_card_valid_months'] ?: 12));
        // Grade boundaries: whole or half percentages, highest first (Distinction > Merit > Pass).
        $pass  = max(1, min(100, (float) ($pairs['grade_pass'] !== '' ? $pairs['grade_pass'] : 50)));
        $merit = max($pass, min(100, (float) ($pairs['grade_merit'] !== '' ? $pairs['grade_merit'] : 60)));
        $dist  = max($merit, min(100, (float) ($pairs['grade_distinction'] !== '' ? $pairs['grade_distinction'] : 75)));
        $pairs['grade_pass'] = score_fmt($pass);
        $pairs['grade_merit'] = score_fmt($merit);
        $pairs['grade_distinction'] = score_fmt($dist);
        $pairs['org_initials'] = strtoupper(mb_substr(preg_replace('/[^A-Za-z]/', '', $pairs['org_initials']), 0, 3)) ?: 'TC';

        $before = $this->settings->all();
        $this->settings->save($pairs);
        $changed = array_keys(array_filter($pairs, function ($v, $k) use ($before) { return ! isset($before[$k]) || (string) $before[$k] !== $v; }, ARRAY_FILTER_USE_BOTH));
        $this->audit->log('settings.updated', null, null, 'Updated organisation settings' . ($changed ? ': ' . implode(', ', $changed) : ''));

        $this->session->set_flashdata('success', 'Settings saved. Receipts, ID cards and the Help page now use these details.');
        redirect('admin_settings');
    }
}
