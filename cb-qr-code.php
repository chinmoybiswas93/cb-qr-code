<?php
/*
Plugin Name: CB QR Code 
Description: A simple QR code plugin which shows a QR Code in pages for sharing the links easily.
Version: 1.0.3
Author: Chinmoy Biswas
Author URI: https://github.com/chinmoybiswas93
Text Domain: cb-qr-code
Domain Path: /languages
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit;

if (!defined('CBQRCODE_PLUGIN_PATH')) {
    define('CBQRCODE_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('CBQRCODE_PLUGIN_URL')) {
    define('CBQRCODE_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('CBQRCODE_PLUGIN_VERSION')) {
    define('CBQRCODE_PLUGIN_VERSION', '1.0.3');
}

if (file_exists(CBQRCODE_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once CBQRCODE_PLUGIN_PATH . 'vendor/autoload.php';
} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>' . esc_html__(
            'CB QR Code: Composer dependencies not installed. Please run "composer install" in the plugin directory.',
            'cb-qr-code'
        ) . '</p></div>';
    });
    return;
}

function cbqrcode_init_plugin()
{
    if (is_admin()) {
        ChinmoyBiswas\CBQRCode\Admin::get_instance();
    } else {
        ChinmoyBiswas\CBQRCode\Frontend::get_instance();
    }
}
add_action('plugins_loaded', 'cbqrcode_init_plugin');
