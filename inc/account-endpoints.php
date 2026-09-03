<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** Orders list — original my-orders.html .list-row markup, real customer orders. */
function seiridge_render_orders_list() {
	$html = seiridge_read_source( 'Seiridge-v3.6.2-my-orders.html' );
	preg_match( '/<div class="list-row">.*?<\/div>\s*<\/div>\s*<\/div>/s', seiridge_extract_body( $html ), $m ); // not used directly, kept for reference

	$user_id = get_current_user_id();
	$orders  = function_exists( 'wc_get_orders' ) ? wc_get_orders( array( 'customer' => $user_id, 'limit' => 20, 'orderby' => 'date', 'order' => 'DESC' ) ) : array();

	$status_class_map = array( 'completed' => 'delivered', 'processing' => 'transit', 'pending' => 'processing', 'on-hold' => 'processing', 'cancelled' => 'cancelled', 'refunded' => 'cancelled', 'failed' => 'cancelled' );

	$rows = '';
	foreach ( $orders as $order ) {
		$items = $order->get_items();
		$first_item = $items ? reset( $items ) : null;
		$title = $first_item ? $first_item->get_name() : 'Order';
		$img = '';
		if ( $first_item ) {
			$product = $first_item->get_product();
			if ( $product ) $img = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );
		}
		$status = $order->get_status();
		$pill_class = $status_class_map[ $status ] ?? 'processing';

		$rows .= '<div class="list-row"><div class="row-thumb">' . ( $img ? '<div class="sw" style="background-image:url(\'' . esc_url( $img ) . '\');background-size:cover;background-position:center;"></div>' : '<div class="sw"></div>' ) . '</div>'
			. '<div class="row-body"><h4>' . esc_html( $title ) . '</h4>'
			. '<div class="meta">Order #' . esc_html( $order->get_order_number() ) . ' · ' . intval( $order->get_item_count() ) . ' item' . ( $order->get_item_count() === 1 ? '' : 's' ) . ' · ' . wp_strip_all_tags( wc_price( $order->get_total() ) ) . '</div></div>'
			. '<div class="row-actions"><span class="status-pill ' . esc_attr( $pill_class ) . '">' . esc_html( wc_get_order_status_name( $status ) ) . '</span>'
			. '<a href="' . esc_url( $order->get_view_order_url() ) . '" class="btn-primary btn-sm">Details</a></div></div>';
	}
	if ( ! $rows ) $rows = '<p class="sub">No orders yet.</p>';
	return $rows;
}

/** Single order view — original order-details.html layout, real order data. */
function seiridge_render_order_details( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_customer_id() !== get_current_user_id() ) {
		return '<p>Order not found.</p>';
	}

	$status = $order->get_status();
	$status_names = array( 'completed' => 'delivered', 'processing' => 'transit', 'pending' => 'processing', 'on-hold' => 'processing', 'cancelled' => 'cancelled', 'refunded' => 'cancelled', 'failed' => 'cancelled' );
	$pill_class = $status_names[ $status ] ?? 'processing';

	// Real 3-step status (core WooCommerce doesn't track "shipped"/"out for delivery" without a
	// shipment-tracking plugin, so the original 5-step timeline is honestly reduced to what
	// WooCommerce's order status actually tells us: Placed / Confirmed / Delivered).
	$placed_done    = true;
	$confirmed_done = in_array( $status, array( 'processing', 'completed' ), true );
	$delivered_done = ( $status === 'completed' );
	$timeline = '<div class="timeline">'
		. '<div class="t-step done"><div class="t-dot"></div><h5>Order Placed</h5><p>' . esc_html( $order->get_date_created()->date( 'j M Y, g:i A' ) ) . '</p></div>'
		. '<div class="t-step' . ( $confirmed_done ? ' done' : '' ) . '"><div class="t-dot"></div><h5>Confirmed</h5><p>' . ( $confirmed_done ? 'Done' : 'Pending' ) . '</p></div>'
		. '<div class="t-step' . ( $delivered_done ? ' done' : '' ) . '"><div class="t-dot"></div><h5>Delivered</h5><p>' . ( $delivered_done ? esc_html( $order->get_date_completed() ? $order->get_date_completed()->date( 'j M Y' ) : 'Done' ) : 'Pending' ) . '</p></div>'
	. '</div>';

	$items_html = '';
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		$img = $product ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '';
		$variant = '';
		if ( $product && $product->is_type( 'variation' ) ) {
			$parts = array();
			foreach ( $product->get_variation_attributes() as $k => $v ) $parts[] = ucfirst( str_replace( array( 'attribute_pa_', 'attribute_' ), '', $k ) ) . ': ' . $v;
			$variant = implode( ' · ', $parts );
		}
		$items_html .= '<div class="list-row"><div class="row-thumb">' . ( $img ? '<div class="sw" style="background-image:url(\'' . esc_url( $img ) . '\');background-size:cover;background-position:center;"></div>' : '<div class="sw"></div>' ) . '</div>'
			. '<div class="row-body"><h4>' . esc_html( $item->get_name() ) . '</h4>'
			. '<div class="meta">' . ( $variant ? esc_html( $variant ) . ' · ' : '' ) . 'Qty: ' . intval( $item->get_quantity() ) . '</div></div>'
			. '<div class="row-actions"><span style="font-family:\'Manrope\',sans-serif; font-weight:700; color:var(--primary-deep);">' . wp_strip_all_tags( wc_price( $order->get_line_total( $item ) ) ) . '</span></div></div>';
	}

	$address = $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address();
	$address = $address ? wp_strip_all_tags( str_replace( '<br/>', "\n", $address ) ) : 'No address on file.';

	$summary = '<div class="order-summary-box">'
		. '<div class="os-row"><span>Subtotal</span><span>' . wp_strip_all_tags( wc_price( $order->get_subtotal() ) ) . '</span></div>'
		. '<div class="os-row"><span>Delivery Fee</span><span>' . wp_strip_all_tags( wc_price( $order->get_shipping_total() ) ) . '</span></div>'
		. '<div class="os-row"><span>Discount</span><span>-' . wp_strip_all_tags( wc_price( $order->get_total_discount() ) ) . '</span></div>'
		. '<div class="os-row total"><span>Total</span><span>' . wp_strip_all_tags( wc_price( $order->get_total() ) ) . '</span></div>'
	. '</div>';

	return array(
		'title'    => 'Order #' . $order->get_order_number(),
		'status'   => wc_get_order_status_name( $status ),
		'pill'     => $pill_class,
		'timeline' => $timeline,
		'items'    => $items_html,
		'address'  => nl2br( esc_html( $address ) ),
		'summary'  => $summary,
		'track_url' => esc_url( seiridge_resolve_url( 'order-tracking' ) ),
	);
}

/** Edit Account — original edit-profile.html markup, real WC account save handling. */
function seiridge_render_edit_account_form() {
	$user = wp_get_current_user();
	$phone = get_user_meta( $user->ID, 'billing_phone', true );

	ob_start();
	?>
	<form method="post" class="edit-account-form">
		<div class="field"><label>Full Name</label><input type="text" name="account_first_name" value="<?php echo esc_attr( $user->first_name ); ?>" placeholder="First name"></div>
		<div class="field"><label>Last Name</label><input type="text" name="account_last_name" value="<?php echo esc_attr( $user->last_name ); ?>"></div>
		<div class="field"><label>Email Address</label><input type="email" name="account_email" value="<?php echo esc_attr( $user->user_email ); ?>"></div>
		<div class="field"><label>Phone Number</label><input type="tel" name="seiridge_billing_phone" value="<?php echo esc_attr( $phone ); ?>"></div>
		<div class="field"><label>New Password (leave blank to keep current)</label><input type="password" name="password_1" autocomplete="new-password"></div>
		<div class="field"><label>Confirm New Password</label><input type="password" name="password_2" autocomplete="new-password"></div>
		<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
		<input type="hidden" name="action" value="save_account_details">
		<button type="submit" class="btn-primary">Save Changes</button>
	</form>
	<?php
	return ob_get_clean();
}

/** Save the extra (non-WC-native) phone field alongside WooCommerce's own real account-save handler. */
function seiridge_save_extra_account_fields( $user_id ) {
	if ( isset( $_POST['seiridge_billing_phone'] ) ) {
		update_user_meta( $user_id, 'billing_phone', sanitize_text_field( wp_unslash( $_POST['seiridge_billing_phone'] ) ) );
	}
}
add_action( 'woocommerce_save_account_details', 'seiridge_save_extra_account_fields' );
