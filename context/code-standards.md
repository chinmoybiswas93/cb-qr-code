# Code standards

Where this file and `agent.md` disagree, `agent.md` wins. Where either disagrees with
`DEVELOPMENT_INSTRUCTIONS.md`, this one wins — that file has drifted (listed at the bottom).

## Every PHP file

```php
<?php
/**
 * One-line summary.
 *
 * @package CBQRCode
 */

namespace ChinmoyBiswas\CBQRCode\App\…;   // path matches namespace: app/Hooks/Handlers → App\Hooks\Handlers

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

Views (`app/Views/`) have no namespace but keep the `ABSPATH` guard. They read only the variables
passed to `App::view()->render()` (documented in a `@var` block at the top). They don't fetch
settings themselves. After adding or moving a class, run `composer dump-autoload -o --no-dev`
and `git add -f vendor/composer/`.

Existing style: 4-space indent, short array syntax `[]`, braces on their own line for classes and
methods, public methods prefixed `cbqrcode_`. Match it in the code you touch; don't reformat the
rest. PHP 7.4 syntax only, even though the runtime floor is 8.1 (see `agent.md` → Stack).

## The input pattern

Every write path — today `SettingsAjaxHandler` and `PreviewAjaxHandler` — runs in this order, and
nothing is reordered or skipped:

1. **Nonce** and 2. **Capability**: `if ( ! AjaxGuard::verify( 'errors' | 'message' ) ) { return; }`.
   That's `check_ajax_referer( 'cbqrcode_ajax_nonce', 'security', false )`, then
   `current_user_can( 'manage_options' )`, each failing with an escaped message in the handler's JSON
   shape. Put the `phpcs:disable/enable WordPress.Security.NonceVerification.Missing` pair around
   the `$_POST` reads that follow it.
3. **Sanitize each field individually**, from `wp_unslash()`, into a local `$sanitized_input`
   array. Never sanitize a whole `$_POST` array, never pass `$_POST` onward.
4. **Validate** the sanitized array with `Settings::cbqrcode_validate_fields( $field_names, $sanitized_input )`.
   Add rules to `Settings::cbqrcode_get_field_definitions()`, not inline.
5. **Act**, using only the sanitized array.

`wp_send_json_error()`/`wp_send_json_success()` die, so no `return` is needed after them — but the
preview handler has them, and they're harmless.

### Pick the sanitizer for what the value *is*

| Value | Sanitize | Notes |
|---|---|---|
| Plain text | `sanitize_text_field()` | Strips `%xx` octets — **never use it on a URL** |
| URL | `esc_url_raw()` | Then validate scheme (`http`/`https`) |
| Integer | `Settings::cbqrcode_sanitize_number( $key, … )` | Clamps to `cbqrcode_get_number_ranges()` (the generator's ranges); empty → default |
| Hex color | `sanitize_hex_color_no_hash()` | Stored without `#` |
| Enum (`left`/`right`, `permalink`/`custom`) | `sanitize_key()` + `in_array( …, true )` whitelist | Position: `Settings::cbqrcode_sanitize_position()` |

Defaults come from `Settings::cbqrcode_get_defaults()` only — no `?? 120` fallbacks in readers.
Render from `Settings::cbqrcode_get_resolved_settings()`; the raw option
(`cbqrcode_get_current_settings()`) is what the public filters receive.
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
  with a regex in `Settings::cbqrcode_validate_fields()`.
- Its security snippet shows the 1.0.0 inline `wp_verify_nonce()`; handlers now call
  `AjaxGuard::verify()` (see *The input pattern* above).
- Says "WordPress ≥ 5.0"; the readme says *Requires at least: 6.0*.
- Suggests `phpcs --standard=WordPress`; the codebase is not WPCS-formatted and that standard will
  flag every line. Use Plugin Check's ruleset (command in `agent.md`), which is what review runs.
- Its "Security Grade A+" / "WordPress.org Security Compliant ✅" banners are claims, not checks —
  `issues.md` has real defects in the input path.
