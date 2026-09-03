<?php
/**
 * Shop + category archive override — uses the ORIGINAL catalog-page layout
 * (electronics.html) as the shared shell for every WooCommerce category,
 * so a newly-added category automatically gets a page in this same design
 * without anyone building it in Elementor.
 *
 * LIMITATION (see README): the original per-category pages (phones.html,
 * fashion.html, grocery.html, ...) each have slightly different sidebar
 * filter options (subcategory checkboxes, brand lists) written by hand for
 * that department. This override standardises on electronics.html's filter
 * shell for ALL WooCommerce categories; the filter option labels themselves
 * are generic placeholders here and should be reviewed per category.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$html   = seiridge_read_source( 'Seiridge-v4.9.3-electronics.html' );
$style  = seiridge_extract_style( $html );
$body   = seiridge_extract_body( $html );
$script = seiridge_extract_script( $html );
$fonts  = seiridge_extract_font_links( $html );

$queried_object = get_queried_object();
$category_slug  = ( $queried_object instanceof WP_Term ) ? $queried_object->slug : '';
$heading        = ( $queried_object instanceof WP_Term ) ? $queried_object->name : __( 'Shop', 'seiridge-wc' );
$subheading     = ( $queried_object instanceof WP_Term ) ? wp_strip_all_tags( $queried_object->description ) : __( 'Every product in the store, in one place.', 'seiridge-wc' );

$body = preg_replace( '/<h1>.*?<\/h1>/s', '<h1>' . esc_html( $heading ) . '</h1>', $body, 1 );
$body = preg_replace( '/<p class="sub">.*?<\/p>/s', '<p class="sub">' . esc_html( $subheading ?: 'Browse the full range.' ) . '</p>', $body, 1 );

$rows = seiridge_get_wc_products( 25, $category_slug );
if ( $rows ) $script = seiridge_inject_products_array( $script, $rows );
$body = seiridge_render_real_filters( $body, $category_slug );

$body   = seiridge_rewrite_internal_links( $body );
$script = seiridge_rewrite_internal_links( $script );

get_header( 'shop' );
?>
<?php if ( $style ) : ?><style><?php echo $style; ?></style><?php endif; ?>
<?php echo $body; ?>
<?php if ( $script ) : ?><script><?php echo $script; ?></script><?php endif; ?>
<?php
get_footer( 'shop' );
