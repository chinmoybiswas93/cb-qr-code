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

use ChinmoyBiswas\CBQRCode\App\Hooks\Handlers\AdminAssetHandler;
use ChinmoyBiswas\CBQRCode\App\Hooks\Handlers\FrontendHandler;
use ChinmoyBiswas\CBQRCode\App\Hooks\Handlers\MenuHandler;
use ChinmoyBiswas\CBQRCode\App\Hooks\Handlers\PreviewAjaxHandler;
use ChinmoyBiswas\CBQRCode\App\Hooks\Handlers\SettingsAjaxHandler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Admin vs frontend, as in 1.0.0: on plugins_loaded, admin requests (including
 * admin-ajax.php) get the admin handlers, everything else gets the frontend.
 * Handlers register in the order the old Admin constructor added its hooks.
 *
 * cbqrcode_init_plugin() keeps its 1.0.0 name and priority, so code that
 * remove_action()s it still works.
 */
function cbqrcode_init_plugin()
{
    if (is_admin()) {
        ( new MenuHandler() )->register();
        ( new AdminAssetHandler() )->register();
        ( new SettingsAjaxHandler() )->register();
        ( new PreviewAjaxHandler() )->register();
    } else {
        ( new FrontendHandler() )->register();
    }
}
add_action('plugins_loaded', 'cbqrcode_init_plugin');
