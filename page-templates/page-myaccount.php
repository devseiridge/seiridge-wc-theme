<?php
/**
 * Template Name: Seiridge My Account (Real WooCommerce, all sub-pages)
 *
 * Assigned to WooCommerce's My Account page by inc/setup.php. Full document
 * template — see page-cart.php's header comment for why (avoids the
 * double-header bug from double-wrapping a WC shortcode partial).
 *
 * Every endpoint uses its OWN original page's design, not a shared
 * WooCommerce default:
 *   dashboard    -> account-dashboard.html (real name/order-count/recent orders)
 *   orders       -> my-orders.html         (real order list)
 *   view-order   -> order-details.html     (real single order: items/address/totals/status)
 *   edit-address -> saved-addresses.html   (real multi-address book, inc/addresses.php)
 *   edit-account -> edit-profile.html      (real WC account save, inc/account-endpoints.php)
 *   logout       -> real wc_logout_url(), no page needed
 *
 * "Wishlist Items", "In Transit", "Reward Credit" dashboard stats have no
 * real WooCommerce equivalent; Wishlist count uses the real wishlist
 * (inc/wishlist.php), the other two are left at 0 rather than invented.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
	exit;
}

$current_user = wp_get_current_user();
$initials = '';
foreach ( explode( ' ', trim( $current_user->display_name ?: $current_user->user_login ) ) as $part ) if ( $part ) $initials .= strtoupper( substr( $part, 0, 1 ) );
$initials = substr( $initials, 0, 2 ) ?: 'U';

/** Shared sidebar link rewrite + user-card fill, applied to every endpoint's page. */
function seiridge_account_sidebar_fill( $body, $active_endpoint, $initials, $display_name ) {
	$body = preg_replace( '/<div class="acc-avatar">.*?<\/div>/', '<div class="acc-avatar">' . esc_html( $initials ) . '</div>', $body, 1 );
	$body = preg_replace( '/<h4>[^<]*<\/h4>\s*<p>Member since \d{4}<\/p>/', '<h4>' . esc_html( $display_name ) . '</h4><p>Member since ' . esc_html( date( 'Y' ) ) . '</p>', $body, 1 );

	$link_targets = array(
		'seiridge-account-dashboard.html' => 'dashboard',
		'seiridge-my-orders.html'         => 'orders',
		'seiridge-profile.html'           => 'edit-account',
		'seiridge-saved-addresses.html'   => 'edit-address',
	);
	foreach ( $link_targets as $old => $endpoint ) {
		$url = wc_get_account_endpoint_url( $endpoint );
		$is_active = ( $endpoint === $active_endpoint );
		// remove any existing class="active" on this link, then re-add if it's the current page
		$body = preg_replace( '/href="' . preg_quote( $old, '/' ) . '"( class="active")?/', 'href="' . esc_url( $url ) . '"' . ( $is_active ? ' class="active"' : '' ), $body );
	}
	$body = str_replace( 'href="seiridge-login-register.html" class="danger"', 'href="' . esc_url( wc_logout_url() ) . '" class="danger"', $body );
	if ( function_exists( 'seiridge_wishlist_page_url' ) ) {
		$body = str_replace( 'href="seiridge-wishlist.html"', 'href="' . esc_url( seiridge_wishlist_page_url() ) . '"', $body );
	}
	return $body;
}

$endpoint = 'dashboard';
foreach ( array( 'orders', 'view-order', 'edit-address', 'edit-account' ) as $ep ) {
	if ( is_wc_endpoint_url( $ep ) ) { $endpoint = $ep; break; }
}

switch ( $endpoint ) {

	case 'orders':
		$html  = seiridge_read_source( 'Seiridge-v3.6.2-my-orders.html' );
		$style = seiridge_extract_style( $html );
		$body  = seiridge_extract_body( $html );
		$body  = preg_replace( '/<div class="list-row">.*?(?=\s*<\/div>\s*\n\s*<\/div>\s*<\/div>\s*<\/section>)/s', seiridge_render_orders_list(), $body, 1 );
		break;

	case 'view-order':
		$order_id = get_query_var( 'view-order' );
		$data = seiridge_render_order_details( $order_id );
		$html  = seiridge_read_source( 'Seiridge-v3.6.2-order-details.html' );
		$style = seiridge_extract_style( $html );
		$body  = seiridge_extract_body( $html );
		if ( is_array( $data ) ) {
			$body = preg_replace( '/<h1>Order #SRD-284193<\/h1>/', '<h1>' . esc_html( $data['title'] ) . '</h1>', $body, 1 );
			$body = preg_replace( '/<span class="current">Order #SRD-284193<\/span>/', '<span class="current">' . esc_html( $data['title'] ) . '</span>', $body, 1 );
			$body = preg_replace( '/<span class="status-pill transit">In Transit<\/span>/', '<span class="status-pill ' . esc_attr( $data['pill'] ) . '">' . esc_html( $data['status'] ) . '</span>', $body, 1 );
			$body = preg_replace( '/<div class="timeline">.*?<\/div>\s*(?=<div class="form-actions")/s', $data['timeline'], $body, 1 );
			$body = preg_replace( '/<a href="seiridge-order-tracking\.html" class="btn-primary">Track Shipment<\/a>/', '<a href="' . $data['track_url'] . '" class="btn-primary">Track Shipment</a>', $body, 1 );
			$body = preg_replace( '/<div class="list-row">.*?<\/div>\s*<\/div>\s*(?=<\/div>\s*<div class="form-grid")/s', $data['items'], $body, 1 );
			$body = preg_replace( '/Rafi Sultana<br>House 14, Road 7, Banani<br>Dhaka 1213, Bangladesh<br>\+880 1XXX-XXXXXX/', $data['address'], $body, 1 );
			$body = preg_replace( '/<div class="order-summary-box">.*?<\/div>\s*<\/div>/s', $data['summary'], $body, 1 );
		} else {
			$body = '<div class="wrap" style="padding:60px 0;"><p>' . esc_html( $data ) . '</p></div>';
		}
		break;

	case 'edit-address':
		$html  = seiridge_read_source( 'Seiridge-v3.6.2-saved-addresses.html' );
		$style = seiridge_extract_style( $html );
		$body  = seiridge_extract_body( $html );
		$body  = preg_replace( '/<div class="addr-grid">.*?<a href="#" class="add-new-card">.*?<\/a>\s*<\/div>/s', '<div class="addr-grid">' . seiridge_render_address_book() . '</div>', $body, 1 );
		break;

	case 'edit-account':
		$html  = seiridge_read_source( 'Seiridge-v3.6.2-edit-profile.html' );
		$style = seiridge_extract_style( $html );
		$body  = seiridge_extract_body( $html );
		$body  = preg_replace( '/<div class="field"><label>Full Name<\/label>.*?<\/div>\s*(<div class="field">.*?<\/div>\s*){2,4}/s', seiridge_render_edit_account_form(), $body, 1 );
		break;

	default: // dashboard
		$html  = seiridge_read_source( 'Seiridge-v4.9.3-account-dashboard.html' );
		$style = seiridge_extract_style( $html );
		$body  = seiridge_extract_body( $html );

		$order_count = function_exists( 'wc_get_customer_order_count' ) ? wc_get_customer_order_count( $current_user->ID ) : 0;
		$wish_count  = function_exists( 'seiridge_wishlist_get' ) ? count( seiridge_wishlist_get() ) : 0;

		$body = preg_replace( '/<div class="num">14<\/div>/', '<div class="num">' . intval( $order_count ) . '</div>', $body, 1 );
		$body = preg_replace( '/(<div class="stat-box"><div class="num">)\d+(<\/div><div class="lbl">Wishlist Items<\/div>)/', '${1}' . intval( $wish_count ) . '$2', $body, 1 );

		$orders_html = '';
		if ( function_exists( 'wc_get_orders' ) ) {
			foreach ( wc_get_orders( array( 'customer' => $current_user->ID, 'limit' => 3, 'orderby' => 'date', 'order' => 'DESC' ) ) as $order ) {
				$items = $order->get_items();
				$first_item = $items ? reset( $items ) : null;
				$title = $first_item ? $first_item->get_name() : 'Order';
				$orders_html .= '<div class="list-row"><div class="row-thumb"><div class="sw"></div></div>'
					. '<div class="row-body"><h4>' . esc_html( $title ) . '</h4>'
					. '<div class="meta">Order #' . esc_html( $order->get_order_number() ) . ' · Placed ' . esc_html( $order->get_date_created()->date( 'j M Y' ) ) . '</div></div>'
					. '<div class="row-actions"><span class="status-pill">' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</span>'
					. '<a href="' . esc_url( $order->get_view_order_url() ) . '" class="btn-ghost btn-sm">View</a></div></div>';
			}
		}
		if ( ! $orders_html ) $orders_html = '<p class="sub">No orders yet.</p>';
		$body = preg_replace(
			'/(<div><h2>Recent Orders<\/h2>.*?<\/div>\s*<a[^>]*>View Orders.*?<\/a>\s*<\/div>).*?(<\/div>\s*(?=<div class="acc-card))/s',
			'$1' . $orders_html . '$2',
			$body, 1
		);
		break;
}

$body = seiridge_account_sidebar_fill( $body, $endpoint, $initials, $current_user->display_name ?: $current_user->user_login );
$body = seiridge_rewrite_internal_links( $body );

$fonts = seiridge_extract_font_links( $html );
$script = seiridge_rewrite_internal_links( seiridge_extract_script( $html ) );
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
