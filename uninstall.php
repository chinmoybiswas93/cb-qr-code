<?php
/**
 * Uninstall cleanup (spec 8).
 *
 * WordPress runs this when the plugin is deleted from Plugins → Delete, not on
 * deactivation, and without loading the plugin — so the option names are
 * written out here rather than read from config/app.php. Every later spec that
 * stores data adds its cleanup to cbqrcode_uninstall_site() in the same PR.
 *
 * @package CBQRCode
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Delete everything the plugin stored on the current site.
 *
 * @return void
 */
function cbqrcode_uninstall_site()
{
    delete_option('cbqrcode_settings');
    delete_option('cbqrcode_post_types');
}

if (is_multisite()) {
    // 'number' => 0 lifts get_sites()' default limit of 100.
    foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $cbqrcode_site_id) {
        switch_to_blog($cbqrcode_site_id);
        cbqrcode_uninstall_site();
        restore_current_blog();
    }
} else {
    cbqrcode_uninstall_site();
}
