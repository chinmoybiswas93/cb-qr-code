# CB QR Code WordPress Plugin

Easily add customizable QR codes to your WordPress posts, pages, or custom post types. Modern, modular, and extensible for developers and site owners alike.

## Features
- **Customizable QR Codes:** Adjust size, colors, margin, label, and add a logo.
- **Copy QR Link:** Click the QR code to copy its link to the clipboard (with tooltip feedback).
- **Custom QR Link:** Use the page/post URL or use your custom link in the QR content.
- **Admin Interface:** Clean, tabbed settings panel for easy configuration.
- **Hooks for Developers:** Add content before/after the QR code with `cb_qr_code_before` and `cb_qr_code_after` hooks.
- **Live Preview:** See QR code changes instantly in the Appearance tab.
- **Translation Ready:** Includes a `.pot` file for easy localization.

## Usage
1. **Install & Activate:** Upload and activate the plugin from your WordPress admin.
2. **Configure:** Go to **WP Dashboard > CB QR Code** to adjust settings and appearance.
3. **Display:** QR codes will appear on supported post types. Use the Appearance tab to style them.
4. **Copy Link:** Click the QR code to copy its link.

## Developer Hooks
- `cb_qr_code_before` — Add content before the QR code.
- `cb_qr_code_after` — Add content after the QR code.

**Example:**
```php
add_filter('cb_qr_code_before', function ($output, $post_type, $settings) {
	$output = 'hello world';
	return $output;
}, 10, 3);
```

## Screenshots
1. **Admin Settings Panel:** [View Screenshot](https://prnt.sc/Dw2yKtR232mW)
2. **Appearance Customization:** [View Screenshot](https://prnt.sc/n1OeXAkSpPBj)
3. **Frontend QR Code Example:** [Example 1](https://prnt.sc/KB9aO2v1kN5R), [Example 2](https://prnt.sc/LNciOCAl4NB_)
 
## Translation
- Use `languages/cb-qr-code.pot` to create your own translation.

## Requirements
- WordPress 5.0+
- PHP 7.2+

## Support
For help or suggestions, open an issue or contact the plugin author.

---
**CB QR Code** — Clean, extensible QR codes for WordPress.
