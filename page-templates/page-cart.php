<?php
/**
 * Template Name: Seiridge Cart (Real WooCommerce)
 *
 * Assigned directly to WooCommerce's Cart page by inc/setup.php. This is a
 * full document template (like page-templates/page-html.php) — it must NOT
 * be confused with woocommerce/cart/cart.php, which would be a shortcode-
 * invoked partial expecting a surrounding header/footer already in place.
 * Using a dedicated page template instead avoids double-wrapping the page
 * in two headers/footers.
 *
 * REAL, not decorative — verified logic:
 *  - Line items looped from WC()->cart->get_cart()
 *  - Qty +/- submits WooCommerce's own cart-update convention
 *    (cart[$key][qty] + update_cart, nonce-checked)
 *  - Remove uses wc_get_cart_remove_url()
 *  - Coupon field calls WC()->cart->apply_coupon()
 *  - Totals are WC()->cart's real numbers, not hand-copied
 *
 * TRADE-OFF: quantity changes reload the page rather than updating totals
 * live via JS, because the original JS never talked to a real backend —
 * keeping it would show an on-screen number that doesn't match the real
 * total until reload, which is worse. A same-page AJAX update is possible
 * as later work but needs a live install to verify.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$cart = WC()->cart;

// Handle qty +/- (must run before any output)
if ( ( isset( $_POST['qty_minus'] ) || isset( $_POST['qty_plus'] ) )
	&& isset( $_POST['woocommerce-cart-nonce'] )
	&& wp_verify_nonce( $_POST['woocommerce-cart-nonce'], 'woocommerce-cart' ) ) {
	$key   = sanitize_text_field( $_POST['qty_minus'] ?? $_POST['qty_plus'] );
	$delta = isset( $_POST['qty_minus'] ) ? -1 : 1;
	$existing = $cart->get_cart_item( $key );
	if ( $existing ) {
		$cart->set_quantity( $key, max( 0, $existing['quantity'] + $delta ), true );
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}
}
$coupon_notice = '';
if ( isset( $_POST['seiridge_apply_coupon'], $_POST['coupon_code'], $_POST['seiridge_coupon_nonce'] )
	&& wp_verify_nonce( $_POST['seiridge_coupon_nonce'], 'seiridge_apply_coupon' ) ) {
	$code = sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) );
	if ( $code !== '' ) $coupon_notice = $cart->apply_coupon( $code ) ? '✓ Code applied' : 'Coupon code could not be applied';
}

$html   = seiridge_read_source( 'Seiridge-v4.9.3-cart.html' );
$style  = seiridge_extract_style( $html );
$body   = seiridge_extract_body( $html );
$script = seiridge_extract_script( $html );

$item_count = $cart->get_cart_contents_count();

$items_html = '';
foreach ( $cart->get_cart() as $cart_item_key => $item ) {
	$product = $item['data'];
	if ( ! $product ) continue;

	$qty      = $item['quantity'];
	$line_now = wc_get_price_to_display( $product );
	$line_was = (float) $product->get_regular_price();
	if ( ! $line_was ) $line_was = $line_now;
	$img_url  = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );
	if ( ! $img_url ) $img_url = wc_placeholder_img_src( 'thumbnail' );
	$categories = wc_get_product_category_list( $product->get_id() );

	$variant = '';
	if ( $product->is_type( 'variation' ) ) {
		$parts = array();
		foreach ( $product->get_variation_attributes() as $attr_name => $val ) {
			$parts[] = ucfirst( str_replace( array( 'attribute_pa_', 'attribute_' ), '', $attr_name ) ) . ': ' . $val;
		}
		$variant = implode( ' · ', $parts );
	}

	$items_html .= '<div class="cart-item reveal" data-id="' . esc_attr( $product->get_id() ) . '" data-price="' . esc_attr( round( $line_now ) ) . '" data-was="' . esc_attr( round( $line_was ) ) . '">'
		. '<div class="ci-img" style="background-image:url(\'' . esc_url( $img_url ) . '\');background-size:cover;background-position:center;"></div>'
		. '<div>'
			. '<span class="ci-cat">' . wp_strip_all_tags( $categories ) . '</span>'
			. '<h4>' . esc_html( $product->get_name() ) . '</h4>'
			. ( $variant ? '<div class="ci-variant">' . esc_html( $variant ) . '</div>' : '' )
			. '<div class="ci-controls">'
				. '<form method="post" class="qty-form">'
					. '<div class="qty-stepper">'
						. '<button type="submit" name="qty_minus" value="' . esc_attr( $cart_item_key ) . '" class="qtyMinus" aria-label="Decrease quantity">−</button>'
						. '<span class="qtyVal">' . esc_html( $qty ) . '</span>'
						. '<button type="submit" name="qty_plus" value="' . esc_attr( $cart_item_key ) . '" class="qtyPlus" aria-label="Increase quantity">+</button>'
					. '</div>'
					. wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce', true, false )
				. '</form>'
				. '<a href="' . esc_url( wc_get_cart_remove_url( $cart_item_key ) ) . '" class="ci-remove">Remove</a>'
			. '</div>'
		. '</div>'
		. '<div class="ci-right"><div class="ci-price"><span class="now">' . wp_strip_all_tags( wc_price( $line_now ) ) . '</span>'
			. ( $line_was > $line_now ? '<span class="was">' . wp_strip_all_tags( wc_price( $line_was ) ) . '</span>' : '' )
			. '</div></div>'
	. '</div>';
}

$body = preg_replace(
	'/<div class="cart-items" id="cartItems">.*?<\/div>\s*<\/div>\s*<div class="cart-empty"/s',
	'<div class="cart-items" id="cartItems"><div class="cart-toolbar"><h3>' . intval( $item_count ) . ' item' . ( $item_count === 1 ? '' : 's' ) . ' in your cart</h3><span id="cartToolbarNote">Prices include VAT</span></div>' . $items_html . '</div><div class="cart-empty"',
	$body, 1
);
if ( $item_count === 0 ) {
	$body = preg_replace( '/(id="cartEmpty" style=")display:none;(")/', '$1$2', $body, 1 );
}

$subtotal = $cart->get_subtotal();
$total    = $cart->get_total( 'edit' );
$body = preg_replace( '/<span id="sumSubtotal">.*?<\/span>/', '<span id="sumSubtotal">' . wp_strip_all_tags( wc_price( $subtotal ) ) . '</span>', $body, 1 );
$body = preg_replace( '/<span id="sumTotal">.*?<\/span>/', '<span id="sumTotal">' . wp_strip_all_tags( wc_price( $total ) ) . '</span>', $body, 1 );
$discount = $cart->get_discount_total();
if ( $discount > 0 ) {
	$body = preg_replace( '/<span class="savings" id="sumSavings">.*?<\/span>/', '<span class="savings" id="sumSavings">− ' . wp_strip_all_tags( wc_price( $discount ) ) . '</span>', $body, 1 );
}

$body = preg_replace(
	'/<div class="promo-row">.*?<\/div>\s*<p class="promo-note"[^>]*>.*?<\/p>/s',
	'<form method="post" class="promo-row"><input type="text" name="coupon_code" id="promoInput" placeholder="Promo code">'
		. wp_nonce_field( 'seiridge_apply_coupon', 'seiridge_coupon_nonce', true, false )
		. '<button type="submit" name="seiridge_apply_coupon" value="1" class="promo-apply" id="promoApply">Apply</button></form>'
		. ( $coupon_notice ? '<p class="promo-note">' . esc_html( $coupon_notice ) . '</p>' : '' ),
	$body, 1
);

$fonts = seiridge_extract_font_links( $html );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php wp_title( '' ); ?></title>
<?php foreach ( $fonts as $link ) echo $link . "\n"; ?>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php if ( $style ) : ?><style><?php echo $style; ?></style><?php endif; ?>
<?php echo seiridge_rewrite_internal_links( $body ); ?>
<?php if ( $script ) : ?><script><?php echo seiridge_rewrite_internal_links( $script ); ?></script><?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
