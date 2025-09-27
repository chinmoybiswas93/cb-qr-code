<?php
namespace ChinmoyBiswas\CBQRCode;
if ( ! defined( 'ABSPATH' ) ) exit;
function cbqrcode_get_current_settings() {
    return get_option('cbqrcode_settings', []);
}
function cbqrcode_get_allowed_post_types() {
    $post_types = get_option('cbqrcode_post_types', ['post', 'page']);
    $post_types = array_diff($post_types, ['attachment']);
    return array_values($post_types);
}
function cbqrcode_get_field_definitions() {
    return [
        'cbqrcode-url-mode' => ['required' => true],
        'cbqrcode-custom-url' => ['required' => false, 'depends_on' => ['cbqrcode-url-mode' => 'custom']],
        'cbqrcode-post-types' => ['required' => true, 'validation' => 'post_types'],
        'qr-code-label' => ['required' => true],
        'qr-code-dark' => ['required' => true, 'validation' => 'hex_color'],
        'qr-code-light' => ['required' => true, 'validation' => 'hex_color'],
        'qr-code-logo-id' => ['required' => false, 'validation' => 'attachment']
    ];
}
function cbqrcode_validate_fields($field_names, $sanitized_data) {
    $definitions = cbqrcode_get_field_definitions();
    $errors = [];
    
    foreach ($field_names as $field_name) {
        if (!isset($definitions[$field_name])) {
            continue;
        }
        
        $config = $definitions[$field_name];
        $value = $sanitized_data[$field_name] ?? '';
        
        if (!empty($config['required']) && empty($value)) {
            $errors[] = sprintf(
                /* translators: %s is the field name */
                esc_html__('%s is required.', 'cb-qr-code'),
                esc_html(str_replace(['-', '_'], ' ', $field_name))
            );
            continue;
        }
        
        if (empty($value) && empty($config['required'])) {
            continue;
        }
        
        $validation = $config['validation'] ?? '';
        switch ($validation) {
            case 'post_types':
                if (!is_array($value) || empty($value)) {
                    $errors[] = esc_html__('Please select at least one post type.', 'cb-qr-code');
                } else {
                    $valid_post_types = get_post_types(['public' => true], 'names');
                    foreach ($value as $post_type) {
                        if (!in_array($post_type, $valid_post_types, true)) {
                            $errors[] = sprintf(
                                /* translators: %s is the invalid post type name */
                                esc_html__('Invalid post type: %s', 'cb-qr-code'),
                                esc_html($post_type)
                            );
                        }
                    }
                }
                break;
                
            case 'attachment':
                if (!empty($value)) {
                    if (!wp_attachment_is_image($value)) {
                        $errors[] = esc_html__('Logo must be a valid image attachment.', 'cb-qr-code');
                    } elseif (!QRGenerator::is_supported_image_format($value)) {
                        $format_name = QRGenerator::get_image_format_name($value);
                        $errors[] = sprintf(
                            /* translators: %s is the unsupported image format */
                            esc_html__('Logo format "%s" is not supported. Please use JPEG, PNG, GIF, or WebP format.', 'cb-qr-code'),
                            esc_html($format_name ?: 'unknown')
                        );
                    }
                }
                break;
                
            case 'hex_color':
                if (!empty($value)) {
                    // Remove # prefix if present for validation
                    $clean_hex = ltrim($value, '#');
                    
                    // Check if it's valid hex (3 or 6 characters, only 0-9, a-f, A-F)
                    if (!preg_match('/^[a-fA-F0-9]{3}$|^[a-fA-F0-9]{6}$/', $clean_hex)) {
                        $errors[] = sprintf(
                            /* translators: %s is the field name */
                            esc_html__('%s must be a valid hex color (e.g., 000000, ffffff, or f00).', 'cb-qr-code'),
                            esc_html(str_replace(['-', '_'], ' ', $field_name))
                        );
                    }
                }
                break;
        }
        
        if (!empty($config['depends_on']) && is_array($config['depends_on'])) {
            foreach ($config['depends_on'] as $depend_field => $depend_value) {
                if (($sanitized_data[$depend_field] ?? '') === $depend_value && empty($value)) {
                    $errors[] = sprintf(
                        /* translators: %s is the field name */
                        esc_html__('%s is required when using custom URL mode.', 'cb-qr-code'),
                        esc_html(str_replace(['-', '_'], ' ', $field_name))
                    );
                }
            }
        }
        
        if ($field_name === 'cbqrcode-custom-url' && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $errors[] = esc_html__('Please enter a valid URL.', 'cb-qr-code');
        }
    }
    
    return $errors;
}


