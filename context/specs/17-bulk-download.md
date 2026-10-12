# 17 — Bulk download / print sheet

| Tier | Depends on | Size |
|---|---|---|
| Later | 3, 4, 9 | M |

## Goal

From one admin screen, select many posts, pages or products and get all their QR codes at once,
either as a **printable sheet** or as a **ZIP of PNG/SVG files**.

## Why

- Shops, museums, restaurants and events print QR codes for many items at once.
- Composer sells "Bulk Print" and "Download QR Code" as Pro features. Master QR Generator and Qyrr
  Pro also gate bulk behind payment. Offering it free would stand out.
- Redirector users ask "Stats & Import".

## How competitors do it

- **Composer Pro:** an admin page that lists posts, with print and download buttons.
- **Qyrr Pro:** a bulk generator.
- **QR Redirector:** bulk edit on its list table, but no bulk download.

## Plan

1. Add a **"QR Codes"** submenu that lists posts of the allowed types, with search and a post-type
   filter, using `WP_List_Table`. Each row has its QR thumbnail and single download links.
2. **Print sheet** (no PHP extension needed): a print-styled admin view of a grid of QR codes with
   titles, then the browser's print dialog (`window.print()`). Offer a grid-size setting. **Build
   this first.**
3. **ZIP**: a bulk action that builds a ZIP with `ZipArchive` in a temp folder and streams it.
   - Check `class_exists( 'ZipArchive' )` under the **web** PHP. CLI PHP extensions differ (see
     the site `CLAUDE.md`). Hide the option if it's missing.
   - Cap the batch size (for example, 200) to avoid timeouts.
4. The download is a `GET` with a nonce and `manage_options`, and it reuses the cached files (9).

## Contracts added

The admin page slug and the bulk action name.

## Test

- Standard steps from the [README](README.md).
- Select 50 posts and print the sheet: each code scans to the right post.
- ZIP with PNG and SVG: the file count matches, the names are the post slugs, and the files open.
- Selecting more than the cap shows a clear notice and doesn't time out.
- A non-admin can't reach the page or the download URL.

## Done when

The print sheet and ZIP both work, and the ZIP is hidden when `ZipArchive` is missing.
