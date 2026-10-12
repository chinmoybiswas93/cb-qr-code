<?php
/**
 * AJAX: save settings (action cbqrcode_save_settings).
 *
 * @package CBQRCode
 */

namespace ChinmoyBiswas\CBQRCode\App\Hooks\Handlers;

use ChinmoyBiswas\CBQRCode\App\Http\AjaxGuard;
use ChinmoyBiswas\CBQRCode\App\Services\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Saves the Appearance or Settings tab. Moved from Admin; the nonce and capability
 * checks now run through AjaxGuard, everything after them is unchanged.
 */
class SettingsAjaxHandler
{
    /**
     * Register hooks.
     *
     * @return void
     */
    public function register()
    {
        add_action('wp_ajax_cbqrcode_save_settings', [$this, 'cbqrcode_ajax_save_settings']);
    }

    public function cbqrcode_ajax_save_settings()
    {
        if (!AjaxGuard::verify('errors')) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce (then capability) verified by AjaxGuard::verify() above, which ends the request on failure.
        $tab = sanitize_text_field(wp_unslash($_POST['tab'] ?? ''));

        if ($tab === 'settings') {
            $sanitized_input = [
                'cbqrcode-url-mode' => sanitize_text_field(wp_unslash($_POST['cbqrcode-url-mode'] ?? '')),
                'cbqrcode-custom-url' => sanitize_text_field(wp_unslash($_POST['cbqrcode-custom-url'] ?? '')),
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
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }
    private function cbqrcode_handle_settings_tab($input)
    {
        $field_names = ['cbqrcode-url-mode', 'cbqrcode-custom-url', 'cbqrcode-post-types'];
        $errors = Settings::cbqrcode_validate_fields($field_names, $input);

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
        $errors = Settings::cbqrcode_validate_fields($field_names, $input);

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
}
