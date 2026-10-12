# 13 — Classic widget

| Tier | Depends on | Size |
|---|---|---|
| Standout | 1 | XS |

## Goal

Add a "CB QR Code" widget for classic-theme sidebars. By default it encodes the current page's URL.

## Why

- Kaya (10k installs) has a widget, and "sidebars" is part of how WPBeginner describes it.
- Composer users ask "Block qr on widgets". QR Code MeCard ships a widget too.
- Block themes already get this through the block (2). The widget is for the many classic-theme
  sites.

## How competitors do it

**Kaya:** `register_widget( 'WPKQCG_Widget_qrcodewidget' )`. Its widget form saves the same fields
as its shortcode and calls the same display function.

## Plan

1. A `WP_Widget` subclass. Its `widget()` method calls the shared renderer from spec 1, and its
   form shows the shortcode attributes. "URL" left empty means the current page, using
   `get_queried_object()` or the home URL.
2. `update()` sanitizes every field the same way the shortcode does.
3. Register it on `widgets_init`. That runs in both admin and frontend, so register it outside the
   `is_admin()` split.

## Out of scope

An Elementor widget. Composer has one (`class-qrc-composer-elemnetor.php`), but in Elementor the
shortcode widget already works. Add it as a new spec if users ask.

## Contracts added

The widget id base, `cbqrcode_widget`.

## Test

- Standard steps from the [README](README.md).
- Switch cb-test to a classic theme and add the widget. On the home page, a post and an archive,
  it encodes that page's URL.
- With a fixed URL set, it encodes that URL everywhere.
- Saving with an invalid colour falls back to the default.

## Done when

The widget works on a classic theme and shares the renderer.
