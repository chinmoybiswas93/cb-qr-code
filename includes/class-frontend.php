<?php
namespace CBQRCode;
if (!function_exists('CBQRCode\\get_current_settings')) {
    require_once CB_QR_CODE_PATH . 'includes/helpers.php';
}
use function CBQRCode\get_current_settings;
use function CBQRCode\get_allowed_post_types;
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
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('the_content', [$this, 'append_qr_code']);
    }
    public function enqueue_scripts()
    {
        wp_enqueue_style('cb-qr-code', CB_QR_CODE_URL . 'assets/css/style.css', [], defined('CB_QR_CODE_VERSION') ? CB_QR_CODE_VERSION : time(), 'all');
        wp_enqueue_script('cb-qr-code', CB_QR_CODE_URL . 'assets/js/script.js', ['jquery'], defined('CB_QR_CODE_VERSION') ? CB_QR_CODE_VERSION : time(), true);
    }
    public function append_qr_code($content)
    {
        if (!is_singular())
            return $content;

        $post_type = get_post_type();

        $allowed_post_types = get_allowed_post_types();

        if (!in_array($post_type, $allowed_post_types))
            return $content;

        $post_url = get_permalink();
        $settings = get_current_settings();
        $url_mode = $settings['cbqc-url-mode'] ?? 'permalink';
        $custom_url = $settings['cbqc-custom-url'] ?? '';
        $qr_url_text = $post_url;
        if ($url_mode === 'custom' && !empty($custom_url))
            $qr_url_text = $custom_url;
        $size = $settings['qr-code-size'] ?? 120;
        $margin = $settings['qr-code-margin'] ?? 2;
        $dark = $settings['qr-code-dark'] ?? '000000';
        $light = $settings['qr-code-light'] ?? 'ffffff';
        $label = $settings['qr-code-label'] ?? __('Scan Me', 'cb-qr-code');
        $logo_url = $settings['qr-code-logo-url'] ?? '';
        $logo_size = $settings['qr-code-logo-size'] ?? 50;
        $fontSize = $settings['qr-code-font-size'] ?? '12px';
        $position = $settings['qr-code-position'] ?? 'right';
        $qr_url = "https://quickchart.io/qr?";

        $params = [
            'text' => $qr_url_text,
            'size' => $size,
            'margin' => $margin,
            'dark' => $dark,
            'light' => $light,
            'format' => 'png',
        ];

        if (!empty($logo_url)) {
            $params['centerImageUrl'] = $logo_url;
            $params['centerImageSizeRatio'] = $logo_size / 100;
        }

        $before_qr = apply_filters('cb_qr_code_before', '', $post_type, $settings);
        $after_qr = apply_filters('cb_qr_code_after', '', $post_type, $settings);

        $qr_url .= http_build_query($params);
        $position_class = ($position == 'left') ? 'cb-qr-left' : 'cb-qr-right';
        $qr_html = sprintf(
            '<div class="cb-qr-code %s" data-size="%d"><div>%s</div><div class="cb-qr-label" style="font-size:%spx">%s</div><img src="%s" alt="%s" style="width: %dpx; height: %dpx;"><div>%s</div></div>',
            esc_attr($position_class),
            intval($size),
            esc_html($before_qr),
            esc_attr($fontSize),
            esc_html($label),
            esc_url($qr_url),
            esc_attr(__('QR Code', 'cb-qr-code')),
            intval($size),
            intval($size),
            esc_html($after_qr)
        );
        return $content . $qr_html;
    }
}