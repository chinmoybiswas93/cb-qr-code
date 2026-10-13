<?php
/**
 * Admin page assets.
 *
 * @package CBQRCode
 */

namespace ChinmoyBiswas\CBQRCode\App\Hooks\Handlers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueues the admin CSS/JS and CBQRCodeAjax on the plugin page only. Moved from Admin.
 * URLs now come from CBQRCODE_PLUGIN_URL; plugin_dir_url(dirname(__FILE__))
 * would point into app/Hooks/ after the move.
 */
class AdminAssetHandler
{
    /**
     * Register hooks.
     *
     * @return void
     */
    public function register()
    {
        add_action('admin_enqueue_scripts', [$this, 'cbqrcode_enqueue_admin_assets']);
    }

    public function cbqrcode_enqueue_admin_assets($hook)
    {
        if ($hook !== 'toplevel_page_cbqrcode_admin_dashboard') {
            return;
        }

        wp_enqueue_style('cbqrcode-admin-style', CBQRCODE_PLUGIN_URL . 'assets/css/admin-style.css', array(), CBQRCODE_PLUGIN_VERSION);

        wp_enqueue_script('cbqrcode-admin-script', CBQRCODE_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], CBQRCODE_PLUGIN_VERSION, true);
        wp_enqueue_script('cbqrcode-admin-tabs', CBQRCODE_PLUGIN_URL . 'assets/js/admin-tabs.js', ['jquery'], CBQRCODE_PLUGIN_VERSION, true);

        // i18n: admin-script.js's strings (issue #11). The ones it inserts with .html()
        // are escaped here; the ones it sets with .text() or hands to wp.media are not.
        wp_localize_script('cbqrcode-admin-script', 'CBQRCodeAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cbqrcode_ajax_nonce'),
            'i18n' => [
                'select_logo' => __('Select Logo Image', 'cb-qr-code'),
                'use_image' => __('Use this image', 'cb-qr-code'),
                'selected_image' => __('Selected image', 'cb-qr-code'),
                'no_image' => __('No image selected', 'cb-qr-code'),
                'preview_error' => esc_html__('Error generating preview', 'cb-qr-code'),
                'dismiss' => esc_html__('Dismiss this notice.', 'cb-qr-code'),
                'error' => esc_html__('An error occurred.', 'cb-qr-code'),
                'unexpected_error' => esc_html__('An unexpected error occurred.', 'cb-qr-code'),
                'nonce_error' => esc_html__('Security check failed (invalid or expired nonce). Please reload the page and try again.', 'cb-qr-code'),
            ],
        ]);

        wp_enqueue_media();
    }
}
