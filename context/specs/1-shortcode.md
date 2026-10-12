# 1 — Shortcode + shared renderer

| Tier | Depends on | Size |
|---|---|---|
| Must-have | — (foundation for 2, 3, 4, 13, 15, 16) | M |

## Goal

Let users put a QR code anywhere with `[cbqrcode]`, using the global Appearance settings unless an
attribute overrides them. Also fix the `shortcode` tag on the WordPress.org listing: that tag is
there today, but the plugin has no shortcode.

## Why

- All five competitors offer a shortcode. CB QR Code is the only one that can't be placed by hand.
- Support threads ask for it: "how to insert this qr code in … elementor", "QR code isn't show on
  some popup", "Using Shortcode Inside Another Plugin" (Kaya).

## How competitors do it

- **Kaya** has `[kaya_qrcode]` (any content) and `[kaya_qrcode_dynamic]` (current page). Both call
  one display function, `wpkqcg_doDisplayQRCode()`, which the block and widget also reuse.
- **Composer** has five separate shortcodes, one per content type. It's harder to learn, and we
  shouldn't copy it.
- **Qyrr** has `[qyrr code="ID"]`, which points to a saved QR post.

## Plan

1. **Move the HTML building out of `Frontend::cbqrcode_append_qr_code()` into one shared renderer**,
   e.g. `Renderer::render( $args )`. The automatic QR, the shortcode, and later the block (2), the
   widget (13) and WooCommerce (15) all call it. This is Kaya's single-function pattern.
2. **Add one defaults source**, `cbqrcode_get_defaults()`. This also closes issue #6, where
   defaults disagree and fresh installs render `12pxpx`.
3. **Register `[cbqrcode]` in its own class** that loads on the frontend. Supported attributes:
   - `url` (default: the current post's permalink)
   - `size`, `margin`, `dark`, `light`
   - `label`
   - `align` (`left`/`center`/`right`)
   - `logo` (`yes`/`no` toggles the global logo)
4. **Sanitize every attribute** the same way the settings path does (see the table in
   `code-standards.md`). Clamp numbers to the generator's ranges. Shortcode attributes come from
   any post author, so treat them like user input.
5. **Load the frontend CSS/JS when the shortcode renders**, not only on allowed post types. This
   lines up with issue #10.

The plugin loads `Frontend` only when `is_admin()` is false. The shortcode lives on that side, so
it renders on the frontend and in REST requests.

## Out of scope

Content types other than URL (that's 11) and placeholders (that's 16).

## Contracts added

- The shortcode tag `cbqrcode`.
- Its attribute names. They're public API from day one.

## Test

- Standard steps from the [README](README.md).
- Use `[cbqrcode]` with no attributes in a post: it encodes that post's permalink.
- Use it in a page that isn't an allowed post type: it still renders.
- Use it in a text widget.
- Try `url="https://example.com/?a=1&b=2"`. The scanned result must contain `&`, not `&#038;`.
- Try `size="5000"`: the size is clamped. Try `dark="zzz"`: falls back to the default.
- Try `label="<script>"`: the label is escaped.
- Check that the automatic QR below content looks exactly the same as before the refactor
  (compare a screenshot).

## Done when

- The shortcode works with every attribute.
- The automatic QR goes through the same renderer.
- The readme documents the shortcode.
- The Contracts table in `agent.md` is updated.
