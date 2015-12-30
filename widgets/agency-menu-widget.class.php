<?php

use Proud\Core;

class AgencyMenu extends Core\ProudWidget {

  function __construct() {
    parent::__construct(
      'agency_menu', // Base ID
      __( 'Agency menu', 'wp-agency' ), // Name
      array( 'description' => __( "Display an agency menu", 'wp-agency' ), ) // Args
    );
  }

  function initialize() {
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function printWidget( $args, $instance ) {
      if ('agency' === get_post_type()) {
          if ( $menu = get_post_meta( get_the_ID(), 'agency_menu', true ) ) {
              echo wp_nav_menu( array('menu' => $menu) );
          }
      }
  }
}

// register Foo_Widget widget
function register_agency_menu_widget() {
    register_widget( 'AgencyMenu' );
}
add_action( 'widgets_init', 'register_agency_menu_widget' );