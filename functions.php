<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require get_template_directory() . '/inc/page-map.php';
require get_template_directory() . '/inc/link-rewrite.php';
require get_template_directory() . '/inc/html-render.php';
require get_template_directory() . '/inc/setup.php';
require get_template_directory() . '/inc/wishlist.php';
require get_template_directory() . '/inc/editable-content.php';
require get_template_directory() . '/inc/variations.php';
require get_template_directory() . '/inc/addresses.php';
require get_template_directory() . '/inc/account-endpoints.php';
require get_template_directory() . '/inc/checkout.php';
require get_template_directory() . '/inc/site-images.php';

function seiridge_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'woocommerce' );
	// Deliberately NOT declaring wc-product-gallery-zoom / -lightbox / -slider:
	// those tell WooCommerce to enqueue JS (zoom/photoswipe/flexslider) that
	// targets WooCommerce's OWN gallery markup (.woocommerce-product-gallery,
	// .woocommerce-product-gallery__wrapper, etc). This theme's single
	// product page uses its own custom gallery markup (#galleryMain /
	// .gallery-thumbs) instead — declaring these supports serves no purpose
	// here and is a real, avoidable source of JS/CSS conflicts (that JS has
	// nothing correctly-classed to attach to, and — combined with
	// WooCommerce's own stylesheet being deliberately dequeued below — can
	// leave elements in an unrevealed/hidden state if any markup happens to
	// share a targeted class name). Removing them is a safety fix, not a
	// design change: the real gallery zoom/lightbox behaviour was never
	// wired to WooCommerce's version in the first place.
}
add_action( 'after_setup_theme', 'seiridge_theme_setup' );

/**
 * This theme supplies 100% of its own CSS (each original page's own
 * embedded <style> block) — it was never built against WooCommerce's
 * default stylesheet. Leaving that stylesheet enqueued means its generic
 * rules (e.g. broad `img { max-width:100%; height:auto; }`-type selectors)
 * load on every WooCommerce-templated page (shop archive, single product)
 * and can visually conflict with this theme's own image/logo sizing on
 * those specific pages — the standard, documented way to opt out is this
 * filter, not overriding every possible clashing rule by hand.
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

function seiridge_enqueue_assets() {
	// Every bundled page prints its own <style>/<script> inline (see
	// html-render.php) rather than through wp_enqueue_style, because the
	// original export is one big embedded <style> block per page, not a
	// shared stylesheet. We only enqueue the Google Fonts here as a shared,
	// theme-level dependency (all pages request the same font families).
	wp_enqueue_style(
		'seiridge-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&family=Poppins:wght@500;600;700&display=swap',
		array(), null
	);
}
add_action( 'wp_enqueue_scripts', 'seiridge_enqueue_assets' );

/** Admin notice if WooCommerce isn't active — nothing else in this theme will work without it. */
function seiridge_require_woocommerce_notice() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="notice notice-error"><p><strong>Seiridge theme:</strong> WooCommerce is not active. Install &amp; activate WooCommerce, then re-run setup.</p></div>';
		return;
	}
	// If WooCommerce IS active but has zero published products, every
	// catalog/home/shop page's product-data injection is skipped (there's
	// nothing real to inject), so those pages fall back to rendering their
	// ORIGINAL, unmodified demo markup — including the original's own
	// non-functional card links. This is the single most common reason
	// "product cards don't open a single product page" on a fresh
	// install: it's not a broken link, it's that there's no real product
	// yet for the link to point to.
	if ( function_exists( 'wc_get_products' ) ) {
		$count = wc_get_products( array( 'status' => 'publish', 'limit' => 1, 'return' => 'ids' ) );
		if ( empty( $count ) ) {
			echo '<div class="notice notice-warning"><p><strong>Seiridge theme:</strong> no published WooCommerce products found yet. Until you add at least one, the Home/Shop/category pages show the ORIGINAL demo content exactly as exported — including its non-functional demo links — because there is no real product to link a card to. Add a product in WooCommerce &rarr; Products to see real, clickable product cards.</p></div>';
		}
	}
}
add_action( 'admin_notices', 'seiridge_require_woocommerce_notice' );

/** Register a page template so it shows in the WP admin dropdown even though it lives in a subfolder. */
function seiridge_register_page_templates( $templates ) {
	$templates['page-templates/page-html.php']      = 'Seiridge Source Page';
	$templates['page-templates/page-editable.php']   = 'Seiridge Editable Page';
	$templates['page-templates/page-wishlist.php']   = 'Seiridge Wishlist (Real)';
	$templates['page-templates/page-cart.php']       = 'Seiridge Cart (Real WooCommerce)';
	$templates['page-templates/page-checkout.php']   = 'Seiridge Checkout (Real WooCommerce)';
	$templates['page-templates/page-myaccount.php']  = 'Seiridge My Account (Real WooCommerce)';
	return $templates;
}
add_filter( 'theme_page_templates', 'seiridge_register_page_templates' );
