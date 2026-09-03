<?php
/**
 * PAGE MAP
 * --------
 * One row per file bundled in /source-html. This is the single source of
 * truth the one-time setup (inc/setup.php) reads to create WordPress pages,
 * and that seiridge_render_source_page() reads to decide whether a page's
 * product grid should be wired to live WooCommerce data.
 *
 * type values:
 *   'home'              -> front page, product grids injected with real products
 *   'catalog_products'  -> category-style listing page using `const PRODUCTS = [...]`
 *                           (electronics, phones, fashion, grocery, home-living,
 *                           sports, books, beauty, automotive, brand-single)
 *   'catalog_pool'      -> listing page using `const pool = [...]` (offers, voucher-coupons)
 *   'product_detail'    -> single product page, handled by woocommerce/single-product.php
 *                           instead of the generic wrapper (NOT created as a WP Page)
 *   'wc_core'           -> maps onto WooCommerce's own Cart / Checkout / My Account
 *                           pages instead of creating a duplicate WP Page
 *   'static'            -> passthrough page, original HTML/CSS/JS preserved as-is,
 *                           no dynamic data wired in
 *
 * IMPORTANT LIMITATION (see README): 'catalog_products' and 'catalog_pool' pages
 * have their in-page JS product ARRAY swapped for real WooCommerce data via
 * seiridge_inject_wc_products(). Everything else on those pages (filters, sort,
 * pagination, sticky nav, drawers, countdown, reveal animation) is the ORIGINAL
 * client-side JS, untouched. That data-swap has been built to the site's actual
 * (verified) JS shape but has not been visually tested in a live browser/WP
 * install, because this sandbox has no PHP/WordPress runtime to render it in.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function seiridge_page_map() {
	return array(

		// ---- Home ----
		'Seiridge-v4.9.3-home.html' => array(
			'slug' => 'home', 'title' => 'Home', 'type' => 'home',
		),

		// ---- Category / catalog listing pages (const PRODUCTS = [...]) ----
		'Seiridge-v4.9.3-electronics.html'   => array( 'slug' => 'electronics',   'title' => 'Electronics & Gadgets',           'type' => 'catalog_products' ),
		'Seiridge-v3.6.2-phones.html'        => array( 'slug' => 'phones',        'title' => 'Phones & Mobile Accessories',     'type' => 'catalog_products' ),
		'Seiridge-v3.6.2-fashion.html'       => array( 'slug' => 'fashion',       'title' => 'Fashion & Apparel',               'type' => 'catalog_products' ),
		'Seiridge-v3.6.2-grocery.html'       => array( 'slug' => 'grocery',       'title' => 'Groceries & Fresh',               'type' => 'catalog_products' ),
		'Seiridge-v3.6.2-home-living.html'   => array( 'slug' => 'home-living',   'title' => 'Home & Living',                   'type' => 'catalog_products' ),
		'Seiridge-v3.6.2-sports.html'        => array( 'slug' => 'sports',        'title' => 'Sports & Fitness',                'type' => 'catalog_products' ),
		'Seiridge-v4.9.3-books.html'         => array( 'slug' => 'books',         'title' => 'Books & Stationery',              'type' => 'catalog_products' ),
		'Seiridge-v3.6.2-beauty.html'        => array( 'slug' => 'beauty',        'title' => 'Beauty & Personal Care',          'type' => 'catalog_products' ),
		'Seiridge-v4.9.3-automotive.html'    => array( 'slug' => 'automotive',    'title' => 'Automotive & Auto Care',          'type' => 'catalog_products' ),
		'Seiridge-v4.9.3-brand-single.html'  => array( 'slug' => 'brand',         'title' => 'AERO',                            'type' => 'catalog_products' ),

		// ---- Listing pages using const pool = [...] ----
		'Seiridge-v3.6.2-offers.html'          => array( 'slug' => 'offers',           'title' => 'Offers & Deals',            'type' => 'catalog_pool' ),
		'Seiridge-v4.9.3-voucher-coupons.html' => array( 'slug' => 'vouchers',         'title' => 'Voucher & Coupon Offers',   'type' => 'catalog_pool' ),

		// ---- This one turned out to use the PRODUCTS object shape, not pool (verified against source) ----
		'Seiridge-v3.6.2-flash-sale.html'      => array( 'slug' => 'flash-sale',       'title' => 'Mega Flash Sale',           'type' => 'catalog_products' ),

		// ---- Single product (NOT created as a WP Page; used by woocommerce/single-product.php) ----
		'Seiridge-v4.9.3-product-detail.html' => array( 'slug' => 'product-detail', 'title' => 'Product Detail', 'type' => 'product_detail' ),

		// ---- WooCommerce core pages (map to WC's own pages, don't duplicate) ----
		'Seiridge-v4.9.3-cart.html'               => array( 'slug' => 'cart',        'title' => 'Cart',        'type' => 'wc_core', 'wc_page' => 'cart' ),
		'Seiridge-v4.9.3-checkout.html'            => array( 'slug' => 'checkout',    'title' => 'Checkout',    'type' => 'wc_core', 'wc_page' => 'checkout' ),
		'Seiridge-v4.9.3-account-dashboard.html'   => array( 'slug' => 'my-account',  'title' => 'My Account',  'type' => 'wc_core', 'wc_page' => 'myaccount' ),

		// ---- Everything else: static passthrough, original design locked, content not WP-editable ----
		'Seiridge-v4.9.3-about-us.html'            => array( 'slug' => 'about-us',            'title' => 'About Us',              'type' => 'editable' ),
		'Seiridge-v4.9.3-contact-us.html'          => array( 'slug' => 'contact-us',           'title' => 'Contact Us',            'type' => 'editable' ),
		'Seiridge-v4.9.3-faq.html'                 => array( 'slug' => 'faq',                  'title' => 'FAQ',                   'type' => 'editable' ),
		'Seiridge-v4.9.3-help-center.html'         => array( 'slug' => 'help-center',          'title' => 'Help Center',           'type' => 'editable' ),
		'Seiridge-v4.9.3-privacy-policy.html'      => array( 'slug' => 'privacy-policy',       'title' => 'Privacy Policy',        'type' => 'editable' ),
		'Seiridge-v3.6.2-return-policy.html'       => array( 'slug' => 'return-policy',        'title' => 'Return Policy',         'type' => 'editable' ),
		'Seiridge-v4.9.3-return-refund-policy.html'=> array( 'slug' => 'returns-refunds',      'title' => 'Returns & Refunds',     'type' => 'editable' ),
		'Seiridge-v4.9.3-shipping-policy.html'     => array( 'slug' => 'shipping-policy',      'title' => 'Shipping Policy',       'type' => 'editable' ),
		'Seiridge-v4.9.3-terms-conditions.html'    => array( 'slug' => 'terms-conditions',     'title' => 'Terms & Conditions',    'type' => 'editable' ),
		'Seiridge-v3.6.2-coming-soon.html'         => array( 'slug' => 'coming-soon',          'title' => 'Coming Soon',           'type' => 'static' ),
		'Seiridge-v3.6.2-maintenance.html'         => array( 'slug' => 'maintenance',          'title' => 'Under Maintenance',     'type' => 'static' ),
		'Seiridge-v4.9.3-403.html'                 => array( 'slug' => 'access-denied',        'title' => 'Access Denied',         'type' => 'static' ),
		'Seiridge-v4.9.3-404.html'                 => array( 'slug' => 'not-found',            'title' => 'Page Not Found',        'type' => 'static' ),
		'Seiridge-v4.9.3-500.html'                 => array( 'slug' => 'server-error',         'title' => 'Server Error',          'type' => 'static' ),
		'Seiridge-v4.9.3-all-categories.html'      => array( 'slug' => 'all-categories',       'title' => 'All Categories',        'type' => 'static' ),
		'Seiridge-v3.6.2-brand-listing.html'       => array( 'slug' => 'brands',               'title' => 'Shop by Brand',         'type' => 'static' ),
		'Seiridge-v4.9.3-login-register.html'      => array( 'slug' => 'login',                'title' => 'Login or Create Account','type' => 'static' ),
		'Seiridge-v3.6.2-forgot-password.html'     => array( 'slug' => 'forgot-password',      'title' => 'Forgot Password',       'type' => 'static' ),
		'Seiridge-v3.6.2-reset-password.html'      => array( 'slug' => 'reset-password',       'title' => 'Reset Password',        'type' => 'static' ),
		'Seiridge-v3_6_2-otp-verification.html'    => array( 'slug' => 'otp-verification',     'title' => 'OTP Verification',      'type' => 'static' ),
		'Seiridge-v3.6.2-session-expired.html'     => array( 'slug' => 'session-expired',      'title' => 'Session Expired',       'type' => 'static' ),
		'Seiridge-v4.9.3-change-password.html'     => array( 'slug' => 'change-password',      'title' => 'Change Password',       'type' => 'static' ),
		'Seiridge-v3.6.2-edit-profile.html'        => array( 'slug' => 'edit-profile',         'title' => 'Edit Profile',          'type' => 'static' ),
		'Seiridge-v3.6.2-profile.html'             => array( 'slug' => 'profile',              'title' => 'My Profile',            'type' => 'static' ),
		'Seiridge-v3.6.2-my-orders.html'           => array( 'slug' => 'my-orders',            'title' => 'My Orders',             'type' => 'static' ),
		'Seiridge-v3.6.2-order-details.html'       => array( 'slug' => 'order-details',        'title' => 'Order Details',         'type' => 'static' ),
		'Seiridge-v4.9.3-order-tracking.html'      => array( 'slug' => 'order-tracking',       'title' => 'Track Your Order',      'type' => 'static' ),
		'Seiridge-v4.9.3-order-confirmation.html'  => array( 'slug' => 'order-confirmation',   'title' => 'Order Confirmed',       'type' => 'static' ),
		'Seiridge-v3.6.2-payment-methods.html'     => array( 'slug' => 'payment-methods',      'title' => 'Payment Methods',       'type' => 'static' ),
		'Seiridge-v3.6.2-saved-addresses.html'     => array( 'slug' => 'saved-addresses',      'title' => 'Saved Addresses',       'type' => 'static' ),
		'Seiridge-v3.6.2-my-reviews.html'          => array( 'slug' => 'my-reviews',           'title' => 'My Reviews',            'type' => 'static' ),
		'Seiridge-v3_6_2-wishlist.html'            => array( 'slug' => 'wishlist',             'title' => 'My Wishlist',           'type' => 'wishlist' ),
		'Seiridge-v3.6.2-recently-viewed.html'     => array( 'slug' => 'recently-viewed',      'title' => 'Recently Viewed',       'type' => 'static' ),
		'Seiridge-v3_6_2-notifications.html'       => array( 'slug' => 'notifications',        'title' => 'Notifications',         'type' => 'static' ),
	);
}
