<?php
/**
 * Settings access and validation.
 *
 * Moved from includes/helpers.php: each namespaced function is now a static
 * method of the same name. The old functions stay available as wrappers
 * (boot/compat.php, spec 0 PR C).
 *
 * @package CBQRCode
 */

namespace ChinmoyBiswas\CBQRCode\App\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Settings
{
    public static function cbqrcode_get_current_settings() {
        return get_option('cbqrcode_settings', []);
    }

    /**
     * The one source of setting defaults. Before 1.0.1 each reader had its own
     * (size was 120, 150 or 300; font size '12px' rendered as 12pxpx — issue #6).
     * These are the values a fresh install has always rendered on the frontend.
     *
     * @return array<string, mixed>
     */
    public static function cbqrcode_get_defaults() {
        return [
            'cbqrcode-url-mode' => 'permalink',
            'cbqrcode-custom-url' => '',
            'qr-code-label' => __('Scan Me', 'cb-qr-code'),
            'qr-code-size' => 120,
            'qr-code-margin' => 2,
            'qr-code-dark' => '000000',
            'qr-code-light' => 'ffffff',
            'qr-code-logo-id' => 0,
            'qr-code-logo-url' => '',
            'qr-code-logo-size' => 50,
            'qr-code-font-size' => 12,
            'qr-code-position' => 'right',
        ];
    }

    /**
     * Inclusive [min, max] for each numeric appearance setting — the generator's
     * clamps (size, margin, logo size) and the form's min/max (font size).
     *
     * @return array<string, int[]>
     */
    public static function cbqrcode_get_number_ranges() {
        return [
            'qr-code-size' => [50, 1000],
            'qr-code-margin' => [0, 20],
            'qr-code-logo-size' => [10, 100],
            'qr-code-font-size' => [6, 100],
        ];
    }

    /**
     * A numeric appearance value clamped to its range; the default when the value
     * is empty or not a number (a cleared field posts '').
     *
     * @param string $key   A key of cbqrcode_get_number_ranges().
     * @param mixed  $value Raw or stored value.
     * @return int
     */
    public static function cbqrcode_sanitize_number($key, $value) {
        $defaults = self::cbqrcode_get_defaults();
        if (!is_scalar($value) || !is_numeric(trim((string) $value))) {
            return (int) $defaults[$key];
        }
        list($min, $max) = self::cbqrcode_get_number_ranges()[$key];
        return max($min, min($max, (int) $value));
    }

    /**
     * 'left' or 'right'; anything else is the default.
     *
     * @param mixed $value Raw or stored value.
     * @return string
     */
    public static function cbqrcode_sanitize_position($value) {
        $value = is_string($value) ? sanitize_key($value) : '';
        return in_array($value, ['left', 'right'], true) ? $value : self::cbqrcode_get_defaults()['qr-code-position'];
    }

    /**
     * Stored settings over the defaults, with numbers and position normalized, for
     * rendering. Values saved before 1.0.1 were never range-checked (issue #5), so
     * the clamps run on read as well as on save. Use cbqrcode_get_current_settings()
     * for the raw option.
     *
     * @return array<string, mixed>
     */
    public static function cbqrcode_get_resolved_settings() {
        $stored = self::cbqrcode_get_current_settings();
        $settings = array_merge(self::cbqrcode_get_defaults(), is_array($stored) ? $stored : []);
        foreach (array_keys(self::cbqrcode_get_number_ranges()) as $key) {
            $settings[$key] = self::cbqrcode_sanitize_number($key, $settings[$key]);
        }
        $settings['qr-code-position'] = self::cbqrcode_sanitize_position($settings['qr-code-position']);
        return $settings;
    }

    public static function cbqrcode_get_allowed_post_types() {
        $post_types = get_option('cbqrcode_post_types', ['post', 'page']);
        $post_types = array_diff($post_types, ['attachment']);
        return array_values($post_types);
    }

    /**
     * The custom URL to encode, or '' when permalink mode is on or the stored URL
     * isn't http(s). Values saved before 1.0.1 were never scheme-checked (issue #7),
     * so the check runs on read as well as on save.
     *
     * @return string Raw URL, not HTML-escaped.
     */
    public static function cbqrcode_get_custom_url() {
        $settings = self::cbqrcode_get_current_settings();
        if (($settings['cbqrcode-url-mode'] ?? 'permalink') !== 'custom') {
            return '';
        }
        $custom_url = $settings['cbqrcode-custom-url'] ?? '';
        return self::cbqrcode_is_http_url($custom_url) ? $custom_url : '';
    }

    /**
     * Whether $url is an absolute http or https URL with a host.
     *
     * FILTER_VALIDATE_URL accepted javascript: and other schemes, and rejected
     * non-ASCII paths, so the scheme and host are checked directly.
     *
     * @param mixed $url Candidate URL.
     * @return bool
     */
    public static function cbqrcode_is_http_url($url) {
        if (!is_string($url) || $url === '') {
            return false;
        }
        $parts = wp_parse_url($url);
        return is_array($parts)
            && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && !empty($parts['host']);
    }

    public static function cbqrcode_get_field_definitions() {
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
    public static function cbqrcode_validate_fields($field_names, $sanitized_data) {
        $definitions = self::cbqrcode_get_field_definitions();
        $errors = [];
    
        foreach ($field_names as $field_name) {
            if (!isset($definitions[$field_name])) {
                continue;
            }
        
            $config = $definitions[$field_name];
            $value = $sanitized_data[$field_name] ?? '';
        
            $dependency_failed = false;
            if (!empty($config['depends_on']) && is_array($config['depends_on'])) {
                foreach ($config['depends_on'] as $depend_field => $depend_value) {
                    $depend_field_value = $sanitized_data[$depend_field] ?? '';
                
                    if ($depend_field_value === $depend_value && empty($value)) {
                        $field_display_name = $field_name === 'cbqrcode-custom-url' ? 'URL' : ucfirst(str_replace(['-', '_'], ' ', $field_name));
                    
                        $error_message = sprintf(
                            /* translators: %s is the field name */
                            esc_html__('%s is required when using custom URL mode.', 'cb-qr-code'),
                            esc_html($field_display_name)
                        );
                        $errors[] = $error_message;
                        $dependency_failed = true;
                        break;
                    }
                }
            }
        
            if ($dependency_failed) {
                continue;
            }
        
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
                        $clean_hex = ltrim($value, '#');
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
        
            if ($field_name === 'cbqrcode-custom-url' && !empty($value) && !self::cbqrcode_is_http_url($value)) {
                $errors[] = esc_html__('Please enter a valid http or https URL.', 'cb-qr-code');
            }
        }
    
        return $errors;
    }
}
