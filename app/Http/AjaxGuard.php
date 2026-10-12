<?php
/**
 * Security gate for the plugin's admin-ajax handlers.
 *
 * @package CBQRCode
 */

namespace ChinmoyBiswas\CBQRCode\App\Http;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The first two steps of the input pattern (nonce, then capability) in one
 * place, so every handler runs them identically. Omnio's Policy idea, for
 * admin-ajax rather than REST.
 *
 * The two handlers have always answered failures in different JSON shapes, and
 * admin-script.js reads each one differently, so the caller picks the shape:
 *
 *     'errors'  → { success: false, data: { errors: [ message ] } }   (save)
 *     'message' → { success: false, data: { message: message } }      (preview)
 */
class AjaxGuard
{
    /**
     * Nonce action, sent by admin-script.js as CBQRCodeAjax.nonce.
     */
    const NONCE_ACTION = 'cbqrcode_ajax_nonce';

    /**
     * POST field the nonce arrives in.
     */
    const NONCE_FIELD = 'security';

    /**
     * Capability every handler requires.
     */
    const CAPABILITY = 'manage_options';

    /**
     * Verify the nonce, then the capability. On failure, sends the JSON error
     * (which ends the request) and returns false.
     *
     * @param string $shape 'errors' or 'message' — see the class comment.
     * @return bool True when the request may proceed.
     */
    public static function verify( $shape )
    {
        if ( ! check_ajax_referer( self::NONCE_ACTION, self::NONCE_FIELD, false ) ) {
            self::fail( $shape, esc_html__( 'Security check failed.', 'cb-qr-code' ) );
            return false;
        }

        if ( ! current_user_can( self::CAPABILITY ) ) {
            self::fail( $shape, esc_html__( 'Insufficient permissions.', 'cb-qr-code' ) );
            return false;
        }

        return true;
    }

    /**
     * Send the failure in the caller's shape.
     *
     * @param string $shape   'errors' or 'message'.
     * @param string $message Already-escaped message.
     * @return void
     */
    private static function fail( $shape, $message )
    {
        if ( 'errors' === $shape ) {
            wp_send_json_error( [ 'errors' => [ $message ] ] );
        } else {
            wp_send_json_error( [ 'message' => $message ] );
        }
    }
}
