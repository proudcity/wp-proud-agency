<?php get_header(); ?>
<section id="primary">
    <?php if ( have_posts() ) : ?>

        <?php while ( have_posts() ) : the_post(); ?>
            <div class="card" data-equalize-height="">
                <a href="<?php the_agency_permalink(); ?>"><?php the_post_thumbnail( array( 400, 200 ) ); ?></a>
                <div class="card-block">
                    <h4 class="card-title"><a href="<?php the_agency_permalink(); ?>"><?php the_title(); ?></a></h4>
                    <p class="card-text">
                      <?php esc_html( get_post_meta( get_the_ID(), 'phone', true ) ); ?><br/>
                    </p>
                    <a href="#" class="btn btn-primary">Learn more</a>
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
</section>
<br /><br />
<?php get_footer(); ?>