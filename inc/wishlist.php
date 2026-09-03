<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SEIRIDGE_WISHLIST_COOKIE', 'seiridge_wishlist' );

/** Get the current visitor's wishlist as an array of product IDs. Real storage, not in-memory JS. */
function seiridge_wishlist_get() {
	if ( is_user_logged_in() ) {
		$ids = get_user_meta( get_current_user_id(), '_seiridge_wishlist', true );
		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}
	if ( ! empty( $_COOKIE[ SEIRIDGE_WISHLIST_COOKIE ] ) ) {
		$ids = array_filter( array_map( 'intval', explode( ',', wp_unslash( $_COOKIE[ SEIRIDGE_WISHLIST_COOKIE ] ) ) ) );
		return array_values( $ids );
	}
	return array();
}

function seiridge_wishlist_save( $ids ) {
	$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), '_seiridge_wishlist', $ids );
	} else {
		setcookie( SEIRIDGE_WISHLIST_COOKIE, implode( ',', $ids ), time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN );
		$_COOKIE[ SEIRIDGE_WISHLIST_COOKIE ] = implode( ',', $ids ); // so it's correct for the rest of THIS request too
	}
}

function seiridge_wishlist_page_url() {
	$page = get_page_by_path( 'wishlist' );
	return $page ? get_permalink( $page->ID ) : home_url( '/wishlist/' );
}

/** Build a real, nonce'd toggle link for one product (used on product cards and the wishlist page). */
function seiridge_wishlist_toggle_url( $product_id, $redirect_to = '' ) {
	$in_wishlist = in_array( (int) $product_id, seiridge_wishlist_get(), true );
	$args = array(
		'seiridge_wishlist_action' => $in_wishlist ? 'remove' : 'add',
		'product_id'               => (int) $product_id,
	);
	if ( $redirect_to ) $args['redirect_to'] = rawurlencode( $redirect_to );
	$url = add_query_arg( $args, home_url( '/' ) );
	return wp_nonce_url( $url, 'seiridge_wishlist_' . $product_id );
}

/** Process add/remove on init, before any output — mirrors how the cart quantity update is handled. */
function seiridge_handle_wishlist_action() {
	if ( empty( $_GET['seiridge_wishlist_action'] ) || empty( $_GET['product_id'] ) ) return;
	$product_id = (int) $_GET['product_id'];
	if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'seiridge_wishlist_' . $product_id ) ) return;

	$ids = seiridge_wishlist_get();
	if ( $_GET['seiridge_wishlist_action'] === 'add' ) {
		$ids[] = $product_id;
	} else {
		$ids = array_diff( $ids, array( $product_id ) );
	}
	seiridge_wishlist_save( $ids );

	$redirect = ! empty( $_GET['redirect_to'] ) ? esc_url_raw( rawurldecode( $_GET['redirect_to'] ) ) : remove_query_arg( array( 'seiridge_wishlist_action', 'product_id', '_wpnonce', 'redirect_to' ) );
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'init', 'seiridge_handle_wishlist_action' );

/**
 * Patch a rendered product-card script so the ♡ icon becomes a real,
 * working add/remove-wishlist link instead of an inert <span>. Applies to
 * every catalog page (they all share the same `<span class="wish">♡</span>`
 * markup — verified against source).
 */
function seiridge_wire_wishlist_icons( $script, $id_field_expr ) {
	// Object-style cards (${p.id})
	$script = str_replace(
		'<span class="wish">♡</span>',
		'<a href="${p.wish_url || \'#\'}" class="wish" aria-label="Toggle wishlist">${p.in_wishlist ? \'♥\' : \'♡\'}</a>',
		$script
	);
	return $script;
}
