<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * LINK MAP
 * --------
 * Built by grepping every href="*.html" and every location.href='*.html'
 * occurrence across all 52 original files (verified list below matches
 * that grep exactly — see AUDIT.md for the command run).
 *
 * Two entries have NO corresponding source page in the original ZIP:
 *   - seiridge-search-results.html  (no search-results page was ever exported)
 *   - draft-v1-directory.html       (a "Directory" breadcrumb link with no target page)
 * Both are mapped to a reasonable fallback (Shop / All Categories) rather
 * than left broken. This is a genuine content gap in the ORIGINAL site,
 * not something introduced by this theme — flagged here so it isn't
 * silently "fixed" without you knowing.
 */
function seiridge_link_map() {
	return array(
		// friendly href slug (as literally found in the HTML) => resolver key
		'seiridge-home.html'               => 'home',
		'seiridge-electronics.html'        => 'electronics',
		'seiridge-phones.html'             => 'phones',
		'seiridge-fashion.html'            => 'fashion',
		'seiridge-grocery.html'            => 'grocery',
		'seiridge-home-living.html'        => 'home-living',
		'seiridge-sports.html'             => 'sports',
		'seiridge-books.html'              => 'books',
		'seiridge-beauty.html'             => 'beauty',
		'seiridge-automotive.html'         => 'automotive',
		'seiridge-brand-single.html'       => 'brand',
		'seiridge-brand-listing.html'      => 'brands',
		'seiridge-all-categories.html'     => 'all-categories',
		'seiridge-faq.html'                => 'faq',
		'seiridge-help-center.html'        => 'help-center',
		'seiridge-contact-us.html'         => 'contact-us',
		'seiridge-return-policy.html'      => 'return-policy',
		'seiridge-returns-refunds.html'    => 'returns-refunds',
		'seiridge-shipping-policy.html'    => 'shipping-policy',
		'seiridge-login-register.html'     => 'login',
		'seiridge-my-orders.html'          => 'my-orders',
		'seiridge-my-reviews.html'         => 'my-reviews',
		'seiridge-notifications.html'      => 'notifications',
		'seiridge-order-details.html'      => 'order-details',
		'seiridge-order-tracking.html'     => 'order-tracking',
		'seiridge-order-confirmation.html' => 'order-confirmation',
		'seiridge-payment-methods.html'    => 'payment-methods',
		'seiridge-profile.html'            => 'profile',
		'seiridge-recently-viewed.html'    => 'recently-viewed',
		'seiridge-saved-addresses.html'    => 'saved-addresses',
		'seiridge-wishlist.html'           => 'wishlist',
		'draft-v1-all-categories.html'     => 'all-categories',
		'draft-v1-forgot-password.html'    => 'forgot-password',
		'draft-v1-otp-verification.html'   => 'otp-verification',
		'draft-v1-reset-password.html'     => 'reset-password',

		// WooCommerce core (resolved via WC's own URL functions, not a fixed slug)
		'seiridge-cart.html'               => 'wc:cart',
		'seiridge-checkout.html'           => 'wc:checkout',
		'seiridge-account-dashboard.html'  => 'wc:myaccount',

		// Product detail nav link has no single target (it's per-product in
		// real WooCommerce) — falls back to the Shop page.
		'seiridge-product-detail.html'     => 'wc:shop',

		// No source page exists for these in the original export — fallback.
		'seiridge-search-results.html'     => 'wc:shop',
		'draft-v1-directory.html'          => 'all-categories',
	);
}

/** Resolve one map target ('slug', 'wc:cart', 'wc:shop', ...) to a real, current URL. */
function seiridge_resolve_url( $target ) {
	if ( $target === 'home' ) return home_url( '/' );
	if ( strpos( $target, 'wc:' ) === 0 ) {
		$key = substr( $target, 3 );
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$url = wc_get_page_permalink( $key );
			if ( $url ) return $url;
		}
		return home_url( '/' );
	}
	$page = get_page_by_path( $target );
	if ( $page ) return get_permalink( $page->ID );
	return home_url( '/' . $target . '/' ); // fallback if page not yet created
}

/**
 * NAMED-LINK MAP
 * --------------
 * The original export wires up exactly ONE category in the main nav
 * ("Electronics") and leaves every sibling item — same menu, same visual
 * treatment — as a literal href="#": Phones, Fashion, Grocery, Home &
 * Living, Beauty, Sports, Automotive, Books in the top nav; the same set
 * again (plus About/Contact/Help/policy links) in the mega-menu and
 * footer. Real pages exist for all of them. This maps their exact,
 * verified anchor text to the real destination, since matching by
 * filename (seiridge_link_map()) can't catch links that were never given
 * an href in the first place.
 *
 * Left deliberately unmapped (stay "#", same as the original — no target
 * exists to point them at): "View All →" filler CTAs, social icons, "Join
 * SEIRIDGE Plus", app-store badges, "Become a Rider", "Toys & Baby",
 * "Watches & Bags" (no corresponding category page in the 52-page export).
 */
function seiridge_named_link_map() {
	return array(
		'Phones' => 'phones',
		'Fashion' => 'fashion',
		'Fashion & Apparel' => 'fashion',
		'Grocery' => 'grocery',
		'Groceries' => 'grocery',
		'Groceries & Fresh' => 'grocery',
		'Home & Living' => 'home-living',
		'Home &amp; Living' => 'home-living',
		'Beauty' => 'beauty',
		'Beauty & Personal Care' => 'beauty',
		'Sports' => 'sports',
		'Automotive' => 'automotive',
		'Auto Accessories' => 'automotive',
		'Books' => 'books',
		'About Us' => 'about-us',
		'Contact Us' => 'contact-us',
		'Help' => 'help-center',
		'Help Centre' => 'help-center',
		'Privacy Policy' => 'privacy-policy',
		'Refund Policy' => 'returns-refunds',
		'Terms & Conditions' => 'terms-conditions',
		'Terms &amp; Conditions' => 'terms-conditions',
		'Shipping Info' => 'shipping-policy',
	);
}

/**
 * Replace href="#" with a real URL when the link's own text unambiguously
 * names a page we have (Phones, Fashion, About Us, ...). Deliberately
 * narrow: only matches href="#"> immediately followed by an optional
 * <span>icon</span> then plain text then </a> — won't touch "#" links with
 * other attributes (e.g. class="campaign-card" JS-templated cards,
 * aria-label social icons), so it can't misfire on something else.
 */
function seiridge_rewrite_named_links( $html ) {
	static $map = null;
	if ( $map === null ) $map = seiridge_named_link_map();

	return preg_replace_callback(
		'/href="#">((?:<span>[^<]*<\/span>)?)([^<]+)<\/a>/',
		function ( $m ) use ( $map ) {
			$text = trim( html_entity_decode( strip_tags( $m[2] ), ENT_QUOTES ) );
			if ( ! isset( $map[ $text ] ) ) return $m[0];
			return 'href="' . esc_url( seiridge_resolve_url( $map[ $text ] ) ) . '">' . $m[1] . $m[2] . '</a>';
		},
		$html
	);
}

/**
 * Wrap the homepage's "🔥 Up to 65% Off — This Week Only" offer badge in a
 * real link to the Mega Flash Sale page. Verified against source: it's a
 * plain, unlinked <div class="offer-badge"> in the original — only ever
 * appears on home.html, so this is a safe no-op on every other page.
 */
function seiridge_link_offer_badge( $html ) {
	if ( strpos( $html, '<div class="offer-badge">🔥 Up to 65% Off — This Week Only</div>' ) === false ) return $html;
	$url = esc_url( seiridge_resolve_url( 'flash-sale' ) );
	return str_replace(
		'<div class="offer-badge">🔥 Up to 65% Off — This Week Only</div>',
		'<a href="' . $url . '" class="offer-badge">🔥 Up to 65% Off — This Week Only</a>',
		$html
	);
}

/**
 * Rewrite every old .html internal link (in markup hrefs AND the two known
 * JS location.href navigations) to its resolved WordPress URL. Applied to
 * BOTH the body markup and the page script for every rendered page.
 * Also applies the global logo image swap (inc/site-images.php) — piggy-
 * backing on this function since every template already calls it right
 * before output, so a logo change from wp-admin reaches every page without
 * having to edit each template individually.
 */
function seiridge_rewrite_internal_links( $html ) {
	static $map = null, $resolved = null;
	if ( $map === null ) {
		$map = seiridge_link_map();
		$resolved = array();
		foreach ( $map as $old => $target ) $resolved[ $old ] = seiridge_resolve_url( $target );
	}

	// href="....html"
	$html = preg_replace_callback(
		'/href="([a-zA-Z0-9_.-]+\.html)"/',
		function ( $m ) use ( $resolved ) {
			return isset( $resolved[ $m[1] ] ) ? 'href="' . esc_url( $resolved[ $m[1] ] ) . '"' : $m[0];
		},
		$html
	);

	// location.href = '....html'  (JS)
	$html = preg_replace_callback(
		"/location\\.href = '([a-zA-Z0-9_.-]+\\.html)'/",
		function ( $m ) use ( $resolved ) {
			return isset( $resolved[ $m[1] ] ) ? "location.href = '" . esc_url_raw( $resolved[ $m[1] ] ) . "'" : $m[0];
		},
		$html
	);

	// Named-text links (href="#" with no rewritten filename, but text that
	// unambiguously names a real page) — fixes the original export's
	// half-wired nav menu.
	$html = seiridge_rewrite_named_links( $html );

	// Homepage offer badge -> real link to Mega Flash Sale page.
	$html = seiridge_link_offer_badge( $html );

	// Global logo image swap (inc/site-images.php) — no-op if no custom
	// logo has been uploaded, or if this call is on a <script> block that
	// has no <img> tags to match.
	if ( function_exists( 'seiridge_apply_global_images' ) ) {
		$html = seiridge_apply_global_images( $html );
	}

	// Wrap header/footer logo <img> in a real link to the homepage — the
	// original design never linked them at all.
	if ( function_exists( 'seiridge_link_logo_to_home' ) ) {
		$html = seiridge_link_logo_to_home( $html );
	}

	return $html;
}
