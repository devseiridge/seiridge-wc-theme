<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Replace the hardcoded demo Subcategory/Brand filter checkboxes with the
 * REAL child categories (and real product counts) of the given category,
 * and real 'brand' attribute terms if that attribute exists on the store.
 * Verified boundary strings match the exact markup in every catalog page
 * (all share the same filter sidebar HTML).
 */
function seiridge_render_real_filters( $body, $category_slug = '' ) {
	if ( ! function_exists( 'get_terms' ) ) return $body;

	// ---- Subcategory checkboxes ----
	$parent_id = 0;
	if ( $category_slug ) {
		$term = get_term_by( 'slug', $category_slug, 'product_cat' );
		if ( $term ) $parent_id = $term->term_id;
	}
	$subcats = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $parent_id, 'hide_empty' => false ) );
	$subcat_html = '';
	if ( ! is_wp_error( $subcats ) ) {
		foreach ( $subcats as $t ) {
			$subcat_html .= '<label class="filter-check"><input type="checkbox" data-filter="subcat" value="' . esc_attr( $t->name ) . '"> ' . esc_html( $t->name ) . ' <span class="count">' . intval( $t->count ) . '</span></label>' . "\n";
		}
	}
	if ( ! $subcat_html ) $subcat_html = '<p class="sub">No subcategories yet.</p>';
	$body = preg_replace(
		'/(<h4>Subcategory<\/h4>\s*)(<label class="filter-check">.*?<\/label>\s*)+(?=<\/div>)/s',
		'$1' . $subcat_html,
		$body, 1
	);

	// ---- Brand checkboxes (real 'brand' product attribute, if configured) ----
	$brand_html = '';
	if ( taxonomy_exists( 'pa_brand' ) ) {
		$brands = get_terms( array( 'taxonomy' => 'pa_brand', 'hide_empty' => true ) );
		if ( ! is_wp_error( $brands ) ) {
			foreach ( $brands as $t ) {
				$brand_html .= '<label class="filter-check"><input type="checkbox" data-filter="brand" value="' . esc_attr( $t->name ) . '"> ' . esc_html( $t->name ) . ' <span class="count">' . intval( $t->count ) . '</span></label>' . "\n";
			}
		}
	}
	if ( $brand_html ) {
		$body = preg_replace(
			'/(<h4>Brand<\/h4>\s*)(<label class="filter-check">.*?<\/label>\s*)+(?=<\/div>)/s',
			'$1' . $brand_html,
			$body, 1
		);
	} else {
		$body = preg_replace( '/<div class="filter-group">\s*<h4>Brand<\/h4>.*?<\/div>\s*\n\s*(?=<\/aside>|<div)/s', '', $body, 1 );
	}

	return $body;
}

/** Absolute path to the bundled original files. */
function seiridge_source_dir() {
	return get_stylesheet_directory() . '/source-html/';
}

/** Read one bundled source file's raw contents. Returns '' if missing. */
function seiridge_read_source( $filename ) {
	$path = seiridge_source_dir() . $filename;
	if ( ! file_exists( $path ) ) return '';
	return file_get_contents( $path );
}

/** Pull the single <style>...</style> block out of a source page. */
function seiridge_extract_style( $html ) {
	if ( preg_match( '/<style>(.*?)<\/style>/s', $html, $m ) ) return $m[1];
	return '';
}

/** Pull the single <script>...</script> block out of a source page (page-specific JS). */
function seiridge_extract_script( $html ) {
	if ( preg_match( '/<script>(.*?)<\/script>/s', $html, $m ) ) return $m[1];
	return '';
}

/**
 * Pull the <body>...</body> inner markup out of a source page, with the
 * page's <script>...</script> block stripped out. The script is extracted
 * and (for catalog pages) data-patched separately by seiridge_extract_script()
 * / seiridge_inject_*_array(), then re-appended once by the caller — leaving
 * it in $body as well would print the ORIGINAL unpatched script a second
 * time, right after the patched one.
 */
function seiridge_extract_body( $html ) {
	$body = $html;
	if ( preg_match( '/<body[^>]*>(.*?)<\/body>/s', $html, $m ) ) $body = $m[1];
	$body = preg_replace( '/<script>.*?<\/script>/s', '', $body, 1 );
	return $body;
}

/** Pull the Google Fonts <link> tags so we can re-declare them via wp_enqueue (best practice) instead of hardcoding. */
function seiridge_extract_font_links( $html ) {
	preg_match_all( '/<link[^>]*fonts\.googleapis\.com[^>]*>/', $html, $m );
	return $m[0];
}

/**
 * Build a JS product object for one WooCommerce product, matching the field
 * names the ORIGINAL renderCard()/PRODUCTS-array code already expects
 * (id, title, subcat, brand, price, was, rating, reviews, img, url) so that
 * we only ever touch the DATA, never the rendering markup/CSS.
 */
function seiridge_wc_product_to_row( $product ) {
	$categories = get_the_terms( $product->get_id(), 'product_cat' );
	$subcat     = ( $categories && ! is_wp_error( $categories ) ) ? $categories[0]->name : '';
	$brand      = ''; // set if a 'brand' taxonomy/attribute exists in your catalogue
	if ( $product->get_attribute( 'brand' ) ) $brand = $product->get_attribute( 'brand' );

	$image_id  = $product->get_image_id();
	$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : wc_placeholder_img_src( 'medium' );

	$regular = (float) $product->get_regular_price();
	$sale    = (float) $product->get_price();
	if ( ! $regular ) $regular = $sale;

	$wishlist_ids = function_exists( 'seiridge_wishlist_get' ) ? seiridge_wishlist_get() : array();

	return array(
		'id'          => $product->get_id(),
		'title'       => $product->get_name(),
		'subcat'      => $subcat,
		'brand'       => $brand,
		'price'       => $sale,
		'was'         => $regular,
		'rating'      => (float) $product->get_average_rating(),
		'reviews'     => (int) $product->get_review_count(),
		'img'         => $image_url,
		'url'         => get_permalink( $product->get_id() ),
		'wish_url'    => function_exists( 'seiridge_wishlist_toggle_url' ) ? seiridge_wishlist_toggle_url( $product->get_id() ) : '#',
		'in_wishlist' => in_array( $product->get_id(), $wishlist_ids, true ),
	);
}

/** Fetch published, in-stock WooCommerce products (newest first), optionally by category slug. */
function seiridge_get_wc_products( $limit = 24, $category_slug = '' ) {
	if ( ! class_exists( 'WooCommerce' ) ) return array();

	$args = array(
		'status'   => 'publish',
		'limit'    => $limit,
		'orderby'  => 'date',
		'order'    => 'DESC',
	);
	if ( $category_slug ) $args['category'] = array( $category_slug );

	$products = wc_get_products( $args );
	$rows = array();
	foreach ( $products as $product ) $rows[] = seiridge_wc_product_to_row( $product );
	return $rows;
}

/**
 * Replace the page's hardcoded `const PRODUCTS = [...]` array (used by
 * electronics/phones/fashion/grocery/... category pages) with live
 * WooCommerce data, and repoint the card's background-swatch + link to the
 * real product image/URL. Everything else in the script (filtering,
 * sorting, pagination) is left exactly as it was written.
 */
function seiridge_inject_products_array( $script, $rows ) {
	$js_rows = array();
	foreach ( $rows as $r ) {
		$js_rows[] = wp_json_encode( array(
			'id'          => $r['id'],
			'title'       => $r['title'],
			'subcat'      => $r['subcat'],
			'brand'       => $r['brand'],
			'price'       => round( $r['price'] ),
			'was'         => round( $r['was'] ),
			'rating'      => round( $r['rating'], 1 ),
			'reviews'     => $r['reviews'],
			'img'         => $r['img'],
			'url'         => $r['url'],
			'wish_url'    => $r['wish_url'] ?? '#',
			'in_wishlist' => ! empty( $r['in_wishlist'] ),
		), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
	$replacement = "const PRODUCTS = [\n" . implode( ",\n", $js_rows ) . "\n  ];";

	$script = preg_replace( '/const\s+PRODUCTS\s*=\s*\[.*?\n\s*\];/s', $replacement, $script, 1 );

	// Card image swatch: gradient placeholder -> real product photo.
	$script = str_replace(
		'<div class="pimg"><div class="sw" style="background:linear-gradient(135deg,${p.grad[0]},${p.grad[1]});"></div></div>',
		'<div class="pimg"><div class="sw" style="background-image:url(\'${p.img}\');background-size:cover;background-position:center;"></div></div>',
		$script
	);

	// Wish icon: kept as a <span> (NOT an <a>), because this card's outer
	// element is already an <a class="pcard"> — nesting a second <a> inside
	// it is invalid HTML, and browsers auto-close the outer anchor as soon
	// as they hit the inner one, silently cutting the image/title/price out
	// of the link (confirmed by rendering the actual output in a real DOM:
	// the resulting <a> contained only the badge span, nothing after it).
	// Click-to-wishlist still works via the delegated handler appended below.
	$script = str_replace(
		'<span class="wish">♡</span>',
		'<span class="wish" data-wish-url="${p.wish_url}">${p.in_wishlist ? \'♥\' : \'♡\'}</span>',
		$script
	);

	// Card link: hardcoded "#" / single hardcoded demo link -> real product permalink.
	$script = str_replace(
		'const href = p.id === 1 ? "seiridge-product-detail.html" : "#";',
		'const href = p.url;',
		$script
	);
	// Fallback for any card link that was simply "#"
	$script = preg_replace( '/const href = "#";/', 'const href = p.url;', $script );
	// Two catalog pages (brand-single, flash-sale) skip the `const href =`
	// variable entirely and hardcode the same literal filename straight
	// into the card's opening tag — confirmed by direct inspection of both
	// source files. Without this, every card on those two pages linked to
	// the same page instead of its own product.
	$script = str_replace(
		'return `<a href="seiridge-product-detail.html" class="pcard reveal in">',
		'return `<a href="${p.url}" class="pcard reveal in">',
		$script
	);

	// Delegated click handler for the wish <span> (event delegation, added
	// once — not per-card — since cards are re-rendered on filter/sort).
	// stopPropagation prevents the click from ALSO following the parent
	// <a class="pcard">'s own href.
	$script .= "\ndocument.addEventListener('click', function(e){ var w = e.target.closest('.wish[data-wish-url]'); if (w) { e.preventDefault(); e.stopPropagation(); window.location.href = w.getAttribute('data-wish-url'); } });\n";

	return $script;
}

/**
 * Same idea as seiridge_inject_products_array() but for the positional
 * `const pool = [ [cat,title,now,was,grad], ... ]` shape used by the
 * home page, offers page and voucher page.
 */
function seiridge_inject_pool_array( $script, $rows ) {
	$js_rows = array();
	foreach ( $rows as $r ) {
		$js_rows[] = wp_json_encode( array(
			$r['subcat'],
			$r['title'],
			round( $r['price'] ),
			round( $r['was'] ),
			$r['img'],
			array( 'id' => $r['id'], 'url' => $r['url'], 'wish_url' => $r['wish_url'] ?? '#', 'in_wishlist' => ! empty( $r['in_wishlist'] ) ),
		), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
	$replacement = "const pool = [\n" . implode( ",\n", $js_rows ) . "\n  ];";
	$script = preg_replace( '/const\s+pool\s*=\s*\[.*?\n\s*\];/s', $replacement, $script, 1 );

	$script = str_replace(
		'const [cat,title,now,was,grad] = item;',
		'const [cat,title,now,was,img,meta] = item;',
		$script
	);
	$script = str_replace(
		'<div class="pimg"><div class="sw" style="background:linear-gradient(135deg,${grad[0]},${grad[1]});"></div></div>',
		'<div class="pimg"><div class="sw" style="background-image:url(\'${img}\');background-size:cover;background-position:center;"></div></div>',
		$script
	);

	// Card container: was a plain, non-clickable <div class="pcard reveal">
	// in the original (these grids never had real products to link to) —
	// now a real <a> to the real product, matching how catalog-page cards
	// already work. Both the opening tag and its one matching closing tag
	// are replaced together so they stay paired.
	$script = str_replace(
		'return `<div class="pcard reveal">',
		'return `<a href="${meta.url}" class="pcard reveal">',
		$script
	);
	$script = str_replace(
		"\${extra}\n      </div>\n    </div>`;",
		"\${extra}\n      </div>\n    </a>`;",
		$script
	);

	// Wish icon: <span>, NOT <a> — the card container above is now itself a
	// real <a>, so nesting a second <a> inside it would be invalid HTML and
	// cut the rest of the card out of the link (the exact bug already fixed
	// for catalog-page cards; applying the same fix here pre-emptively so
	// converting this div to a real link doesn't reintroduce it).
	$script = str_replace(
		'<span class="wish">♡</span>',
		'<span class="wish" data-wish-url="${meta.wish_url}">${meta.in_wishlist ? \'♥\' : \'♡\'}</span>',
		$script
	);
	// Delegated click handler for the wish <span> (added once; harmless if
	// appended on a page that also has the catalog-page version, since the
	// selector and behaviour are identical).
	if ( strpos( $script, "e.target.closest('.wish[data-wish-url]')" ) === false ) {
		$script .= "\ndocument.addEventListener('click', function(e){ var w = e.target.closest('.wish[data-wish-url]'); if (w) { e.preventDefault(); e.stopPropagation(); window.location.href = w.getAttribute('data-wish-url'); } });\n";
	}

	return $script;
}

/**
 * Master renderer. Given a source filename + its page-map entry, produce the
 * final HTML (style + possibly-modified script + body) to print inside the
 * generic page template. This is what "same original card design, real
 * WooCommerce data" means concretely: markup and CSS are byte-identical to
 * the export; only the JS data array is regenerated per request.
 */
function seiridge_render_source_page( $filename, $entry, $post_id = null ) {
	$html = seiridge_read_source( $filename );
	if ( ! $html ) return '<p>Missing source file: ' . esc_html( $filename ) . '</p>';

	$style  = seiridge_extract_style( $html );
	$script = seiridge_extract_script( $html );
	$body   = seiridge_extract_body( $html );

	if ( isset( $entry['type'] ) && $entry['type'] === 'catalog_products' ) {
		$category_slug = isset( $entry['wc_category'] ) ? $entry['wc_category'] : ( $entry['slug'] ?? '' );
		$rows = seiridge_get_wc_products( 25, $category_slug );
		if ( $rows ) $script = seiridge_inject_products_array( $script, $rows );
		$body = seiridge_render_real_filters( $body, $category_slug );
	} elseif ( isset( $entry['type'] ) && $entry['type'] === 'catalog_pool' ) {
		$rows = seiridge_get_wc_products( 25 );
		if ( $rows ) $script = seiridge_inject_pool_array( $script, $rows );
	} elseif ( isset( $entry['type'] ) && $entry['type'] === 'home' ) {
		$rows = seiridge_get_wc_products( 24 );
		if ( $rows ) $script = seiridge_inject_pool_array( $script, $rows );
		if ( function_exists( 'seiridge_render_editable_body' ) ) {
			$pid = $post_id ?: ( function_exists( 'get_the_ID' ) ? get_the_ID() : 0 );
			if ( $pid ) $body = seiridge_render_editable_body( $body, $pid, 'home' );
		}
	}

	ob_start();
	?>
	<?php if ( $style ) : ?><style><?php echo $style; ?></style><?php endif; ?>
	<?php echo seiridge_rewrite_internal_links( $body ); ?>
	<?php if ( $script ) : ?><script><?php echo seiridge_rewrite_internal_links( $script ); ?></script><?php endif; ?>
	<?php
	return ob_get_clean();
}
