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
        
        wp_enqueue_media();
        
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
                if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'] ?? '')), 'cb_qr_code_ajax_nonce')) {
            wp_send_json_error(['errors' => [esc_html__('Security check failed.', 'cb-qr-code')]]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['errors' => [esc_html__('Insufficient permissions.', 'cb-qr-code')]]);
        }

        $tab = sanitize_text_field(wp_unslash($_POST['tab'] ?? ''));
        $post_data = map_deep(wp_unslash($_POST), 'sanitize_text_field');

        if ($tab === 'settings') {
            $input = $this->sanitize_settings_input($_POST);
            $this->handle_settings_tab($input);
        } else {
            $input = $this->sanitize_appearance_input($_POST);
            $this->handle_appearance_tab($input);
        }
    }

    /**
     * Legacy method - no longer used. Kept for reference.
     * Use sanitize_settings_input(), sanitize_appearance_input(), or sanitize_preview_input() instead.
     */
    /*
    public function sanitize_recursive($data)
    {
        if (!is_array($data)) {
            return sanitize_text_field($data);
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized_key = sanitize_key($key);

            if (is_array($value)) {
                $sanitized[$sanitized_key] = $this->sanitize_recursive($value);
            } else {
                switch ($key) {
                    case 'qr-code-dark':
                    case 'qr-code-light':
                        $sanitized[$sanitized_key] = sanitize_hex_color_no_hash($value);
                        break;
                    case 'qr-code-logo-url':
                        $sanitized[$sanitized_key] = esc_url_raw($value);
                        break;
                    default:
                        $sanitized[$sanitized_key] = sanitize_text_field($value);
                        break;
                }
            }
        }
        return $sanitized;
    }
    */

    private function sanitize_settings_input($post_data)
    {

        $settings_fields = [
            'cbqc-url-mode',
            'cbqc-custom-url',
            'cbqc-post-types',
            'action',
            'tab'
        ];

        $input = array();
        foreach ($settings_fields as $field) {
            if (isset($post_data[$field])) {
                $clean_key = sanitize_key($field);
                $value = wp_unslash($post_data[$field]);
                
                if (is_array($value)) {
                    $input[$clean_key] = array_map('sanitize_text_field', $value);
                } else {
                    switch ($clean_key) {
                        case 'cbqc-custom-url':
                            $input[$clean_key] = esc_url_raw($value);
                            break;
                        default:
                            $input[$clean_key] = sanitize_text_field($value);
                            break;
                    }
                }
            }
        }
        return $input;
    }

    private function sanitize_appearance_input($post_data)
    {
        $appearance_fields = [
            'qr-code-label',
            'qr-code-size',
            'qr-code-margin',
            'qr-code-dark',
            'qr-code-light',
            'qr-code-logo-id',
            'qr-code-logo-url',
            'qr-code-logo-size',
            'qr-code-font-size',
            'qr-code-position',
            'action',
            'tab'
        ];

        $input = array();
        foreach ($appearance_fields as $field) {
            if (isset($post_data[$field])) {
                $clean_key = sanitize_key($field);
                $value = wp_unslash($post_data[$field]);
                
                switch ($clean_key) {
                    case 'qr-code-logo-url':
                        $input[$clean_key] = esc_url_raw($value);
                        break;
                    case 'qr-code-logo-id':
                        $input[$clean_key] = absint($value);
                        break;
                    case 'qr-code-dark':
                    case 'qr-code-light':

                        $input[$clean_key] = preg_replace('/[^a-fA-F0-9]/', '', $value);
                        break;
                    case 'qr-code-size':
                    case 'qr-code-margin':
                    case 'qr-code-logo-size':
                    case 'qr-code-font-size':
                        $input[$clean_key] = absint($value);
                        break;
                    default:
                        $input[$clean_key] = sanitize_text_field($value);
                        break;
                }
            }
        }
        return $input;
    }

    private function sanitize_preview_input($post_data)
    {

        $preview_fields = [
            'qr-code-label',
            'qr-code-size',
            'qr-code-margin',
            'qr-code-dark',
            'qr-code-light',
            'qr-code-logo-id',
            'qr-code-logo-url',
            'qr-code-logo-size',
            'qr-code-font-size'
        ];

        $input = array();
        foreach ($preview_fields as $field) {
            if (isset($post_data[$field])) {
                $clean_key = sanitize_key($field);
                $value = wp_unslash($post_data[$field]);
                
                switch ($clean_key) {
                    case 'qr-code-logo-url':
                        $input[$clean_key] = esc_url_raw($value);
                        break;
                    case 'qr-code-logo-id':
                        $input[$clean_key] = absint($value);
                        break;
                    case 'qr-code-dark':
                    case 'qr-code-light':

                        $input[$clean_key] = preg_replace('/[^a-fA-F0-9]/', '', $value);
                        break;
                    case 'qr-code-size':
                    case 'qr-code-margin':
                    case 'qr-code-logo-size':
                    case 'qr-code-font-size':
                        $input[$clean_key] = absint($value);
                        break;
                    default:
                        $input[$clean_key] = sanitize_text_field($value);
                        break;
                }
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


        if (empty($input['cbqc-post-types']) || !is_array($input['cbqc-post-types'])) {
            $errors[] = esc_html__('Please select at least one post type.', 'cb-qr-code');
        } else {

            $valid_post_types = get_post_types(['public' => true], 'names');
            foreach ($input['cbqc-post-types'] as $post_type) {
                $sanitized_post_type = sanitize_text_field($post_type);
                if (!in_array($sanitized_post_type, $valid_post_types, true)) {
                    $errors[] = sprintf(
                        /* translators: %s is the invalid post type name */
                        esc_html__('Invalid post type: %s', 'cb-qr-code'),
                        esc_html($sanitized_post_type)
                    );
                }
            }
        }


        $url_mode = sanitize_text_field($input['cbqc-url-mode'] ?? 'permalink');
        if (!in_array($url_mode, ['permalink', 'custom'], true)) {
            $errors[] = esc_html__('Invalid URL mode selected.', 'cb-qr-code');
        }

        if ($url_mode === 'custom') {
            $custom_url = esc_url_raw($input['cbqc-custom-url'] ?? '');
            if (empty($custom_url) || !filter_var($custom_url, FILTER_VALIDATE_URL)) {
                $errors[] = esc_html__('Please enter a valid custom URL.', 'cb-qr-code');
            }
        }

        return $errors;
    }
    private function validate_appearance_tab($input)
    {
        $errors = [];


        $label = sanitize_text_field($input['qr-code-label'] ?? '');
        if (empty($label)) {
            $errors[] = esc_html__('Label is required.', 'cb-qr-code');
        }


        $size = absint($input['qr-code-size'] ?? 0);
        if ($size < 50 || $size > 1000) {
            $errors[] = esc_html__('Size must be between 50 and 1000.', 'cb-qr-code');
        }


        $margin = absint($input['qr-code-margin'] ?? 0);
        if ($margin < 0 || $margin > 20) {
            $errors[] = esc_html__('Margin must be between 0 and 20.', 'cb-qr-code');
        }


        $dark = sanitize_text_field($input['qr-code-dark'] ?? '');
        if (!empty($dark) && !preg_match('/^[a-fA-F0-9]{6}$/', $dark)) {
            $errors[] = esc_html__('Dark color must be a 6-digit hex code.', 'cb-qr-code');
        }


        $light = sanitize_text_field($input['qr-code-light'] ?? '');
        if (!empty($light) && !preg_match('/^[a-fA-F0-9]{6}$/', $light)) {
            $errors[] = esc_html__('Light color must be a 6-digit hex code.', 'cb-qr-code');
        }


        $logo_id = absint($input['qr-code-logo-id'] ?? 0);
        if ($logo_id > 0 && !wp_attachment_is_image($logo_id)) {
            $errors[] = esc_html__('Logo must be a valid image attachment.', 'cb-qr-code');
        }


        $logo_url = esc_url_raw($input['qr-code-logo-url'] ?? '');
        if ($logo_id === 0 && !empty($logo_url) && !filter_var($logo_url, FILTER_VALIDATE_URL)) {
            $errors[] = esc_html__('Logo URL must be a valid URL.', 'cb-qr-code');
        }


        $logo_size = absint($input['qr-code-logo-size'] ?? 0);
        if ($logo_size < 10 || $logo_size > 100) {
            $errors[] = esc_html__('Logo size must be between 10 and 100.', 'cb-qr-code');
        }


        $font_size = absint($input['qr-code-font-size'] ?? 0);
        if ($font_size < 6 || $font_size > 100) {
            $errors[] = esc_html__('Font size must be between 6 and 100.', 'cb-qr-code');
        }


        $position = sanitize_text_field($input['qr-code-position'] ?? '');
        if (!in_array($position, ['left', 'right'], true)) {
            $errors[] = esc_html__('Position must be left or right.', 'cb-qr-code');
        }

        return $errors;
    }

    private function sanitize_settings_tab($input)
    {
        $url_mode = sanitize_text_field($input['cbqc-url-mode'] ?? 'permalink');
        $custom_url = '';

        if ($url_mode === 'custom' && isset($input['cbqc-custom-url'])) {
            $custom_url = esc_url_raw($input['cbqc-custom-url']);
        }

        $post_types = [];
        if (isset($input['cbqc-post-types']) && is_array($input['cbqc-post-types'])) {

            $valid_post_types = get_post_types(['public' => true], 'names');
            foreach ($input['cbqc-post-types'] as $post_type) {
                $sanitized_post_type = sanitize_text_field($post_type);
                if (in_array($sanitized_post_type, $valid_post_types, true) && $sanitized_post_type !== 'attachment') {
                    $post_types[] = $sanitized_post_type;
                }
            }
        }

        return [
            'settings' => [
                'cbqc-url-mode' => $url_mode,
                'cbqc-custom-url' => $custom_url,
            ],
            'post_types' => array_values($post_types)
        ];
    }
    private function sanitize_appearance_tab($input)
    {

        $logo_id = absint($input['qr-code-logo-id'] ?? 0);
        $logo_url = '';
        

        if ($logo_id > 0) {
            $attachment_url = wp_get_attachment_url($logo_id);

            if ($attachment_url && wp_attachment_is_image($logo_id)) {
                $logo_url = $attachment_url;
            } else {
                $logo_id = 0;
            }
        }
        

        if ($logo_id === 0 && !empty($input['qr-code-logo-url'])) {
            $logo_url = esc_url_raw($input['qr-code-logo-url']);
        }
        
        return [
            'qr-code-label' => sanitize_text_field($input['qr-code-label'] ?? ''),
            'qr-code-size' => max(50, min(1000, absint($input['qr-code-size'] ?? 200))),
            'qr-code-margin' => max(0, min(20, absint($input['qr-code-margin'] ?? 4))),
            'qr-code-dark' => preg_replace('/[^a-fA-F0-9]/', '', sanitize_text_field($input['qr-code-dark'] ?? '000000')),
            'qr-code-light' => preg_replace('/[^a-fA-F0-9]/', '', sanitize_text_field($input['qr-code-light'] ?? 'ffffff')),
            'qr-code-logo-id' => $logo_id,
            'qr-code-logo-url' => $logo_url,
            'qr-code-logo-size' => max(10, min(100, absint($input['qr-code-logo-size'] ?? 20))),
            'qr-code-font-size' => max(6, min(100, absint($input['qr-code-font-size'] ?? 12))),
            'qr-code-position' => in_array($input['qr-code-position'] ?? 'right', ['left', 'right'], true) ? sanitize_text_field($input['qr-code-position']) : 'right',
        ];
    }

    public function ajax_preview()
    {

        if (!check_ajax_referer('cb_qr_code_ajax_nonce', 'security', false)) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'cb-qr-code')]);
            return;
        }


        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Insufficient permissions.', 'cb-qr-code')]);
            return;
        }
        


        $input = $this->sanitize_preview_input($_POST);
        
        $label = sanitize_text_field($input['qr-code-label'] ?? 'Scan Me');
        $size = max(50, min(1000, absint($input['qr-code-size'] ?? 150)));
        $margin = max(0, min(20, absint($input['qr-code-margin'] ?? 2)));
        $dark = preg_replace('/[^a-fA-F0-9]/', '', $input['qr-code-dark'] ?? '000000');
        $light = preg_replace('/[^a-fA-F0-9]/', '', $input['qr-code-light'] ?? 'ffffff');
        $logo_id = absint($input['qr-code-logo-id'] ?? 0);
        $logo_url = esc_url_raw($input['qr-code-logo-url'] ?? '');
        $logo_size = max(10, min(100, absint($input['qr-code-logo-size'] ?? 50)));
        $font_size = max(6, min(100, absint($input['qr-code-font-size'] ?? 12)));
        
        $foreground = QRGenerator::hex_to_rgb($dark);
        $background = QRGenerator::hex_to_rgb($light);
        

        $logo_path = '';
        if (!empty($logo_id)) {
            $logo_path = QRGenerator::get_logo_path_from_attachment($logo_id);
        } elseif (!empty($logo_url)) {
            $logo_path = QRGenerator::download_logo($logo_url);
        }
        
        $qr_data_uri = QRGenerator::generate(esc_url(site_url()), [
            'size' => $size,
            'margin' => $margin,
            'foreground' => $foreground,
            'background' => $background,
            'logo' => $logo_path,
            'logo_size' => $logo_size,
        ]);
        
        if (empty($qr_data_uri)) {
            wp_send_json_error(['message' => esc_html__('Failed to generate QR code', 'cb-qr-code')]);
            return;
        }
        
        $html = sprintf(
            '<div class="cb-qr-label" style="font-size: %dpx; font-weight:bold; margin-bottom: 10px;">%s</div><img src="%s" alt="QR Code Preview" style="max-width: 100%%; height: auto;">',
            $font_size,
            esc_html($label),
            esc_attr($qr_data_uri)  
        );
        
        wp_send_json_success(['html' => $html]);
    }
}
