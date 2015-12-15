<?php

//require_once str_replace('wp-agency/widgets/','wp-proud-core', plugin_dir_path(__FILE__)) . '/modules/proud-widget/widgets/base/widget.class.php';
//use Proud\Core;

class AgencyHours extends \WP_Widget {

  function __construct() {
    parent::__construct(
      'agency_hours', // Base ID
      __( 'Agency hours', 'wp-agency' ), // Name
      array( 'description' => __( "Display the agency's weekly hours", 'wp-agency' ), ) // Args
    );
  }

  function initialize() {
    parent::initialize();
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function widget( $args, $instance ) {
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