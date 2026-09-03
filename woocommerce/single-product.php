<?php
/**
 * Single product page — original product-detail.html design, real WooCommerce data.
 *
 * WooCommerce loads this file automatically for any single product URL
 * (it overrides the plugin's own woocommerce/templates/single-product.php).
 * No WordPress Page is created for this — see inc/page-map.php, type
 * 'product_detail'.
 *
 * LIMITATIONS (see README): the Color/Storage swatches and pills in the
 * original design are visual only here. Wiring them to real WooCommerce
 * product variations requires your variation attribute names (e.g. "Color",
 * "Storage") to match what's below in seiridge_variation_bridge(), and
 * hasn't been visually verified in a live install.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $product;
if ( ! is_a( $product, 'WC_Product' ) ) {
	$product = wc_get_product( get_the_ID() );
}

$html   = seiridge_read_source( 'Seiridge-v4.9.3-product-detail.html' );
$style  = seiridge_extract_style( $html );
$body   = seiridge_extract_body( $html );
$script = seiridge_extract_script( $html );
$fonts  = seiridge_extract_font_links( $html );

// ---- Root-cause fix: the original design wraps the ENTIRE hero section
// (gallery column + info column — title/price/rating/add-to-cart/etc, i.e.
// everything above Description/Specifications/Reviews) in two bare
// <div class="reveal"> wrappers, which the original CSS sets to
// opacity:0 by default; only JS (an IntersectionObserver) ever makes them
// visible, by adding a ".in" class. Verified against the source file:
// this EXACT bare `class="reveal"` string occurs only twice in the whole
// page, and both occurrences are these two hero wrappers — every other
// section (Description/Specs/Reviews, etc.) uses a different, compound
// class ("sec-head reveal") and is untouched by this. If that JS doesn't
// run at the right moment in a WordPress/WooCommerce context (script
// load order, other enqueued scripts, etc.), the single most important
// part of the page — image, title, price, Add to Cart — stays invisible
// indefinitely, with no fallback. This makes those two specific wrappers
// visible unconditionally, independent of whether the reveal JS fires;
// the fade-in *animation* still plays normally if the JS does run (the
// ".in" class addition is harmless once opacity is already 1), so nothing
// about the design changes when things work — this only removes the
// single point of failure where a purchase-critical section could go
// permanently blank.
$body = preg_replace( '/<div class="reveal">/', '<div>', $body, 2 );

// ---- Real product data ----
$title       = $product->get_name();
$categories  = wc_get_product_category_list( $product->get_id() );
$price_now   = wc_get_price_to_display( $product );
$price_was   = (float) $product->get_regular_price();
$off_pct     = $price_was > 0 ? round( ( 1 - ( $price_now / $price_was ) ) * 100 ) : 0;
$avg_rating  = $product->get_average_rating();
$review_ct   = $product->get_review_count();
$main_image  = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
if ( ! $main_image ) $main_image = wc_placeholder_img_src( 'large' );
$gallery_ids = $product->get_gallery_image_ids();

// ---- Title / meta / price swaps (targeted string replace, not regex, so
//      we never touch anything we haven't explicitly verified) ----
$body = preg_replace( '/<span class="pdp-brand">.*?<\/span>/s', '<span class="pdp-brand">' . esc_html( wp_strip_all_tags( $categories ) ) . '</span>', $body, 1 );
$body = preg_replace( '/<h1 class="pdp-title">.*?<\/h1>/s', '<h1 class="pdp-title">' . esc_html( $title ) . '</h1>', $body, 1 );
$body = preg_replace(
	'/<div class="pdp-rating"><span class="stars">.*?<\/div>/s',
	'<div class="pdp-rating"><span class="stars">' . str_repeat( '★', round( $avg_rating ) ) . str_repeat( '☆', 5 - round( $avg_rating ) ) . '</span> ' . number_format( $avg_rating, 1 ) . ' <a href="#reviews">(' . $review_ct . ' reviews)</a></div>',
	$body, 1
);
$body = preg_replace(
	'/<div class="pdp-price-row">.*?<\/div>/s',
	'<div class="pdp-price-row"><span class="now">' . wp_strip_all_tags( wc_price( $price_now ) ) . '</span>'
		. ( $price_was > $price_now ? '<span class="was">' . wp_strip_all_tags( wc_price( $price_was ) ) . '</span><span class="off">-' . $off_pct . '%</span>' : '' )
		. '</div>',
	$body, 1
);

// ---- Gallery main image + thumbs -> real product image + real gallery images ----
$body = preg_replace(
	'/<div class="sw" id="galleryMain" style="[^"]*"><\/div>/',
	'<div class="sw" id="galleryMain" style="background-image:url(\'' . esc_url( $main_image ) . '\');background-size:cover;background-position:center;"></div>',
	$body, 1
);
if ( $gallery_ids ) {
	$thumbs_html = '';
	$all_images  = array_merge( array( $product->get_image_id() ), $gallery_ids );
	foreach ( $all_images as $i => $img_id ) {
		$url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
		if ( ! $url ) continue;
		$active = $i === 0 ? ' active' : '';
		$thumbs_html .= '<button class="' . ltrim( "$active" ) . '" data-img="' . esc_url( $url ) . '"><div class="sw" style="background-image:url(\'' . esc_url( $url ) . '\');background-size:cover;background-position:center;"></div></button>';
	}
	$body = preg_replace( '/<div class="gallery-thumbs" id="galleryThumbs">.*?<\/div>\s*<\/div>/s', '<div class="gallery-thumbs" id="galleryThumbs">' . $thumbs_html . '</div></div>', $body, 1 );
}

// ---- Real WooCommerce Add to Cart form (handles variations if this is a variable product) ----
ob_start();
woocommerce_template_single_add_to_cart();
$real_cart_form = ob_get_clean();
$wish_url = seiridge_wishlist_toggle_url( $product->get_id(), get_permalink( $product->get_id() ) );
$in_wishlist = in_array( $product->get_id(), seiridge_wishlist_get(), true );
$body = preg_replace(
	'/<div class="pdp-actions">.*?<\/div>/s',
	'<div class="pdp-actions">' . $real_cart_form . '<a href="' . esc_url( $wish_url ) . '" class="btn-wish-lg" aria-label="Toggle wishlist">' . ( $in_wishlist ? '♥' : '♡' ) . '</a></div>',
	$body, 1
);

// ---- Variation selectors: swap the original's hardcoded demo swatches/pills
// for the REAL product's actual variation options (any attribute names),
// wired to WooCommerce's real hidden variation form. See inc/variations.php.
$variation_data = seiridge_render_variation_selectors( $product );
if ( $variation_data['has_variations'] ) {
	$body = preg_replace(
		'/<div class="pdp-option-group">\s*<h4>Color<\/h4>.*?<\/div>\s*<div class="pdp-option-group">\s*<h4>Storage<\/h4>.*?<\/div>\s*<\/div>/s',
		$variation_data['html'],
		$body, 1
	);
}
$body .= seiridge_variation_bridge_script();
$body .= seiridge_variation_bridge_css();

// Swap the gallery-thumb click script's data source (data-grad -> data-img) so
// it works against real photos instead of CSS gradients.
$script = str_replace(
	"document.querySelectorAll('#galleryThumbs button').forEach(btn=>{",
	"document.querySelectorAll('#galleryThumbs button').forEach(btn=>{ btn.addEventListener('click', ()=>{ const u = btn.getAttribute('data-img'); if(u){ galleryMain.style.backgroundImage = `url('\${u}')`; galleryMain.style.backgroundSize='cover'; galleryMain.style.backgroundPosition='center'; } }); ",
	$script
);

// Rewrite old .html nav links to real WordPress URLs (same as every other page).
$body   = seiridge_rewrite_internal_links( $body );
$script = seiridge_rewrite_internal_links( $script );

get_header( 'shop' ); // WooCommerce hooks fire here; harmless if the theme's own header.php is minimal.
?>
<?php if ( $style ) : ?><style><?php echo $style; ?></style><?php endif; ?>
<?php echo $body; ?>
<?php if ( $script ) : ?><script><?php echo $script; ?></script><?php endif; ?>
<?php
get_footer( 'shop' );
