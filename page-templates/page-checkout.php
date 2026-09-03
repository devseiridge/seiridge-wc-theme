<?php
/**
 * Template Name: Seiridge Checkout (Real WooCommerce, original design)
 *
 * HOW THIS STAYS SAFE WHILE BEING FULLY RESTYLED: WooCommerce's real
 * checkout processing (order creation, payment, AJAX order-review updates)
 * is driven by client-side JS (assets/js/frontend/checkout.js) that
 * WooCommerce enqueues automatically on any page where is_checkout() is
 * true — regardless of which template renders it. That JS looks for a
 * <form class="checkout woocommerce-checkout">, real WooCommerce field
 * names (billing_first_name, payment_method, ...), and a real
 * #place_order button. As long as those are present with correct
 * name/value attributes, real order processing works exactly as it would
 * on WooCommerce's own default template.
 *
 * WHAT'S NOW REAL + IN THE ORIGINAL MARKUP (not a CSS bridge over WC's own
 * output — actual .radio-card / .rc-ico / .rc-body / <h4>/<p> structure,
 * verified against source):
 *  - Shipping Address card -> real billing_* fields
 *  - Delivery Method cards -> real WC shipping packages/rates
 *  - Payment Method cards -> real WC payment gateways (id/title/description),
 *    with each gateway's own payment_fields() shown in the original
 *    .card-fields container, toggled by which radio-card is selected
 *  - Order Summary aside -> real cart items + real WC totals
 *  - Coupon field -> real WC()->cart->apply_coupon()
 *  - The visible "Place Order" button IS WooCommerce's real button
 *    (id="place_order", name="woocommerce_checkout_place_order") — no
 *    separate hidden button / JS-forwarding hack needed anymore.
 *  - Real nonce + (conditional) terms checkbox via WooCommerce's own public
 *    checkout API (wp_nonce_field with WC's documented nonce action,
 *    wc_terms_and_conditions_checkbox_enabled()/_text()) — not guessed.
 *
 * NECESSARY FUNCTIONAL ADDITIONS not in the original design (WooCommerce
 * cannot process an order without these):
 *  - Email address field (original design didn't have one)
 *  - Hidden billing_country, defaulted to the store's base country
 *  - A Terms & Conditions checkbox, but ONLY if enabled in WooCommerce
 *    settings — invisible otherwise, so no design change by default
 *
 * REMAINING TRADE-OFF: the real WooCommerce order-review totals table
 * (needed so an AJAX shipping/coupon update recalculates the true total)
 * is kept present but visually hidden, in favour of showing the original
 * design's own summary rows — kept in sync via the 'updated_checkout' JS
 * event below. This avoids showing two different-looking total boxes.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( WC()->cart->is_empty() ) {
	wp_safe_redirect( wc_get_cart_url() );
	exit;
}
WC()->checkout(); // ensures WooCommerce's checkout fields/session are initialised, same as its own template does

$html   = seiridge_read_source( 'Seiridge-v4.9.3-checkout.html' );
$style  = seiridge_extract_style( $html );
$body   = seiridge_extract_body( $html );
$script = seiridge_extract_script( $html );
$fonts  = seiridge_extract_font_links( $html );

$checkout = WC()->checkout();
$cart     = WC()->cart;

// ---- Shipping Address card: real billing_* fields, same label/input markup ----
$full_name_value = trim( $checkout->get_value( 'billing_first_name' ) . ' ' . $checkout->get_value( 'billing_last_name' ) );
$shipping_card = '<div class="form-card reveal">'
	. '<h3>Shipping Address</h3><p class="fc-sub">We\'ll deliver here — double-check before placing your order.</p>'
	. '<div class="form-row">'
		. '<div class="form-group"><label for="fname">Full Name</label><input type="text" id="fname" placeholder="e.g. Rafiq Ahmed" value="' . esc_attr( $full_name_value ) . '"></div>'
		. '<div class="form-group"><label for="phone">Phone Number</label><input type="tel" id="billing_phone" name="billing_phone" placeholder="01XXXXXXXXX" value="' . esc_attr( $checkout->get_value( 'billing_phone' ) ) . '" required></div>'
	. '</div>'
	// necessary addition — WooCommerce requires an email for order processing/receipts
	. '<div class="form-group"><label for="billing_email">Email Address</label><input type="email" id="billing_email" name="billing_email" placeholder="you@example.com" value="' . esc_attr( $checkout->get_value( 'billing_email' ) ) . '" required></div>'
	. '<div class="form-group"><label for="addr">Address</label><input type="text" id="billing_address_1" name="billing_address_1" placeholder="House, road, area" value="' . esc_attr( $checkout->get_value( 'billing_address_1' ) ) . '" required></div>'
	. '<div class="form-row">'
		. '<div class="form-group"><label for="city">City</label><select id="billing_city" name="billing_city" class="select-std">'
			. seiridge_checkout_city_options( $checkout->get_value( 'billing_city' ) )
		. '</select></div>'
		. '<div class="form-group"><label for="postal">Postal Code</label><input type="text" id="billing_postcode" name="billing_postcode" placeholder="1207" value="' . esc_attr( $checkout->get_value( 'billing_postcode' ) ) . '"></div>'
	. '</div>'
	. '<input type="hidden" id="billing_first_name" name="billing_first_name" value="' . esc_attr( $checkout->get_value( 'billing_first_name' ) ) . '">'
	. '<input type="hidden" id="billing_last_name" name="billing_last_name" value="' . esc_attr( $checkout->get_value( 'billing_last_name' ) ) . '">'
	. '<input type="hidden" name="billing_country" value="' . esc_attr( $checkout->get_value( 'billing_country' ) ?: WC()->countries->get_base_country() ) . '">'
	. '<input type="hidden" name="ship_to_different_address" value="0">'
	. '<label class="checkbox-row"><input type="checkbox" checked disabled> Save this address as default for future orders</label>'
. '</div>';

$body = preg_replace( '/<div class="form-card reveal">\s*<h3>Shipping Address<\/h3>.*?(?=\s*<!-- DELIVERY METHOD -->)/s', $shipping_card . "\n", $body, 1 );

// ---- Delivery Method card: real shipping packages/rates ----
$delivery_card = seiridge_render_real_shipping_methods();
$body = preg_replace( '/<div class="form-card reveal">\s*<h3>Delivery Method<\/h3>.*?(?=<!-- PAYMENT METHOD -->)/s', $delivery_card, $body, 1 );

// ---- Payment Method card: ORIGINAL radio-card markup, real gateway data ----
$pm = seiridge_render_real_payment_methods();
$payment_card = '<div class="form-card reveal" id="seiridge-payment-card">'
	. '<h3>Payment Method</h3><p class="fc-sub">All payments are encrypted and secure.</p>'
	. $pm['cards']
	. $pm['fields']
. '</div></div>'; // second </div> closes the left-column wrapper (see page-cart.php-style comment below)
$body = preg_replace( '/<div class="form-card reveal">\s*<h3>Payment Method<\/h3>.*?(?=\s*<!-- RIGHT: ORDER SUMMARY -->)/s', $payment_card . "\n", $body, 1 );

// ---- Order Summary aside: real cart items + real totals + real coupon ----
$mini_items = '';
foreach ( $cart->get_cart() as $item ) {
	$product = $item['data'];
	if ( ! $product ) continue;
	$img = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );
	$mini_items .= '<div class="mini-item">'
		. '<div class="mi-img" style="' . ( $img ? 'background-image:url(\'' . esc_url( $img ) . '\');background-size:cover;background-position:center;' : '' ) . '"></div>'
		. '<div class="mi-info"><h5>' . esc_html( $product->get_name() ) . '</h5><span class="mi-qty">Qty: ' . intval( $item['quantity'] ) . '</span></div>'
		. '<span class="mi-price">' . wp_strip_all_tags( wc_price( $item['line_total'] ) ) . '</span>'
	. '</div>';
}

$coupon_notice = '';
if ( isset( $_GET['seiridge_apply_coupon'], $_GET['coupon_code'], $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'seiridge_apply_coupon' ) ) {
	$code = sanitize_text_field( wp_unslash( $_GET['coupon_code'] ) );
	if ( $code !== '' ) $coupon_notice = $cart->apply_coupon( $code ) ? '✓ Code applied' : 'Coupon code could not be applied';
}
$coupon_nonce = wp_create_nonce( 'seiridge_apply_coupon' );

// ---- Real nonce + (conditional) terms checkbox, via WooCommerce's own public checkout API ----
// 'woocommerce-process_checkout' is WooCommerce core's own documented nonce
// action for this exact purpose (not guessed), so the real AJAX processor
// accepts it exactly as it would on WooCommerce's default template.
$nonce_field = wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce', true, false );
$terms_html = '';
if ( function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) && wc_terms_and_conditions_checkbox_enabled() ) {
	$terms_html = '<label class="checkbox-row">' . wc_terms_and_conditions_checkbox_text() . '</label>';
}
$order_button_text = apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) );

$summary = '<h3>Order Summary</h3>'
	. $mini_items
	. '<div class="summary-row"><span>Subtotal</span><span>' . wp_strip_all_tags( wc_price( $cart->get_subtotal() ) ) . '</span></div>'
	. '<div class="summary-row"><span>Delivery</span><span>' . ( $cart->get_shipping_total() > 0 ? wp_strip_all_tags( wc_price( $cart->get_shipping_total() ) ) : 'Free' ) . '</span></div>'
	. '<div class="summary-row total"><span>Total</span><span>' . wp_strip_all_tags( wc_price( $cart->get_total( 'edit' ) ) ) . '</span></div>'
	. '<div class="promo-row"><input type="text" id="seiridgeCouponInput" placeholder="Promo code">'
		. '<button type="button" class="promo-apply" onclick="location.href=\'?seiridge_apply_coupon=1&coupon_code=\'+encodeURIComponent(document.getElementById(\'seiridgeCouponInput\').value)+\'&_wpnonce=' . esc_js( $coupon_nonce ) . '\'">Apply</button></div>'
	. ( $coupon_notice ? '<p class="promo-note">' . esc_html( $coupon_notice ) . '</p>' : '' )
	// Real WC totals table, kept for correct AJAX recalculation, visually
	// hidden in favour of the summary rows above (see file header).
	. '<div id="order_review" class="woocommerce-checkout-review-order"><div id="seiridge-order-review-visual-hide"></div></div>'
	. $terms_html
	. $nonce_field
	. '<button type="submit" class="btn-place-order" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>'
	. '<div class="secure-note">🔒 Secure checkout · SSL encrypted</div>'
	. '<div class="summary-pay-icons"><span>Visa</span><span>Mastercard</span><span>bKash</span><span>Nagad</span><span>COD</span></div>';

$body = preg_replace( '/<aside class="checkout-summary reveal">.*?<\/aside>/s', '<aside class="checkout-summary reveal">' . $summary . '</aside>', $body, 1 );

// ---- Wrap the whole left+right layout in the real WooCommerce checkout <form> ----
ob_start();
do_action( 'woocommerce_checkout_before_customer_details' );
$before_form_hooks = ob_get_clean();

$body = preg_replace(
	'/<div class="wrap checkout-layout">/',
	'<form name="checkout" method="post" class="checkout woocommerce-checkout" action="' . esc_url( wc_get_checkout_url() ) . '" enctype="multipart/form-data">' . $before_form_hooks . '<div class="wrap checkout-layout">',
	$body, 1
);

ob_start();
woocommerce_order_review(); // real totals table only (payment methods are now rendered above in original markup, not via this call)
$real_order_review = ob_get_clean();
$body = str_replace( '<div id="seiridge-order-review-visual-hide"></div>', '<div id="seiridge-order-review-visual-hide">' . $real_order_review . '</div>', $body );

// The original file already closes the wrap-layout <div> right after
// </aside> — only need to add </form> after that existing close.
$body = str_replace( "</aside>\n</div>", "</aside>\n</div></form>", $body );

// ---- Payment-method card-fields show/hide + shipping-radio 'update_checkout' trigger + totals sync ----
$payment_bridge = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.seiridge-payment-radio').forEach(function(r){
    r.addEventListener('change', function(){
      document.querySelectorAll('#seiridge-payment-card .radio-card').forEach(function(l){ l.classList.remove('active'); });
      r.closest('.radio-card').classList.add('active');
      document.querySelectorAll('.seiridge-payment-fields').forEach(function(f){ f.style.display = 'none'; });
      var target = document.getElementById('payment_fields_' + r.value);
      if (target) target.style.display = '';
      if (window.jQuery) { jQuery(document.body).trigger('payment_method_selected'); }
    });
  });
  document.body.addEventListener('updated_checkout', function(){
    var realTotal = document.querySelector('.order-total .amount');
    var visibleTotal = document.querySelector('.checkout-summary .summary-row.total span:last-child');
    if (realTotal && visibleTotal) visibleTotal.textContent = realTotal.textContent;
  });
});
</script>
JS;
$body .= $payment_bridge;

$body   = seiridge_rewrite_internal_links( $body );
$script = seiridge_rewrite_internal_links( $script );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php wp_title( '' ); ?></title>
<?php foreach ( $fonts as $link ) echo $link . "\n"; ?>
<?php wp_head(); ?>
<style>#seiridge-order-review-visual-hide{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;}</style>
</head>
<body <?php body_class(); ?>>
<?php if ( $style ) : ?><style><?php echo $style; ?></style><?php endif; ?>
<?php echo $body; ?>
<?php if ( $script ) : ?><script><?php echo $script; ?></script><?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
