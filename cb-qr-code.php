<?php
/*
Plugin Name: CB QR Code 
Description: A simple QR code plugin which shows a QR Code in pages for sharing the links easily.
Version: 1.0.0
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

function cb_qr_code_load_textdomain()
{
    load_plugin_textdomain('cb-qr-code', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'cb_qr_code_load_textdomain');

spl_autoload_register(function ($class) {
    $prefix = 'CBQRCode\\';
    if (strpos($class, $prefix) === 0) {
        $file = CB_QR_CODE_PATH . 'includes/class-' . strtolower(str_replace($prefix, '', $class)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

function cb_qr_code_init_plugin()
{
    if (is_admin()) {
        CBQRCode\Admin::get_instance();
    } else {
        CBQRCode\Frontend::get_instance();
    }
}
add_action('plugins_loaded', 'cb_qr_code_init_plugin');