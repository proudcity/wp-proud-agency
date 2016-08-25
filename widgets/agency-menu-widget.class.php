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
   * Determines if content empty, show widget, title ect?  
   *
   * @see self::widget()
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function hasContent( $args, &$instance ) {
    // always show
    return true;
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function printWidget( $args, $instance ) {
      $args = array(
        'menu_class' => 'nav nav-pills nav-stacked submenu',
        'fallback_cb' => false,
      );
      if ('agency' === get_post_type()) {
          if ( $menu = get_post_meta( get_the_ID(), 'post_menu', true ) ) {
              $instance['menu_class'] = new Core\ProudMenu($menu);
          }
      }
      else {
        global $pageInfo;
        $menu = $pageInfo['menu'];
        $instance['menu_class'] = new Core\ProudMenu($menu);
      }

      if( !empty( $instance['menu_class'] ) ) {
        $instance['menu_class']->print_menu();
      }
  }
}

// register Foo_Widget widget
function register_agency_menu_widget() {
    register_widget( 'AgencyMenu' );
}
add_action( 'widgets_init', 'register_agency_menu_widget' );