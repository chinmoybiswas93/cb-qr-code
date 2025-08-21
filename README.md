# CB QR Code

**Contributors:** chinmoybiswas93  
**Tags:** qr code, sharing, shortcode, post, page  
**Requires at least:** 6.0  
**Tested up to:** 6.8
**Stable tag:** 1.0.2  
**License:** GPL-2.0+  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

A simple plugin to display a QR code on your WordPress posts, pages, or custom post types for easy link sharing.

---

## Description

CB QR Code lets you add a customizable QR code to your WordPress content. Visitors can scan or click the QR code to quickly copy the link, making sharing your content effortless. Now with local QR generation for better performance and WordPress.org compliance.

---

## Installation

1. Upload the plugin files to the `/wp-content/plugins/cb-qr-code` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **CB QR Code** in your WordPress admin menu to configure settings.

---

## Usage

- Select which post types should display the QR code.
- Customize the QR code appearance (size, margin, colors, label, logo, position).
- Choose between permalink or custom URL for the QR code.
- The QR code will automatically appear on the selected post types in the frontend.
- Admin tabs remember your last selected tab for better user experience.

---

## Key Features

- **Local QR Generation:** Uses endroid/qr-code library for local generation (no external dependencies)
- Display QR codes on posts, pages, or custom post types (Media post type excluded)
- Choose between permalink or custom URL for the QR code
- Customize QR code size, margin, colors, label, and logo
- Live preview in the Appearance tab with AJAX updates
- Click QR code to copy the link (with visual feedback)
- Persistent admin tabs (remembers last active tab)
- Modern PSR-4 autoloading with Composer
- Developer hooks to add content before/after the QR code
- WordPress.org compliant (no external API calls)

---

## Screenshots

1. **Admin Settings Panel:**  
   [Admin Settings Panel](https://prnt.sc/Dw2yKtR232mW)
2. **Appearance Customization:**  
   [Appearance Customization](https://prnt.sc/n1OeXAkSpPBj)
2. **Appearance Customization:**  
   [Front End Appearance ](https://prnt.sc/sqqiv3tPF2xR)

---

## Changelog

### 1.0.2
* **NEW:** Local QR code generation using endroid/qr-code library (WordPress.org compliant)
* **NEW:** Persistent admin tabs - remembers last active tab after page reload
* **IMPROVED:** Replaced custom autoloader with modern PSR-4 Composer autoloading
* **IMPROVED:** Enhanced admin preview with real-time AJAX updates
* **REMOVED:** Media post type from available options (better UX)
* **REMOVED:** External API dependency for QR generation
* **FIXED:** Data URI handling for QR code images
* **OPTIMIZED:** Cleaned up debug lines and comments for production

### 1.0.1
* Updated About and Support

### 1.0.0
* Initial release

---

## License

This plugin is licensed under the GPLv2 or later.  
See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

---

## Upgrade Notice

### 1.0.2
Major update with local QR generation, persistent admin tabs, and improved performance. No external dependencies required.

### 1.0.0
First release.

---

## Author

Developed by [Chinmoy Biswas](https://github.com/chinmoybiswas93)
