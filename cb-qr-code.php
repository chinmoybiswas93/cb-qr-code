<?php
/*
Plugin Name: CB QR Code 
Description: A simple QR code plugin which shows a QR Code in pages for sharing the links easily.
Version: 1.0.1
Requires at least: 6.0
Requires PHP: 8.1
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
    define('CBQRCODE_PLUGIN_VERSION', '1.0.1');
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

// Everything else is registered from app/Hooks/actions.php.
$cbqrcode_boot = require CBQRCODE_PLUGIN_PATH . 'boot/app.php';
$cbqrcode_boot(__FILE__);
