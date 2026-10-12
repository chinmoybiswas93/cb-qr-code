# 9 — QR image cache

| Tier | Depends on | Size |
|---|---|---|
| Must-have | 1 (one render path to cache) | M |

## Goal

Stop regenerating the PNG on **every page view**. Generate it once per URL and appearance, store
it, and reuse it. Storing it as a real file also gives us a normal image URL, which emails (15)
and bulk download (17) need.

## Why

- Issue #4: GD work, plus logo compositing, runs on every singular request, uncached.
- With the shortcode (1) and block (2), a page can hold several QR codes, and SVG (4) adds a
  second render. The cost adds up.
- Data URIs also make the HTML heavier and can't be cached by the browser.

## How competitors do it

- **QR Redirector:** writes `uploads/qrcodes/qr{md5}.png` when the post is saved, and deletes the
  old file when it changes.
- **Qyrr:** writes files into `uploads` through REST when saving.
- **Kaya and Composer:** avoid the problem by drawing client-side. That needs JS and gives you no
  real image.

## Plan

1. **Cache key:** a hash of the encoded text plus every appearance setting that affects the image,
   plus the format. Bump a cache version whenever settings are saved, so all old entries become
   invalid at once.
2. **Storage:** `uploads/cb-qr-code/{hash}.png|svg`, served as a normal `<img src>` URL.
   - If `uploads` isn't writable, **fall back to a transient** holding the data URI.
   - If that fails too, fall back to rendering every time, as today.
3. **Cleanup:**
   - On settings save, remove the old files (or let them expire).
   - Optional weekly cron to delete orphaned files.
   - Removed by uninstall (8).
4. **Keep the data-URI path available**, because the editor preview and AJAX preview need fresh
   output.
5. **Download links (3, 4)** point to the cached file URL instead of a data URI.

## Out of scope

Pre-generating codes for all posts. Generating lazily on first view is enough.

## Contracts added

The `uploads/cb-qr-code/` folder, and a cache-version option, e.g. `cbqrcode_cache_ver`.

## Test

- Standard steps from the [README](README.md).
- Load a post twice and use Query Monitor (installed on cb-test) to check that no GD work happens
  on the second load.
- Change a colour and save: the next view shows the new colour, not a stale image.
- Make `uploads/cb-qr-code` read-only: the QR still renders through the fallback.
- A non-Latin slug post caches and scans correctly.

## Done when

- Repeat views render from the cache.
- A settings change invalidates it.
- The fallback works.
- Issue #4 is closed.
