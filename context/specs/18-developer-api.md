# 18 — Developer API

| Tier | Depends on | Size |
|---|---|---|
| Later | 1, 9 | S |

## Goal

Give developers a stable way to make QR codes in their own code:
- a PHP function
- filters on the data and options

A REST endpoint is optional and comes later.

## Why

- Kaya: "Generate QR code to Specific File Name with PHP", "Using Shortcode Inside Another Plugin".
- Qyrr: "Create QR Codes via REST API".
- The two existing filters, `cbqrcode_before_qr_code` and `cbqrcode_after_qr_code`, only wrap the
  HTML. They give no control over what's encoded.

## How competitors do it

- **Qyrr:** a `qyrr/v1` REST namespace (`/generate`, `/settings`, `/meta`…) used by its own
  editor.
- **Kaya:** developers call `do_shortcode()`.

## Plan

1. **Global functions** (thin wrappers around the namespaced code, so they're easy to call):
   - `cbqrcode_get_qr_url( $data, $args )` returns the cached image URL
   - `cbqrcode_get_qr_html( $data, $args )` returns HTML from the shared renderer
   - Document them in the readme. Guard against name clashes with `function_exists()`.
2. **Filters:**
   - `cbqrcode_qr_data` (the text to encode)
   - `cbqrcode_qr_options` (generator options)
   - `cbqrcode_qr_html` (the final HTML)
   - Keep the two existing filters exactly as they are, because they're a public contract.
3. **REST (optional, separate PR):** `cb-qr-code/v1/qr` that **requires authentication**, either
   `manage_options` or `edit_posts`. An open "generate an image" endpoint lets anyone make the
   server do GD work on demand, which is a denial-of-service risk. Rate-limit it even when
   authenticated.

## Contracts added

The function names, the filter names, and the REST route (if it's built).

## Test

- Standard steps from the [README](README.md).
- Call the functions from a mu-plugin snippet on cb-test: the output matches the shortcode.
- Each filter changes its output.
- REST: a 401 when logged out, and a working image with an application password.

## Done when

- The functions and filters are documented in the readme and listed as contracts.
