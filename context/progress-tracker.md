# Progress tracker

Where the project is now. Keep this short; move finished history into the changelog in the readme.

## Phase

**v1.0.0 — live on WordPress.org since 2025-10-04, and the repo matches it.**
On 2026-10-13 the repo was re-labelled from 1.0.3 to 1.0.0: the live zip
(`downloads.wordpress.org/plugin/cb-qr-code.1.0.0.zip`) and every shipped file here are
byte-identical, and that commit is tagged `v1.0.0` with a GitHub release. The code itself never
changed — 1.0.1–1.0.3 were GitHub-only labels on what became 1.0.0.

The next WordPress.org upload is **1.0.1**. It is prepared on `release/1.0.1` (version in all five
places, changelog, `.pot`, `Tested up to: 7.1`), stacked on the four fix PRs — **not tagged or
uploaded**. Pre-release checks 1, 3–9 were run on 2026-10-13 (see that PR); check 1's diff against
the live 1.0.0 couldn't run because `cb-qr-code-org/` isn't on cb-test.

## As of 2026-10-13

- Agent rules and `context/` set up (this file, `agent.md`, `CLAUDE.md`).
- PHP 7.4 lint and Plugin Check's PHPCS ruleset (`plugin-review.xml`) both pass clean.
- [issues.md](issues.md): the 1.0.1 fix round is in flight as four stacked PRs, merged in order
  A → B → C → D — A `fix/url-handling` (#1 #2 #3 #7), B `fix/settings-input` (#5 #6), C
  `fix/assets-admin-js` (#10 #11 #13), D `feat/uninstall-cleanup` (#8, spec 8) — then a separate
  1.0.1 version-bump PR. #4 (cache) stays open for spec 9.
- Plugin is **active** on cb-test (Kaya QR is active too — deactivate it before running the
  `tests/` harness).

## Next

Land the fix PRs A–D and the 1.0.1 bump. After that, the feature roadmap below. Issue #3 is
fixed by PR A, so spec 6 only adds the placement setting; issue #6 is fixed by PR B, whose
`Settings::cbqrcode_get_defaults()` is the defaults source spec 1 builds on.

## Feature roadmap

Planned from competitor research on 2026-10-13 (see [specs/README.md](specs/README.md)). One spec =
one branch = one PR. Status: `todo` → `in progress` → `in review` → `done` (with PR link).

| # | Feature | Summary | Tier | Depends on | Status |
|---|---|---|---|---|---|
| 0 | [Restructure](specs/0-restructure.md) | omnio-style `boot/` + `framework/` + `app/` layout; 1.0.0 names kept via `boot/compat.php`; output byte-identical to 1.0.0 throughout | Foundation | — | done — [#1](https://github.com/chinmoybiswas93/cb-qr-code/pull/1) docs, [#2](https://github.com/chinmoybiswas93/cb-qr-code/pull/2) PHP 8.1, [#3](https://github.com/chinmoybiswas93/cb-qr-code/pull/3) kernel, [#4](https://github.com/chinmoybiswas93/cb-qr-code/pull/4) handlers, [#5](https://github.com/chinmoybiswas93/cb-qr-code/pull/5) compat |
| 1 | [Shortcode](specs/1-shortcode.md) | `[cbqrcode]` with attribute overrides; extract a shared renderer + one defaults source | Must | — | todo |
| 2 | [Gutenberg block](specs/2-gutenberg-block.md) | Dynamic block, no build step, ServerSideRender preview | Must | 1 | todo |
| 3 | [PNG download](specs/3-download-button.md) | Optional download button, slug-based filename | Must | 1 | todo |
| 4 | [SVG download](specs/4-svg-download.md) | endroid `SvgWriter` (already in vendor) for print-quality output | Must | 3 | todo |
| 5 | [Per-post control](specs/5-per-post-control.md) | Meta box: hide on this post / custom URL for this post | Must | 1 | todo |
| 6 | [Placement](specs/6-placement-options.md) | Before / after / both / manual; main-loop guard already landed (#3, PR A) | Must | 1 | todo |
| 7 | [Error correction](specs/7-error-correction.md) | `auto` = High with logo; fixes hard-coded Low | Must | — | todo |
| 8 | [Uninstall cleanup](specs/8-uninstall-cleanup.md) | `uninstall.php`; later specs add their data to it (closes #8) | Must | — | in review — PR D |
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
