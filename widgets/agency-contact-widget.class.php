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
    $instance['email'] = get_post_meta( $id, 'email', true );
    $instance['phone'] = get_post_meta( $id, 'phone', true );
    $instance['fax'] = get_post_meta( $id, 'fax', true );
    $instance['address'] = get_post_meta( $id, 'address', true );
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
    <?php if($name): ?><p class="field-contact-name"><?php print esc_html($name) ?></p><?php endif; ?>
    <?php if($phone || $email): ?><p>
      <?php if($phone): ?><div class="field-contact-phone"><a href="tel:<?php print esc_url($phone) ?>"><i class="fa fa-fw fa-phone"></i><?php print esc_html($phone) ?></a></div><?php endif; ?>
      <?php if($fax): ?><div class="field-contact-fax"><a href="tel:<?php print esc_url($fax) ?>"><i class="fa fa-fw fa-fax"></i><?php print esc_html($fax) ?></a></div><?php endif; ?>
      <?php if($email): ?><div class="field-contact-email"><a href="<?php print esc_url( "mailto:$email" ) ?>"><i class="fa fa-fw fa-envelope"></i><?php print esc_html( $email ) ?></a></div><?php endif; ?>
    </p><?php endif; ?>
    <?php if($address): ?><div class="field-contact-address"><?php print nl2br(esc_html($address)) ?></div><?php endif; ?>
    <?php
  }
}

// register Foo_Widget widget
function register_agency_contact_widget() {
  register_widget( 'AgencyContact' );
}
add_action( 'widgets_init', 'register_agency_contact_widget' );