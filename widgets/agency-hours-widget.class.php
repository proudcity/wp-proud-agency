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
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function printWidget( $args, $instance ) {
    ?>
    <div class="field-hours"><?php print esc_html( get_post_meta( get_the_ID(), 'hours', true ) ) ?></div>
    <?php
  }
}

// register Foo_Widget widget
function register_agency_hours_widget() {
  register_widget( 'AgencyHours' );
}
add_action( 'widgets_init', 'register_agency_hours_widget' );