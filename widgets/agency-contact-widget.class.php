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
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function printWidget( $args, $instance ) {
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