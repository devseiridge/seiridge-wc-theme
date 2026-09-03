<?php
/**
 * Template Name: Seiridge Source Page
 *
 * Generic wrapper used for every imported original page (static pages and
 * catalog/listing pages alike). Do NOT hand-edit page content in Elementor —
 * this template intentionally bypasses block/Elementor rendering so the
 * original HTML/CSS/JS is served unchanged (or, for catalog pages, with only
 * the product data swapped — see inc/html-render.php).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$source_file = get_post_meta( get_the_ID(), '_seiridge_source_file', true );
$page_entry  = get_post_meta( get_the_ID(), '_seiridge_page_entry', true );
$fonts       = seiridge_extract_font_links( seiridge_read_source( $source_file ) );
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
<?php echo seiridge_render_source_page( $source_file, is_array( $page_entry ) ? $page_entry : array() ); ?>
<?php wp_footer(); ?>
</body>
</html>
