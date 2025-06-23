<?php

function get_pixelgrade_club_page_layout() {
	// Retrieve the products (themes) the activation customer has access to
	// They should match the ones on his My Account page
	$user_themes = PixelgradeCare_Admin::get_customer_products(); ?>

	<div class="wrap pixelgrade-themes-page">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Pixelgrade Themes', 'pixelgrade_care' ); ?></h1>
		<div class="theme-browser">
			<div class="themes wp-clearfix <?php echo empty($user_themes) ? 'no-results' : ''; ?>">

				<?php
				if ( empty( $user_themes ) ) {
					echo '<p class="no-themes">' . esc_html__( 'Sorry, but we couldn\'t find any themes.', 'pixelgrade_care' ) . '</p>';
				} else {
					foreach ( $user_themes as $theme ) {
						$aria_action = esc_attr( $theme['sku'] . '-action' );
						$aria_name = esc_attr( $theme['sku'] . '-name' );

						// do a double check to see if theme is installed
						$get_theme = wp_get_theme( $theme['slug'] );
						if ( ! $get_theme->errors() ) {// theme exists / installed = true
							$theme['installed'] = true;
						} else {
							$theme['installed'] = false;
						}

						// do a double check to see if theme is active
						$active_theme = wp_get_theme();
						if ( $active_theme->get_stylesheet() == $theme['slug'] ) {
							$theme['active'] = true;

							// If we are dealing with an LT theme, then being active takes on an extended meaning.
							// Since LT themes share the same theme code, we need to take into account
							// the actual product SKU in the active license to see which theme is active and which not.
							if ( $theme['is_lt_theme'] ) {
								$license_main_product_sku = PixelgradeCare_Admin::get_theme_main_product_sku();
								if ( $theme['sku'] !== $license_main_product_sku ) {
									$theme['active'] = false;
								}
							}
						} else {
							$theme['active'] = false;
						} ?>

						<div class="theme<?php if ( $theme['active'] ) { echo ' active'; } elseif ( $theme['installed'] ) { echo ' installed'; } ?>" tabindex="0" aria-describedby="<?php echo esc_attr( $aria_action . ' ' . $aria_name ); ?>">
							<?php if ( ! empty( $theme['screenshot'] ) ) { ?>
								<div class="theme-screenshot">
									<?php echo $theme['screenshot'] ?>
								</div>
							<?php } else { ?>
								<div class="theme-screenshot blank"></div>
							<?php } ?>

							<?php if ( $theme['hasUpdate'] ) { ?>
								<div class="update-message notice inline notice-warning notice-alt">
									<?php if ( $theme['hasPackage'] ) { ?>
										<p><?php esc_html_e( 'New version available.', 'pixelgrade_care' ); ?><button class="button-link" type="button"><?php esc_html_e( 'Update now', 'pixelgrade_care' ); ?></button></p>
									<?php } else { ?>
										<p><?php esc_html_e( 'New version available.', 'pixelgrade_care' ); ?></p>
									<?php } ?>
								</div>
							<?php } ?>

							<span class="more-details"
							      id="<?php echo esc_attr( $aria_action ); ?>"><?php esc_html_e( 'Theme Details', 'pixelgrade_care' ); ?></span>
							<div class="theme-author"><?php
								/* translators: %s: theme author name */
								printf( esc_html__( 'By %s', 'pixelgrade_care' ), $theme['author'] ); ?></div>

							<div class="theme-id-container">
								<?php if ( $theme['active'] ) { ?>
									<h2 class="theme-name" id="<?php echo esc_attr( $aria_name ); ?>">
										<?php
										/* translators: %s: theme name */
										printf( __( '<span>Active:</span> %s', 'pixelgrade_care' ), $theme['name'] );
										?>
									</h2>
								<?php } else { ?>
									<h2 class="theme-name" id="<?php echo esc_attr( $aria_name ); ?>"><?php echo $theme['name']; ?></h2>
								<?php } ?>

								<div class="theme-actions">
									<?php if ( $theme['active'] ) { ?>
										<?php if ( current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) { ?>
											<a class="button button-primary customize load-customize hide-if-no-customize"
											   href="<?php echo esc_url( wp_customize_url( $theme['slug'] ) ); ?>"><?php esc_html_e( 'Customize', 'pixelgrade_care' ); ?></a>
										<?php } ?>
									<?php } else { ?>
										<?php
										/* translators: %s: Theme name */
										if ( $theme['installed'] ) {
											$aria_label        = sprintf( esc_html_x( 'Activate %s', 'theme', 'pixelgrade_care' ), '{{ data.name }}' );
											$aria_theme_action = esc_html__( 'Activate', 'pixelgrade_care' );
											$aria_class        = 'button-primary club-activate-theme';
										} else {
											$aria_label        = sprintf( esc_html_x( 'Install %s', 'theme', 'pixelgrade_care' ), '{{ data.name }}' );
											$aria_theme_action = esc_html__( 'Install', 'pixelgrade_care' );
											$aria_class        = 'club-install-theme';
										}
										?>
										<?php if ( current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) { ?>
											<a class="button load-customize hide-if-no-customize"
											   href="<?php echo esc_url( $theme['demo_url'] ); ?>"
											   target="_blank"><?php esc_html_e( 'Live Demo', 'pixelgrade_care' ); ?></a>
										<?php } ?>
										<a class="button activate <?php echo esc_attr( $aria_class ); ?>"
										   href="<?php echo '#'; ?>"
										   data-url="<?php echo esc_url( $theme['download_url'] ); ?>"
										   data-slug="<?php echo esc_attr( $theme['slug'] ); ?>"
										   data-sku="<?php echo esc_attr( $theme['sku'] ); ?>"
										   data-is-lt-theme="<?php echo $theme['is_lt_theme'] ? 'yes' : 'no'; ?>"
										   aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php echo $aria_theme_action; ?></a>
									<?php } ?>

								</div>
							</div>
						</div>
						<?php
					}
				} ?>
			</div>
		</div>
	</div>
	<script>
		jQuery(document).ready(function () {
			jQuery('.more-details').remove();
		});
	</script>
	<?php
}
