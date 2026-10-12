# Progress tracker

Where the project is now. Keep this short; move finished history into the changelog in the readme.

## Phase

**v1.0.0 — live on WordPress.org since 2025-10-04, and the repo matches it.**
On 2026-10-13 the repo was re-labelled from 1.0.3 to 1.0.0: the live zip
(`downloads.wordpress.org/plugin/cb-qr-code.1.0.0.zip`) and every shipped file here are
byte-identical, and that commit is tagged `v1.0.0` with a GitHub release. The code itself never
changed — 1.0.1–1.0.3 were GitHub-only labels on what became 1.0.0.

The next WordPress.org upload is **1.0.1**.

## As of 2026-10-13

- Agent rules and `context/` set up (this file, `agent.md`, `CLAUDE.md`).
- PHP 7.4 lint and Plugin Check's PHPCS ruleset (`plugin-review.xml`) both pass clean.
- [issues.md](issues.md) has open entries from a read-through; #1 (percent-encoded URLs) matters
  most for the next release.
- Plugin is **not active** on cb-test and has no saved settings.

## Next

Issues 1–3 as one "URL handling" change for 1.0.1. After that, the feature roadmap below. Specs 1
and 6 overlap issues #3/#6 — fold those fixes into whichever lands first.

## Feature roadmap

Planned from competitor research on 2026-10-13 (see [specs/README.md](specs/README.md)). One spec =
one branch = one PR. Status: `todo` → `in progress` → `in review` → `done` (with PR link).

| # | Feature | Summary | Tier | Depends on | Status |
|---|---|---|---|---|---|
| 0 | [Restructure](specs/0-restructure.md) | Docs PR → PR 0 (PHP floor 8.1, issue #12) → A kernel → B handlers → C compat; each gated by snapshot diffs + browser pass | Foundation | — | in progress — docs [#1](https://github.com/chinmoybiswas93/cb-qr-code/pull/1), PR 0 [#2](https://github.com/chinmoybiswas93/cb-qr-code/pull/2), PR A [#3](https://github.com/chinmoybiswas93/cb-qr-code/pull/3) merged; PR B in review |
| 1 | [Shortcode](specs/1-shortcode.md) | `[cbqrcode]` with attribute overrides; extract a shared renderer + one defaults source | Must | — | todo |
| 2 | [Gutenberg block](specs/2-gutenberg-block.md) | Dynamic block, no build step, ServerSideRender preview | Must | 1 | todo |
| 3 | [PNG download](specs/3-download-button.md) | Optional download button, slug-based filename | Must | 1 | todo |
| 4 | [SVG download](specs/4-svg-download.md) | endroid `SvgWriter` (already in vendor) for print-quality output | Must | 3 | todo |
| 5 | [Per-post control](specs/5-per-post-control.md) | Meta box: hide on this post / custom URL for this post | Must | 1 | todo |
| 6 | [Placement](specs/6-placement-options.md) | Before / after / both / manual; main-loop guard (closes #3) | Must | 1 | todo |
| 7 | [Error correction](specs/7-error-correction.md) | `auto` = High with logo; fixes hard-coded Low | Must | — | todo |
| 8 | [Uninstall cleanup](specs/8-uninstall-cleanup.md) | `uninstall.php`; later specs add their data to it (closes #8) | Must | — | todo |
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

Suggested order: 0 → 1 → 7 → 8 → 6 → 3 → 4 → 5 → 9 → 2 → 14 → 13 → 11 → 12 → 10 → rest.
