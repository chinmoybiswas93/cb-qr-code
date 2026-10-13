<?php
/**
 * Frontend output: assets and the QR block after post content.
 *
 * @package CBQRCode
 */

namespace ChinmoyBiswas\CBQRCode\App\Hooks\Handlers;

use ChinmoyBiswas\CBQRCode\App\Services\QRGenerator;
use ChinmoyBiswas\CBQRCode\App\Services\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Appends the QR code to the main post's content on singular views of allowed
 * post types. Moved from Frontend.
 */
class FrontendHandler
{
    /**
     * Register hooks.
     *
     * @return void
     */
    public function register()
    {
        add_action('wp_enqueue_scripts', [$this, 'cbqrcode_enqueue_scripts']);
        add_filter('the_content', [$this, 'cbqrcode_append_qr_code']);
    }

    public function cbqrcode_enqueue_scripts()
    {
        wp_enqueue_style('cbqrcode-frontend-style', CBQRCODE_PLUGIN_URL . 'assets/css/style.css', [], defined('CBQRCODE_PLUGIN_VERSION') ? CBQRCODE_PLUGIN_VERSION : time(), 'all');
        wp_enqueue_script('cbqrcode-frontend-script', CBQRCODE_PLUGIN_URL . 'assets/js/script.js', ['jquery'], defined('CBQRCODE_PLUGIN_VERSION') ? CBQRCODE_PLUGIN_VERSION : time(), true);
    }
    public function cbqrcode_append_qr_code($content)
    {
        // Only the main post's own content (issue #3). the_content also runs for other
        // posts on the page (a Query Loop on a single post, related-post blocks) and
        // outside the loop (SEO plugins building descriptions); in block themes the
        // whole template renders inside the main loop, so the post ID check is what
        // tells a Query Loop item from the main post.
        $post_id = get_the_ID();
        if (!is_singular() || !in_the_loop() || !is_main_query() || $post_id !== get_queried_object_id())
            return $content;

        $post_type = get_post_type($post_id);

        $allowed_post_types = Settings::cbqrcode_get_allowed_post_types();

        if (!in_array($post_type, $allowed_post_types))
            return $content;

        // The public filters keep getting the raw stored option, as in 1.0.0;
        // rendering uses it merged over the defaults and clamped (issues #5, #6).
        $settings = Settings::cbqrcode_get_current_settings();
        $resolved = Settings::cbqrcode_get_resolved_settings();
        $custom_url = Settings::cbqrcode_get_custom_url();
        $qr_url_text = $custom_url !== '' ? $custom_url : get_permalink($post_id);
        $size = $resolved['qr-code-size'];
        $margin = $resolved['qr-code-margin'];
        $dark = $resolved['qr-code-dark'];
        $light = $resolved['qr-code-light'];
        $label = $resolved['qr-code-label'];
        $logo_id = $resolved['qr-code-logo-id'];
        $logo_size = $resolved['qr-code-logo-size'];
        $fontSize = $resolved['qr-code-font-size'];
        $position = $resolved['qr-code-position'];

        $foreground = QRGenerator::hex_to_rgb($dark);
        $background = QRGenerator::hex_to_rgb($light);

        $logo_path = '';
        if (!empty($logo_id)) {
            $logo_path = QRGenerator::get_logo_path_from_attachment($logo_id);
        }

        $qr_data_uri = QRGenerator::generate($qr_url_text, [
            'size' => $size,
            'margin' => $margin,
            'foreground' => $foreground,
            'background' => $background,
            'logo' => $logo_path,
            'logo_size' => $logo_size
        ]);

        if (empty($qr_data_uri)) {
            return $content; 
        }

        $before_qr = apply_filters('cbqrcode_before_qr_code', '', $post_type, $settings);
        $after_qr = apply_filters('cbqrcode_after_qr_code', '', $post_type, $settings);

        $position_class = ($position == 'left') ? 'cbqrcode-left' : 'cbqrcode-right';
        $qr_html = sprintf(
            '<div class="cbqrcode-qr %s" data-url="%s"><div>%s</div><div class="cbqrcode-label" style="font-size:%dpx">%s</div><img src="%s" alt="%s" style="width: %dpx; height: %dpx;"><div>%s</div></div>',
            esc_attr($position_class),
            esc_url($qr_url_text),
            wp_kses_post($before_qr),
            absint($fontSize),
            esc_html($label),
            esc_attr($qr_data_uri), 
            esc_attr(__('QR Code', 'cb-qr-code')),
            absint($size),
            absint($size),
            wp_kses_post($after_qr)
        );
        return $content . $qr_html;
    }
}
