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
    $instance['name_link'] = get_post_meta( $id, 'name_link', true );
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
   *https://github.com/proudcity/wp-proudcity/issues/545
   * @param array $args
   * @param array $instance
   */
  public function printWidget( $args, $instance ) {
    extract( $instance );
    ?>
    <?php if($name): ?><p class="field-contact-name">
      <?php if( !empty($name_link) ): ?>
        <?php print sprintf( '<a href="%s" rel="bookmark">%s</a>', esc_url( $name_link ), esc_html($name) ); ?>
      <?php else: ?>
        <?php print esc_html($name) ?>
      <?php endif; ?>
    </p><?php endif; ?>
    <?php if($phone || $email): ?><p>
      <?php if($phone): ?><div class="field-contact-phone"><a href="tel:<?php print esc_url($phone) ?>"><i class="fa fa-fw fa-phone"></i><?php print esc_html($phone) ?></a></div><?php endif; ?>
      <?php if($fax): ?><div class="field-contact-fax"><a href="tel:<?php print esc_url($fax) ?>"><i class="fa fa-fw fa-fax"></i><?php print esc_html($fax) ?></a></div><?php endif; ?>
      <?php if($email): ?>
        <?php if(filter_var( $email, FILTER_VALIDATE_EMAIL ) ): ?>
          <div class="field-contact-email"><a href="<?php print esc_url( "mailto:$email" ) ?>"><i class="fa fa-fw fa-envelope"></i><?php print esc_html( $email ) ?></a></div>
        <?php else: ?>
            <div class="field-contact-email"><a href="<?php print esc_url( "$email" ) ?>"><i class="fa fa-fw fa-external-link"></i><?php print __('Contact us', 'wp-proud-agency') ?></a></div>
        <?php endif; ?>
      <?php endif; ?>
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