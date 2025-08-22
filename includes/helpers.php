<?php
namespace ChinmoyBiswas\CBQRCode;

if ( ! defined( 'ABSPATH' ) ) exit; 

function get_current_settings() {
    return get_option('cbqrcode_settings', []);
}
function get_allowed_post_types() {
    $post_types = get_option('cbqrcode_post_types', ['post', 'page']);
    $post_types = array_diff($post_types, ['attachment']);
    return array_values($post_types);
}
