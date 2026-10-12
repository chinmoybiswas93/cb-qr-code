# 15 — WooCommerce

| Tier | Depends on | Size |
|---|---|---|
| Later | 1, 6, 9 (needs real image URLs for email) | M |

## Goal

When WooCommerce is active:
- show the QR on **product pages** at a chosen hook position
- optionally put a QR in **order emails** (for example, linking to the order's view page)

## Why

- Composer threads: "Add QR to email template", "Email QR code", "Problem with QR code in invoice",
  "Confirmation QR Code", "each registered customer QRCODE".
- Kaya threads: "QR code in the woocommerce Thank you page", "QR With user data from woocommerce".
- WPBeginner lists WooCommerce integration among the key features.

## How competitors do it

- **Composer:**
  - product tab through `woocommerce_product_tabs`
  - placement through `woocommerce_product_meta_end`, `woocommerce_before_add_to_cart_form` and
    `woocommerce_after_add_to_cart_form`
  - a customer QR on `woocommerce_account_dashboard`
- **Kaya, QR Redirector and Qyrr:** no WooCommerce support (Qyrr has it in Pro).

## Plan

1. **Load only when WooCommerce is active.** Check `class_exists( 'WooCommerce' )` on
   `plugins_loaded`. No hard dependency.
2. **Product page position**, as a WooCommerce section in Settings: off / after the summary / a
   product tab / before or after add-to-cart. Products are a post type, so turn off the generic
   `the_content` QR for `product` when a WooCommerce position is chosen. Otherwise products get two
   QR codes.
3. **Order emails:** on `woocommerce_email_order_details`, add a QR encoding the customer's order
   URL. Show it in the customer emails only, and only when that's enabled.
   - **Emails must use a real image URL from the cache (9).** Gmail and many other clients block
     `data:` images.
   - The order URL stays secret-ish (it needs login or an order key), so encoding it is no more
     exposure than the email link that's already there.
4. Add a "Thank you" page QR on `woocommerce_thankyou`, using the same setting.

## Out of scope

Payment QR codes (UPI, bank transfer). That's a different product category, and the UPI plugins own
it.

## Contracts added

- Settings `qr-code-wc-position` and `qr-code-wc-email`.

## Test

- Standard steps from the [README](README.md).
- Do this on a site with WooCommerce. cb-test has `fluent-cart`, not WooCommerce, so install
  WooCommerce on a scratch site or temporarily.
- Try each product position: exactly one QR appears.
- Place an order. The QR image shows in the customer email in Gmail and in Apple Mail, and it scans
  to the order.
- With WooCommerce deactivated, the plugin runs with no errors or notices.

## Done when

- Product positions and email QR codes work.
- With WooCommerce absent, there are no errors.
