<div class="layout-posts-list">
    <?php while ( have_posts() ) : the_post(); ?>
        <?php get_template_part( 'template-posts/loop/inner-list-v2', null, array('thumbsize' => $args['thumbsize']) ); ?>
    <?php endwhile; ?>
</div>