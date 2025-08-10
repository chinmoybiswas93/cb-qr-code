<?php
if (!function_exists('CBQRCode\\cbqr_get_settings')) {
    require_once CB_QR_CODE_PATH . 'includes/helpers.php';
}
use function CBQRCode\cbqr_get_settings;

$settings = cbqr_get_settings();
?>
<div class="cbqc-appearance-cols">
    <form id="cbqc-appearance-form" method="post" action="#" autocomplete="off">
        <div class="cbqc-form-row">
            <label for="qr-code-label"><?php esc_html_e('QR Code Label', 'cb-qr-code'); ?></label>
            <input type="text" id="qr-code-label" name="qr-code-label"
                placeholder="<?php esc_attr_e('QR Code Label', 'cb-qr-code'); ?>"
                value="<?php echo esc_attr($settings['qr-code-label'] ?? ''); ?>">
        </div>
        <div class="cbqc-form-row">
            <label for="qr-code-size"><?php esc_html_e('QR Code Size (px)', 'cb-qr-code'); ?></label>
            <div class="cbqc-input-unit-row">
                <input type="number" id="qr-code-size" name="qr-code-size" min="50" max="1000"
                    value="<?php echo esc_attr($settings['qr-code-size'] ?? 120); ?>">
                <span class="cbqc-unit-indicator">px</span>
            </div>
        </div>
        <div class="cbqc-form-row">
            <label for="qr-code-margin"><?php esc_html_e('QR Code Margin', 'cb-qr-code'); ?></label>
            <div class="cbqc-input-unit-row">
                <input type="number" id="qr-code-margin" name="qr-code-margin" min="0" max="20"
                    value="<?php echo esc_attr($settings['qr-code-margin'] ?? 2); ?>">
                <span class="cbqc-unit-indicator">units</span>
            </div>
        </div>
        <div class="cbqc-form-row cbqc-form-row-colors">
            <label><?php esc_html_e('QR Code Colors', 'cb-qr-code'); ?></label>
            <div class="cbqc-color-cols">
                <div>
                    <label for="qr-code-dark"
                        class="cbqc-color-label"><?php esc_html_e('Dark (hex)', 'cb-qr-code'); ?></label>
                    <input type="text" id="qr-code-dark" name="qr-code-dark" placeholder="000000"
                        value="<?php echo esc_attr($settings['qr-code-dark'] ?? '000000'); ?>">
                </div>
                <div>
                    <label for="qr-code-light"
                        class="cbqc-color-label"><?php esc_html_e('Light (hex)', 'cb-qr-code'); ?></label>
                    <input type="text" id="qr-code-light" name="qr-code-light" placeholder="ffffff"
                        value="<?php echo esc_attr($settings['qr-code-light'] ?? 'ffffff'); ?>">
                </div>
            </div>
        </div>
        <div class="cbqc-form-row">
            <label for="qr-code-logo-url"><?php esc_html_e('Logo URL (optional)', 'cb-qr-code'); ?></label>
            <input type="url" id="qr-code-logo-url" name="qr-code-logo-url" placeholder="https://example.com/logo.png"
                value="<?php echo esc_url($settings['qr-code-logo-url'] ?? ''); ?>">
        </div>
        <div class="cbqc-form-row">
            <label for="qr-code-logo-size"><?php esc_html_e('Logo Size (%)', 'cb-qr-code'); ?></label>
            <div class="cbqc-input-unit-row">
                <input type="number" id="qr-code-logo-size" name="qr-code-logo-size" min="10" max="100"
                    value="<?php echo esc_attr($settings['qr-code-logo-size'] ?? 50); ?>">
                <span class="cbqc-unit-indicator">%</span>
            </div>
        </div>
        <div class="cbqc-form-row">
            <label for="qr-code-font-size"><?php esc_html_e('Label Font Size', 'cb-qr-code'); ?></label>
            <div class="cbqc-input-unit-row">
                <input type="number" id="qr-code-font-size" name="qr-code-font-size" min="6" max="100" placeholder="12"
                    value="<?php echo esc_attr($settings['qr-code-font-size'] ?? 12); ?>">
                <span class="cbqc-unit-indicator">px</span>
            </div>
        </div>
        <div class="cbqc-form-row">
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
        <input type="hidden" name="action" value="cb_qr_code_save_settings">
        <input type="hidden" name="tab" value="appearance">
    </form>
    <div class="cbqc-appearance-preview">
        <h3><?php esc_html_e('Preview', 'cb-qr-code'); ?></h3>
        <div id="cbqc-preview"></div>
    </div>
</div>