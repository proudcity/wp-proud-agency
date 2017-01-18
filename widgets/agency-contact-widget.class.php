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
    $instance['hours'] = get_post_meta( $id, 'hours', true );
    $instance['social'] = [];
    foreach ( Proud\Agency\agency_social_services() as $service => $label ) {
      $url = esc_html( get_post_meta( $id, 'social_'.$service, true ) );
      if ( !empty( $url ) ) {
          $instance['social'][$service] = $url;
      }
    }

    return !empty( $instance['name'] )  
        || !empty( $instance['email'] )
        || !empty( $instance['phone'] )
        || !empty( $instance['address'] )
        || !empty( $instance['hours'] )
        || !empty( $instance['social'] );
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
    <div class="agency-contact-widget">

    <?php if($name): ?><div class="row field-contact-name">
      <div class="col-xs-2"><i class="fa fa-user fa-2x text-muted"></i></div>
      <div class="col-xs-10">
        <?php if( !empty($name_link) ): ?>
          <?php print sprintf( '<a href="%s" rel="bookmark">%s</a>', esc_url( $name_link ), esc_html($name) ); ?>
        <?php else: ?>
          <?php print esc_html($name) ?>
        <?php endif; ?>
        <hr/>
      </div>
    </div><?php endif; ?>

    <?php if($phone): ?><div class="row field-contact-phone">
      <div class="col-xs-2"><i class="fa fa-phone fa-2x text-muted"></i></div>
      <div class="col-xs-10">
        <a href="tel:<?php print esc_html($phone) ?>"><?php print esc_html($phone) ?></a>
        <hr/>
      </div>
    </div><?php endif; ?>

    <?php if($fax): ?><div class="row field-contact-fax">
      <div class="col-xs-2"><i class="fa fa-fax fa-2x text-muted"></i></div>
      <div class="col-xs-10">
        <a href="tel:<?php print esc_url($fax) ?>"><?php print esc_html($fax) ?></a> (FAX)
        <hr/>
      </div>
    </div><?php endif; ?>

    <?php if($email): ?><div class="row field-contact-email">
      <div class="col-xs-2"><i class="fa fa-2x text-muted fa-<?php if(filter_var( $email, FILTER_VALIDATE_EMAIL ) ): ?>envelope<?php else: ?>external-link<?php endif; ?>"></i></div>
      <div class="col-xs-10">
        <?php if(filter_var( $email, FILTER_VALIDATE_EMAIL ) ): ?>
          <a href="<?php print esc_url( "mailto:$email" ) ?>"><?php print esc_html( $email ) ?></a>
        <?php else: ?>
          <a href="<?php print esc_url( "$email" ) ?>"><?php print __('Contact us', 'wp-proud-agency') ?></a>
        <?php endif; ?>
        <hr/>
      </div>
    </div><?php endif; ?>

    <?php if($address): ?><div class="row field-contact-address">
      <div class="col-xs-2"><i class="fa fa-map-marker fa-2x text-muted"></i></div>
      <div class="col-xs-10">
        <?php print nl2br(esc_html($address)) ?>
        <hr/>
      </div>
    </div><?php endif; ?>

    <?php if($hours): ?><div class="row field-contact-hours">
      <div class="col-xs-2"><i class="fa fa-clock-o fa-2x text-muted"></i></div>
      <div class="col-xs-10">
        <?php print nl2br(esc_html($hours)) ?>
        <hr/>
      </div>
    </div><?php endif; ?>

    <?php if( !empty( $instance['social'] ) ): ?><div class="row field-contact-social">
      <div class="col-xs-2"><i class="fa fa-share-alt fa-2x text-muted"></i></div>
      <div class="col-xs-10">
        <ul class="list-inline">
        <?php foreach ($instance['social'] as $service => $url): ?>
          <li>
            <a href="<?php print $url; ?>" title="<?php print ucfirst($service); ?>" target="_blank" class="fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-<?php print $service; ?> fa-stack-1x fa-inverse"></i></a>
          </li>
        <?php endforeach; ?>
        </ul>
      </div>
    </div><?php endif; ?>

    <style>
      .agency-contact-widget hr {
        margin: 16px 0;
      }
    </style>

    <?php
  }
}

// register Foo_Widget widget
function register_agency_contact_widget() {
  register_widget( 'AgencyContact' );
}
add_action( 'widgets_init', 'register_agency_contact_widget' );