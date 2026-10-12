# 12 — Display conditions

| Tier | Depends on | Size |
|---|---|---|
| Standout | 5, 6 | S |

## Goal

Control **who** and **where** sees the QR, beyond post type:
- logged-in users only
- logged-out users only
- by role
- excluded post IDs
- desktop only (on a phone, the QR is less useful than a link)

## Why

- Kaya "Qr code for logged in users only". Composer "QR Codes Appear if Logged in Only!" and
  "Problem to hide QR code".
- Qyrr "Login to Website, track clicks".
- WPBeginner lists conditional logic as a must-have.

## How competitors do it

- **Composer:** a global "hide on frontend" toggle and a popup mode.
- Nobody offers real conditions in the free tier, so this is a gap we can fill.

## Plan

1. Add a "Visibility" section to the Settings tab:
   - Audience: everyone / logged in / logged out
   - optional role multi-select when the audience is "logged in"
   - excluded post IDs, comma-separated (the per-post hide from spec 5 covers single posts)
   - "Hide on mobile" (CSS media query class, not user-agent sniffing, so page caches stay safe)
2. Put the conditions in one function, e.g. `cbqrcode_should_display( $post_id )`, that the
   automatic QR checks. Add a shortcode attribute `audience` that does the same.
3. Add a filter `cbqrcode_should_display` so developers can add their own rules.
4. **Caching:** if the audience is logged-in only and the site uses a full-page cache, logged-out
   visitors can still be served a cached page that includes the QR. Explain this in the help text.

## Contracts added

- Settings keys `qr-code-audience`, `qr-code-roles`, `qr-code-exclude`, `qr-code-hide-mobile`.
- The `cbqrcode_should_display` filter.
- The `audience` attribute.

## Test

- Standard steps from the [README](README.md).
- For each audience, check as logged out, as a Subscriber and as an Admin.
- An excluded ID shows no QR. Other IDs are unaffected.
- With "Hide on mobile" on, the QR disappears under the mobile breakpoint, and the HTML is the same
  for every user agent.
- The filter returns `false` and the QR is hidden.

## Done when

All conditions work for both the automatic QR and the shortcode.
