# 4 — SVG output and download

| Tier | Depends on | Size |
|---|---|---|
| Must-have | 3 | S |

## Goal

Offer a **vector (SVG)** download next to the PNG, for print. Optionally let the site render the
on-page QR as SVG too: it's smaller and stays sharp on every screen.

## Why

- Qyrr's forum asks "Add SVG download option?", and Qyrr itself keeps SVG behind its paid plan.
- QR Code Creator advertises PNG/SVG download as a feature.
- Print-focused reviews (wpreset, QRStuff) list vector output as essential for flyers and signage.

## How competitors do it

- **Qyrr**: SVG is a paid feature, made with endroid's `SvgWriter` server-side.
- **QR Code Creator**: makes the SVG client-side.

## Plan

`vendor/endroid/qr-code/src/Writer/SvgWriter.php` **already ships** in our pruned vendor folder,
and it supports logos (it embeds them as a data URI). So this needs no new dependency.

1. **Generator:** let `QRGenerator::generate()` take a `format` option (`png` default, `svg`) and
   pick the writer from it.
2. **Download:** "Show SVG download" setting, plus `download="png|svg|both"` on the
   shortcode/block. The link is a data URI with `download="{slug}-qr.svg"`.
3. **Optional on-page format setting:** PNG or SVG. When it's SVG, output the SVG as an `<img>`
   data URI. Never output the SVG markup inline: inline SVG would need careful sanitizing.
4. **Performance:** generating both formats doubles the work on each view. Only generate the SVG
   when it's actually shown or offered for download. Spec 9's cache removes the rest of the cost.

## Out of scope

EPS and PDF. endroid has writers for both, but nobody asked for them except one "PDF" thread.

## Contracts added

- Settings `qr-code-svg-download` and `qr-code-format`.
- The `download` attribute gains the values `svg` and `both`.

## Test

- Standard steps from the [README](README.md).
- Download the SVG. It opens in a browser and in Inkscape or Illustrator, scales without blurring,
  and scans when printed small and large.
- An SVG with a logo still shows the logo and still scans.
- Run the on-page SVG mode with the dark/light colours you'd actually use. Colours must match the
  PNG.

## Done when

- SVG download works with and without a logo.
- PNG stays the default everywhere.
