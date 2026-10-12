<?php
/**
 * 1.0.0 names, kept working after the move to app/.
 *
 * Loaded on every request by Composer (autoload "files"). Permanent and
 * silent: no deprecation notices, nothing to migrate. Each name maps onto its
 * new home:
 *
 *   ChinmoyBiswas\CBQRCode\QRGenerator           → App\Services\QRGenerator (class_alias)
 *   ChinmoyBiswas\CBQRCode\cbqrcode_*() helpers  → App\Services\Settings::cbqrcode_*()
 *   ChinmoyBiswas\CBQRCode\Admin / Frontend      → no-op singletons, because
 *       app/Hooks/actions.php already registers every hook; letting
 *       get_instance() register them again would add each callback twice.
 *
 * @package CBQRCode
 */

namespace ChinmoyBiswas\CBQRCode;

use ChinmoyBiswas\CBQRCode\App\Services\QRGenerator as ServicesQRGenerator;
use ChinmoyBiswas\CBQRCode\App\Services\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( __NAMESPACE__ . '\\QRGenerator', false ) ) {
    class_alias( ServicesQRGenerator::class, __NAMESPACE__ . '\\QRGenerator' );
}

function cbqrcode_get_current_settings()
{
    return Settings::cbqrcode_get_current_settings();
}

function cbqrcode_get_allowed_post_types()
{
    return Settings::cbqrcode_get_allowed_post_types();
}

function cbqrcode_get_field_definitions()
{
    return Settings::cbqrcode_get_field_definitions();
}

function cbqrcode_validate_fields($field_names, $sanitized_data)
{
    return Settings::cbqrcode_validate_fields($field_names, $sanitized_data);
}

/**
 * 1.0.0's admin bootstrap class. Hooks are now registered by the admin
 * handlers in app/Hooks/Handlers/, so this does nothing.
 */
class Admin
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

/**
 * 1.0.0's frontend bootstrap class. Hooks are now registered by
 * App\Hooks\Handlers\FrontendHandler, so this does nothing.
 */
class Frontend
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
