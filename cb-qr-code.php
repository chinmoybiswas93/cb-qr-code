<?php
/*
Plugin Name: CB QR Code 
Description: A simple QR code plugin which shows a QR Code in pages for sharing the links easily.
Version: 1.0.2
Author: Chinmoy Biswas
Author URI: https://github.com/chinmoybiswas93
Text Domain: cb-qr-code
Domain Path: /languages
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CB_QR_CODE_PATH')) {
    define('CB_QR_CODE_PATH', plugin_dir_path(__FILE__));
}
if (!defined('CB_QR_CODE_URL')) {
    define('CB_QR_CODE_URL', plugin_dir_url(__FILE__));
}
if (!defined('CB_QR_CODE_VERSION')) {
    define('CB_QR_CODE_VERSION', '1.0.2');
}

if (file_exists(CB_QR_CODE_PATH . 'vendor/autoload.php')) {
    require_once CB_QR_CODE_PATH . 'vendor/autoload.php';
} else {
    add_action('admin_notices', function () {
        /* translators: Error message shown when Composer dependencies are missing */
        echo '<div class="notice notice-error"><p>' . esc_html__(
            'CB QR Code: Composer dependencies not installed. Please run "composer install" in the plugin directory.',
            'cb-qr-code'
        ) . '</p></div>';
    });
    return;
}

function cb_qr_code_init_plugin()
{
    if (is_admin()) {
        CBQRCode\Admin::get_instance();
    } else {
        CBQRCode\Frontend::get_instance();
    }
}
add_action('plugins_loaded', 'cb_qr_code_init_plugin');