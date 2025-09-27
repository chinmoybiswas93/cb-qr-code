<?php
if ( ! defined( 'ABSPATH' ) ) exit; 

if (!function_exists('ChinmoyBiswas\\CBQRCode\\cbqrcode_get_current_settings')) {
    require_once CBQRCODE_PLUGIN_PATH . 'includes/helpers.php';
}
use function ChinmoyBiswas\CBQRCode\cbqrcode_get_current_settings;
use function ChinmoyBiswas\CBQRCode\cbqrcode_get_allowed_post_types;

$settings = cbqrcode_get_current_settings();
$post_types = get_post_types(['public' => true], 'objects');
unset($post_types['attachment']);
$allowed_post_types = cbqrcode_get_allowed_post_types();
?>
<div class="cbqrcode-form-row cbqrcode-form-row-posttypes">
    <label for="cbqrcode-post-types" style="flex:1;max-width:100%;margin-right:0;">
        <?php esc_html_e('Show QR Code for these post types:', 'cb-qr-code'); ?>
    </label>
</div>
<form id="cbqrcode-settings-form" method="post" action="#" autocomplete="off">
    <div class="cbqrcode-form-row cbqrcode-form-row-checkboxes">
        <?php foreach ($post_types as $pt): ?>
            <label style="display:inline-flex;align-items:center;margin-right:24px;font-size:1rem;">
                <input type="checkbox" name="cbqrcode-post-types[]" value="<?php echo esc_attr($pt->name); ?>" <?php echo in_array($pt->name, $allowed_post_types) ? 'checked' : ''; ?>>
                <span style="margin-left:8px;"><?php echo esc_html($pt->labels->singular_name); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <div class="cbqrcode-form-row cbqrcode-form-row-urlmode">
        <label for="cbqrcode-url-mode" style="flex:1;max-width:100%;margin-right:0;">
            <?php esc_html_e('QR Code URL Mode:', 'cb-qr-code'); ?>
        </label>
    </div>
    <div class="cbqrcode-form-row cbqrcode-form-row-urlmode-select">
        <label style="margin-right:16px;">
            <input type="radio" name="cbqrcode-url-mode" value="permalink" <?php checked(($settings['cbqrcode-url-mode'] ?? 'permalink'), 'permalink'); ?>>
            <?php esc_html_e('Permalink', 'cb-qr-code'); ?>
        </label>
        <label style="margin-right:16px;">
            <input type="radio" name="cbqrcode-url-mode" value="custom" <?php checked(($settings['cbqrcode-url-mode'] ?? ''), 'custom'); ?>>
            <?php esc_html_e('Custom URL', 'cb-qr-code'); ?>
        </label>
        <input type="url" id="cbqrcode-custom-url" name="cbqrcode-custom-url" placeholder="Enter custom URL" value="<?php echo esc_url($settings['cbqrcode-custom-url'] ?? ''); ?>" style="min-width:260px;<?php echo (($settings['cbqrcode-url-mode'] ?? 'permalink') === 'custom') ? '' : 'display:none;'; ?>">
    </div>
    <input type="hidden" name="action" value="cbqrcode_save_settings">
    <input type="hidden" name="tab" value="settings">
</form>
