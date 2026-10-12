# 11 — Content types

| Tier | Depends on | Size |
|---|---|---|
| Standout | 1, 2 | M |

## Goal

Encode more than URLs:
- plain text
- phone
- SMS
- email
- WhatsApp
- WiFi login
- vCard contact

Available from the shortcode and the block.

## Why

- Composer threads ask: "vCard QR code", "VCF file". So does Redirector: "Would it possible to add
  VCARD fields".
- QR Code MeCard/vCard has 2k installs for vCard alone.
- Master QR Generator and Qyrr Pro advertise 9+ types. WPBeginner lists contact sharing as a core
  use case.

## How competitors do it

- **Composer:** a separate shortcode per type: `qrc_phonenumber`, `qr_whatsapp_composer`,
  `qrc_vcard_single`. vCard values come from a settings page and are built as a
  `BEGIN:VCARD\nVERSION:3.0…` string in PHP.
- **Qyrr Pro:** a `source` meta field picks the type, and the editor shows matching fields.

## Plan

1. **One shortcode with a `type` attribute**, not Composer's five shortcodes:
   `[cbqrcode type="phone" phone="+8801…"]`, `type="wifi" ssid="…" password="…" encryption="WPA"`,
   and so on.
2. Add a builder function per type that returns the exact string to encode:
   - `tel:`
   - `SMSTO:number:message`
   - `mailto:…?subject=&body=`
   - `https://wa.me/{digits}?text=`
   - `WIFI:T:;S:;P:;H:;;`
   - vCard 3.0
3. **Escape each format's special characters.** WiFi needs `\;,:"` backslash-escaped, and vCard
   needs `,;\` escaped and newlines folded. Getting this wrong makes codes that scan to garbage.
4. In the block: a "Type" select that shows that type's fields.
5. Sanitize per field: phone digits and `+`, email with `sanitize_email`, text with
   `sanitize_textarea_field`, and so on.
6. A text or WiFi QR on a public page **shows its content to anyone who scans it**. Put a warning
   on the WiFi type.

## Out of scope

- A saved global vCard (Composer's settings page).
- Per-user vCards (that's 16).
- Map/geo, calendar event and crypto types. Add them later if people ask.

## Contracts added

The `type` attribute value set, and each type's attribute names.

## Test

- Standard steps from the [README](README.md).
- **Scan each type with iOS and Android**:
  - phone opens the dialler
  - SMS opens Messages with the text filled in
  - WiFi offers to join
  - vCard offers "Add contact" with every field in place
- Use special characters in a WiFi password (`;`, `:`, `"`) and in vCard fields (commas, Bengali
  text). They survive the scan intact.
- Required fields left empty show an editor-only notice and nothing on the frontend.

## Done when

- All types scan correctly on both phone OSes.
- The readme documents each type's attributes.
