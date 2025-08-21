<?php
namespace CBQRCode;
class Admin
{
    private static $instance = null;
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_cb_qr_code_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_cb_qr_code_preview', [$this, 'ajax_preview']);
    }
    public function add_admin_menu()
    {
        add_menu_page(
            'CB QR Code Settings',
            'CB QR Code',
            'manage_options',
            'cb-qr-code',
            [$this, 'settings_page'],
            'dashicons-admin-generic',
            100
        );
    }
    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'toplevel_page_cb-qr-code')
            return;
        
        wp_enqueue_style('cbqc-admin', CB_QR_CODE_URL . 'assets/css/admin-style.css', [], defined('CB_QR_CODE_VERSION') ? CB_QR_CODE_VERSION : time(), 'all');
        wp_enqueue_script('cbqc-admin', CB_QR_CODE_URL . 'assets/js/admin-script.js', ['jquery'], defined('CB_QR_CODE_VERSION') ? CB_QR_CODE_VERSION : time(), true);
        wp_enqueue_script('cbqc-admin-tabs', CB_QR_CODE_URL . 'assets/js/admin-tabs.js', ['jquery'], defined('CB_QR_CODE_VERSION') ? CB_QR_CODE_VERSION : time(), true);
        wp_localize_script('cbqc-admin', 'CBQRCodeAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cb_qr_code_ajax_nonce'),
            'siteUrl' => site_url(),
        ]);
    }
    public function settings_page()
    {
        if (!current_user_can('manage_options'))
            return;

        echo '<div class="wrap">';
        echo '<div class="cb-qr-dashboard">';
        include CB_QR_CODE_PATH . 'templates/header.php';

        $tabs = [
            'appearance' => [
                'label' => esc_html__('Appearance', 'cb-qr-code'),
                'file' => 'templates/appearance.php'
            ],
            'settings' => [
                'label' => esc_html__('Settings', 'cb-qr-code'),
                'file' => 'templates/settings.php'
            ],
            'about' => [
                'label' => esc_html__('About', 'cb-qr-code'),
                'file' => 'templates/about.php'
            ],
            'support' => [
                'label' => esc_html__('Support', 'cb-qr-code'),
                'file' => 'templates/support.php'
            ],
        ];

        echo '<div class="cbqc-tabs-nav" style="margin: 20px 0 1px 0;">';
        foreach ($tabs as $tab => $data) {
            echo '<button type="button" data-tab="' . esc_attr($tab) . '">' . esc_html($data['label']) . '</button>';
        }
        echo '</div>';

        foreach ($tabs as $tab => $data) {
            $full_path = CB_QR_CODE_PATH . $data['file'];
            if (file_exists($full_path)) {
                echo '<div id="cbqc-tab-' . esc_attr($tab) . '" class="cbqc-tab-content" style="display:none;">';
                include $full_path;
                echo '</div>';
            }
        }
        echo '</div>';
        echo '</div>';
    }
    public function ajax_save_settings()
    {
        check_ajax_referer('cb_qr_code_ajax_nonce', 'security');

        $input = $this->sanitize_input($_POST);

        $tab = isset($input['tab']) ? $input['tab'] : '';

        if ($tab === 'settings') {
            $this->handle_settings_tab($input);
        } else {
            $this->handle_appearance_tab($input);
        }
    }

    private function sanitize_input($post_data)
    {
        $input = array();
        foreach ($post_data as $key => $value) {
            if (is_array($value)) {
                $input[$key] = array_map('sanitize_text_field', $value);
            } else {
                $input[$key] = sanitize_text_field($value);
            }
        }
        return $input;
    }

    private function handle_settings_tab($input)
    {
        $errors = $this->validate_settings_tab($input);

        if (!empty($errors)) {
            $errors = array_map('esc_html', $errors);
            wp_send_json_error(['errors' => $errors]);
        }

        $sanitized = $this->sanitize_settings_tab($input);
        
        $existing_settings = get_option('cb_qr_code_settings', []);
        
        $merged_settings = array_merge($existing_settings, $sanitized['settings']);
        
        update_option('cb_qr_code_settings', $merged_settings);
        update_option('cb_qr_code_post_types', $sanitized['post_types']);

        wp_send_json_success(['message' => esc_html__('Settings saved successfully.', 'cb-qr-code')]);
    }
    private function handle_appearance_tab($input)
    {
        $errors = $this->validate_appearance_tab($input);

        if (!empty($errors)) {
            $errors = array_map('esc_html', $errors);
            wp_send_json_error(['errors' => $errors]);
        }

        $sanitized = $this->sanitize_appearance_tab($input);
        
        $existing_settings = get_option('cb_qr_code_settings', []);
        
        $merged_settings = array_merge($existing_settings, $sanitized);
        
        update_option('cb_qr_code_settings', $merged_settings);

        wp_send_json_success(['message' => esc_html__('Settings saved successfully.', 'cb-qr-code')]);
    }

    private function validate_settings_tab($input)
    {
        $errors = [];

        if (empty($input['cbqc-post-types'])) {
            $errors[] = esc_html__('Please select at least one post type.', 'cb-qr-code');
        }

        $url_mode = isset($input['cbqc-url-mode']) ? $input['cbqc-url-mode'] : 'permalink';
        if ($url_mode === 'custom') {
            $custom_url = isset($input['cbqc-custom-url']) ? $input['cbqc-custom-url'] : '';
            if (empty($custom_url) || !filter_var($custom_url, FILTER_VALIDATE_URL)) {
                $errors[] = esc_html__('Please enter a valid custom URL.', 'cb-qr-code');
            }
        }

        return $errors;
    }
    private function validate_appearance_tab($input)
    {
        $errors = [];

        if (empty($input['qr-code-label'])) {
            $errors[] = esc_html__('Label is required.', 'cb-qr-code');
        }

        if (!isset($input['qr-code-size']) || !is_numeric($input['qr-code-size']) || $input['qr-code-size'] < 50 || $input['qr-code-size'] > 1000) {
            $errors[] = esc_html__('Size must be between 50 and 1000.', 'cb-qr-code');
        }

        if (!isset($input['qr-code-margin']) || !is_numeric($input['qr-code-margin']) || $input['qr-code-margin'] < 0 || $input['qr-code-margin'] > 20) {
            $errors[] = esc_html__('Margin must be between 0 and 20.', 'cb-qr-code');
        }

        if (!empty($input['qr-code-dark']) && !preg_match('/^[a-fA-F0-9]{6}$/', $input['qr-code-dark'])) {
            $errors[] = esc_html__('Dark color must be a 6-digit hex code.', 'cb-qr-code');
        }

        if (!empty($input['qr-code-light']) && !preg_match('/^[a-fA-F0-9]{6}$/', $input['qr-code-light'])) {
            $errors[] = esc_html__('Light color must be a 6-digit hex code.', 'cb-qr-code');
        }

        if (!empty($input['qr-code-logo-url']) && !filter_var($input['qr-code-logo-url'], FILTER_VALIDATE_URL)) {
            $errors[] = esc_html__('Logo URL must be a valid URL.', 'cb-qr-code');
        }

        if (!isset($input['qr-code-logo-size']) || !is_numeric($input['qr-code-logo-size']) || $input['qr-code-logo-size'] < 10 || $input['qr-code-logo-size'] > 100) {
            $errors[] = esc_html__('Logo size must be between 10 and 100.', 'cb-qr-code');
        }

        if (empty($input['qr-code-font-size'])) {
            $errors[] = esc_html__('Font size is required.', 'cb-qr-code');
        }

        if (!in_array($input['qr-code-position'] ?? '', ['left', 'right'])) {
            $errors[] = esc_html__('Position must be left or right.', 'cb-qr-code');
        }

        return $errors;
    }

    private function sanitize_settings_tab($input)
    {
        $url_mode = isset($input['cbqc-url-mode']) ? $input['cbqc-url-mode'] : 'permalink';
        $custom_url = '';

        if ($url_mode === 'custom' && isset($input['cbqc-custom-url'])) {
            $custom_url = esc_url_raw($input['cbqc-custom-url']);
        }

        return [
            'settings' => [
                'cbqc-url-mode' => sanitize_text_field($url_mode),
                'cbqc-custom-url' => $custom_url,
            ],
            'post_types' => isset($input['cbqc-post-types']) ? 
                array_values(array_diff(array_map('sanitize_text_field', $input['cbqc-post-types']), ['attachment'])) : []
        ];
    }
    private function sanitize_appearance_tab($input)
    {
        return [
            'qr-code-label' => sanitize_text_field($input['qr-code-label'] ?? ''),
            'qr-code-size' => max(50, min(1000, intval($input['qr-code-size'] ?? 200))),
            'qr-code-margin' => max(0, min(20, intval($input['qr-code-margin'] ?? 4))),
            'qr-code-dark' => preg_replace('/[^a-fA-F0-9]/', '', $input['qr-code-dark'] ?? '000000'),
            'qr-code-light' => preg_replace('/[^a-fA-F0-9]/', '', $input['qr-code-light'] ?? 'ffffff'),
            'qr-code-logo-url' => esc_url_raw($input['qr-code-logo-url'] ?? ''),
            'qr-code-logo-size' => max(10, min(100, intval($input['qr-code-logo-size'] ?? 20))),
            'qr-code-font-size' => sanitize_text_field($input['qr-code-font-size'] ?? '12px'),
            'qr-code-position' => in_array($input['qr-code-position'] ?? 'right', ['left', 'right']) ? $input['qr-code-position'] : 'right',
        ];
    }

    public function ajax_preview()
    {
        check_ajax_referer('cb_qr_code_ajax_nonce', 'security');
        
        $input = $this->sanitize_input($_POST);
        
        $label = $input['qr-code-label'] ?? 'Scan Me';
        $size = max(50, min(1000, intval($input['qr-code-size'] ?? 150)));
        $margin = max(0, min(20, intval($input['qr-code-margin'] ?? 2)));
        $dark = preg_replace('/[^a-fA-F0-9]/', '', $input['qr-code-dark'] ?? '000000');
        $light = preg_replace('/[^a-fA-F0-9]/', '', $input['qr-code-light'] ?? 'ffffff');
        $logo_url = esc_url_raw($input['qr-code-logo-url'] ?? '');
        $logo_size = max(10, min(100, intval($input['qr-code-logo-size'] ?? 50)));
        $font_size = intval($input['qr-code-font-size'] ?? 12);
        
        $foreground = QRGenerator::hex_to_rgb($dark);
        $background = QRGenerator::hex_to_rgb($light);
        
        $qr_data_uri = QRGenerator::generate(site_url(), [
            'size' => $size,
            'margin' => $margin,
            'foreground' => $foreground,
            'background' => $background,
            'logo' => $logo_url ? QRGenerator::download_logo($logo_url) : '',
            'logo_size' => $logo_size,
        ]);
        
        if (empty($qr_data_uri)) {
            wp_send_json_error(['message' => 'Failed to generate QR code']);
            return;
        }
        
        $html = sprintf(
            '<div class="cb-qr-label" style="font-size: %dpx; font-weight:bold; margin-bottom: 10px;">%s</div><img src="%s" alt="QR Code Preview" style="max-width: 100%%; height: auto;">',
            $font_size,
            esc_html($label),
            $qr_data_uri  
        );
        
        wp_send_json_success(['html' => $html]);
    }
}
