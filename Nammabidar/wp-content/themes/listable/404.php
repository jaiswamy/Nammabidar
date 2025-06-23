<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Listable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<article>

			<div class="entry-content" id="entry-content-anchor">
                <div class="header-content">
                    <h1 class="entry-title"><?php esc_html_e( 'Oops! We can&rsquo;t seem to find the page you&rsquo;re looking for.', 'listable' ); ?></h1>
                </div>
                
				<p><?php esc_html_e( 'The page you were looking for could not be found. It might have been removed, renamed, or did not exist in the first place.', 'listable' ); ?></p>

                <a class="btn" href="<?php echo home_url(); ?>" title="<?php bloginfo( 'name' ) ?>" rel="home">
					<?php printf( __( '&#8592; Back to homepage', 'pile' ), home_url() ); ?>
                </a>

				<?php

                if ( listable_using_facetwp() ) {
	                get_template_part( 'template-parts/facetwp-loop-placeholder' );
                }
                ?>

			</div><!-- .entry-content -->

		</article><!-- #post-## -->
	</main><!-- #main -->
</div><!-- #primary -->

<?php
get_sidebar();

get_footer();
