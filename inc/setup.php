<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** Which page template a given page-map 'type' should be assigned. */
function seiridge_template_for_type( $type ) {
	switch ( $type ) {
		case 'wishlist': return 'page-templates/page-wishlist.php';
		case 'editable':  return 'page-templates/page-editable.php';
		default:          return 'page-templates/page-html.php';
	}
}

/**
 * The 9 real product categories from the original site (brand-single and
 * flash-sale are promotional pages, not categories, and are intentionally
 * excluded). Creates any that don't already exist as WooCommerce
 * `product_cat` terms — checked by slug first, so re-running this never
 * creates a duplicate, and an admin's own edits to an existing category
 * (renaming, re-slugging) are never overwritten.
 */
function seiridge_category_map() {
	return array(
		'electronics'   => 'Electronics & Gadgets',
		'phones'        => 'Phones & Mobile Accessories',
		'fashion'       => 'Fashion & Apparel',
		'grocery'       => 'Groceries & Fresh',
		'home-living'   => 'Home & Living',
		'sports'        => 'Sports & Fitness',
		'books'         => 'Books & Stationery',
		'beauty'        => 'Beauty & Personal Care',
		'automotive'    => 'Automotive & Auto Care',
	);
}

/** Create any missing WooCommerce product categories from the map above. Idempotent. */
function seiridge_ensure_product_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) return; // WooCommerce not active
	foreach ( seiridge_category_map() as $slug => $name ) {
		if ( term_exists( $slug, 'product_cat' ) ) continue; // already exists — never touch it
		wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
	}
}

/** Runs once on theme activation. Also exposed as a manual "Run setup again" admin action. */
function seiridge_run_setup() {
	$map = seiridge_page_map();

	foreach ( $map as $filename => $entry ) {

		if ( $entry['type'] === 'product_detail' ) continue; // handled by woocommerce/single-product.php, not a WP Page

		if ( $entry['type'] === 'wc_core' ) {
			// Don't create a duplicate page — assign our real template to
			// WooCommerce's OWN cart/checkout/my-account page instead.
			$wc_templates = array(
				'cart'      => 'page-templates/page-cart.php',
				'checkout'  => 'page-templates/page-checkout.php',
				'myaccount' => 'page-templates/page-myaccount.php',
			);
			$wc_page = get_page_by_path( $entry['wc_page'] );
			if ( $wc_page && isset( $wc_templates[ $entry['wc_page'] ] ) ) {
				update_post_meta( $wc_page->ID, '_wp_page_template', $wc_templates[ $entry['wc_page'] ] );
			}
			continue;
		}

		$existing = get_page_by_path( $entry['slug'] );
		$post_id  = $existing ? $existing->ID : null;

		$postarr = array(
			'ID'           => $post_id,
			'post_title'   => $entry['title'],
			'post_name'    => $entry['slug'],
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '', // content comes from the template, not the block editor
		);
		$post_id = wp_insert_post( $postarr );

		if ( is_wp_error( $post_id ) || ! $post_id ) continue;

		update_post_meta( $post_id, '_wp_page_template', seiridge_template_for_type( $entry['type'] ) );
		update_post_meta( $post_id, '_seiridge_source_file', $filename );
		update_post_meta( $post_id, '_seiridge_page_entry', $entry );

		if ( in_array( $entry['type'], array( 'editable', 'home' ), true ) && function_exists( 'seiridge_populate_editable_defaults' ) ) {
			seiridge_populate_editable_defaults( $post_id, $filename, $entry['slug'] );
		}

		if ( $entry['type'] === 'home' ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $post_id );
		}
	}

	// Point WooCommerce's built-in Cart / Checkout / My Account pages at the
	// IDs WooCommerce already created on plugin activation — we don't
	// duplicate those, we just make sure the option points at them.
	if ( class_exists( 'WooCommerce' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $wc_page ) {
			$page = get_page_by_path( $wc_page );
			if ( $page ) update_option( 'woocommerce_' . $wc_page . '_page_id', $page->ID );
		}
		// Shop page: WooCommerce creates this itself on its own activation
		// (default slug 'shop') — just make sure the option points at it,
		// same safety-net pattern as cart/checkout/account.
		$shop_page = get_page_by_path( 'shop' );
		if ( $shop_page ) update_option( 'woocommerce_shop_page_id', $shop_page->ID );

		// Terms & Conditions: link WooCommerce's checkout-terms setting to
		// the real terms-conditions page this theme already creates, only
		// if nothing is configured yet (never override an admin's choice).
		if ( ! get_option( 'woocommerce_terms_page_id' ) ) {
			$terms_page = get_page_by_path( 'terms-conditions' );
			if ( $terms_page ) update_option( 'woocommerce_terms_page_id', $terms_page->ID );
		}

		seiridge_ensure_product_categories();
	}

	update_option( 'seiridge_setup_ran', current_time( 'mysql' ) );
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'seiridge_run_setup' );

/** Manual re-run link in wp-admin, in case an admin adds pages later or wants to re-sync titles/templates. */
function seiridge_admin_setup_notice() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( isset( $_GET['seiridge_run_setup'] ) && check_admin_referer( 'seiridge_run_setup' ) ) {
		seiridge_run_setup();
		echo '<div class="notice notice-success"><p>Seiridge setup ran again — all ' . count( seiridge_page_map() ) . ' source pages were (re)created/updated.</p></div>';
		return;
	}
	$url = wp_nonce_url( admin_url( '?seiridge_run_setup=1' ), 'seiridge_run_setup' );
	echo '<div class="notice notice-info"><p>Seiridge theme: <a href="' . esc_url( $url ) . '">Run the one-time page setup again</a> (creates/updates all imported pages; safe to click more than once).</p></div>';
}
add_action( 'admin_notices', 'seiridge_admin_setup_notice' );
