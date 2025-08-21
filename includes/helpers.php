<?php
namespace CBQRCode;
function get_current_settings() {
    return get_option('cb_qr_code_settings', []);
}
function get_allowed_post_types() {
    $post_types = get_option('cb_qr_code_post_types', ['post', 'page']);
    $post_types = array_diff($post_types, ['attachment']);
    return array_values($post_types);
}
