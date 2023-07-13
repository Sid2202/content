<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="widget-service-orders box-dashboard-wrapper">
	<h3 class="widget-title"><?php echo esc_html__('Service History','freeio') ?></h3>
	<div class="inner-list">
		<?php
		$service_id = isset($_GET['service_id']) ? $_GET['service_id'] : '';
		$service_order_id = isset($_GET['service_order_id']) ? $_GET['service_order_id'] : '';
		$user_id = WP_Freeio_User::get_user_id();
		$freelancer_user_id = get_post_field('post_author', $service_id);
		$employer_user_id = get_post_field('post_author', $service_order_id);
		$service_order_service_id = get_post_meta($service_order_id, WP_FREEIO_SERVICE_ORDER_PREFIX.'service_id', true);

		if ( $user_id != $freelancer_user_id || $service_id != $service_order_service_id ) {
			?>
			<div class="not-found"><?php esc_html_e('You have not permission to view this page.', 'freeio'); ?></div>
			<?php
		} else {
			?>
			<div class="service-details-history mb-4">
				<h2 class="inner-title"><?php esc_html_e('Service Details', 'freeio'); ?></h2>
				<div class="inner-content">
					<?php
					$service = get_post($service_id);
					?>
					<div class="service-content">
						<div class="title-wrapper">
							<h3 class="service-tittle">
								<a href="<?php echo esc_url(get_permalink($service)); ?>"><?php echo get_the_title($service); ?></a>
							</h3>
							<?php freeio_service_display_featured_icon($service); ?>
						</div>
						<div class="listing-metas d-flex align-items-start flex-wrap">
							<?php freeio_service_display_category($service, 'icon'); ?>
							<?php freeio_service_display_short_location($service, 'icon'); ?>
							<?php freeio_service_display_postdate($service, 'icon'); ?>
						</div>
					</div>
					<div class="price-wrapper">
		                <?php

		                $meta_obj = WP_Freeio_Service_Meta::get_instance($service_id);
						if ( $meta_obj->check_post_meta_exist('price') ) {
							$service_price = $meta_obj->get_post_meta( 'price' );

							$addons = get_post_meta($service_order_id, WP_FREEIO_SERVICE_ORDER_PREFIX.'addons', true);
							if ( !empty($addons) ) {
								foreach ($addons as $addon) {
									$addon_price = !empty($addon['price']) ? $addon['price'] : 0;
									$service_price += $addon_price;
								}
							}
			                echo WP_Freeio_Price::format_price($service_price);
			            }
		                ?>
		            </div>
					<?php if ( $addons ) { ?>
						<div class="service-status">
							
							<h5><?php esc_html_e('Addons', 'freeio'); ?></h5>
							<ul class="addons-list">
								<?php foreach ($addons as $addon) {
									if ( !empty($addon['id']) ) {
									$addon_post = get_post($addon['id']);
										if ( $addon_post ) {
									?>
											<li>
												
						                            <div class="addon-item">
						                                
					                                    <div class="content">
					                                        <h5 class="title"><?php echo trim($addon_post->post_title); ?></h5>
					                                        <div class="inner">
					                                            <?php echo trim($addon_post->post_content); ?>
					                                        </div>
					                                        <div class="price">
					                                            <?php
					                                                $price = !empty($addon['price']) ? $addon['price'] : '';
					                                                echo WP_Freeio_Price::format_price($price, true);
					                                            ?>
					                                        </div>
					                                    </div>
						                            </div>
						                        
											</li>
										<?php
										}
									}
								} ?>
							</ul>
							
						</div>
					<?php } ?>
				</div>
			</div>
			
			<div class="freelancer-history-service mt-4 mb-4">
				<div class="freelancer-item">
					<div class="d-sm-flex align-items-center">
						<?php
							$employer_post_id = WP_Freeio_User::get_employer_by_user_id($employer_user_id);
							$employer = get_post($employer_post_id);

							$status = get_post_status($service_order_id);
						?>
						<div class="flex-shrink-0">
	        				<?php freeio_employer_display_logo($employer); ?>
						</div>
						<div class="information-right d-lg-flex">
							<div class="inner-middle">

								<div class="d-flex freelancer-title-wrapper">
									<h3 class="freelancer-title">
										<a href="<?php echo esc_url(get_permalink($employer)); ?>"><?php echo get_the_title($employer); ?></a>
									</h3>
									<span class="flex-shrink-0">
										<?php freeio_employer_display_featured_icon($employer); ?>
									</span>
								</div>

								<div class="listing-metas d-flex align-items-start flex-wrap">
									<?php freeio_employer_display_short_location($employer, 'icon'); ?>
		                    		<?php freeio_employer_display_rating_reviews($employer); ?>
								</div>
								
							</div>
							<div class="inner-right d-flex align-items-center mt-2 mt-lg-0">
								<?php
								$statuses = WP_Freeio::post_statuses();
								
								$classes = 'bg-primary';
								if ( $status == 'cancelled' ) {
									$classes = 'bg-cancelled';
								}
								?>
								<span class="badge <?php echo esc_attr($classes); ?>">
									<?php if ( !empty($statuses[$status]) ) {
										echo trim($statuses[$status]);
									} else {
										echo trim($status); 
									}
									?>
								</span>

							</div>
						</div>
					</div>
				</div>
			</div>

			<div id="messages" class="messages messages-service-history">
				<div id="messages-list" class="messages-list">
					<?php echo WP_Freeio_Service::list_service_order_messages($service_order_id); ?>
				</div>
				<div class="service-order-message-form-wrapper">
					<form id="service-order-message-form-<?php echo esc_attr($service_order_id); ?>" method="post" action="?" class="service-order-message-form form-theme" action="" enctype="multipart/form-data">
			            <div class="form-group">
			                <textarea class="form-control" name="message" placeholder="<?php esc_attr_e( 'Message', 'freeio' ); ?>" required="required"></textarea>
			            </div><!-- /.form-group -->

			            <?php
						$file_types = wp_freeio_get_option('image_file_types');
						$file_types = !empty($file_types) ? $file_types : array();
						$cv_types = wp_freeio_get_option('cv_file_types');
						$file_types = !empty($cv_types) ? array_merge($file_types, $cv_types) : $file_types;
						
						$file_types_str = !empty($file_types) ? implode(', ', $file_types) : '';
						?>
						<div class="col-12">
					     	<div class="form-group upload-file-btn-wrapper">
					            <input type="file" name="attachments[]" data-file_types="<?php echo esc_attr(!empty($file_types) ? implode('|', $file_types) : ''); ?>" multiple="multiple">

					            <div class="label-can-drag">
									<div class="group-upload">
								        <div class="upload-file-btn" data-text="<?php echo esc_attr(sprintf(esc_html__('Upload File (%s)', 'freeio'), $file_types_str)); ?>">
							            	<span class="text"><?php echo sprintf(esc_html__('Upload File (%s)', 'freeio'), $file_types_str); ?></span>
								        </div>
								    </div>
								</div>
					        </div>

				        </div><!-- /.form-group -->

			            <input type="hidden" name="service_id" value="<?php echo esc_attr($service_id); ?>">
			            <input type="hidden" name="service_order_id" value="<?php echo esc_attr($service_order_id); ?>">
			            <button class="button btn btn-theme btn-outline" name="contact-form"><?php echo esc_html__( 'Send Message', 'freeio' ); ?><i class="flaticon-right-up next"></i></button>
			        </form>
				</div>
			</div>
			<?php
			
		} ?>
	</div>
</div>