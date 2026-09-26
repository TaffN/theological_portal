<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Organisation settings stored in the `settings` table.
 *     $this->settings->get('org_name')
 *     setting('org_phone')              // helper, usable in views
 * Loaded once per request and cached. Falls back to sensible defaults if
 * the table doesn't exist yet (before migration 14).
 */
class Settings
{
    protected $CI;
    protected $values = null;

    protected $fallback = [
        'org_name' => 'Theological Center', 'org_short_name' => 'Theological Center',
        'org_initials' => 'TC', 'org_tagline' => 'Learning Portal', 'org_country' => 'Zimbabwe', 'id_card_valid_months' => '12',
    ];

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function all()
    {
        if ($this->values === null) {
            $this->values = $this->fallback;
            try {
                if ($this->CI->db->table_exists('settings')) {
                    foreach ($this->CI->db->get('settings')->result_array() as $r) {
                        $this->values[$r['setting_key']] = (string) $r['setting_value'];
                    }
                } else {
                    // Before migration 14: keep reading the old config file.
                    $this->CI->config->load('portal', true, true);
                    $this->values = array_merge($this->values, array_filter((array) $this->CI->config->item('portal'), 'strlen'));
                }
            } catch (Throwable $e) {
                // keep fallbacks
            }
        }
        return $this->values;
    }

    public function get($key, $default = '')
    {
        $all = $this->all();
        return isset($all[$key]) && $all[$key] !== '' ? $all[$key] : $default;
    }

    public function save(array $pairs)
    {
        $now = date('Y-m-d H:i:s');
        foreach ($pairs as $k => $v) {
            $exists = $this->CI->db->where('setting_key', $k)->count_all_results('settings') > 0;
            if ($exists) {
                $this->CI->db->where('setting_key', $k)->update('settings', ['setting_value' => $v, 'updated_at' => $now]);
            } else {
                $this->CI->db->insert('settings', ['setting_key' => $k, 'setting_value' => $v, 'updated_at' => $now]);
            }
        }
        $this->values = null;
    }

    /** One-line postal address from the settings. */
    public function address($glue = ', ')
    {
        $parts = [];
        foreach (['org_address', 'org_city', 'org_province', 'org_country'] as $k) {
            if ($this->get($k) !== '') {
                $parts[] = $this->get($k);
            }
        }
        return implode($glue, $parts);
    }
}
