<?php
// @todo: move this to wp-proud-core plugin

/**
* Remove extra fields on the admin pages.
*/
add_action( 'init', 'proud_core_remove_post_admin_fields', 25 );
function proud_core_remove_post_admin_fields() {
  remove_post_type_support( 'question', 'author' );
  remove_post_type_support( 'question', 'comments' );
  remove_post_type_support( 'question', 'custom-fields' );
}


/**
* Add REST API support to an already registered post types.
*/
add_action( 'init', 'proud_core_rest_post_support', 25 );
function proud_core_rest_post_support() {
  global $wp_post_types;
  $types = array('event', 'question', 'job_listing');
  foreach ($types as $post_type_name) {
    if( isset( $wp_post_types[ $post_type_name ] ) ) {
        $wp_post_types[$post_type_name]->show_in_rest = true;
        $wp_post_types[$post_type_name]->rest_base = $post_type_name.'s';
        $wp_post_types[$post_type_name]->rest_controller_class = 'WP_REST_Posts_Controller';
    }
  }
}



/**
* Add REST API support to an already registered taxonomy.
*/
add_action( 'init', 'proud_core_rest_taxonomy_support', 25 );
function proud_core_rest_taxonomy_support() {
  global $wp_taxonomies;
  $taxonomy_names = array('event-categories', 'event-tags', 'faq-topic', 'faq-tags', 'job_listing_type');
  foreach ($taxonomy_names as $taxonomy_name) {
    if ( isset( $wp_taxonomies[ $taxonomy_name ] ) ) {
        $wp_taxonomies[ $taxonomy_name ]->show_in_rest = true;
        $wp_taxonomies[ $taxonomy_name ]->rest_base = $taxonomy_name;
        $wp_taxonomies[ $taxonomy_name ]->rest_controller_class = 'WP_REST_Terms_Controller';
    }
  }
}