<?php
/**
 * Hook registrations.
 *
 * The plugin's registration manifest: everything that wires cb-qr-code into
 * WordPress starts from this file. Required by Framework\Application, with
 * $app in scope.
 *
 * @package CBQRCode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Admin vs frontend. Unchanged from 1.0.0: on plugins_loaded, admin requests
 * (including admin-ajax.php) get Admin, everything else gets Frontend.
 *
 * cbqrcode_init_plugin() keeps its 1.0.0 name and priority, so code that
 * remove_action()s it still works.
 */
function cbqrcode_init_plugin()
{
    if (is_admin()) {
        ChinmoyBiswas\CBQRCode\Admin::get_instance();
    } else {
        ChinmoyBiswas\CBQRCode\Frontend::get_instance();
    }
}
add_action('plugins_loaded', 'cbqrcode_init_plugin');
