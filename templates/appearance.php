<?php
if ( ! defined( 'ABSPATH' ) ) exit; 

if (!function_exists('ChinmoyBiswas\\CBQRCode\\get_current_settings')) {
    require_once CBQRCODE_PLUGIN_PATH . 'includes/helpers.php';
}
use function ChinmoyBiswas\CBQRCode\get_current_settings;

$settings = get_current_settings();
?>
<div class="cbqrcode-appearance-cols">
    <form id="cbqrcode-appearance-form" method="post" action="#" autocomplete="off">
        <div class="cbqrcode-form-row">
            <label for="qr-code-label"><?php esc_html_e('QR Code Label', 'cb-qr-code'); ?></label>
            <input type="text" id="qr-code-label" name="qr-code-label"
                placeholder="<?php esc_attr_e('QR Code Label', 'cb-qr-code'); ?>"
                value="<?php echo esc_attr($settings['qr-code-label'] ?? ''); ?>">
        </div>
        <div class="cbqrcode-form-row">
            <label for="qr-code-size"><?php esc_html_e('QR Code Size (px)', 'cb-qr-code'); ?></label>
            <div class="cbqrcode-input-unit-row">
                <input type="number" id="qr-code-size" name="qr-code-size" min="50" max="1000"
                    value="<?php echo esc_attr($settings['qr-code-size'] ?? 120); ?>">
                <span class="cbqrcode-unit-indicator">px</span>
            </div>
        </div>
        <div class="cbqrcode-form-row">
            <label for="qr-code-margin"><?php esc_html_e('QR Code Margin', 'cb-qr-code'); ?></label>
            <div class="cbqrcode-input-unit-row">
                <input type="number" id="qr-code-margin" name="qr-code-margin" min="0" max="20"
                    value="<?php echo esc_attr($settings['qr-code-margin'] ?? 2); ?>">
                <span class="cbqrcode-unit-indicator">units</span>
            </div>
        </div>
        <div class="cbqrcode-form-row cbqrcode-form-row-colors">
            <label><?php esc_html_e('QR Code Colors', 'cb-qr-code'); ?></label>
            <div class="cbqrcode-color-cols">
                <div>
                    <label for="qr-code-dark"
                        class="cbqrcode-color-label"><?php esc_html_e('Dark (hex)', 'cb-qr-code'); ?></label>
                    <input type="text" id="qr-code-dark" name="qr-code-dark" placeholder="000000"
                        value="<?php echo esc_attr($settings['qr-code-dark'] ?? '000000'); ?>">
                </div>
                <div>
                    <label for="qr-code-light"
                        class="cbqrcode-color-label"><?php esc_html_e('Light (hex)', 'cb-qr-code'); ?></label>
                    <input type="text" id="qr-code-light" name="qr-code-light" placeholder="ffffff"
                        value="<?php echo esc_attr($settings['qr-code-light'] ?? 'ffffff'); ?>">
                </div>
            </div>
        </div>
        <div class="cbqrcode-form-row">
            <label for="qr-code-logo-id"><?php esc_html_e('Logo Image (optional)', 'cb-qr-code'); ?></label>
            <div class="cbqrcode-media-upload-wrapper">
                <input type="hidden" id="qr-code-logo-id" name="qr-code-logo-id" 
                    value="<?php echo esc_attr($settings['qr-code-logo-id'] ?? ''); ?>">
                <input type="hidden" id="qr-code-logo-url" name="qr-code-logo-url" 
                    value="<?php echo esc_url($settings['qr-code-logo-url'] ?? ''); ?>">
                
                <div class="cbqrcode-media-horizontal" style="display: flex; align-items: center; gap: 10px;">
                    <span class="cbqrcode-selected-image-name" style="flex: 1; color: #666; font-style: italic; min-height: 20px;">
                        <?php 
                        $logo_id = $settings['qr-code-logo-id'] ?? '';
                        if (!empty($logo_id)) {
                            $attachment = get_post($logo_id);
                            if ($attachment) {
                                $filename = get_post_meta($logo_id, '_wp_attached_file', true);
                                if ($filename) {
                                    $filename = basename($filename);
                                } else {
                                    $filename = $attachment->post_title;
                                }
                                
                                if (strlen($filename) > 25) {
                                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                                    $name_without_ext = pathinfo($filename, PATHINFO_FILENAME);
                                    if (strlen($name_without_ext) > 15) {
                                        $start = substr($name_without_ext, 0, 5);
                                        $end = substr($name_without_ext, -6);
                                        $filename = $start . '...' . $end . ($extension ? '.' . $extension : '');
                                    }
                                }
                                echo esc_html($filename);
                            } else {
                                esc_html_e('No image selected', 'cb-qr-code');
                            }
                        } else {
                            esc_html_e('No image selected', 'cb-qr-code');
                        }
                        ?>
                    </span>
                    <button type="button" class="button cbqrcode-select-media">
                        <?php esc_html_e('Select Image', 'cb-qr-code'); ?>
                    </button>
                    <button type="button" class="button cbqrcode-remove-media" style="<?php echo empty($logo_id) ? 'display:none;' : ''; ?>">
                        <?php esc_html_e('Remove Image', 'cb-qr-code'); ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="cbqrcode-form-row">
            <label for="qr-code-logo-size"><?php esc_html_e('Logo Size (%)', 'cb-qr-code'); ?></label>
            <div class="cbqrcode-input-unit-row">
                <input type="number" id="qr-code-logo-size" name="qr-code-logo-size" min="10" max="100"
                    value="<?php echo esc_attr($settings['qr-code-logo-size'] ?? 50); ?>">
                <span class="cbqrcode-unit-indicator">%</span>
            </div>
        </div>
        <div class="cbqrcode-form-row">
            <label for="qr-code-font-size"><?php esc_html_e('Label Font Size', 'cb-qr-code'); ?></label>
            <div class="cbqrcode-input-unit-row">
                <input type="number" id="qr-code-font-size" name="qr-code-font-size" min="6" max="100" placeholder="12"
                    value="<?php echo esc_attr($settings['qr-code-font-size'] ?? 12); ?>">
                <span class="cbqrcode-unit-indicator">px</span>
            </div>
        </div>
        <div class="cbqrcode-form-row">
            <label for="qr-code-position"><?php esc_html_e('Position', 'cb-qr-code'); ?></label>
            <select id="qr-code-position" name="qr-code-position">
                <option value="right" <?php selected($settings['qr-code-position'] ?? 'right', 'right'); ?>>
                    <?php esc_html_e('Right', 'cb-qr-code'); ?>
                </option>
                <option value="left" <?php selected($settings['qr-code-position'] ?? '', 'left'); ?>>
                    <?php esc_html_e('Left', 'cb-qr-code'); ?>
                </option>
            </select>
        </div>
        <input type="hidden" name="action" value="cbqrcode_save_settings">
        <input type="hidden" name="tab" value="appearance">
    </form>
    <div class="cbqrcode-appearance-preview">
        <h3><?php esc_html_e('Preview', 'cb-qr-code'); ?></h3>
        <div id="cbqrcode-preview"></div>
    </div>
</div>