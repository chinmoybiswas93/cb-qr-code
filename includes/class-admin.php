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
        wp_enqueue_style('cb-qr-code-admin', CB_QR_CODE_URL . 'assets/css/admin-style.css', [], defined('CB_QR_CODE_VERSION') ? CB_QR_CODE_VERSION : time(), 'all');
        wp_enqueue_script('cb-qr-code-admin', CB_QR_CODE_URL . 'assets/js/admin-script.js', ['jquery'], defined('CB_QR_CODE_VERSION') ? CB_QR_CODE_VERSION : time(), true);
        wp_enqueue_script('cb-qr-code-admin-tabs', CB_QR_CODE_URL . 'assets/js/admin-tabs.js', ['jquery'], defined('CB_QR_CODE_VERSION') ? CB_QR_CODE_VERSION : time(), true);
        wp_localize_script('cb-qr-code-admin', 'CBQRCodeAjax', [
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
                'label' => __('Appearance', 'cb-qr-code'),
                'file' => 'templates/appearance.php'
            ],
            'settings' => [
                'label' => __('Settings', 'cb-qr-code'),
                'file' => 'templates/settings.php'
            ],
            'about' => [
                'label' => __('About', 'cb-qr-code'),
                'file' => 'templates/about.php'
            ],
            'support' => [
                'label' => __('Support', 'cb-qr-code'),
                'file' => 'templates/support.php'
            ],
        ];

        // Render tab navigation
        echo '<div class="cbqr-tabs-nav" style="margin: 20px 0 1px 0;">';
        foreach ($tabs as $tab => $data) {
            echo '<button type="button" data-tab="' . esc_attr($tab) . '">' . esc_html($data['label']) . '</button>';
        }
        echo '</div>';

        // Render tab contents
        foreach ($tabs as $tab => $data) {
            $full_path = CB_QR_CODE_PATH . $data['file'];
            if (file_exists($full_path)) {
                echo '<div id="cbqr-tab-' . esc_attr($tab) . '" class="cbqr-tab-content" style="display:none;">';
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
        $input = $_POST;
        $tab = isset($input['tab']) ? $input['tab'] : '';
        $errors = [];
        if ($tab === 'settings') {
            if (empty($input['cbqr-post-types']))
                $errors[] = __('Please select at least one post type.', 'cb-qr-code');
            $url_mode = isset($input['cbqr-url-mode']) ? $input['cbqr-url-mode'] : 'permalink';
            if ($url_mode === 'custom') {
                if (empty($input['cbqr-custom-url']) || !filter_var($input['cbqr-custom-url'], FILTER_VALIDATE_URL))
                    $errors[] = __('Please enter a valid custom URL.', 'cb-qr-code');
            }
            if (!empty($errors))
                wp_send_json_error(['errors' => $errors]);
            $settings = get_option('cb_qr_code_settings', []);
            $settings['cbqr-url-mode'] = $url_mode;
            $settings['cbqr-custom-url'] = $url_mode === 'custom' ? esc_url_raw($input['cbqr-custom-url']) : '';
            update_option('cb_qr_code_settings', $settings);
            update_option('cb_qr_code_post_types', array_map('sanitize_text_field', $input['cbqr-post-types']));
            wp_send_json_success(['message' => __('Settings saved successfully.', 'cb-qr-code')]);
        } else {
            $errors = $this->validate_settings($input);
            if (!empty($errors))
                wp_send_json_error(['errors' => $errors]);
            $sanitized = $this->sanitize_settings($input);
            update_option('cb_qr_code_settings', $sanitized);
            wp_send_json_success(['message' => __('Settings saved successfully.', 'cb-qr-code')]);
        }
    }
    private function validate_settings($input)
    {
        $errors = [];
        if (empty($input['qr-code-label']))
            $errors[] = __('Label is required.', 'cb-qr-code');
        if (!isset($input['qr-code-size']) || !is_numeric($input['qr-code-size']) || $input['qr-code-size'] < 50 || $input['qr-code-size'] > 1000)
            $errors[] = __('Size must be between 50 and 1000.', 'cb-qr-code');
        if (!isset($input['qr-code-margin']) || !is_numeric($input['qr-code-margin']) || $input['qr-code-margin'] < 0 || $input['qr-code-margin'] > 20)
            $errors[] = __('Margin must be between 0 and 20.', 'cb-qr-code');
        if (!empty($input['qr-code-dark']) && !preg_match('/^[a-fA-F0-9]{6}$/', $input['qr-code-dark']))
            $errors[] = __('Dark color must be a 6-digit hex code.', 'cb-qr-code');
        if (!empty($input['qr-code-light']) && !preg_match('/^[a-fA-F0-9]{6}$/', $input['qr-code-light']))
            $errors[] = __('Light color must be a 6-digit hex code.', 'cb-qr-code');
        if (!empty($input['qr-code-logo-url']) && !filter_var($input['qr-code-logo-url'], FILTER_VALIDATE_URL))
            $errors[] = __('Logo URL must be a valid URL.', 'cb-qr-code');
        if (!isset($input['qr-code-logo-size']) || !is_numeric($input['qr-code-logo-size']) || $input['qr-code-logo-size'] < 10 || $input['qr-code-logo-size'] > 100)
            $errors[] = __('Logo size must be between 10 and 100.', 'cb-qr-code');
        if (empty($input['qr-code-font-size']))
            $errors[] = __('Font size is required.', 'cb-qr-code');
        if (!in_array($input['qr-code-position'] ?? '', ['left', 'right']))
            $errors[] = __('Position must be left or right.', 'cb-qr-code');
        return $errors;
    }
    private function sanitize_settings($input)
    {
        return [
            'qr-code-label' => sanitize_text_field($input['qr-code-label'] ?? ''),
            'qr-code-size' => max(50, min(1000, intval($input['qr-code-size']))),
            'qr-code-margin' => max(0, min(20, intval($input['qr-code-margin']))),
            'qr-code-dark' => preg_replace('/[^a-fA-F0-9]/', '', $input['qr-code-dark'] ?? '000000'),
            'qr-code-light' => preg_replace('/[^a-fA-F0-9]/', '', $input['qr-code-light'] ?? 'ffffff'),
            'qr-code-logo-url' => esc_url_raw($input['qr-code-logo-url'] ?? ''),
            'qr-code-logo-size' => max(10, min(100, intval($input['qr-code-logo-size']))),
            'qr-code-font-size' => sanitize_text_field($input['qr-code-font-size'] ?? '12px'),
            'qr-code-position' => in_array($input['qr-code-position'] ?? 'right', ['left', 'right']) ? $input['qr-code-position'] : 'right',
        ];
    }
}
