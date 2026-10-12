# 6 — Placement options

| Tier | Depends on | Size |
|---|---|---|
| Must-have | 1 | S |

## Goal

Choose **where** the automatic QR appears:
- below the content (today's behaviour, and the default)
- above the content
- both
- manual only, meaning the QR shows only where the shortcode or block is placed

Also stop the QR being added to content it doesn't belong to.

## Why

- With the shortcode and block in place, many sites will want "manual only".
- Composer offers page and product location choices (`qrcppagelocation`, `qrc_select_alignment`).
- Issue #3: today the QR is added to **every** `the_content` call on a singular page. That
  includes related-post blocks and widgets, and each of those copies encodes the *main* post's URL.

## How competitors do it

- **Composer:** a location select, plus separate WooCommerce hooks for products (see 15).
- **Master QR Generator:** before or after the content, plus a shortcode.

## Plan

1. Add a Settings-tab select, `qr-code-placement`, with the values `after` (default), `before`,
   `both` and `manual`.
2. In the `the_content` filter, guard with `in_the_loop() && is_main_query()` and pass the post ID
   explicitly. That closes issue #3.
3. `manual` skips the filter entirely. The post-type list then only affects which pages load
   assets, so the Settings tab text should say so.
4. Keep the existing left/right alignment as it is. Placement and alignment are independent.

## Out of scope

WooCommerce hook positions (that's 15).

## Contracts added

Settings key `qr-code-placement`, defaulting to `after` so existing sites don't change.

## Test

- Standard steps from the [README](README.md).
- Try each of the four values on a post and a page.
- Add a block theme "Query Loop" or a related-posts block to a single post. Only the main content
  gets a QR.
- With `manual` set, the QR appears only where the shortcode is.
- Upgrade with existing saved settings: placement is `after` with no re-save needed.

## Done when

- All four modes work.
- Issue #3 is closed in `issues.md`.
