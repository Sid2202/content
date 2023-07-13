<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


global $post, $preview_post;
if ( $preview_post ) {
    $post = $preview_post;
}
if ( empty($post->post_type) || $post->post_type !== 'service' ) {
    return;
}

$meta_obj = WP_Freeio_Service_Meta::get_instance($post->ID);

if ( !$meta_obj->check_post_meta_exist('price') ) {
    return;
}
$price = $meta_obj->get_post_meta( 'price' );

if ( empty( $price ) || ! is_numeric( $price ) ) {
    return;
}


extract( $args );

extract( $args );
extract( $instance );


echo trim($before_widget);
$title = apply_filters('widget_title', $instance['title']);

if ( $title ) {
    echo trim($before_title)  . trim( $title ) . $after_title;
}
$rand = freeio_random_key();

?>
    <div class="service-price">
        <form id="service-add-to-cart-<?php echo esc_attr($post->ID.'_'.$rand); ?>" class="service-add-to-cart" method="post">
            <div class="service-price-inner">
                <?php echo WP_Freeio_Service::get_price_html($post->ID); ?>
            </div>
            <?php if ( $meta_obj->check_post_meta_exist('addons') && ($addons = $meta_obj->get_post_meta( 'addons' )) ) { ?>
                <div class="service-price-addons">
                    <?php foreach ($addons as $addon_id) {
                        $addon_post = get_post($addon_id);
                        if ( $addon_post ) {
                    ?>
                            <div class="addon-item">
                                <label for="addon-item-<?php echo esc_attr($addon_id.'_'.$rand);?>">
                                    <input id="addon-item-<?php echo esc_attr($addon_id.'_'.$rand);?>" type="checkbox" name="service_addons[]" value="<?php echo esc_attr($addon_post->ID); ?>">

                                    <div class="content">
                                        <h5 class="title"><?php echo trim($addon_post->post_title); ?></h5>
                                        <div class="inner">
                                            <?php echo trim($addon_post->post_content); ?>
                                        </div>
                                        <div class="price">
                                            <?php
                                                $price = get_post_meta($addon_post->ID, WP_FREEIO_SERVICE_ADDON_PREFIX . 'price', true);
                                                echo WP_Freeio_Price::format_price($price, true);
                                            ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            <?php } ?>

            <input type="hidden" name="service_id" value="<?php echo esc_attr($post->ID); ?>">
            <button type="submit" class="btn btn-theme btn-inverse w-100"><?php esc_html_e('Buy Now', 'freeio'); ?> <span><?php echo WP_Freeio_Service::get_price_html($post->ID, false); ?></span> <i class="flaticon-right-up next"></i></button>
        </form>
    </div>
<?php

echo trim($after_widget);