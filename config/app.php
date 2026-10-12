<?php
/**
 * Application configuration.
 *
 * Every value here is a contract (see agent.md → Contracts): stored option
 * names, AJAX actions, the nonce and the admin page slug. Changing one is a
 * breaking change, not a config tweak.
 *
 * @package CBQRCode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'name'              => 'CB QR Code',
    'slug'              => 'cb-qr-code',
    'text_domain'       => 'cb-qr-code',
    'domain_path'       => '/languages',

    // Prefix for functions, options, hooks and CSS classes.
    'prefix'            => 'cbqrcode_',

    'option_settings'   => 'cbqrcode_settings',
    'option_post_types' => 'cbqrcode_post_types',

    'ajax_save'         => 'cbqrcode_save_settings',
    'ajax_preview'      => 'cbqrcode_preview',
    'nonce_action'      => 'cbqrcode_ajax_nonce',
    'nonce_field'       => 'security',

    'admin_page_slug'   => 'cbqrcode_admin_dashboard',
    'admin_page_hook'   => 'toplevel_page_cbqrcode_admin_dashboard',
];
