# 2 — Gutenberg block

| Tier | Depends on | Size |
|---|---|---|
| Must-have | 1 (shared renderer) | M |

## Goal

Add a **CB QR Code** block that site editors insert like any other block, with the shortcode's
options in the sidebar and a live preview in the editor.

## Why

- Kaya and Qyrr both ship a block. WPBeginner's roundup lists "block" as the easiest way for
  beginners to place a code.
- Once this lands, block themes and the block widget editor get QR codes for free.

## How competitors do it

- **Kaya**: `blocks/qrcode/block.json` with `render.php`, which is a dynamic block. Its PHP calls
  the same display function as the shortcode. The editor JS is built with `@wordpress/scripts`.
- **Qyrr**: two built blocks (`qr`, `qr-selector`). The selector picks a saved QR post.

## Plan

1. **A dynamic block.** `block.json` (apiVersion 3) with a `render` callback that calls the shared
   renderer from spec 1. Attributes mirror the shortcode attributes.
2. **No build step** (agent.md hard rule). Write the editor script in plain ES5/ES2015 against the
   `wp.blocks`, `wp.element`, `wp.components`, `wp.blockEditor` and `wp.serverSideRender` globals,
   using `wp.element.createElement`, not JSX. Declare its dependencies in a hand-written
   `index.asset.php`.
3. **Preview** with `ServerSideRender`, so the editor shows exactly the PNG the server makes.
4. **Sidebar controls**:
   - URL (empty = current post)
   - size, margin
   - dark/light colours (`ColorPicker` or `ColorPalette`)
   - label, alignment, logo toggle
5. Register the block on `init` in both admin and frontend contexts. The `is_admin()` split in
   `cb-qr-code.php` must not hide it from either one.

## Out of scope

- Selecting saved dynamic QR codes. That comes after spec 10.
- Content types (that's 11).

## Contracts added

- Block name `cb-qr-code/qr-code`.
- Its attribute names. Renaming either one later needs a block deprecation.

## Test

- Standard steps from the [README](README.md).
- Insert the block in a post and change each control: the preview updates.
- Save and view the post: the frontend matches the editor preview.
- Insert it in a template part in the Site Editor (block theme) and in a widget area.
- Open the post with the classic editor plugin active. Nothing should break; the block just isn't
  available.
- The browser console shows no errors in the editor.

## Done when

- The block inserts, previews, saves and renders.
- It works in post content, templates and widget areas.
- It uses no build step.
