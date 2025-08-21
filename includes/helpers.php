<?php
namespace CBQRCode;
function get_current_settings() {
    return get_option('cb_qr_code_settings', []);
}
function get_allowed_post_types() {
    return get_option('cb_qr_code_post_types', ['post', 'page']);
}
