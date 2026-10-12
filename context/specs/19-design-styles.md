# 19 — Design styles (dots, rounded, eyes)

| Tier | Depends on | Size |
|---|---|---|
| Later | 4, 9 | L |

## Goal

Branded-looking QR codes: rounded or dot modules, styled corner "eyes", and optionally a frame with
call-to-action text ("Scan me").

## Why

- Kaya: "Branding the QR Code" ×2. Composer Pro sells "circular, square, dotted, and rounded
  styles". Master QR Generator advertises eye styles and DotScale.
- Every commercial QR service (QR TIGER, Uniqode…) leads with design.

## How competitors do it

- **Composer, Master QR Generator and others:** draw **client-side** with a JS library such as
  qr-code-styling on a `<canvas>`.
- **Qyrr:** rounded corners and a quiet zone, done server-side through endroid plus its own SVG
  handling.
- **endroid 4.8 (our dependency) has no module or eye styling.**

## Plan

We can't add npm or a JS library without asking, and we can't hand-edit `vendor/`. So:

1. **Write our own small renderer from endroid's matrix.** endroid exposes the matrix of the code
   (`Matrix` / `getBlockValue()`). Draw each module ourselves:
   - **SVG:** a `<rect rx>` or `<circle>` for each module, plus three styled eye groups. It's easy
     and gives the best result.
   - **PNG:** GD `imagefilledellipse` or rounded rectangles. Simpler shapes only.
2. Add Appearance options for module shape (square/rounded/dots) and eye shape (square/rounded/
   circle). Square stays the default and keeps using endroid's own writer, so nothing changes for
   existing users.
3. **Readability guard:** styled modules lower the scan margin. Force at least the `Q`
   error-correction level (7) when a style is active, and keep the quiet zone at 2 or more.
4. A frame with text, as an optional layer around the output.

## Out of scope

Gradients and custom eye colours. Revisit once shapes ship.

## Contracts added

Settings `qr-code-module-style`, `qr-code-eye-style` and `qr-code-frame`, plus matching attributes.

## Test

- Standard steps from the [README](README.md).
- **Scan every style combination** on iOS and Android, at 120px on screen and at 2cm printed. Any
  combination that fails is removed or restricted.
- Styled with a logo plus High error correction still scans.
- With the square default, the output is unchanged, byte for byte, from before.

## Done when

- Each offered style scans reliably.
- The default output is unchanged.
