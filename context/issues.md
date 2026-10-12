# Open issues

Defects that need a **code fix**. Release-process gaps that are only *unverified* live in
[pre-release-checks.md](pre-release-checks.md).

Every entry below was found by reading the code on 2026-10-13, not by a failing test — there are no
tests. None has been reproduced in a browser yet; "What would close it" says what would.

| # | Issue | What would close it |
|---|---|---|
| 1 | **Percent-encoded URLs are corrupted before encoding.** `QRGenerator::generate()` runs `sanitize_text_field( $text )`, which deletes every `%xx` octet (`wp-includes/formatting.php`, "Remove percent-encoded characters"). `get_permalink()` percent-encodes non-ASCII slugs, so a post at `/%e0%a6%b9…/` gets a QR pointing at `/…/` — a 404. The custom URL is saved through `sanitize_text_field()` too, so it is damaged before it is stored. | Sanitize URLs with `esc_url_raw()` at input and don't re-sanitize as text in the generator. Verify by scanning a QR for a post with a non-Latin slug. |
| 2 | **Preview encodes HTML-escaped text.** `cbqrcode_handle_ajax_preview()` passes `esc_url( $qr_url_text )` to the generator, so a custom URL with a query string is encoded with `&#038;` instead of `&`. Frontend passes the raw URL, so the two disagree. | Pass the raw (input-sanitized) URL; escape only at HTML output. |
| 3 | **QR is appended to every `the_content` call on a singular page**, not just the main post — no `in_the_loop()` / `is_main_query()` check. Blocks or widgets that render other posts' content get a QR each, all encoding the *main* post's permalink (`get_permalink()` with no ID). | Guard with `in_the_loop() && is_main_query()`, and pass the post ID explicitly. |
| 4 | **QR is regenerated on every page view.** A GD PNG render (plus logo compositing) per singular request, uncached. | Cache the data URI keyed by a hash of URL + appearance settings (transient or post meta), invalidated on settings save. |
| 5 | **Appearance numbers and position are saved unvalidated.** `qr-code-size`, `-margin`, `-logo-size`, `-font-size`, `-position` have no entry in `cbqrcode_get_field_definitions()`, so `cbqrcode_validate_fields()` skips them. The generator clamps size to 1000, but `Frontend` renders `<img style="width: {size}px">` from the raw saved value, and font size goes into an inline style unbounded. | Clamp at input to the generator's ranges; whitelist `position` to `left`/`right`. |
| 6 | **Fresh-install font size renders as `12pxpx`.** `Frontend` defaults `qr-code-font-size` to `'12px'` and formats it with `font-size:%spx`. Hit whenever Appearance has never been saved — which is cb-test's current state. | Default to `12`. Better: one `cbqrcode_get_defaults()` read by Frontend, Admin and the templates — today size defaults to 120 / 150 / 300 depending on who reads it. |
| 7 | **Custom URL scheme is not restricted.** Validation is `filter_var( FILTER_VALIDATE_URL )`, which accepts `javascript:` and other schemes; that string is then encoded into the QR. Admin-only input, so low severity, but the QR is shown to every visitor. | Allow `http`/`https` only (`wp_http_validate_url()` or an explicit scheme check). |
| 8 | **No uninstall cleanup.** No `uninstall.php`; `cbqrcode_settings` and `cbqrcode_post_types` stay in `wp_options` forever. | `uninstall.php` with the `WP_UNINSTALL_PLUGIN` guard, deleting both options. |
| 9 | **Plugin header lacks `Requires at least` and `Requires PHP`.** `readme.txt` declares 6.0 / 7.4, but WordPress reads the *header* to block activation on an unsupported install. (The readme format itself was fixed 2026-10-13 by adopting the live `readme.txt`.) | Add both header fields in 1.0.1. |
| 10 | **Frontend assets load on every page.** `style.css`, `script.js` and jQuery are enqueued site-wide even where no QR renders. | Enqueue only when `is_singular()` and the post type is allowed. |
| 11 | **Small hygiene.** `templates/nav.php` is never included. `add_menu_page()` titles and all JS strings (`'Select Logo Image'`, error notices) aren't translatable. `CBQRCODE_PLUGIN_VERSION` has a `time()` fallback in `Frontend` that can't trigger. | Delete / translate / drop as they're touched. |
