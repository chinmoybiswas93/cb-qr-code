# Open issues

Defects that need a **code fix**. Release-process gaps that are only *unverified* live in
[pre-release-checks.md](pre-release-checks.md).

Every entry below was found by reading the code on 2026-10-13, not by a failing test — there are no
unit tests. Unless an entry says otherwise it hasn't been reproduced in a browser yet; "What would
close it" says what would.

| # | Issue | What would close it |
|---|---|---|
| 4 | **QR is regenerated on every page view.** A GD PNG render (plus logo compositing) per singular request, uncached. | Cache the data URI keyed by a hash of URL + appearance settings (transient or post meta), invalidated on settings save. |
| 14 | **A logo at the default 50% makes the QR unscannable.** Error correction is hard-coded to Low (`QRGenerator`), which recovers ~7% of the code; a 50%-wide logo covers far more. Found in the 1.0.1 browser pass: with attachment 104 as the logo, Chrome's `BarcodeDetector` decodes the QR at 10–30% logo size and fails at 50% — on `main` and on 1.0.1 alike, so not a regression. | Spec 7 (`auto` = High with a logo), and consider a lower default logo size (≤ 30%). Verify by decoding at each logo size |

## Closed

| # | Issue | Closed by |
|---|---|---|
| 8 | No uninstall cleanup; both options stayed in `wp_options` forever. | PR D (`feat/uninstall-cleanup`, spec 8): `uninstall.php` with the `WP_UNINSTALL_PLUGIN` guard deletes both options, per site on multisite. Checked through core's `uninstall_plugin()`: options survive deactivation and are gone after uninstall. Multisite loop not exercised (cb-test is single-site) |
| 10 | Frontend `style.css`, `script.js` and jQuery loaded on every page. | PR C (`fix/assets-admin-js`): enqueued only on a singular view whose queried post type is allowed. Checked: home, search and a non-allowed page load none; the fixture post loads both |
| 11 | Menu titles and all `admin-script.js` strings weren't translatable; dead `time()` version fallback. | PR C: `__()` on the menu titles; the 9 JS strings come from `CBQRCodeAjax.i18n` (escaped where the JS uses `.html()`); fallback removed |
| 13 | Preview responses weren't sequenced, so a slow older response could overwrite a newer one. Reproduced deterministically by delaying the first of two requests: the stale label won. | PR C: a request counter; only the latest request's response (or error) is rendered. Same reproduction now shows the newer label |
| 5 | Appearance numbers and position were saved unvalidated; the frontend rendered `width: {size}px` and the font size from the raw value. | PR B (`fix/settings-input`): `Settings::cbqrcode_sanitize_number()` clamps size 50–1000, margin 0–20, logo size 10–100, font size 6–100 at input (empty/non-numeric → default); `cbqrcode_sanitize_position()` whitelists `left`/`right`. `cbqrcode_get_resolved_settings()` repeats both on read, so pre-1.0.1 stored values render clamped. Checked: UI save of 5000/99/''/3 stored 1000/20/12/10; a forged position stored `right` |
| 6 | Fresh install rendered `font-size:12pxpx`; size defaulted to 120/150/300 depending on the reader. | PR B: one `Settings::cbqrcode_get_defaults()` read by the frontend, the preview and (via `MenuHandler`) the admin views; the label style is `%dpx`. Fresh install now renders `font-size:12px`, 120px. `QRGenerator::generate()`'s own 300/10 fallbacks stay: they're its public API for callers that pass no options |
| 1 | `sanitize_text_field()` deleted the `%xx` octets of percent-encoded URLs before encoding. Reproduced: a post with a Bengali slug scanned as `https://cb-test.local/-/`. | PR A (`fix/url-handling`): the generator encodes the text as given; the custom URL is sanitized with `esc_url_raw()`. The same post now scans as its full percent-encoded permalink |
| 2 | Preview encoded `esc_url()` output, so a custom URL `?a=1&b=2` scanned as `?a=1&#038;b=2`. Reproduced in the admin preview. | PR A: the preview passes the raw URL |
| 3 | `the_content` got a QR on every call on a singular page. Reproduced on Twenty Twenty-Five with a Query Loop on a single post: the loop item got its own QR block (encoding *its* permalink — block themes set up each item's post — not the main one as first guessed). | PR A: `is_singular() && in_the_loop() && is_main_query()` **and** the filtered post is the queried post. The ID check is what matters in block themes, where the whole template renders inside the main loop. Post ID passed to `get_post_type()`/`get_permalink()` |
| 7 | Custom URL accepted any scheme `FILTER_VALIDATE_URL` passes, `javascript:` included. | PR A: `Settings::cbqrcode_is_http_url()` (http/https + host) at save, and `cbqrcode_get_custom_url()` re-checks on read, so a pre-1.0.1 stored `javascript:` URL falls back to the permalink (frontend) / site URL (preview) |
| 9 | Plugin header lacked `Requires at least` / `Requires PHP`. | PR 0 (`fix/php-floor-8-1`): header now declares `Requires at least: 6.0`, `Requires PHP: 8.1` |
| 12 | Real PHP floor was 8.1 while header/readme claimed 7.4; `composer.lock` stale; `config.platform.php` invalid. | PR 0 (`fix/php-floor-8-1`): floor 8.1 in header, `readme.txt`, `composer.json`; `composer update --lock --no-dev` resolved the same versions; `composer validate` clean |
