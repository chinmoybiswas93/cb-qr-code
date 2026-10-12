# 10 — Dynamic QR codes + scan counts

| Tier | Depends on | Size |
|---|---|---|
| Standout | 1, 2, 9 | L (split into 2 PRs: redirect first, then stats) |

## Goal

Let site owners create **QR codes whose destination can change after printing**, and see **how
many times each one was scanned**. Everything runs on their own site, with no outside service.

## Why

- It's the clearest feature that splits paid from free. Qyrr and Composer charge for it. Dynamic
  QR Code (5k installs) and QR Redirector (4k) exist only to do this, and both have more installs
  than Qyrr.
- Requests: Kaya "QR code Anlytics", "Changing URL in existing QR Code", "about dynamic qrcode";
  Qyrr "Track code", "dynamic qr code", "Login to Website, track clicks"; Redirector "Stats &
  Import", "Change redirects to 301".
- WPBeginner and every review site rank dynamic codes and scan tracking as top features.

## How competitors do it

- **QR Redirector** (simplest, and the model to follow):
  - a `qrcode` custom post type
  - a `wp` hook redirects the post's permalink to a destination stored in meta
  - a counter in post meta `qr_redirect_count`, with a reset button
  - a choice of 301 or 302
  - bulk and quick edit
  - a PNG written to uploads
- **Dynamic QR Code:**
  - its own tables (`sos_dqc_*`)
  - logs each scan with device type, OS and day of the week
  - redirects with a `Refresh` header (slow, and we shouldn't copy that)
- **Qyrr:** a `qr` post type with a `campaign` taxonomy. Tracking is in the paid plan only.

## Plan

**PR A: redirect**
1. Add a non-public post type `cbqrcode_link` with an admin UI. Its fields:
   - title
   - destination URL (`http`/`https` only)
   - notes
   - redirect type: 302 by default, so changing the destination always takes effect; 301 optional
2. Each link gets a short URL, `/qr/{slug}`, through a rewrite rule. Handle the request early (on
   `parse_request` or `template_redirect`), send `nocache_headers()` so page caches don't store the
   redirect, then `wp_redirect()` and exit. Flush the rewrite rules on activation and deactivation.
   The plugin has no activation hook yet, so add one.
3. The QR image for a link encodes the **short URL**, not the destination. The admin shows a
   preview with PNG/SVG download (3, 4).
4. `[cbqrcode link="ID"]` and a block option to place a saved link.

**PR B: scan stats**
5. On each redirect, add 1 to a total counter and a per-day counter in post meta. Keep only the
   last 90 days so the meta doesn't grow forever. Record the last-scan time.
6. **No IP addresses and no personal data.** That keeps us out of GDPR consent territory, and it's
   a selling point.
7. Skip common bots and link-preview fetchers by user agent, so a Slack or WhatsApp link preview
   isn't counted.
8. Show a count column in the list table, a small daily chart on the edit screen (plain HTML/CSS
   bars, no chart library), and a reset button. The reset is an AJAX handler that follows the full
   nonce → capability pattern.

## Out of scope

- Location, device and OS breakdown (that's Dynamic QR Code's approach). Maybe later, without IPs.
- UTM builder and campaigns.

## Contracts added

- The post type `cbqrcode_link` and its meta keys.
- The `/qr/` rewrite base. It should be changeable in settings, in case `/qr/` clashes with a page.
- The `link` attribute.
- All of these must go in uninstall (8).

## Test

- Standard steps from the [README](README.md).
- Create a link, print or scan its code, and land on the destination. Change the destination and
  scan the **same** code: you land on the new one.
- The counter goes up on a real phone scan and does **not** go up for a curl request with a bot
  user agent.
- Turn on a page cache plugin and scan twice: both scans redirect and both are counted.
- Creating a page with the slug `qr` doesn't break anything, or the clash is handled through the
  rewrite-base setting.
- Try `javascript:` and `data:` destinations: they're rejected.
- `/qr/{unknown}` returns a 404.

## Done when

- Codes redirect.
- Destinations change without reprinting.
- Counts are correct and contain no personal data.
- Uninstall removes everything.
