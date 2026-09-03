<?php
/**
 * Template Name: Seiridge Wishlist (Real)
 *
 * Assigned to the 'wishlist' page by inc/setup.php. Original wishlist.html
 * shell; the item list is now server-rendered from a REAL, persistent
 * wishlist (inc/wishlist.php — usermeta for logged-in users, a cookie for
 * guests), replacing the original's in-memory `let WISHLIST = [...]` JS
 * array that reset on every page reload and was never real to begin with.
 *
 * Add: the ♡ icon on every product card sitewide (see inc/html-render.php)
 * now links to a real, nonce'd toggle URL.
 * Remove: the ✕ button here is a real toggle-remove link, same mechanism.
 * Clear: real action, empties the stored wishlist.
 * Add to Cart: real WooCommerce add-to-cart link.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_GET['seiridge_wishlist_clear'] ) && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'seiridge_wishlist_clear' ) ) {
	seiridge_wishlist_save( array() );
	wp_safe_redirect( remove_query_arg( array( 'seiridge_wishlist_clear', '_wpnonce' ) ) );
	exit;
}

$html   = seiridge_read_source( 'Seiridge-v3_6_2-wishlist.html' );
$style  = seiridge_extract_style( $html );
$body   = seiridge_extract_body( $html );
$script = seiridge_extract_script( $html ); // kept for the drawer/nav-bar JS, NOT for wishlist rendering
$fonts  = seiridge_extract_font_links( $html );

// Strip out the original in-memory wishlist logic entirely — it's replaced by real server rendering below.
$script = preg_replace( '/\/\* ---------- wishlist data.*$/s', '', $script );

$ids = seiridge_wishlist_get();
$rows_html = '';
$count = 0;
if ( $ids && function_exists( 'wc_get_products' ) ) {
	$products = wc_get_products( array( 'include' => $ids, 'status' => 'publish' ) );
	foreach ( $products as $product ) {
		$count++;
		$now = wc_get_price_to_display( $product );
		$was = (float) $product->get_regular_price();
		if ( ! $was ) $was = $now;
		$off = $was > 0 ? round( ( 1 - $now / $was ) * 100 ) : 0;
		$img = wp_get_attachment_image_url( $product->get_image_id(), 'medium' );
		if ( ! $img ) $img = wc_placeholder_img_src( 'medium' );
		$cat = wp_strip_all_tags( wc_get_product_category_list( $product->get_id() ) );

		$remove_url = seiridge_wishlist_toggle_url( $product->get_id(), get_permalink() );
		$cart_url   = wp_nonce_url( add_query_arg( array( 'add-to-cart' => $product->get_id() ), wc_get_cart_url() ), 'woocommerce-add-to-cart' );

		$rows_html .= '<div class="wl-row reveal in" data-id="' . esc_attr( $product->get_id() ) . '">'
			. '<a href="' . esc_url( $remove_url ) . '" class="wl-remove" aria-label="Remove from wishlist">✕</a>'
			. '<div class="wl-thumb"><span class="wl-badge">-' . esc_html( $off ) . '%</span>'
			. '<div class="sw" style="background-image:url(\'' . esc_url( $img ) . '\');background-size:cover;background-position:center;"></div></div>'
			. '<div class="wl-info"><span class="cat">' . esc_html( $cat ) . '</span><h4>' . esc_html( $product->get_name() ) . '</h4>'
			. '<div class="price-row"><span class="now">' . wp_strip_all_tags( wc_price( $now ) ) . '</span>'
			. ( $was > $now ? '<span class="was">' . wp_strip_all_tags( wc_price( $was ) ) . '</span>' : '' ) . '</div></div>'
			. '<div class="wl-actions"><a href="' . esc_url( get_permalink( $product->get_id() ) ) . '" class="wl-buy">View</a>'
			. '<a href="' . esc_url( $cart_url ) . '" class="wl-cart">Add to Cart</a></div>'
		. '</div>';
	}
}

$body = preg_replace( '/<strong id="wlCount">3<\/strong>/', '<strong id="wlCount">' . intval( $count ) . '</strong>', $body, 1 );
$clear_url = wp_nonce_url( add_query_arg( 'seiridge_wishlist_clear', '1' ), 'seiridge_wishlist_clear' );
$body = preg_replace( '/<a href="#" class="wl-clear" id="wlClear">/', '<a href="' . esc_url( $clear_url ) . '" class="wl-clear" id="wlClear">', $body, 1 );
$body = preg_replace( '/<div class="wl-list" id="wlGrid"><\/div>/', '<div class="wl-list" id="wlGrid" style="' . ( $count ? '' : 'display:none;' ) . '">' . $rows_html . '</div>', $body, 1 );
if ( $count > 0 ) {
	$body = preg_replace( '/(id="wlEmpty")/', '$1 style="display:none;"', $body, 1 );
}

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
</head>
<body <?php body_class(); ?>>
<?php if ( $style ) : ?><style><?php echo $style; ?></style><?php endif; ?>
<?php echo $body; ?>
<?php if ( $script ) : ?><script><?php echo $script; ?></script><?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
