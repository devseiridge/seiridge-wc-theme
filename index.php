<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php get_header(); ?>
<div class="wrap" style="padding:60px 20px;text-align:center;">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<h1><?php the_title(); ?></h1>
		<div><?php the_content(); ?></div>
	<?php endwhile; else : ?>
		<p>Nothing found. This theme is built to serve the imported Seiridge pages — see wp-admin → Pages.</p>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
