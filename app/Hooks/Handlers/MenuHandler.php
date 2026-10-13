<?php
/**
 * Admin menu page and its tabbed settings screen.
 *
 * @package CBQRCode
 */

namespace ChinmoyBiswas\CBQRCode\App\Hooks\Handlers;

use ChinmoyBiswas\CBQRCode\App\App;
use ChinmoyBiswas\CBQRCode\App\Services\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds the top-level CB QR Code page and renders its tabs from app/Views/admin.
 * Moved from Admin; tabs render through App::view() instead of include.
 */
class MenuHandler
{
    /**
     * Register hooks.
     *
     * @return void
     */
    public function register()
    {
        add_action('admin_menu', [$this, 'cbqrcode_add_admin_menu']);
    }

    public function cbqrcode_add_admin_menu()
    {
        add_menu_page(
            __('CB QR Code Settings', 'cb-qr-code'),
            __('CB QR Code', 'cb-qr-code'),
            'manage_options',
            'cbqrcode_admin_dashboard',
            [$this, 'cbqrcode_settings_page'],
            'dashicons-admin-generic',
            100
        );
    }
    public function cbqrcode_settings_page()
    {
        if (!current_user_can('manage_options'))
            return;

        echo '<div class="wrap">';
        echo '<div class="cbqrcode-dashboard">';
        $view = App::view();
        $data = [
            'settings'           => Settings::cbqrcode_get_resolved_settings(),
            'post_types'         => $this->cbqrcode_get_public_post_types(),
            'allowed_post_types' => Settings::cbqrcode_get_allowed_post_types(),
        ];

        $view->render('admin.header', $data);

        $tabs = [
            'appearance' => [
                'label' => esc_html__('Appearance', 'cb-qr-code'),
                'view' => 'admin.appearance'
            ],
            'settings' => [
                'label' => esc_html__('Settings', 'cb-qr-code'),
                'view' => 'admin.settings'
            ],
            'about' => [
                'label' => esc_html__('About', 'cb-qr-code'),
                'view' => 'admin.about'
            ],
            'support' => [
                'label' => esc_html__('Support', 'cb-qr-code'),
                'view' => 'admin.support'
            ],
        ];

        echo '<div class="cbqrcode-tabs-nav" style="margin: 20px 0 1px 0;">';
        foreach ($tabs as $tab => $tab_data) {
            echo '<button type="button" data-tab="' . esc_attr($tab) . '">' . esc_html($tab_data['label']) . '</button>';
        }
        echo '</div>';

        foreach ($tabs as $tab => $tab_data) {
            if ($view->exists($tab_data['view'])) {
                echo '<div id="cbqrcode-tab-' . esc_attr($tab) . '" class="cbqrcode-tab-content" style="display:none;">';
                $view->render($tab_data['view'], $data);
                echo '</div>';
            }
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Public post types offered on the Settings tab, minus attachments.
     * Moved from templates/settings.php unchanged.
     *
     * @return array<string, \WP_Post_Type>
     */
    private function cbqrcode_get_public_post_types()
    {
        $post_types = get_post_types(['public' => true], 'objects');
        unset($post_types['attachment']);
        return $post_types;
    }
}
