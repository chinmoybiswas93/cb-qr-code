# Project overview

CB QR Code appends a QR code to the content of selected post types so visitors can scan it — or
click it to copy the link. The QR is generated **on the site's own server** with `endroid/qr-code`;
no remote service is ever called.

## Two surfaces

| Surface | Entry point | What happens |
|---|---|---|
| **Frontend** | `the_content` filter → `Frontend::cbqrcode_append_qr_code()` | On `is_singular()` for an allowed post type, builds a PNG data URI and appends `<div class="cbqrcode-qr …" data-url="…">` after the content. `script.js` copies `data-url` to the clipboard on click |
| **Admin** | Top-level menu *CB QR Code* (`manage_options`) | Four tabs: Appearance (form + live preview), Settings (post types, URL mode), About, Support. Saves and previews over `admin-ajax.php` |

## Data flow

```
Appearance/Settings form ─ajaxSave()─▶ wp_ajax_cbqrcode_save_settings
                                        nonce → cap → sanitize → cbqrcode_validate_fields()
                                        → array_merge into cbqrcode_settings
                                        (post types → cbqrcode_post_types)

Appearance input change ─generateQrCodePreview()─▶ wp_ajax_cbqrcode_preview
                                        → QRGenerator::generate( site_url() or custom URL )
                                        → JSON { html } → injected into #cbqrcode-preview

Singular page view ─▶ the_content ─▶ QRGenerator::generate( permalink or custom URL )
                                     → appended HTML, wrapped by the two public filters
```

The QR is **regenerated on every page view** — there is no cache. See `issues.md`.

## Settings

`cbqrcode_settings` is one array; its keys are the form field names. Defaults are not centralised —
each reader supplies its own fallback, and they disagree (see `issues.md`):

| Key | Meaning | Frontend default | Template default |
|---|---|---|---|
| `cbqrcode-url-mode` | `permalink` \| `custom` | `permalink` | `permalink` |
| `cbqrcode-custom-url` | Used when mode is `custom` | `''` | `''` |
| `qr-code-label` | Text above the code | `Scan Me` (translated) | `Scan Me` |
| `qr-code-size` | px, clamped 50–1000 in the generator | 120 | 120 |
| `qr-code-margin` | Quiet-zone units, clamped 0–20 | 2 | 2 |
| `qr-code-dark` / `qr-code-light` | Hex without `#` | `000000` / `ffffff` | same |
| `qr-code-logo-id` / `qr-code-logo-url` | Optional centre logo (JPEG/PNG/GIF/WebP) | 0 / `''` | same |
| `qr-code-logo-size` | Logo width, clamped 10–100 | 50 | 50 |
| `qr-code-font-size` | Label px | `'12px'` | 12 |
| `qr-code-position` | `left` \| anything else = right | `right` | `right` |

`cbqrcode_post_types` is a separate option: a list of public post-type names, `attachment` always
removed. Default `['post', 'page']`.

## What it deliberately does not do

- No remote QR API (removed in 1.0.2 for WordPress.org compliance).
- No shortcode or block, despite the `shortcode` tag in the readme — the QR is only ever appended
  via `the_content`.
- No custom tables, cron, REST routes, or uninstall cleanup (the last one is a gap, not a choice —
  see `issues.md`).
