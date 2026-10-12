# Feature specs

One file per feature, numbered by tier (must-have → standout → later). Build order follows the
dependencies; the suggested sequence is in the tracker. Each one is sized to be **one branch, one
PR**, developed and tested on its own. Status for all of them lives in
[../progress-tracker.md](../progress-tracker.md) — not here.

These are plans, not designs: goal, evidence, how competitors do it, our approach, and how to test.
Work out the exact code when the spec is picked up, and update the spec if the plan changes.

## Where the evidence comes from

Research done 2026-10-13:
- **Installed and read the code** of the five most-installed general QR plugins on cb-test (not
  activated): Kaya QR Code Generator (10k), Dynamic QR Code (5k), QR Redirector (4k), QR Code
  Composer (3k), Qyrr (2k).
- **Read the WordPress.org support forums** of Kaya, Composer, Qyrr and Redirector for feature
  requests, plus WPBeginner's QR plugin roundup.

## Every spec follows the plugin's rules

`agent.md` hard rules apply to all of them, and each spec only notes where they bite:
- PHP 7.4, no new Composer packages, no npm or build step. Blocks use plain `wp.*` JS.
- Local generation only. No remote QR API, ever.
- Every write path does nonce, then capability, then sanitizing each field, then validation, then
  the action.
- Escape at output.
- New option or meta keys are **new contracts**. Add them to the Contracts table in `agent.md` in
  the same PR.

## Standard test steps for every PR

1. PHP 7.4 lint and the Plugin Check PHPCS ruleset (commands in `agent.md` → *Verify*). Both must be
   clean.
2. Activate **cb-qr-code** on `https://cb-test.local`, never alongside `cb-qr-code-org`, and run
   the spec's browser checks.
3. **Scan every new QR variant with a real phone.** A QR code that renders but doesn't scan is a
   failure.
4. Check that existing behaviour is unchanged: the automatic QR on a post, the Appearance preview,
   and save.
5. Run `/security-review` on the diff for anything that adds input, output or a new endpoint.
6. Update `issues.md` and `progress-tracker.md`.

## Index

| # | Spec | Tier |
|---|---|---|
| 0 | [Restructure onto Omnio's layout](0-restructure.md) | Foundation |
| 1 | [Shortcode + shared renderer](1-shortcode.md) | Must-have |
| 2 | [Gutenberg block](2-gutenberg-block.md) | Must-have |
| 3 | [PNG download button](3-download-button.md) | Must-have |
| 4 | [SVG output and download](4-svg-download.md) | Must-have |
| 5 | [Per-post control](5-per-post-control.md) | Must-have |
| 6 | [Placement options](6-placement-options.md) | Must-have |
| 7 | [Error-correction level](7-error-correction.md) | Must-have |
| 8 | [Uninstall cleanup](8-uninstall-cleanup.md) | Must-have |
| 9 | [QR image cache](9-qr-cache.md) | Must-have |
| 10 | [Dynamic QR + scan counts](10-dynamic-qr-tracking.md) | Standout |
| 11 | [Content types](11-content-types.md) | Standout |
| 12 | [Display conditions](12-display-conditions.md) | Standout |
| 13 | [Classic widget](13-widget.md) | Standout |
| 14 | [Transparent background + label position](14-transparent-label.md) | Standout |
| 15 | [WooCommerce](15-woocommerce.md) | Later |
| 16 | [Dynamic data placeholders](16-dynamic-placeholders.md) | Later |
| 17 | [Bulk download / print sheet](17-bulk-download.md) | Later |
| 18 | [Developer API](18-developer-api.md) | Later |
| 19 | [Design styles](19-design-styles.md) | Later |
