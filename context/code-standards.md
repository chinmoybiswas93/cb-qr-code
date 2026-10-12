# Code standards

Where this file and `agent.md` disagree, `agent.md` wins. Where either disagrees with
`DEVELOPMENT_INSTRUCTIONS.md`, this one wins — that file has drifted (listed at the bottom).

## Every PHP file

```php
<?php
namespace ChinmoyBiswas\CBQRCode;

if ( ! defined( 'ABSPATH' ) ) exit;
```

Templates have no namespace but keep the `ABSPATH` guard and import helpers with
`use function ChinmoyBiswas\CBQRCode\…;`.

Existing style: 4-space indent, short array syntax `[]`, braces on their own line for classes and
methods, public methods prefixed `cbqrcode_`. Match it in the code you touch; don't reformat the
rest. PHP 7.4 syntax only.

## The input pattern

Every write path — today the two AJAX handlers in `Admin.php` — runs in this order, and nothing
is reordered or skipped:

1. **Nonce** — `wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ?? '' ) ), 'cbqrcode_ajax_nonce' )`
   (or `check_ajax_referer( 'cbqrcode_ajax_nonce', 'security', false )`).
2. **Capability** — `current_user_can( 'manage_options' )`.
3. **Sanitize each field individually**, from `wp_unslash()`, into a local `$sanitized_input`
   array. Never sanitize a whole `$_POST` array, never pass `$_POST` onward.
4. **Validate** the sanitized array with `cbqrcode_validate_fields( $field_names, $sanitized_input )`.
   Add rules to `cbqrcode_get_field_definitions()`, not inline.
5. **Act**, using only the sanitized array.

`wp_send_json_error()`/`wp_send_json_success()` die, so no `return` is needed after them — but the
preview handler has them, and they're harmless.

### Pick the sanitizer for what the value *is*

| Value | Sanitize | Notes |
|---|---|---|
| Plain text | `sanitize_text_field()` | Strips `%xx` octets — **never use it on a URL** |
| URL | `esc_url_raw()` | Then validate scheme (`http`/`https`) |
| Integer | `absint()` / `intval()` then clamp | Clamp to the same range the generator uses |
| Hex color | `sanitize_hex_color_no_hash()` | Stored without `#` |
| Enum (`left`/`right`, `permalink`/`custom`) | `sanitize_key()` + `in_array( …, true )` whitelist | |
| Post-type list | `array_map( 'sanitize_key', … )` + check against `get_post_types( [ 'public' => true ] )` | |

## Output

- `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` at the point of echo — templates too.
- `esc_url()` is for **HTML output only**. Data being encoded into the QR is not HTML; escaping it
  first puts `&#038;` into the code. Encode the raw (`esc_url_raw`-sanitized) URL.
- Messages sent back over AJAX are escaped server-side, because `admin-script.js` inserts them with
  string concatenation into `.html()`.
- Data URIs go into `src` via `esc_attr()` — `esc_url()` strips the `data:` scheme.

## i18n

Text domain `cb-qr-code` on every user-visible PHP string. `/* translators: */` above every
`sprintf` with a placeholder. JS strings are currently hard-coded English; if you add or touch
one, pass it through `wp_localize_script()` rather than adding more.

## Assets

Enqueue admin assets only on `toplevel_page_cbqrcode_admin_dashboard`. Version with
`CBQRCODE_PLUGIN_VERSION`. Plain files — there is no build, so what you edit is what ships.

## Comments

Explain why, not what. No commented-out code, no `TODO` markers — put the open item in `issues.md`.

## Where `DEVELOPMENT_INSTRUCTIONS.md` has drifted from the code

- Says hex colors use `sanitize_hex_color()`; the code uses `sanitize_text_field()` and validates
  with a regex in `cbqrcode_validate_fields()`.
- Says "WordPress ≥ 5.0"; the readme says *Requires at least: 6.0*.
- Suggests `phpcs --standard=WordPress`; the codebase is not WPCS-formatted and that standard will
  flag every line. Use Plugin Check's ruleset (command in `agent.md`), which is what review runs.
- Its "Security Grade A+" / "WordPress.org Security Compliant ✅" banners are claims, not checks —
  `issues.md` has real defects in the input path.
