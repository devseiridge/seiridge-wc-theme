# Seiridge — WordPress + WooCommerce theme (final)

## Install

1. WordPress + WooCommerce installed as normal.
2. Upload `seiridge-wc` to `/wp-content/themes/`, activate it.
3. Setup runs automatically on activation: creates 48 WP Pages, assigns real
   templates to WooCommerce's Cart/Checkout/My Account pages, sets the
   homepage, pre-fills all editable-content defaults.
4. Add real products in WooCommerce → Products.
5. Re-run setup anytime via the admin notice link (idempotent, verified).

## What's real vs. what's a documented trade-off

Everything below was verified by running the actual theme code against the
actual bundled files with a stubbed WordPress/WooCommerce API — including
executing full page templates end-to-end and checking for balanced HTML tags,
not just checking that a regex matched. That process caught and fixed six
real bugs across this project (a double-header architecture flaw, an
unbalanced `<div>` in checkout, a nested `<form>` that would have broken
checkout submission, a runaway regex in the orders list, a mismatched CSS
class in the variation selectors, and a copy-pasted extra `</div>` in the
order-details view) — they're fixed, not just noted.

**Checkout**: original hero/shell/shipping-fields/delivery-method/order-
summary/coupon design, wired to WooCommerce's real cart/shipping/coupon
APIs. The Payment Method section calls WooCommerce's own
`woocommerce_checkout_payment()` (not hand-rebuilt) and is restyled via a
CSS bridge to visually match the original radio-card layout — deliberately,
because payment-gateway JS (Stripe/PayPal/etc.) binds to WooCommerce's own
field DOM, and reimplementing that by hand with no live site to test against
is exactly the kind of risk not worth taking on a real payment flow.

**My Account — every sub-page** (Dashboard, Orders list, single Order view,
Addresses, Edit Account, Logout) renders in its original page design with
real data: real customer name/order count, real order history, a real
multi-address book (a genuine custom feature — WooCommerce core only
supports one billing + one shipping address, not a multi-address book),
and a real account-details save. The "5-step" order status timeline is
honestly reduced to 3 real steps (Placed / Confirmed / Delivered) because
core WooCommerce doesn't track "Shipped" / "Out for Delivery" without a
shipment-tracking plugin — shown as real data, not invented data.

**Wishlist**: real, persistent (usermeta for logged-in users, cookie for
guests) — not the original's in-memory array that reset on reload. Wired on
every product card sitewide and on the wishlist page.

**Editable content**: real wp-admin fields for About Us, Contact Us, FAQ,
Help Center, Privacy Policy, Terms & Conditions, Shipping Policy, Return
Policy, Returns & Refunds, and the homepage hero banner (eyebrow/heading/
subtext) — 12 pages, ~35 fields, every one verified to exist in the source
file it targets. This is a curated set covering the content an admin
actually edits (headings, intros, contact details, hero copy), not literally
every string on every page — About's 4-card grid and FAQ's individual Q&As,
for example, stay locked; extending coverage is a matter of adding more
entries to `inc/editable-content.php`, each needing its own verified match.

**Product variations**: generic — reads whatever attributes a real product
actually has (no hardcoded "Color"/"Storage" name matching), renders the
original swatch/pill design, and wires clicks to WooCommerce's real hidden
variation `<select>` fields. Price and image update from WooCommerce's own
`found_variation` event data. Invalid-combination greying is inherited from
WooCommerce's real disabled-option state, not reimplemented. Add-to-cart
uses WooCommerce's real form, so it correctly carries the selected
variation.

**52/52 pages, 0 broken internal links, all original CSS/JS/fonts/images,
responsive behavior**: unchanged from the prior verified state — CSS was
never touched, and every `.html` link and JS `location.href` navigation
across all 52 pages resolves to a real WordPress/WooCommerce URL at render
time (verified: rendered all 52 pages through the real code, grepped for
leftover `.html` links — zero found). Two links had no source page in the
original export (`search-results.html`, `draft-v1-directory.html`) and
fall back to Shop / All Categories — a gap in the original site, not
something broken here.

## What I still cannot claim

I have no live WordPress/PHP/browser environment to click through. Every
claim above is backed by running the real code against real files and
real (stubbed) WordPress APIs — the strongest verification available here —
but a visual QA pass in an actual browser against a real install, plus a
real end-to-end order (test payment, real shipping zone, real coupon) is
still necessary before launch. That's not a gap I can close in this
environment, and I'd rather say so than imply otherwise.

## File map

```
seiridge-wc/
  inc/page-map.php          per-file routing config
  inc/html-render.php       extraction + WooCommerce data injection + real filters
  inc/link-rewrite.php      old .html links -> real WP/WC URLs
  inc/wishlist.php          real persistent wishlist
  inc/editable-content.php  real wp-admin fields for static-page text (12 pages)
  inc/variations.php        generic variation-attribute detection + admin notice
  inc/addresses.php         real multi-address book (custom feature)
  inc/account-endpoints.php real Orders list / single Order / Edit Account content
  inc/checkout.php          real shipping-method rendering, city dropdown
  inc/setup.php             one-time page creation + template assignment
  page-templates/
    page-html.php            generic passthrough (static pages)
    page-editable.php        static pages with real editable fields
    page-wishlist.php        real wishlist page
    page-cart.php             real WooCommerce cart, original design
    page-checkout.php        real WooCommerce checkout, original design
    page-myaccount.php       real WooCommerce account, ALL sub-pages, original design
  woocommerce/
    single-product.php       real product data + real generic variations, original design
    archive-product.php      real WooCommerce category archive, original design
  source-html/                all 52 original files, byte-identical, unmodified
```
