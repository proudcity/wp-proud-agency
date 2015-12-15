<?php

//require_once str_replace('wp-agency/widgets/','wp-proud-core', plugin_dir_path(__FILE__)) . '/modules/proud-widget/widgets/base/widget.class.php';
//use Proud\Core;

class AgencyContact extends \WP_Widget {

  function __construct() {
    parent::__construct(
      'agency_contact', // Base ID
      __( 'Agency contact info', 'wp-agency' ), // Name
      array( 'description' => __( "Display current agency's contact info", 'wp-agency' ), ) // Args
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
    $email = get_post_meta( get_the_ID(), 'email', true );
    ?>
    <div class="field-contact-name"><?php print get_post_meta( get_the_ID(), 'name', true ) ?></div>
    <div class="field-contact-email"><a href="<?php print esc_url( "mailto:$email" ) ?>"><?php print esc_html( $email ) ?></a></div>
    <div class="field-address"><?php print esc_html( get_post_meta( get_the_ID(), 'address', true ) ) ?></div>
    <?php
  }
}

// register Foo_Widget widget
function register_agency_contact_widget() {
  register_widget( 'AgencyContact' );
}
add_action( 'widgets_init', 'register_agency_contact_widget' );