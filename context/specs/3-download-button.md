# 3 — PNG download button

| Tier | Depends on | Size |
|---|---|---|
| Must-have | 1 | S |

## Goal

Let visitors download the QR code as a PNG with one click. Site owners turn it on or off, set the
button text, and get a useful file name (the post slug plus `-qr.png`).

## Why

Download is the **most-requested feature** across the support forums:
- **Kaya:** "Download QR code after generating it with shortcode", "Download Button Only",
  "Download button for dynamic content QR Code", "Parameter to Name PNG when Downloaded",
  "Saving a generated QR Code as image / PDF file".
- **Composer:** "Download button", "padding around downloaded qrcode".
- **Redirector:** "Add title to file name when downloading".

## How competitors do it

- **Kaya and Composer** draw the QR code client-side on a `<canvas>`, then download it with JS
  (`wpkqcg_qrcode_download()`). They have settings for button text, colour and alignment.
- **Redirector and Qyrr** write PNG files to `uploads/` and link to them.

## Plan

Our QR code is already a **PNG data URI**, so the button can be a plain
`<a href="data:image/png;base64,…" download="{slug}-qr.png">`. That needs no JS and no extra
request.

1. **Appearance settings:**
   - "Show download button" (off by default, so existing sites don't change)
   - button text (default "Download QR code")
2. **Shortcode/block attributes:** `download="yes|no"` and `download_text`.
3. **File name:** `sanitize_file_name( post slug )` + `-qr.png`. Fall back to `qr-code.png`
   outside a post.
4. **Style the button** with a `cbqrcode-download` class. Keep it neutral so it inherits the
   theme's button styles.
5. **Click-to-copy:** `script.js` copies the URL when the QR wrapper is clicked. Make sure clicking
   the button does **not** also copy the URL. Stop propagation, or keep the button outside the
   click target.

## Out of scope

SVG download (that's 4) and bulk download (that's 17).

## Contracts added

- Settings keys `qr-code-download`, `qr-code-download-text`.
- Shortcode/block attributes `download` and `download_text`.

## Test

- Standard steps from the [README](README.md).
- Turn the button on and download from a post: the file name is `{slug}-qr.png`, and the file
  opens and scans.
- A post with a non-Latin slug gets a valid file name.
- Test in Safari, Chrome and Firefox. Safari has had quirks with `download` on data URIs.
- Clicking the button does not show the "copied" feedback.
- With the button off, the HTML is identical to before.

## Done when

- The button works in all three browsers.
- It's off by default.
- Its text is translatable.
