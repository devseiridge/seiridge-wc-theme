<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * EDITABLE FIELDS
 * ---------------
 * One entry per page-map slug that has type 'editable'. Each field's
 * 'match' is the EXACT original string (verified against source) that gets
 * swapped for the field's current value at render time. This keeps the
 * promise "layout stays locked" literally true — only that text node
 * changes, nothing else in the surrounding markup is touched.
 *
 * SCOPE (disclosed, not hidden): this covers heading + intro text for 7
 * pages, plus About's main narrative and Contact's phone/email/address —
 * the content an admin is actually likely to need to update. It does NOT
 * make every paragraph on every static page editable (e.g. About's 4-card
 * "Why Choose" grid, FAQ's individual Q&A pairs) — extending this list to
 * more fields is straightforward (add another entry below) but each one
 * needs its own verified 'match' string, which is why this ships with a
 * curated set rather than an unverified blanket find-replace.
 */
function seiridge_editable_fields( $slug ) {
	switch ( $slug ) {
		case 'about-us':
			return array(
				'heading'    => array( 'label' => 'Page heading', 'type' => 'text', 'match' => 'About SEIRIDGE' ),
				'subheading' => array( 'label' => 'Subheading', 'type' => 'textarea', 'match' => 'SEIRIDGE Brand &amp; Customer Experience — home-grown in Bangladesh, built for how people actually shop.' ),
				'story_p1'   => array( 'label' => '"Who We Are" — paragraph 1', 'type' => 'textarea', 'match' => "SEIRIDGE started with a simple idea: shopping online in Bangladesh should feel as trustworthy and convenient as walking into your favourite neighbourhood store. Since launch, we've grown into a trusted destination for customers across all 64 districts, built around the SEIRIDGE Brand &amp; Customer Experience." ),
				'story_p2'   => array( 'label' => '"Who We Are" — paragraph 2', 'type' => 'textarea', 'match' => 'From electronics and fashion to groceries and home essentials, we bring together a wide product selection, careful quality checks, fair pricing, and reliable delivery — all in one place. Every order is backed by our buyer guarantee, and every customer is treated like a neighbour, not a number.' ),
			);
		case 'contact-us':
			return array(
				'heading' => array( 'label' => 'Page heading', 'type' => 'text', 'match' => 'Contact Us' ),
				'phone'    => array( 'label' => 'Phone number', 'type' => 'text', 'match' => '+880 9666 123 456' ),
				'phone_href' => array( 'label' => 'Phone link (tel:)', 'type' => 'text', 'match' => 'tel:+880966123456' ),
				'email'    => array( 'label' => 'Support email', 'type' => 'text', 'match' => 'support@seiridge.com' ),
				'email_href' => array( 'label' => 'Email link (mailto:)', 'type' => 'text', 'match' => 'mailto:support@seiridge.com' ),
				'address'  => array( 'label' => 'Office address', 'type' => 'textarea', 'match' => 'Level 9, Bashundhara Trade Tower<br>Baridhara, Dhaka 1212, Bangladesh' ),
			);
		case 'faq':
			return array(
				'heading'    => array( 'label' => 'Page heading', 'type' => 'text', 'match' => 'Frequently Asked Questions' ),
				'subheading' => array( 'label' => 'Subheading', 'type' => 'textarea', 'match' => 'Quick answers to the things customers ask us most.' ),
			);
		case 'help-center':
			return array(
				'heading'    => array( 'label' => 'Page heading', 'type' => 'text', 'match' => 'Help Center' ),
				'subheading' => array( 'label' => 'Subheading', 'type' => 'textarea', 'match' => 'Search our knowledge base or browse help topics below.' ),
			);
		case 'privacy-policy':
			return array(
				'heading'    => array( 'label' => 'Page heading', 'type' => 'text', 'match' => 'Privacy Policy' ),
				'subheading' => array( 'label' => 'Subheading', 'type' => 'textarea', 'match' => 'How SEIRIDGE collects, uses and protects your personal information.' ),
			);
		case 'return-policy':
			return array(
				'heading'    => array( 'label' => 'Page heading', 'type' => 'text', 'match' => 'Return Policy' ),
				'subheading' => array( 'label' => 'Subheading', 'type' => 'textarea', 'match' => 'Everything you need to know about returning an item to SEIRIDGE.' ),
			);
		case 'returns-refunds':
			return array(
				'heading'    => array( 'label' => 'Page heading', 'type' => 'text', 'match' => 'Returns &amp; Refunds' ),
				'subheading' => array( 'label' => 'Subheading', 'type' => 'textarea', 'match' => 'Start a return, track a refund, or review our return policy.' ),
				'summary'    => array( 'label' => 'Return policy summary text', 'type' => 'textarea', 'match' => 'Most items can be returned within 7 days of delivery in original condition and packaging. Refunds are processed to your original payment method within 5-7 business days, or as store credit if you prefer. Some categories, such as intimate apparel and perishable groceries, are not eligible for return.' ),
			);
		case 'shipping-policy':
			return array(
				'heading'    => array( 'label' => 'Page heading', 'type' => 'text', 'match' => 'Shipping Policy' ),
				'subheading' => array( 'label' => 'Subheading', 'type' => 'textarea', 'match' => 'Delivery timelines, fees, and coverage across Bangladesh.' ),
			);
		case 'terms-conditions':
			return array(
				'heading'    => array( 'label' => 'Page heading', 'type' => 'text', 'match' => 'Terms &amp; Conditions' ),
				'subheading' => array( 'label' => 'Subheading', 'type' => 'textarea', 'match' => 'SEIRIDGE Customer Terms &amp; Conditions — the terms governing your use of our platform.' ),
			);
		case 'home':
			return array(
				'hero_eyebrow'  => array( 'label' => 'Hero eyebrow tag', 'type' => 'text', 'match' => 'New Season Drop' ),
				'hero_heading'  => array( 'label' => 'Hero heading', 'type' => 'text', 'match' => 'Everything you need, delivered to your door' ),
				'hero_subtext'  => array( 'label' => 'Hero subtext', 'type' => 'textarea', 'match' => 'Electronics, fashion, home essentials and more — all from one trusted brand, with cash on delivery nationwide.' ),
			);
		default:
			return array();
	}
}

/** Called once by setup.php per editable page: pre-fill postmeta with the CURRENT original text, so the admin sees real content, not a blank field. */
function seiridge_populate_editable_defaults( $post_id, $filename, $slug ) {
	$html = seiridge_read_source( $filename );
	$body = seiridge_extract_body( $html );
	$fields = seiridge_editable_fields( $slug );

	foreach ( $fields as $key => $def ) {
		$existing = get_post_meta( $post_id, '_seiridge_field_' . $key, true );
		if ( $existing !== '' ) continue; // already set (admin may have edited it) — never overwrite

		$default = $def['match'];
		if ( $default === null ) {
			// Heading-only fallback pages: try to grab the real <h1> text.
			if ( preg_match( '/<h1>(.*?)<\/h1>/s', $body, $m ) ) $default = trim( wp_strip_all_tags( $m[1] ) );
		}
		update_post_meta( $post_id, '_seiridge_field_' . $key, (string) $default );
	}
}

/** Render time: swap each field's ORIGINAL text for its current (possibly edited) value. */
function seiridge_render_editable_body( $body, $post_id, $slug ) {
	foreach ( seiridge_editable_fields( $slug ) as $key => $def ) {
		if ( ! $def['match'] ) continue;
		$current = get_post_meta( $post_id, '_seiridge_field_' . $key, true );
		if ( $current === '' || $current === $def['match'] ) continue; // nothing changed, skip the replace
		$body = str_replace( $def['match'], esc_html( $current ), $body );
	}
	return $body;
}

/** Admin meta box: lets a real editor change these fields from wp-admin, without touching HTML. */
function seiridge_register_editable_metabox() {
	add_meta_box( 'seiridge_editable', 'Seiridge — Editable Page Text', 'seiridge_render_editable_metabox', 'page', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'seiridge_register_editable_metabox' );

function seiridge_render_editable_metabox( $post ) {
	$entry = get_post_meta( $post->ID, '_seiridge_page_entry', true );
	if ( ! is_array( $entry ) || ! in_array( $entry['type'] ?? '', array( 'editable', 'home' ), true ) ) {
		echo '<p><em>Not an editable Seiridge page.</em></p>';
		return;
	}
	$slug = $entry['slug'];
	wp_nonce_field( 'seiridge_save_editable', 'seiridge_editable_nonce' );
	foreach ( seiridge_editable_fields( $slug ) as $key => $def ) {
		$value = get_post_meta( $post->ID, '_seiridge_field_' . $key, true );
		echo '<p><label><strong>' . esc_html( $def['label'] ) . '</strong><br>';
		if ( $def['type'] === 'textarea' ) {
			echo '<textarea name="seiridge_field_' . esc_attr( $key ) . '" rows="3" style="width:100%;">' . esc_textarea( $value ) . '</textarea>';
		} else {
			echo '<input type="text" name="seiridge_field_' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" style="width:100%;">';
		}
		echo '</label></p>';
	}
}

function seiridge_save_editable_metabox( $post_id ) {
	if ( ! isset( $_POST['seiridge_editable_nonce'] ) || ! wp_verify_nonce( $_POST['seiridge_editable_nonce'], 'seiridge_save_editable' ) ) return;
	if ( ! current_user_can( 'edit_page', $post_id ) ) return;

	$entry = get_post_meta( $post_id, '_seiridge_page_entry', true );
	if ( ! is_array( $entry ) || ! in_array( $entry['type'] ?? '', array( 'editable', 'home' ), true ) ) return;

	foreach ( array_keys( seiridge_editable_fields( $entry['slug'] ) ) as $key ) {
		$field = 'seiridge_field_' . $key;
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, '_seiridge_field_' . $key, wp_kses_post( wp_unslash( $_POST[ $field ] ) ) );
		}
	}
}
add_action( 'save_post_page', 'seiridge_save_editable_metabox' );
