<?php

use Proud\Core;

class AgencyContact extends Core\ProudWidget {

  function __construct() {
    parent::__construct(
      'agency_contact', // Base ID
      __( 'Agency contact info', 'wp-agency' ), // Name
      array( 'description' => __( "Display current agency's contact info", 'wp-agency' ), ) // Args
    );
  }

  function initialize() {
  }

  /**
   * Determines if content empty, show widget, title ect?  
   *
   * @see self::widget()
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function hasContent( $args, &$instance ) {
    global $pageInfo;
    $id = get_post_type() === 'agency' ? get_the_ID(): $pageInfo['parent_post'];
    $instance['name'] = get_post_meta( $id, 'name', true );
    $instance['name_title'] = get_post_meta( $id, 'name_title', true );
    $instance['name_link'] = get_post_meta( $id, 'name_link', true );
    $instance['email'] = get_post_meta( $id, 'email', true );
    $instance['phone'] = get_post_meta( $id, 'phone', true );
    $instance['fax'] = get_post_meta( $id, 'fax', true );
    $instance['address'] = get_post_meta( $id, 'address', true );
    $instance['hours'] = get_post_meta( $id, 'hours', true );
    $instance['social'] = [];
    foreach ( Proud\Agency\agency_social_services() as $service => $label ) {
      $url = esc_html( get_post_meta( $id, 'social_'.$service, true ) );
      if ( !empty( $url ) ) {
          $instance['social'][$service] = $url;
      }
    }

    return !empty( $instance['name'] )  
        || !empty( $instance['email'] )
        || !empty( $instance['phone'] )
        || !empty( $instance['address'] )
        || !empty( $instance['hours'] )
        || !empty( $instance['social'] );
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function printWidget( $args, $instance ) {
    extract( $instance );
    include(plugin_dir_path( __FILE__ ) . 'templates/agency-contact.php');
  }
}

// register Foo_Widget widget
function register_agency_contact_widget() {
  register_widget( 'AgencyContact' );
}
add_action( 'widgets_init', 'register_agency_contact_widget' );