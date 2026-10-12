<?php
/**
 * AJAX: live preview (action cbqrcode_preview).
 *
 * @package CBQRCode
 */

namespace ChinmoyBiswas\CBQRCode\App\Hooks\Handlers;

use ChinmoyBiswas\CBQRCode\App\Http\AjaxGuard;
use ChinmoyBiswas\CBQRCode\App\Services\QRGenerator;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders the Appearance preview. Moved from Admin; the nonce and capability
 * checks now run through AjaxGuard, everything after them is unchanged.
 */
class PreviewAjaxHandler
{
    /**
     * Register hooks.
     *
     * @return void
     */
    public function register()
    {
        add_action('wp_ajax_cbqrcode_preview', [$this, 'cbqrcode_handle_ajax_preview']);
    }

    public function cbqrcode_handle_ajax_preview()
    {

        if (!AjaxGuard::verify('message')) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce (then capability) verified by AjaxGuard::verify() above, which ends the request on failure.
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
        // phpcs:enable WordPress.Security.NonceVerification.Missing

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
