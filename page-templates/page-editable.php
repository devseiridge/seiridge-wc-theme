<?php
/**
 * Template Name: Seiridge Editable Page
 *
 * Same as page-templates/page-html.php, plus: after the original body is
 * extracted, seiridge_render_editable_body() swaps in any admin-edited
 * text for the specific fields defined in inc/editable-content.php
 * (heading/subheading/etc. for this slug). Layout/markup is otherwise
 * untouched — same discipline as every other page.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$source_file = get_post_meta( get_the_ID(), '_seiridge_source_file', true );
$page_entry  = get_post_meta( get_the_ID(), '_seiridge_page_entry', true );
$slug        = is_array( $page_entry ) ? $page_entry['slug'] : '';
$fonts       = seiridge_extract_font_links( seiridge_read_source( $source_file ) );

$html   = seiridge_read_source( $source_file );
$style  = seiridge_extract_style( $html );
$body   = seiridge_extract_body( $html );
$script = seiridge_extract_script( $html );

$body = seiridge_render_editable_body( $body, get_the_ID(), $slug );
$body = seiridge_rewrite_internal_links( $body );
$script = seiridge_rewrite_internal_links( $script );
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
