# 7 — Error-correction level

| Tier | Depends on | Size |
|---|---|---|
| Must-have | — | XS |

## Goal

Make QR codes with a logo scan reliably, and let advanced users pick the level: Low, Medium,
Quartile or High.

## Why

- `QRGenerator` hard-codes **`ErrorCorrectionLevelLow`**, which can recover only about 7% damage.
  A centre logo covers more than that, so codes with logos can fail to scan. This is a hidden
  quality bug in a feature we already ship.
- Kaya (`ecclevel`), QR Redirector (`qr_redirect_ecl`) and Qyrr (`error-handling-level`) all
  expose this setting. WPBeginner lists it under customisation.

## How competitors do it

- **Qyrr** maps L/M/Q/H to endroid's levels and **defaults to High**
  (`class-qyrr-api-generator.php`).
- **Redirector** has a select on each QR post.

## Plan

1. Add an Appearance select, `qr-code-ecc`: `auto` (default), `L`, `M`, `Q`, `H`.
2. **`auto`** means **H when a logo is set, M otherwise.** That fixes logo scanning without anyone
   touching a setting.
3. The generator maps each value to endroid's `ErrorCorrectionLevel{Low,Medium,Quartile,High}`
   classes. All four are in `vendor/`.
4. Add a shortcode/block attribute `ecc`.
5. Add help text: a higher level makes a denser code. Use it with logos or for codes that get
   printed and handled.

## Contracts added

Settings key `qr-code-ecc`, and attribute `ecc`.

## Test

- Standard steps from the [README](README.md).
- With a logo at 30–50% size: on `L` it scans badly or not at all, on `auto` it scans every time.
  Record which phone you used.
- With no logo on `auto`, the code is slightly denser than before and still scans.
- The preview updates when the level changes.

## Done when

- `auto` is the default.
- Logo QR codes scan.
- The level is passed through every path: automatic QR, shortcode, block and preview.
