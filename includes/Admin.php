<?php

namespace ChinmoyBiswas\CBQRCode;

if (! defined('ABSPATH')) exit;

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
        add_action('wp_ajax_cbqrcode_preview', [$this, 'cbqrcode_handle_ajax_preview']);
    }
    public function cbqrcode_add_admin_menu()
    {
        add_menu_page(
            'CB QR Code Settings',
            'CB QR Code',
            'manage_options',
            'cbqrcode_admin_dashboard',
            [$this, 'cbqrcode_settings_page'],
            'dashicons-admin-generic',
            100
        );
    }
    public function cbqrcode_enqueue_admin_assets($hook)
    {
        if ($hook !== 'toplevel_page_cbqrcode_admin_dashboard') {
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
            $sanitized_input = [
                'cbqrcode-url-mode' => sanitize_text_field(wp_unslash($_POST['cbqrcode-url-mode'] ?? '')),
                'cbqrcode-custom-url' => sanitize_url(wp_unslash($_POST['cbqrcode-custom-url'] ?? '')),
                'cbqrcode-post-types' => isset($_POST['cbqrcode-post-types']) && is_array($_POST['cbqrcode-post-types'])
                    ? array_map('sanitize_text_field', wp_unslash($_POST['cbqrcode-post-types']))
                    : []
            ];
            $this->cbqrcode_handle_settings_tab($sanitized_input);
        } else {
            $sanitized_input = [
                'qr-code-label' => sanitize_text_field(wp_unslash($_POST['qr-code-label'] ?? '')),
                'qr-code-size' => intval(wp_unslash($_POST['qr-code-size'] ?? 150)),
                'qr-code-margin' => intval(wp_unslash($_POST['qr-code-margin'] ?? 2)),
                'qr-code-dark' => sanitize_text_field(wp_unslash($_POST['qr-code-dark'] ?? '000000')),
                'qr-code-light' => sanitize_text_field(wp_unslash($_POST['qr-code-light'] ?? 'ffffff')),
                'qr-code-logo-id' => intval(wp_unslash($_POST['qr-code-logo-id'] ?? 0)),
                'qr-code-logo-size' => intval(wp_unslash($_POST['qr-code-logo-size'] ?? 50)),
                'qr-code-font-size' => intval(wp_unslash($_POST['qr-code-font-size'] ?? 12)),
                'qr-code-position' => sanitize_text_field(wp_unslash($_POST['qr-code-position'] ?? ''))
            ];
            $this->cbqrcode_handle_appearance_tab($sanitized_input);
        }
    }
    private function cbqrcode_handle_settings_tab($input)
    {
        $field_names = ['cbqrcode-url-mode', 'cbqrcode-custom-url', 'cbqrcode-post-types'];
        $errors = cbqrcode_validate_fields($field_names, $input);

        if (!empty($errors)) {
            wp_send_json_error(['errors' => $errors]);
        }

        $url_mode = $input['cbqrcode-url-mode'] ?? 'permalink';
        $custom_url = ($url_mode === 'custom') ? ($input['cbqrcode-custom-url'] ?? '') : '';

        $post_types = [];
        if (!empty($input['cbqrcode-post-types']) && is_array($input['cbqrcode-post-types'])) {
            $valid_post_types = get_post_types(['public' => true], 'names');
            foreach ($input['cbqrcode-post-types'] as $post_type) {
                if (in_array($post_type, $valid_post_types, true) && $post_type !== 'attachment') {
                    $post_types[] = $post_type;
                }
            }
        }

        $existing_settings = get_option('cbqrcode_settings', []);
        $settings_to_save = [
            'cbqrcode-url-mode' => $url_mode,
            'cbqrcode-custom-url' => $custom_url,
        ];
        $merged_settings = array_merge($existing_settings, $settings_to_save);

        update_option('cbqrcode_settings', $merged_settings);
        update_option('cbqrcode_post_types', array_values($post_types));

        wp_send_json_success(['message' => esc_html__('Settings saved successfully.', 'cb-qr-code')]);
    }
    private function cbqrcode_handle_appearance_tab($input)
    {
        $field_names = [
            'qr-code-label',
            'qr-code-size',
            'qr-code-margin',
            'qr-code-dark',
            'qr-code-light',
            'qr-code-logo-id',
            'qr-code-logo-size',
            'qr-code-font-size',
            'qr-code-position'
        ];
        $errors = cbqrcode_validate_fields($field_names, $input);

        if (!empty($errors)) {
            wp_send_json_error(['errors' => $errors]);
        }

        $logo_id = $input['qr-code-logo-id'] ?? 0;
        $logo_url = '';
        if ($logo_id > 0) {
            $attachment_url = wp_get_attachment_url($logo_id);
            if ($attachment_url && wp_attachment_is_image($logo_id)) {
                $logo_url = $attachment_url;
            } else {
                $logo_id = 0;
            }
        }

        $settings_to_save = $input;
        $settings_to_save['qr-code-logo-id'] = $logo_id;
        $settings_to_save['qr-code-logo-url'] = $logo_url;

        $existing_settings = get_option('cbqrcode_settings', []);
        $merged_settings = array_merge($existing_settings, $settings_to_save);

        update_option('cbqrcode_settings', $merged_settings);

        wp_send_json_success(['message' => esc_html__('Settings saved successfully.', 'cb-qr-code')]);
    }
    public function cbqrcode_handle_ajax_preview()
    {

        if (!check_ajax_referer('cbqrcode_ajax_nonce', 'security', false)) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'cb-qr-code')]);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Insufficient permissions.', 'cb-qr-code')]);
            return;
        }

        $input = [
            'qr-code-label' => sanitize_text_field(wp_unslash($_POST['qr-code-label'] ?? '')),
            'qr-code-size' => intval(wp_unslash($_POST['qr-code-size'] ?? 150)),
            'qr-code-margin' => intval(wp_unslash($_POST['qr-code-margin'] ?? 2)),
            'qr-code-dark' => sanitize_text_field(wp_unslash($_POST['qr-code-dark'] ?? '000000')),
            'qr-code-light' => sanitize_text_field(wp_unslash($_POST['qr-code-light'] ?? 'ffffff')),
            'qr-code-logo-id' => intval(wp_unslash($_POST['qr-code-logo-id'] ?? 0)),
            'qr-code-logo-size' => intval(wp_unslash($_POST['qr-code-logo-size'] ?? 50)),
            'qr-code-font-size' => intval(wp_unslash($_POST['qr-code-font-size'] ?? 12))
        ];

        $current_settings = get_option('cbqrcode_settings', []);
        $url_mode = $current_settings['cbqrcode-url-mode'] ?? 'permalink';
        $custom_url = $current_settings['cbqrcode-custom-url'] ?? '';

        $qr_url_text = site_url();
        if ($url_mode === 'custom' && !empty($custom_url)) {
            $qr_url_text = $custom_url;
        }

        $label = $input['qr-code-label'] ?? 'Scan Me';
        $size = $input['qr-code-size'] ?? 150;
        $margin = $input['qr-code-margin'] ?? 2;
        $dark = $input['qr-code-dark'] ?? '000000';
        $light = $input['qr-code-light'] ?? 'ffffff';
        $logo_id = $input['qr-code-logo-id'] ?? 0;
        $logo_size = $input['qr-code-logo-size'] ?? 50;
        $font_size = $input['qr-code-font-size'] ?? 12;

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
