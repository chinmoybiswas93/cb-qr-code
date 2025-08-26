<?php
namespace ChinmoyBiswas\CBQRCode;

if ( ! defined( 'ABSPATH' ) ) exit;

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
        add_action('admin_menu', [$this, 'cbqrcode_add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'cbqrcode_enqueue_admin_assets']);
        add_action('wp_ajax_cbqrcode_save_settings', [$this, 'cbqrcode_ajax_save_settings']);
        add_action('wp_ajax_cbqrcode_preview', [$this, 'cbqrcode_ajax_preview']);
    }
    public function cbqrcode_add_admin_menu()
    {
        add_menu_page(
            'CB QR Code Settings',
            'CB QR Code',
            'manage_options',
            'cbqrcode_admin',
            [$this, 'cbqrcode_settings_page'],
            'dashicons-admin-generic',
            100
        );
    }
    public function cbqrcode_enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_cbqrcode_admin') {
            return;
        }
        
        wp_enqueue_style('cbqrcode-admin-style', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-style.css', array(), CBQRCODE_PLUGIN_VERSION);

        wp_enqueue_script('cbqrcode-admin-script', plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin-script.js', ['jquery'], CBQRCODE_PLUGIN_VERSION, true);
        wp_enqueue_script('cbqrcode-admin-tabs', plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin-tabs.js', ['jquery'], CBQRCODE_PLUGIN_VERSION, true);
        
        wp_localize_script('cbqrcode-admin-script', 'CBQRCodeAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cbqrcode_ajax_nonce')
        ]);
        
        wp_enqueue_media();
    }

    public function cbqrcode_settings_page()
    {
        if (!current_user_can('manage_options'))
            return;

        echo '<div class="wrap">';
        echo '<div class="cbqrcode-dashboard">';
        include CBQRCODE_PLUGIN_PATH . 'templates/header.php';

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

        echo '<div class="cbqrcode-tabs-nav" style="margin: 20px 0 1px 0;">';
        foreach ($tabs as $tab => $data) {
            echo '<button type="button" data-tab="' . esc_attr($tab) . '">' . esc_html($data['label']) . '</button>';
        }
        echo '</div>';

        foreach ($tabs as $tab => $data) {
            $full_path = CBQRCODE_PLUGIN_PATH . $data['file'];
            if (file_exists($full_path)) {
                echo '<div id="cbqrcode-tab-' . esc_attr($tab) . '" class="cbqrcode-tab-content" style="display:none;">';
                include $full_path;
                echo '</div>';
            }
        }
        echo '</div>';
        echo '</div>';
    }
    public function cbqrcode_ajax_save_settings()
    {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'] ?? '')), 'cbqrcode_ajax_nonce')) {
            wp_send_json_error(['errors' => [esc_html__('Security check failed.', 'cb-qr-code')]]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['errors' => [esc_html__('Insufficient permissions.', 'cb-qr-code')]]);
        }

        $tab = sanitize_text_field(wp_unslash($_POST['tab'] ?? ''));

        if ($tab === 'settings') {
            // Only extract the required fields for settings
            $required_data = [];
            $expected_fields = ['cbqrcode-url-mode', 'cbqrcode-custom-url', 'cbqrcode-post-types'];
            
            foreach ($expected_fields as $field) {
                if (isset($_POST[$field])) {
                    $required_data[$field] = wp_unslash($_POST[$field]);
                }
            }
            
            $input = $this->cbqrcode_sanitize_settings_input($required_data);
            $this->cbqrcode_handle_settings_tab($input);
        } else {
            // Only extract the required fields for appearance
            $required_data = [];
            $appearance_fields = [
                'qr-code-label', 'qr-code-size', 'qr-code-margin', 'qr-code-dark', 
                'qr-code-light', 'qr-code-logo-id', 'qr-code-logo-size', 
                'qr-code-font-size', 'qr-code-position'
            ];
            
            foreach ($appearance_fields as $field) {
                if (isset($_POST[$field])) {
                    $required_data[$field] = wp_unslash($_POST[$field]);
                }
            }
            
            $input = $this->cbqrcode_sanitize_appearance_input($required_data);
            $this->cbqrcode_handle_appearance_tab($input);
        }
    }

    private function cbqrcode_sanitize_settings_input($required_data)
    {
        $input = array();
        
        // Process URL mode
        if (isset($required_data['cbqrcode-url-mode'])) {
            $input['cbqrcode-url-mode'] = sanitize_text_field($required_data['cbqrcode-url-mode']);
        }
        
        // Process custom URL
        if (isset($required_data['cbqrcode-custom-url'])) {
            $input['cbqrcode-custom-url'] = esc_url_raw($required_data['cbqrcode-custom-url']);
        }
        
        // Process post types
        if (isset($required_data['cbqrcode-post-types']) && is_array($required_data['cbqrcode-post-types'])) {
            $input['cbqrcode-post-types'] = array_map('sanitize_text_field', $required_data['cbqrcode-post-types']);
        }
        
        return $input;
    }

    private function cbqrcode_sanitize_appearance_input($required_data)
    {
        $input = array();
        
        foreach ($required_data as $field => $value) {
            $clean_key = sanitize_key($field);
            
            switch ($clean_key) {
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
        
        return $input;
    }

    private function cbqrcode_sanitize_preview_input($required_data)
    {
        $input = array();
        
        foreach ($required_data as $field => $value) {
            $clean_key = sanitize_key($field);
            
            switch ($clean_key) {
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
        
        return $input;
    }

    private function cbqrcode_handle_settings_tab($input)
    {
        $errors = $this->cbqrcode_validate_settings_tab($input);

        if (!empty($errors)) {
            $errors = array_map('esc_html', $errors);
            wp_send_json_error(['errors' => $errors]);
        }

        $sanitized = $this->cbqrcode_sanitize_settings_tab($input);
        
        $existing_settings = get_option('cbqrcode_settings', []);
        
        $merged_settings = array_merge($existing_settings, $sanitized['settings']);
        
        update_option('cbqrcode_settings', $merged_settings);
        update_option('cbqrcode_post_types', $sanitized['post_types']);

        wp_send_json_success(['message' => esc_html__('Settings saved successfully.', 'cb-qr-code')]);
    }
    private function cbqrcode_handle_appearance_tab($input)
    {
        $errors = $this->cbqrcode_validate_appearance_tab($input);

        if (!empty($errors)) {
            $errors = array_map('esc_html', $errors);
            wp_send_json_error(['errors' => $errors]);
        }

        $sanitized = $this->cbqrcode_sanitize_appearance_tab($input);
        
        $existing_settings = get_option('cbqrcode_settings', []);
        
        $merged_settings = array_merge($existing_settings, $sanitized);
        
        update_option('cbqrcode_settings', $merged_settings);

        wp_send_json_success(['message' => esc_html__('Settings saved successfully.', 'cb-qr-code')]);
    }

    private function cbqrcode_validate_settings_tab($input)
    {
        $errors = [];

        if (empty($input['cbqrcode-post-types']) || !is_array($input['cbqrcode-post-types'])) {
            $errors[] = esc_html__('Please select at least one post type.', 'cb-qr-code');
        } else {
            $valid_post_types = get_post_types(['public' => true], 'names');
            foreach ($input['cbqrcode-post-types'] as $post_type) {
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

        $url_mode = sanitize_text_field($input['cbqrcode-url-mode'] ?? 'permalink');
        if (!in_array($url_mode, ['permalink', 'custom'], true)) {
            $errors[] = esc_html__('Invalid URL mode selected.', 'cb-qr-code');
        }

        if ($url_mode === 'custom') {
            $custom_url = esc_url_raw($input['cbqrcode-custom-url'] ?? '');
            if (empty($custom_url) || !filter_var($custom_url, FILTER_VALIDATE_URL)) {
                $errors[] = esc_html__('Please enter a valid custom URL.', 'cb-qr-code');
            }
        }

        return $errors;
    }
    private function cbqrcode_validate_appearance_tab($input)
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
        if ($logo_id > 0) {
            if (!wp_attachment_is_image($logo_id)) {
                $errors[] = esc_html__('Logo must be a valid image attachment.', 'cb-qr-code');
            } elseif (!QRGenerator::is_supported_image_format($logo_id)) {
                $format_name = QRGenerator::get_image_format_name($logo_id);
                $errors[] = sprintf(
                    /* translators: %s is the unsupported image format */
                    esc_html__('Logo format "%s" is not supported. Please use JPEG, PNG, GIF, or WebP format.', 'cb-qr-code'),
                    esc_html($format_name ?: 'unknown')
                );
            }
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

    private function cbqrcode_sanitize_settings_tab($input)
    {
        $url_mode = sanitize_text_field($input['cbqrcode-url-mode'] ?? 'permalink');
        $custom_url = '';

        if ($url_mode === 'custom' && isset($input['cbqrcode-custom-url'])) {
            $custom_url = esc_url_raw($input['cbqrcode-custom-url']);
        }

        $post_types = [];
        if (isset($input['cbqrcode-post-types']) && is_array($input['cbqrcode-post-types'])) {

            $valid_post_types = get_post_types(['public' => true], 'names');
            foreach ($input['cbqrcode-post-types'] as $post_type) {
                $sanitized_post_type = sanitize_text_field($post_type);
                if (in_array($sanitized_post_type, $valid_post_types, true) && $sanitized_post_type !== 'attachment') {
                    $post_types[] = $sanitized_post_type;
                }
            }
        }

        return [
            'settings' => [
                'cbqrcode-url-mode' => $url_mode,
                'cbqrcode-custom-url' => $custom_url,
            ],
            'post_types' => array_values($post_types)
        ];
    }
    private function cbqrcode_sanitize_appearance_tab($input)
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

    public function cbqrcode_ajax_preview()
    {

        if (!check_ajax_referer('cbqrcode_ajax_nonce', 'security', false)) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'cb-qr-code')]);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Insufficient permissions.', 'cb-qr-code')]);
            return;
        }

        // Only extract the required fields for preview
        $required_data = [];
        $preview_fields = [
            'qr-code-label', 'qr-code-size', 'qr-code-margin', 'qr-code-dark',
            'qr-code-light', 'qr-code-logo-id', 'qr-code-logo-size', 'qr-code-font-size'
        ];
        
        foreach ($preview_fields as $field) {
            if (isset($_POST[$field])) {
                $required_data[$field] = wp_unslash($_POST[$field]);
            }
        }

        $input = $this->cbqrcode_sanitize_preview_input($required_data);
        
        $current_settings = get_option('cbqrcode_settings', []);
        $url_mode = $current_settings['cbqrcode-url-mode'] ?? 'permalink';
        $custom_url = $current_settings['cbqrcode-custom-url'] ?? '';
        
        $qr_url_text = site_url();
        if ($url_mode === 'custom' && !empty($custom_url)) {
            $qr_url_text = $custom_url;
        }
        
        $label = sanitize_text_field($input['qr-code-label'] ?? 'Scan Me');
        $size = max(50, min(1000, absint($input['qr-code-size'] ?? 150)));
        $margin = max(0, min(20, absint($input['qr-code-margin'] ?? 2)));
        $dark = preg_replace('/[^a-fA-F0-9]/', '', $input['qr-code-dark'] ?? '000000');
        $light = preg_replace('/[^a-fA-F0-9]/', '', $input['qr-code-light'] ?? 'ffffff');
        $logo_id = absint($input['qr-code-logo-id'] ?? 0);
        $logo_size = max(10, min(100, absint($input['qr-code-logo-size'] ?? 50)));
        $font_size = max(6, min(100, absint($input['qr-code-font-size'] ?? 12)));
        
        $foreground = QRGenerator::hex_to_rgb($dark);
        $background = QRGenerator::hex_to_rgb($light);
        

        $logo_path = '';
        if (!empty($logo_id)) {
            $logo_path = QRGenerator::get_logo_path_from_attachment($logo_id);
        }
        
        $qr_data_uri = QRGenerator::generate(esc_url($qr_url_text), [
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
            '<div class="cbqrcode-label" style="font-size: %dpx;">%s</div><img src="%s" alt="QR Code Preview" style="max-width: 100%%; height: auto;">',
            $font_size,
            esc_html($label),
            esc_attr($qr_data_uri)  
        );
        
        wp_send_json_success(['html' => $html]);
    }
}
