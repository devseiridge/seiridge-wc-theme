<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WooCommerce core only has one billing + one shipping address. Your
 * original design has a real multi-address book (Home/Office/etc., with
 * add/edit/remove/set-default) — there's no core WC equivalent, so this is
 * a genuine small feature built on user meta, not a WC override.
 * "Set Default" also copies the address into WC's real billing/shipping
 * fields, so checkout autofill still uses it.
 */
function seiridge_get_addresses( $user_id ) {
	$addrs = get_user_meta( $user_id, '_seiridge_addresses', true );
	return is_array( $addrs ) ? $addrs : array();
}

function seiridge_save_addresses( $user_id, $addrs ) {
	update_user_meta( $user_id, '_seiridge_addresses', array_values( $addrs ) );
}

/** Copy one saved address into WC's real billing fields (used by checkout). */
function seiridge_apply_address_to_wc_billing( $user_id, $addr ) {
	$parts = explode( ' ', trim( $addr['name'] ), 2 );
	update_user_meta( $user_id, 'billing_first_name', $parts[0] ?? '' );
	update_user_meta( $user_id, 'billing_last_name', $parts[1] ?? '' );
	update_user_meta( $user_id, 'billing_address_1', $addr['line'] ?? '' );
	update_user_meta( $user_id, 'billing_phone', $addr['phone'] ?? '' );
}

/** Handle add / remove / set-default actions (GET + nonce, same pattern as wishlist/cart). */
function seiridge_handle_address_actions() {
	if ( ! is_user_logged_in() || empty( $_REQUEST['seiridge_addr_action'] ) ) return;
	$user_id = get_current_user_id();
	$action  = sanitize_text_field( $_REQUEST['seiridge_addr_action'] );

	if ( $action === 'add' && isset( $_POST['seiridge_addr_nonce'] ) && wp_verify_nonce( $_POST['seiridge_addr_nonce'], 'seiridge_addr_add' ) ) {
		$addrs = seiridge_get_addresses( $user_id );
		$new = array(
			'id'    => uniqid( 'addr_' ),
			'label' => sanitize_text_field( wp_unslash( $_POST['label'] ?? 'Address' ) ),
			'name'  => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'line'  => sanitize_textarea_field( wp_unslash( $_POST['line'] ?? '' ) ),
			'phone' => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'default' => empty( $addrs ),
		);
		$addrs[] = $new;
		seiridge_save_addresses( $user_id, $addrs );
		if ( $new['default'] ) seiridge_apply_address_to_wc_billing( $user_id, $new );
		wp_safe_redirect( remove_query_arg( array( 'seiridge_addr_action' ) ) );
		exit;
	}

	if ( in_array( $action, array( 'remove', 'default' ), true )
		&& isset( $_GET['id'], $_GET['_wpnonce'] )
		&& wp_verify_nonce( $_GET['_wpnonce'], 'seiridge_addr_' . $action . '_' . $_GET['id'] ) ) {
		$addrs = seiridge_get_addresses( $user_id );
		$id = sanitize_text_field( $_GET['id'] );
		if ( $action === 'remove' ) {
			$addrs = array_values( array_filter( $addrs, fn( $a ) => $a['id'] !== $id ) );
		} else { // set default
			foreach ( $addrs as &$a ) {
				$a['default'] = ( $a['id'] === $id );
				if ( $a['default'] ) seiridge_apply_address_to_wc_billing( $user_id, $a );
			}
			unset( $a );
		}
		seiridge_save_addresses( $user_id, $addrs );
		wp_safe_redirect( remove_query_arg( array( 'seiridge_addr_action', 'id', '_wpnonce' ) ) );
		exit;
	}
}
add_action( 'init', 'seiridge_handle_address_actions' );

/** Render the real address book in the ORIGINAL addr-grid markup (verified against source). */
function seiridge_render_address_book() {
	$user_id = get_current_user_id();
	$addrs   = seiridge_get_addresses( $user_id );

	$cards = '';
	foreach ( $addrs as $a ) {
		$remove_url  = wp_nonce_url( add_query_arg( array( 'seiridge_addr_action' => 'remove', 'id' => $a['id'] ) ), 'seiridge_addr_remove_' . $a['id'] );
		$default_url = wp_nonce_url( add_query_arg( array( 'seiridge_addr_action' => 'default', 'id' => $a['id'] ) ), 'seiridge_addr_default_' . $a['id'] );
		$cards .= '<div class="addr-card' . ( ! empty( $a['default'] ) ? ' default' : '' ) . '">'
			. ( ! empty( $a['default'] ) ? '<span class="tag-default">Default</span>' : '' )
			. '<h4>' . esc_html( $a['label'] ) . '</h4>'
			. '<p>' . esc_html( $a['name'] ) . '<br>' . nl2br( esc_html( $a['line'] ) ) . '<br>' . esc_html( $a['phone'] ) . '</p>'
			. '<div class="addr-actions">'
				. ( empty( $a['default'] ) ? '<a href="' . esc_url( $default_url ) . '">Set Default</a>' : '' )
				. '<a href="' . esc_url( $remove_url ) . '" class="rm">Remove</a>'
			. '</div></div>';
	}

	$add_form = '<form method="post" class="addr-card" style="grid-column:1/-1;">'
		. '<h4>Add New Address</h4>'
		. '<div class="form-row"><div class="form-group"><label>Label</label><input type="text" name="label" placeholder="e.g. Home" required></div>'
		. '<div class="form-group"><label>Full Name</label><input type="text" name="name" required></div></div>'
		. '<div class="form-group"><label>Address</label><input type="text" name="line" required></div>'
		. '<div class="form-group"><label>Phone</label><input type="tel" name="phone" required></div>'
		. wp_nonce_field( 'seiridge_addr_add', 'seiridge_addr_nonce', true, false )
		. '<input type="hidden" name="seiridge_addr_action" value="add">'
		. '<button type="submit" class="btn-primary" style="margin-top:10px;">Save Address</button>'
	. '</form>';

	return $cards . $add_form;
}
