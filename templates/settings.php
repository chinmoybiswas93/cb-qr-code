<?php
if (!function_exists('CBQRCode\\get_settings')) {
    require_once CB_QR_CODE_PATH . 'includes/helpers.php';
}
use function CBQRCode\get_settings;
use function CBQRCode\get_allowed_post_types;

$settings = get_settings();
$post_types = get_post_types(['public' => true], 'objects');
$allowed_post_types = get_allowed_post_types();
?>
<div class="qqc-form-row qqc-form-row-posttypes">
    <label for="cbqr-post-types" style="flex:1;max-width:100%;margin-right:0;">
        <?php esc_html_e('Show QR Code for these post types:', 'cb-qr-code'); ?>
    </label>
</div>
<form id="cb-qr-code-settings-form" method="post" action="#" autocomplete="off">
    <div class="qqc-form-row qqc-form-row-checkboxes">
        <?php foreach ($post_types as $pt): ?>
            <label style="display:inline-flex;align-items:center;margin-right:24px;font-size:1rem;">
                <input type="checkbox" name="cbqr-post-types[]" value="<?php echo esc_attr($pt->name); ?>" <?php echo in_array($pt->name, $allowed_post_types) ? 'checked' : ''; ?>>
                <span style="margin-left:8px;"><?php echo esc_html($pt->labels->singular_name); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <div class="qqc-form-row qqc-form-row-urlmode">
        <label for="cbqr-url-mode" style="flex:1;max-width:100%;margin-right:0;">
            <?php esc_html_e('QR Code URL Mode:', 'cb-qr-code'); ?>
        </label>
    </div>
    <div class="qqc-form-row qqc-form-row-urlmode-select">
        <label style="margin-right:16px;">
            <input type="radio" name="cbqr-url-mode" value="permalink" <?php checked(($settings['cbqr-url-mode'] ?? 'permalink'), 'permalink'); ?>>
            <?php esc_html_e('Permalink', 'cb-qr-code'); ?>
        </label>
        <label style="margin-right:16px;">
            <input type="radio" name="cbqr-url-mode" value="custom" <?php checked(($settings['cbqr-url-mode'] ?? ''), 'custom'); ?>>
            <?php esc_html_e('Custom URL', 'cb-qr-code'); ?>
        </label>
        <input type="url" id="cbqr-custom-url" name="cbqr-custom-url" placeholder="https://example.com/your-url" value="<?php echo esc_url($settings['cbqr-custom-url'] ?? ''); ?>" style="min-width:260px;<?php echo (($settings['cbqr-url-mode'] ?? 'permalink') === 'custom') ? '' : 'display:none;'; ?>">
    </div>
    <input type="hidden" name="action" value="cb_qr_code_settings">
</form>
