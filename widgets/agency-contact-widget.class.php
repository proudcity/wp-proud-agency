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
    // Load hours
    $instance['name'] = get_post_meta( get_the_ID(), 'name', true );
    $instance['email'] = get_post_meta( get_the_ID(), 'email', true );
    $instance['phone'] = get_post_meta( get_the_ID(), 'phone', true );
    $instance['address'] = get_post_meta( get_the_ID(), 'address', true );
    return !empty( $instance['name'] )  
        || !empty( $instance['email'] )
        || !empty( $instance['phone'] )
        || !empty( $instance['address'] );
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function printWidget( $args, $instance ) {
    extract( $instance );
    ?>
    <?php if($name): ?><div class="field-contact-name"><?php print esc_html($name) ?></div><?php endif; ?>
    <?php if($phone): ?><div class="field-contact-phone"><?php print esc_html($phone) ?></div><?php endif; ?>
    <?php if($email): ?><p class="field-contact-email"><a href="<?php print esc_url( "mailto:$email" ) ?>"><?php print esc_html( $email ) ?></a></p><?php endif; ?>
    <?php if($address): ?><div class="field-contact-address"><?php print esc_html($address) ?></div><?php endif; ?>
    <?php
  }
}

// register Foo_Widget widget
function register_agency_contact_widget() {
  register_widget( 'AgencyContact' );
}
add_action( 'widgets_init', 'register_agency_contact_widget' );