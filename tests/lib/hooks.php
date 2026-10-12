<?php
/**
 * Dump every callback cb-qr-code has on the hooks it uses — hook, priority,
 * method name, accepted args — one per line, in registration order.
 *
 * Owning class names are left out on purpose, so moving a method between
 * classes compares equal while a change of order, priority or method doesn't.
 *
 *   wp eval-file tests/lib/hooks.php                                   # frontend context
 *   wp --exec='define("WP_ADMIN", true);' eval-file tests/lib/hooks.php  # admin context
 *
 * Dev tool only — excluded from the release zip by .distignore.
 */
// classes compares equal while order, priority and method do not.
global $wp_filter;
$hooks = [ 'plugins_loaded', 'admin_menu', 'admin_enqueue_scripts', 'wp_ajax_cbqrcode_save_settings', 'wp_ajax_cbqrcode_preview', 'wp_enqueue_scripts', 'the_content' ];
echo 'is_admin=' . ( is_admin() ? 'yes' : 'no' ) . "\n";
foreach ( $hooks as $hook ) {
    if ( empty( $wp_filter[ $hook ] ) ) { continue; }
    foreach ( $wp_filter[ $hook ]->callbacks as $prio => $cbs ) {
        foreach ( $cbs as $cb ) {
            $f = $cb['function'];
            if ( is_array( $f ) && is_object( $f[0] ) ) {
                $cls = get_class( $f[0] );
                if ( false === strpos( $cls, 'CBQRCode' ) ) { continue; }
                echo "$hook\t$prio\t{$f[1]}\targs={$cb['accepted_args']}\n";
            } elseif ( is_string( $f ) && 0 === strpos( $f, 'cbqrcode_' ) ) {
                echo "$hook\t$prio\t$f\targs={$cb['accepted_args']}\n";
            }
        }
    }
}
