<?php get_header(); ?>
<div class="card-columns card-columns-sm-2 card-columns-md-3 card-columns-xs-1">
    <?php if ( have_posts() ) : ?>

        <?php while ( have_posts() ) : the_post(); ?>
            <div class="card" data-equalize-height="">
                <a href="<?php $Agency->the_agency_permalink() ?>"><?php the_post_thumbnail( 'agency-thumb' ) ?></a>
                <div class="card-block">
                    <h4 class="card-title"><a href="<?php $Agency->the_agency_permalink() ?>"><?php the_title() ?></a></h4>
                    <p class="card-text">
                      <?php //esc_html( get_post_meta( get_the_ID(), 'phone', true ) ) ?>
                      <?php $Agency->the_agency_social() ?>
                    </p>
                </div>
            </div>
        <?php endwhile; ?>

 
        <?php global $wp_query;
        if ( isset( $wp_query->max_num_pages ) && $wp_query->max_num_pages > 1 ) { ?>
            <nav id="<?php echo $nav_id; ?>">
                <div class="nav-previous"><?php next_posts_link( '<span class="meta-nav">&larr;</span> Older reviews'); ?></div>
                <div class="nav-next"><?php previous_posts_link( 'Newer reviews <span class= "meta-nav">&rarr;</span>' ); ?></div>
            </nav>
        <?php };
    endif; ?>
</div>
<?php get_footer(); ?>