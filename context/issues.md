# Open issues

Defects that need a **code fix**. Release-process gaps that are only *unverified* live in
[pre-release-checks.md](pre-release-checks.md).

Every entry below was found by reading the code on 2026-10-13, not by a failing test — there are no
unit tests. Unless an entry says otherwise it hasn't been reproduced in a browser yet; "What would
close it" says what would.

| # | Issue | What would close it |
|---|---|---|
| 4 | **QR is regenerated on every page view.** A GD PNG render (plus logo compositing) per singular request, uncached. | Cache the data URI keyed by a hash of URL + appearance settings (transient or post meta), invalidated on settings save. |
| 5 | **Appearance numbers and position are saved unvalidated.** `qr-code-size`, `-margin`, `-logo-size`, `-font-size`, `-position` have no entry in `cbqrcode_get_field_definitions()`, so `cbqrcode_validate_fields()` skips them. The generator clamps size to 1000, but `Frontend` renders `<img style="width: {size}px">` from the raw saved value, and font size goes into an inline style unbounded. | Clamp at input to the generator's ranges; whitelist `position` to `left`/`right`. |
| 6 | **Fresh-install font size renders as `12pxpx`.** `Frontend` defaults `qr-code-font-size` to `'12px'` and formats it with `font-size:%spx`. Hit whenever Appearance has never been saved — which is cb-test's current state. | Default to `12`. Better: one `cbqrcode_get_defaults()` read by Frontend, Admin and the templates — today size defaults to 120 / 150 / 300 depending on who reads it. |
| 8 | **No uninstall cleanup.** No `uninstall.php`; `cbqrcode_settings` and `cbqrcode_post_types` stay in `wp_options` forever. | `uninstall.php` with the `WP_UNINSTALL_PLUGIN` guard, deleting both options. |
| 10 | **Frontend assets load on every page.** `style.css`, `script.js` and jQuery are enqueued site-wide even where no QR renders. | Enqueue only when `is_singular()` and the post type is allowed. |
| 11 | **Small hygiene.** `add_menu_page()` titles and all JS strings (`'Select Logo Image'`, error notices) aren't translatable. `CBQRCODE_PLUGIN_VERSION` has a `time()` fallback in `Frontend` that can't trigger. | Translate / drop as they're touched. (The unused `templates/nav.php` was deleted in spec 0 PR C.) |
| 13 | **Preview responses aren't sequenced.** `admin-script.js` fires `cbqrcode_preview` on every `input` *and* `change`, and renders whichever response arrives last. Typing in one field and moving to another fires the old field's `change` alongside the new field's `input`; if the older request returns last, the preview shows stale settings. Seen 1 in 3 runs of the browser checklist on 2026-10-13. Cosmetic — saving isn't affected. | Drop responses older than the latest request (a counter), or debounce and abort the in-flight `$.ajax`. |

## Closed

| # | Issue | Closed by |
|---|---|---|
| 1 | `sanitize_text_field()` deleted the `%xx` octets of percent-encoded URLs before encoding. Reproduced: a post with a Bengali slug scanned as `https://cb-test.local/-/`. | PR A (`fix/url-handling`): the generator encodes the text as given; the custom URL is sanitized with `esc_url_raw()`. The same post now scans as its full percent-encoded permalink |
| 2 | Preview encoded `esc_url()` output, so a custom URL `?a=1&b=2` scanned as `?a=1&#038;b=2`. Reproduced in the admin preview. | PR A: the preview passes the raw URL |
| 3 | `the_content` got a QR on every call on a singular page. Reproduced on Twenty Twenty-Five with a Query Loop on a single post: the loop item got its own QR block (encoding *its* permalink — block themes set up each item's post — not the main one as first guessed). | PR A: `is_singular() && in_the_loop() && is_main_query()` **and** the filtered post is the queried post. The ID check is what matters in block themes, where the whole template renders inside the main loop. Post ID passed to `get_post_type()`/`get_permalink()` |
| 7 | Custom URL accepted any scheme `FILTER_VALIDATE_URL` passes, `javascript:` included. | PR A: `Settings::cbqrcode_is_http_url()` (http/https + host) at save, and `cbqrcode_get_custom_url()` re-checks on read, so a pre-1.0.1 stored `javascript:` URL falls back to the permalink (frontend) / site URL (preview) |
| 9 | Plugin header lacked `Requires at least` / `Requires PHP`. | PR 0 (`fix/php-floor-8-1`): header now declares `Requires at least: 6.0`, `Requires PHP: 8.1` |
| 12 | Real PHP floor was 8.1 while header/readme claimed 7.4; `composer.lock` stale; `config.platform.php` invalid. | PR 0 (`fix/php-floor-8-1`): floor 8.1 in header, `readme.txt`, `composer.json`; `composer update --lock --no-dev` resolved the same versions; `composer validate` clean |
