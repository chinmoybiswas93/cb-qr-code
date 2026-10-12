<?php
/**
 * Application bootstrap.
 *
 * Returns the closure the main plugin file invokes with __FILE__. Activation
 * and deactivation hooks belong here once the plugin has any (specs 8, 10).
 *
 * @package CBQRCode
 */

use ChinmoyBiswas\CBQRCode\Framework\Application;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return function ( $file ) {
    // Constructing the Application loads config/, points the App facade at it
    // and requires app/Hooks/actions.php.
    return new Application( $file );
};
