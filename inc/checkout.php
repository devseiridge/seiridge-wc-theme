<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render the ORIGINAL payment-method radio-card markup, populated with
 * REAL WooCommerce gateways (id, title, description) instead of the demo
 * COD/Card/bKash/Nagad entries — same <label class="radio-card"> /
 * <span class="rc-ico"> / <span class="rc-body"><h4>/<p> structure,
 * verified against the source file's exact markup. The radio's real
 * name="payment_method" / value="{gateway id}" is what WooCommerce's own
 * checkout.js reads to know which gateway was chosen — this is the field
 * WooCommerce actually processes, not a cosmetic label.
 *
 * Each gateway's extra fields (e.g. a card gateway's card-number inputs)
 * render via the gateway's own real payment_fields() method, inside a
 * container reusing the original design's .card-fields class — shown/
 * hidden by gateway selection via the small script in page-checkout.php,
 * matching the original's own card-fields show/hide behaviour (previously
 * hardcoded to only the "card" method).
 */
function seiridge_render_real_payment_methods() {
	$gateways = WC()->payment_gateways()->get_available_payment_gateways();
	if ( ! $gateways ) {
		return array( 'cards' => '<p class="fc-sub">No payment methods are enabled yet — add one in WooCommerce &rarr; Settings &rarr; Payments.</p>', 'fields' => '', 'first_id' => '' );
	}

	$icon_map = array( 'cod' => '💵', 'bacs' => '🏦', 'cheque' => '📝', 'paypal' => '📱', 'bkash' => '📱', 'nagad' => '📱' );
	$default_icon = '💳';

	$chosen = WC()->session ? WC()->session->get( 'chosen_payment_method' ) : '';
	$cards = '';
	$fields_html = '';
	$first_id = '';
	$first = true;

	foreach ( $gateways as $gateway ) {
		if ( ! $first_id ) $first_id = $gateway->id;
		$active = $chosen ? ( $chosen === $gateway->id ) : $first;
		$icon = $icon_map[ $gateway->id ] ?? $default_icon;
		$description = $gateway->get_description() ? wp_strip_all_tags( $gateway->get_description() ) : '';

		$cards .= '<label class="radio-card' . ( $active ? ' active' : '' ) . '" data-method="' . esc_attr( $gateway->id ) . '">'
			. '<input type="radio" name="payment_method" value="' . esc_attr( $gateway->id ) . '" class="seiridge-payment-radio"' . ( $active ? ' checked' : '' ) . '>'
			. '<span class="rc-ico">' . $icon . '</span>'
			. '<span class="rc-body"><h4>' . esc_html( $gateway->get_title() ) . '</h4>' . ( $description ? '<p>' . esc_html( $description ) . '</p>' : '' ) . '</span>'
		. '</label>';

		if ( $gateway->has_fields() || $gateway->get_description() ) {
			ob_start();
			$gateway->payment_fields();
			$inner = ob_get_clean();
			if ( trim( $inner ) ) {
				$fields_html .= '<div class="card-fields seiridge-payment-fields" id="payment_fields_' . esc_attr( $gateway->id ) . '" style="' . ( $active ? '' : 'display:none;' ) . '">' . $inner . '</div>';
			}
		}
		$first = false;
	}

	return array( 'cards' => $cards, 'fields' => $fields_html, 'first_id' => $first_id );
}

/** Same 5 cities as the original hardcoded dropdown — kept exactly, just wired to a real field name. */
function seiridge_checkout_city_options( $selected = '' ) {
	$cities = array( 'Dhaka', 'Chattogram', 'Khulna', 'Rajshahi', 'Sylhet' );
	$html = '';
	foreach ( $cities as $c ) {
		$html .= '<option' . ( strcasecmp( $selected, $c ) === 0 ? ' selected' : '' ) . '>' . esc_html( $c ) . '</option>';
	}
	return $html;
}

/**
 * Real WooCommerce shipping rates for the current cart, rendered in the
 * original .radio-card markup. Falls back to a single "Standard Delivery /
 * Free" card (matching the original's own default) if no shipping zones
 * are configured yet — a fresh WooCommerce install has none until the
 * store owner sets them up in WooCommerce -> Settings -> Shipping.
 */
function seiridge_render_real_shipping_methods() {
	$packages = WC()->shipping()->get_packages();
	$rates = array();
	foreach ( $packages as $package ) {
		if ( ! empty( $package['rates'] ) ) $rates += $package['rates'];
	}

	$chosen = WC()->session ? WC()->session->get( 'chosen_shipping_methods' ) : array();
	$chosen_id = $chosen[0] ?? '';

	$icon_for = function ( $rate ) {
		$id = strtolower( $rate->get_method_id() );
		if ( strpos( $id, 'free' ) !== false ) return '🚚';
		if ( strpos( $id, 'flat_rate' ) !== false ) return '📦';
		return '⚡';
	};

	$cards = '';
	if ( $rates ) {
		$first = true;
		foreach ( $rates as $rate_id => $rate ) {
			$active = $chosen_id ? ( $chosen_id === $rate_id ) : $first;
			$cards .= '<label class="radio-card' . ( $active ? ' active' : '' ) . '" data-fee="' . esc_attr( $rate->get_cost() ) . '">'
				. '<input type="radio" name="shipping_method[0]" value="' . esc_attr( $rate_id ) . '"' . ( $active ? ' checked' : '' ) . ' class="seiridge-shipping-radio">'
				. '<span class="rc-ico">' . $icon_for( $rate ) . '</span>'
				. '<span class="rc-body"><h4>' . esc_html( $rate->get_label() ) . '</h4><p>' . ( $rate->get_cost() > 0 ? 'Delivery fee applies' : 'Standard delivery' ) . '</p></span>'
				. '<span class="rc-price">' . ( $rate->get_cost() > 0 ? wp_strip_all_tags( wc_price( $rate->get_cost() ) ) : 'Free' ) . '</span>'
			. '</label>';
			$first = false;
		}
	} else {
		// No shipping zones configured — the honest fallback is a single free
		// "Standard Delivery" card (this is also what the original design's
		// default state showed), rather than inventing rates that don't exist.
		$cards = '<label class="radio-card active" data-fee="0">'
			. '<input type="radio" name="shipping_method[0]" value="" checked disabled>'
			. '<span class="rc-ico">🚚</span>'
			. '<span class="rc-body"><h4>Standard Delivery</h4><p>Configure shipping zones in WooCommerce → Settings → Shipping to offer real delivery options here.</p></span>'
			. '<span class="rc-price">Free</span>'
		. '</label>';
	}

	// Trigger WooCommerce's real 'update_checkout' event when a shipping radio changes, so the
	// AJAX order-review (real totals) recalculates — same mechanism WC's own shipping radios use.
	$js = '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".seiridge-shipping-radio").forEach(function(r){r.addEventListener("change",function(){document.querySelectorAll(".radio-card").forEach(function(l){l.classList.remove("active");});r.closest(".radio-card").classList.add("active");if(window.jQuery){jQuery(document.body).trigger("update_checkout");}});});});</script>';

	return '<div class="form-card reveal"><h3>Delivery Method</h3><p class="fc-sub">Choose how quickly you\'d like it delivered.</p>' . $cards . '</div>' . $js;
}
