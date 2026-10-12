# 14 — Transparent background + label position

| Tier | Depends on | Size |
|---|---|---|
| Standout | — | XS |

## Goal

Two small appearance options:
- a **transparent background**, so the QR sits cleanly on coloured themes and print artwork
- the **label** above, below or hidden

## Why

- QR Redirector's forum asks for both: "Transparent QR Code Option" and "Title under qr-code".
- Composer users ask about alignment and padding.

## How competitors do it

**QR Redirector:** when its transparent checkbox is set, it overrides the background with
`new Color( r, g, b, 127 )`, which is endroid's alpha value. The label goes under the code in its
shortcode output.

## Plan

1. Add an Appearance checkbox, `qr-code-transparent`. When it's on, the generator passes alpha 127
   for the background colour. That works for PNG, and for SVG through fill-opacity.
2. Show a warning next to the setting: a transparent QR needs a **light** surface behind it, and a
   dark theme makes it unscannable. Show the preview on a checkerboard so the admin can see it.
3. Add an Appearance select, `qr-code-label-position`: `above` (today's behaviour, the default),
   `below` or `hidden`.
4. Add matching attributes `transparent` and `label_position`.

## Contracts added

Settings keys `qr-code-transparent` and `qr-code-label-position`, plus the two attributes.

## Test

- Standard steps from the [README](README.md).
- Transparent on: a downloaded PNG has real alpha (open it in an image editor) and scans on a white
  page.
- The label position changes the order in the HTML, and existing sites still show the label above.

## Done when

Both options work in the preview, on the frontend and in downloads.
