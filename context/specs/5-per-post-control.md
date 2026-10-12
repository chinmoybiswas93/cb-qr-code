# 5 — Per-post control

| Tier | Depends on | Size |
|---|---|---|
| Must-have | 1 (shared renderer) | S |

## Goal

Let editors control the automatic QR **post by post**, from the post editor:
- hide the QR on this post
- encode a different URL for this post

## Why

- Today it's all-or-nothing per post type. Composer threads ask for exactly this: "Problem to hide
  QR code", "Exclude Qr code from URL", "QR Codes Appear if Logged in Only!".
- Kaya's "Not showing in Custom Posts" shows people expect control per post.

## How competitors do it

- **Composer:** an `add_meta_box( 'qrccompoer_metabox' )` on post types, plus a global
  "hide on frontend" option.
- **Kaya:** a meta box that holds a full shortcode builder. That's heavier than we need.

## Plan

1. Add a small meta box ("CB QR Code", side context) on the **allowed post types** only:
   - checkbox: "Hide QR code on this post"
   - URL field: "Encode this URL instead" (blank = use the global setting)
2. Classic meta boxes also show in Gutenberg's meta box area, so we don't need a JS sidebar panel
   and don't need a build step.
3. **Saving:** on `save_post`, run nonce → `current_user_can( 'edit_post', $post_id )` → skip
   autosaves and revisions → sanitize the URL with `esc_url_raw` and allow `http`/`https` only
   (see issue #7).
4. **Order in the renderer:** per-post hide wins. If there's a per-post URL, use it. Otherwise use
   the global URL mode.
5. Use underscore-prefixed meta keys, so they don't appear in the Custom Fields box.

## Out of scope

Per-post appearance (colours, size). Nobody has asked for it.

## Contracts added

Post meta `_cbqrcode_hide` and `_cbqrcode_url`. Delete both in the uninstall spec (8).

## Test

- Standard steps from the [README](README.md).
- Hide on one post: no QR there, and other posts are unaffected.
- A per-post URL scans to that URL. Clearing the field goes back to the global setting.
- An Author role user can set the fields on their own post, but not on someone else's.
- Quick Edit and autosave don't wipe the values.
- Try `javascript:alert(1)` as the URL: it's rejected.

## Done when

- Both controls work in both editors.
- The meta keys are listed in the Contracts table in `agent.md`.
