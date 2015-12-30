<?php
/*Template Name: New Template
*/
 
get_header(); ?>
<div id="primary">
    <div id="content" role="main">
    <?php
    $mypost = array( 'post_type' => 'agencies', );
    $loop = new WP_Query( $mypost );
    ?>
    <?php while ( $loop->have_posts() ) : $loop->the_post();?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
 
                <!-- Display featured image in right-aligned floating div -->
                <div style="float: right; margin: 10px">
                    <?php the_post_thumbnail( array( 100, 100 ) ); ?>
                </div>
 
                <!-- Display Title and Author Name -->
                <h1><?php the_title(); ?></h1>

                <!-- Display social media fields -->
                <h3>Contact us: </h3>
                <?php
                foreach (agency_contact_fields() as $key => $label) {
                    $value = esc_html( get_post_meta( get_the_ID(), $key, true ) );
                    if (!empty($value)) {
                    ?>
                        <div class="field field-contact field-<?php print $key; ?>"><?php print $value; ?></div>
                    <?php
                    }
                }
                ?>
 
                <!-- Display social media fields -->
                <strong>Find us: </strong>
                <?php
                


                ?>
            </header>
 
            <!-- Display movie review contents -->
            <div class="entry-content"><?php the_content(); ?></div>
        </article>
 
    <?php endwhile; ?>
    </div>
</div>
<?php wp_reset_query(); ?>
<?php get_footer(); ?>