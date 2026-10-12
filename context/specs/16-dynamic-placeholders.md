# 16 — Dynamic data placeholders

| Tier | Depends on | Size |
|---|---|---|
| Later | 1, 11 | S |

## Goal

Let the shortcode encode data from the **current post or the logged-in user**, through
placeholders:
- `{post_title}`, `{post_id}`, `{permalink}`
- `{meta:field_name}` (also covers ACF fields stored as post meta)
- `{user_email}`, `{user_login}`, `{user_id}`

## Why

- Kaya: "QR code with ACF field encoded", "QR With user data from woocommerce", "Send a dynamic QR
  code to the user's activation email".
- Composer: "user QR / author QR", "each registered customer QRCODE".
- Qyrr: "Different QR code for each user".
- People use this for membership cards, event tickets and staff badges.

## How competitors do it

- **Kaya:** `[kaya_qrcode_dynamic]` wraps content and runs nested shortcodes.
- **Composer:** user-profile and My Account vCard QR codes from user fields (`show_user_profile`,
  `woocommerce_account_dashboard`).

## Plan

1. Resolve placeholders in the text before encoding, in one function, with a whitelist of tokens.
2. **Security limits.** Any post author can write shortcodes, so:
   - `{meta:*}` reads **only the current post's** meta, and never protected keys (anything starting
     with `_`).
   - `{user_*}` reads **only the current logged-in viewer**. If no one is logged in, the QR is
     empty.
   - Contributors can't reach other users' data or private posts.
3. A QR that has a `{user_*}` token is different for each viewer, so **skip the cache (9)** for it.
   Send no-cache headers too, or a page cache will show one person's code to everyone.
4. Add a filter `cbqrcode_placeholder_{name}` so developers can add tokens.

## Contracts added

The placeholder names and the filter.

## Test

- Standard steps from the [README](README.md).
- `{meta:price}` on a post with that meta encodes its value. `{meta:_edit_lock}` encodes nothing.
- `{user_email}` as two different users gives two different codes. Logged out gives no QR.
- A Contributor's post can't read another post's meta.
- With a page cache plugin on, a user QR never appears in a cached page.

## Done when

- Tokens resolve.
- The security limits are proven by the tests above.
- Per-user codes are never cached.
