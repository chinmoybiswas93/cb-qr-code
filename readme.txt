=== CB QR Code ===
Contributors: chinmoy709
Donate link: https://github.com/chinmoybiswas93/cb-qr-code
Tags: qr code, sharing, shortcode, post, page
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.0.1
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple plugin to display a QR code on your WordPress posts, pages, or custom post types for easy link sharing.

== Description ==

CB QR Code lets you add a customizable QR code to your WordPress content. Visitors can scan or click the QR code to quickly copy the link, making sharing your content effortless. Now with local QR generation for better performance and WordPress.org compliance.

**Key Features:**

* Uses endroid/qr-code library for local generation (no external dependencies)
* Display QR codes on posts, pages, or custom post types (Media post type excluded)
* Choose between permalink or custom URL for the QR code
* Customize QR code size, margin, colors, label, and logo
* Live preview in the Appearance tab with AJAX updates
* Click QR code to copy the link (with visual feedback)
* Persistent admin tabs (remembers last active tab)
* Developer hooks to add content before/after the QR code

**Usage:**

* Select which post types should display the QR code
* Customize the QR code appearance (size, margin, colors, label, logo, position)
* Choose between permalink or custom URL for the QR code
* The QR code will automatically appear on the selected post types in the frontend
* Admin tabs remember your last selected tab for better user experience

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/cb-qr-code` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **CB QR Code** in your WordPress admin menu to configure settings.

== Frequently Asked Questions ==

= How do I display QR codes on my posts? =

After activating the plugin, go to CB QR Code in your admin menu. Select the post types where you want QR codes to appear, and they will automatically be displayed on the frontend.

= Can I customize the appearance of QR codes? =

Yes! You can customize the size, margin, colors, label, and even add a logo. The Appearance tab provides a live preview of your customizations.

= Does this plugin require external services? =

No, CB QR Code generates QR codes locally, ensuring better performance and compliance with WordPress.org guidelines.

= Can visitors copy the link by clicking the QR code? =

Yes, visitors can click on the QR code to copy the link to their clipboard. Visual feedback is provided to confirm the action.

== Screenshots ==

1. Appearance Customization - Customize colors, size, and appearance with live preview
2. Admin Settings Panel - Configure which post types display QR codes
3. Frontend Display - How QR codes appear on your website

== Changelog ==

= 1.0.1 =
* Fix: QR codes for posts with non-Latin (percent-encoded) permalinks pointed to a broken URL.
* Fix: the admin preview encoded `&` in custom URLs as `&#038;`.
* Fix: the QR code is added only to the main post's content, not to other posts shown on the same page (Query Loop, related posts).
* Fix: the label font size rendered as `12pxpx` on new installs.
* Fix: older preview responses could overwrite newer ones while editing.
* Security: custom URLs must be http or https.
* Security: QR size, margin, logo size and font size are clamped to their ranges; position is limited to left/right.
* Improvement: frontend CSS/JS load only where a QR code can appear.
* Improvement: admin menu titles and admin script messages are translatable.
* Improvement: deleting the plugin removes its settings.

= 1.0.0 =
* Initial release
* Local QR code generation using endroid/qr-code library
* Customizable appearance options
* Support for posts, pages, and custom post types
* Live preview functionality
* Click-to-copy link feature
* Developer hooks for extensibility

== Upgrade Notice ==

= 1.0.1 =
Fixes broken QR codes for non-Latin permalinks and duplicate QR codes in Query Loops, restricts custom URLs to http/https, and removes settings on uninstall.

= 1.0.0 =
Initial release of CB QR Code plugin with local QR generation and customizable appearance options.

== Contribute ==

If you want to contribute to this project or just report a bug, you are more than welcome. Please check repository from <a href="https://github.com/chinmoybiswas93/cb-qr-code">Github</a>.