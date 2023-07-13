<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

?>
<section class="service-detail-version-v1">
	
	<section id="primary" class="content-area inner">
		<div id="main" class="site-main content" role="main">
			<?php if ( have_posts() ) : ?>

				<?php while ( have_posts() ) : the_post();
					global $post;
					if ( $post->post_status == 'expired' ) {
						echo WP_Freeio_Template_Loader::get_template_part( 'content-single-service-expired' );
					} else {
						if ( !WP_Freeio_Service::check_view_service_detail() ) {
						?>
							<div class="restrict-wrapper container">
								<?php
									$restrict_detail = wp_freeio_get_option('service_restrict_detail', 'all');
									switch ($restrict_detail) {
										case 'register_user':
											?>
											<h2 class="restrict-title"><?php esc_html_e( 'This page is restricted for registered users only.', 'freeio' ); ?></h2>
											<div class="restrict-content"><?php esc_html_e( 'Please login to view this page', 'freeio' ); ?></div>
											<?php
											break;
										case 'register_freelancer':
											?>
											<h2 class="restrict-title"><?php esc_html_e( 'Please login as freelancer to view service.', 'freeio' ); ?></h2>
											<?php
											break;
										default:
											$content = apply_filters('wp-freeio-restrict-service-detail-information', '', $post);
											echo trim($content);
											break;
									}
								?>
							</div><!-- /.alert -->

							<?php
						} else {
						?>
							<div class="single-listing-wrapper service" <?php freeio_service_item_map_meta($post); ?>>
								<?php
									echo WP_Freeio_Template_Loader::get_template_part( 'content-single-service' );
								?>
							</div>
					<?php
						}
					}
				endwhile; ?>

				<?php the_posts_pagination( array(
					'prev_text'          => esc_html__( 'Previous page', 'freeio' ),
					'next_text'          => esc_html__( 'Next page', 'freeio' ),
					'before_page_number' => '<span class="meta-nav screen-reader-text">' . esc_html__( 'Page', 'freeio' ) . ' </span>',
				) ); ?>
			<?php else : ?>
				<?php get_template_part( 'content', 'none' ); ?>
			<?php endif; ?>
		</div><!-- .site-main -->
	</section><!-- .content-area -->
</section>
<?php get_footer(); ?>
