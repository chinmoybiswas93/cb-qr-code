# Progress tracker

Where the project is now. Keep this short; move finished history into the changelog in the readme.

## Phase

**v1.0.1 — live on WordPress.org since 2026-10-13** (SVN r3744468, `trunk` + `tags/1.0.1`), tagged
`v1.0.1` with a GitHub release. The zip WordPress.org serves
(`downloads.wordpress.org/plugin/cb-qr-code.1.0.1.zip`) is file-for-file identical to the one built
from `main` with `.distignore`. First release declaring `Requires PHP: 8.1`.

1.0.0 (2025-10-04) is `v1.0.0`; the repo was re-labelled to it from 1.0.3 on 2026-10-13
(1.0.1–1.0.3 had been GitHub-only labels on what became 1.0.0).

Release mechanics, learned on 1.0.1: stacked PRs merge into their base branch, not `main`, unless
the base is deleted first — check `main` before tagging. WordPress.org updates go through SVN
(`plugins.svn.wordpress.org/cb-qr-code`): replace `trunk` with the built zip's contents, `svn cp
trunk tags/<version>`, commit; the API picked it up within a minute.

## As of 2026-10-13

- Agent rules and `context/` set up (this file, `agent.md`, `CLAUDE.md`).
- PHP 7.4 lint and Plugin Check's PHPCS ruleset (`plugin-review.xml`) both pass clean.
- [issues.md](issues.md): 1.0.1 closed issues #1–#3, #5–#8, #10, #11, #13. Open: issues #4 (cache → spec 9) and
  #14 (a 50% logo is unscannable → spec 7).
- Plugin is **active** on cb-test (Kaya QR is active too — deactivate it before running the
  `tests/` harness).

## Next

The feature roadmap below. Issue #3 is already fixed, so spec 6 only adds the placement setting;
`Settings::cbqrcode_get_defaults()` (PR #7) is the defaults source spec 1 builds on. Spec 7 should
also close #14.

## Feature roadmap

Planned from competitor research on 2026-10-13 (see [specs/README.md](specs/README.md)). One spec =
one branch = one PR. Status: `todo` → `in progress` → `in review` → `done` (with PR link).

| # | Feature | Summary | Tier | Depends on | Status |
|---|---|---|---|---|---|
| 0 | [Restructure](specs/0-restructure.md) | omnio-style `boot/` + `framework/` + `app/` layout; 1.0.0 names kept via `boot/compat.php`; output byte-identical to 1.0.0 throughout | Foundation | — | done — [PR #1](https://github.com/chinmoybiswas93/cb-qr-code/pull/1) docs, [PR #2](https://github.com/chinmoybiswas93/cb-qr-code/pull/2) PHP 8.1, [PR #3](https://github.com/chinmoybiswas93/cb-qr-code/pull/3) kernel, [PR #4](https://github.com/chinmoybiswas93/cb-qr-code/pull/4) handlers, [PR #5](https://github.com/chinmoybiswas93/cb-qr-code/pull/5) compat |
| 1 | [Shortcode](specs/1-shortcode.md) | `[cbqrcode]` with attribute overrides; extract a shared renderer + one defaults source | Must | — | todo |
| 2 | [Gutenberg block](specs/2-gutenberg-block.md) | Dynamic block, no build step, ServerSideRender preview | Must | 1 | todo |
| 3 | [PNG download](specs/3-download-button.md) | Optional download button, slug-based filename | Must | 1 | todo |
| 4 | [SVG download](specs/4-svg-download.md) | endroid `SvgWriter` (already in vendor) for print-quality output | Must | 3 | todo |
| 5 | [Per-post control](specs/5-per-post-control.md) | Meta box: hide on this post / custom URL for this post | Must | 1 | todo |
| 6 | [Placement](specs/6-placement-options.md) | Before / after / both / manual; main-loop guard already landed (#3, [PR #6](https://github.com/chinmoybiswas93/cb-qr-code/pull/6)) | Must | 1 | todo |
| 7 | [Error correction](specs/7-error-correction.md) | `auto` = High with logo; fixes hard-coded Low | Must | — | todo |
| 8 | [Uninstall cleanup](specs/8-uninstall-cleanup.md) | `uninstall.php`; later specs add their data to it (closes #8) | Must | — | done — [PR #9](https://github.com/chinmoybiswas93/cb-qr-code/pull/9), released in 1.0.1 |
| 9 | [QR cache](specs/9-qr-cache.md) | Files in `uploads/cb-qr-code/`, transient fallback (closes #4) | Must | 1 | todo |
| 10 | [Dynamic QR + scans](specs/10-dynamic-qr-tracking.md) | Editable `/qr/{slug}` redirects, privacy-safe daily counts. 2 PRs | Standout | 1, 2, 9 | todo |
| 11 | [Content types](specs/11-content-types.md) | text, phone, SMS, email, WhatsApp, WiFi, vCard via `type=` | Standout | 1, 2 | todo |
| 12 | [Display conditions](specs/12-display-conditions.md) | Audience, roles, excluded IDs, hide on mobile, filter | Standout | 5, 6 | todo |
| 13 | [Widget](specs/13-widget.md) | Classic `WP_Widget` on the shared renderer | Standout | 1 | todo |
| 14 | [Transparent + label](specs/14-transparent-label.md) | Alpha background; label above/below/hidden | Standout | — | todo |
| 15 | [WooCommerce](specs/15-woocommerce.md) | Product page positions; QR in order emails (needs file URLs) | Later | 1, 6, 9 | todo |
| 16 | [Placeholders](specs/16-dynamic-placeholders.md) | `{meta:x}`, `{user_email}`…, restricted + uncached | Later | 1, 11 | todo |
| 17 | [Bulk download](specs/17-bulk-download.md) | Print sheet first, then ZIP (if `ZipArchive`) | Later | 3, 4, 9 | todo |
| 18 | [Developer API](specs/18-developer-api.md) | Global PHP functions + data/options filters; auth-only REST later | Later | 1, 9 | todo |
| 19 | [Design styles](specs/19-design-styles.md) | Own renderer from endroid's matrix: dots/rounded/eyes | Later | 4, 9 | todo |

Suggested order: ~~0~~ → 1 → 7 → 8 → 6 → 3 → 4 → 5 → 9 → 2 → 14 → 13 → 11 → 12 → 10 → rest.
