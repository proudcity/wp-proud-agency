<?php

//require_once str_replace('wp-agency/widgets/','wp-proud-core', plugin_dir_path(__FILE__)) . '/modules/proud-widget/widgets/base/widget.class.php';
//use Proud\Core;

class AgencySocial extends \WP_Widget {

  function __construct() {
    parent::__construct(
      'agency_social', // Base ID
      __( 'Agency social media', 'wp-agency' ), // Name
      array( 'description' => __( "Display social media icons", 'wp-agency' ), ) // Args
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
      foreach (agency_social_services() as $service => $label) {
          $url = esc_html( get_post_meta( get_the_ID(), 'social_'.$service, true ) );
          if (!empty($url)) {
              ?>
              <a href="<?php print $url; ?>" title="<?php print ucfirst($service); ?>" target="_blank">
                  <i class="fa fa-<?php print $service; ?>"></i>
              </a> 
              <?php
          }
      }
  }
}

// register Foo_Widget widget
function register_agency_social_widget() {
  register_widget( 'AgencySocial' );
}
add_action( 'widgets_init', 'register_agency_social_widget' );