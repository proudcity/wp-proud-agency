<?php

use Proud\Core;

class AgencyHours extends Core\ProudWidget {

  function __construct() {
    parent::__construct(
      'agency_hours', // Base ID
      __( 'Agency hours', 'wp-agency' ), // Name
      array( 'description' => __( "Display the agency's weekly hours", 'wp-agency' ), ) // Args
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
    return false;
    global $pageInfo;
    $id = get_post_type() === 'agency' ? get_the_ID(): $pageInfo['parent_post'];
    // Load hours
    $instance['hours'] = get_post_meta( $id, 'hours', true );
    return !empty( $instance['hours'] );
  }
    

  /**
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function printWidget( $args, $instance ) {
    ?>
    <div class="row field-hours">
      <div class="col-xs-2"><i aria-hidden="true" class="fa fa-clock-o fa-2x text-muted"></i></div>
      <div class="col-xs-10"><?php print nl2br(esc_html($instance['hours'])); ?></div>
    </div>
    <?php
  }
}

// register Foo_Widget widget
function register_agency_hours_widget() {
  register_widget( 'AgencyHours' );
}
add_action( 'widgets_init', 'register_agency_hours_widget' );