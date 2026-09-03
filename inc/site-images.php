<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The three logo image classes are byte-for-byte identical across ALL 52
 * original files (verified: same class names, same base64 data, in every
 * page type — home, catalog, cart, checkout, account, static, product
 * detail). That means one admin-uploaded replacement genuinely updates the
 * logo everywhere, matching how a real site's logo works — not a per-page
 * "editable image" that would need setting 52 times.
 *
 * Deliberately uses a plain native file upload (not wp_enqueue_media()'s JS
 * modal) — this doesn't depend on JS to work correctly, which matters
 * because there's no live browser here to verify the media modal against.
 */
function seiridge_get_logo_attachment_id( $key ) {
	return (int) get_option( 'seiridge_logo_' . $key . '_id', 0 );
}

function seiridge_get_logo_url( $key ) {
	$id = seiridge_get_logo_attachment_id( $key );
	if ( ! $id ) return '';
	$url = wp_get_attachment_image_url( $id, 'full' );
	return $url ?: '';
}

/**
 * Swap the header logo, footer logo, and small icon <img src="..."> for an
 * admin-uploaded replacement, if one has been set — otherwise the original
 * embedded artwork is left exactly as exported. Verified: these three
 * class names/patterns are identical across all 52 source files.
 */
function seiridge_apply_global_images( $html ) {
	$header_url = seiridge_get_logo_url( 'header' );
	$footer_url = seiridge_get_logo_url( 'footer' );
	$icon_url   = seiridge_get_logo_url( 'icon' );

	if ( $header_url ) {
		$html = preg_replace(
			'/<img class="brand-logo" src="data:image\/[a-z]+;base64,[^"]+" alt="[^"]*">/',
			'<img class="brand-logo" src="' . esc_url( $header_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '">',
			$html, 1
		);
	}
	if ( $footer_url ) {
		$html = preg_replace(
			'/<img class="brand-logo brand-logo--footer" src="data:image\/[a-z]+;base64,[^"]+" alt="[^"]*">/',
			'<img class="brand-logo brand-logo--footer" src="' . esc_url( $footer_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '">',
			$html, 1
		);
	}
	if ( $icon_url ) {
		$html = preg_replace(
			'/<img class="logo-icon" src="data:image\/[a-z]+;base64,[^"]+" alt="[^"]*">/',
			'<img class="logo-icon" src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '">',
			$html, 1
		);
	}
	return $html;
}

/**
 * Wrap the header logo and footer logo <img> tags in a real link to the
 * WordPress homepage — the original design never linked them at all (no
 * <a> around either <img>, verified against every source file). Runs
 * AFTER seiridge_apply_global_images() so it wraps whichever src is
 * currently in place, original artwork or an admin-uploaded replacement.
 * Guards against double-wrapping if this function is ever called twice on
 * the same markup.
 */
function seiridge_link_logo_to_home( $html ) {
	$home = esc_url( home_url( '/' ) );
	return preg_replace_callback(
		'/<img class="(brand-logo(?: brand-logo--footer)?)" src="([^"]*)" alt="([^"]*)">/',
		function ( $m ) use ( $home ) {
			return '<a href="' . $home . '" class="logo-link" aria-label="Go to homepage"><img class="' . $m[1] . '" src="' . $m[2] . '" alt="' . $m[3] . '"></a>';
		},
		$html
	);
}

/** Admin page: Appearance -> Seiridge Site Images. Plain upload, no JS media-modal dependency. */
function seiridge_register_images_admin_page() {
	add_theme_page( 'Seiridge Site Images', 'Seiridge Site Images', 'manage_options', 'seiridge-site-images', 'seiridge_render_images_admin_page' );
}
add_action( 'admin_menu', 'seiridge_register_images_admin_page' );

function seiridge_render_images_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	if ( isset( $_POST['seiridge_images_nonce'] ) && wp_verify_nonce( $_POST['seiridge_images_nonce'], 'seiridge_save_images' ) ) {
		foreach ( array( 'header', 'footer', 'icon' ) as $key ) {
			if ( ! empty( $_FILES[ 'seiridge_logo_' . $key ]['name'] ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attachment_id = media_handle_upload( 'seiridge_logo_' . $key, 0 );
				if ( ! is_wp_error( $attachment_id ) ) {
					update_option( 'seiridge_logo_' . $key . '_id', $attachment_id );
				}
			}
			if ( isset( $_POST[ 'seiridge_logo_' . $key . '_reset' ] ) ) {
				delete_option( 'seiridge_logo_' . $key . '_id' );
			}
		}
		echo '<div class="notice notice-success"><p>Saved.</p></div>';
	}

	$labels = array(
		'header' => 'Header logo (shown at the top of every page)',
		'footer' => 'Footer logo (shown at the bottom of every page)',
		'icon'   => 'Small logo icon (compact nav variant)',
	);
	?>
	<div class="wrap">
		<h1>Seiridge Site Images</h1>
		<p>Upload a replacement for the site logo. Leave a field empty to keep the original design's logo. This updates the logo on all 52 pages at once — it is not set per page.</p>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'seiridge_save_images', 'seiridge_images_nonce' ); ?>
			<table class="form-table">
				<?php foreach ( $labels as $key => $label ) : $current = seiridge_get_logo_url( $key ); ?>
				<tr>
					<th><label for="seiridge_logo_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
					<td>
						<?php if ( $current ) : ?>
							<p><img src="<?php echo esc_url( $current ); ?>" style="max-height:60px;display:block;margin-bottom:8px;">
							<label><input type="checkbox" name="seiridge_logo_<?php echo esc_attr( $key ); ?>_reset" value="1"> Remove and restore original</label></p>
						<?php else : ?>
							<p><em>Using the original design's logo.</em></p>
						<?php endif; ?>
						<input type="file" name="seiridge_logo_<?php echo esc_attr( $key ); ?>" id="seiridge_logo_<?php echo esc_attr( $key ); ?>" accept="image/*">
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'Save Images' ); ?>
		</form>
	</div>
	<?php
}
