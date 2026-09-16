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
    $instance['name_title'] = get_post_meta( $id, 'name_title', true );
    $instance['name_link'] = get_post_meta( $id, 'name_link', true );
    // filter_var(FILTER_VALIDATE_EMAIL) accepts RFC quoted local parts, so a
    // payload like '"><img/src/onerror=alert(1)>"@a.com' validates as an email
    // and used to be concatenated into a mailto: and printed unescaped.
    // sanitize_email() strips the characters that make that work, and is_email()
    // rejects what is left if it is no longer an address, so a payload falls
    // through to the esc_url() branch instead.
    $email = sanitize_email( $instance['name_link'] );
    $instance['name_link'] = ( !empty( $email ) && is_email( $email ) )
        ? esc_url( 'mailto:' . $email )
        : esc_url( $instance['name_link'] );
    $instance['email'] = get_post_meta( $id, 'email', true );
    $instance['phone'] = get_post_meta( $id, 'phone', true );
    $instance['fax'] = get_post_meta( $id, 'fax', true );
    $instance['sms'] = get_post_meta( $id, 'sms', true );
    $instance['address'] = get_post_meta( $id, 'address', true );
    $instance['hours'] = get_post_meta( $id, 'hours', true );
    // Writes both the flat social_<service> keys the shared template scans for
    // and the nested 'social' array the emptiness check below reads.
    $instance = array_merge( $instance, Proud\Agency\agency_social_meta( $id ) );

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
    include(plugin_dir_path( __FILE__ ) . 'templates/agency-contact.php');
  }
}

// register Foo_Widget widget
function register_agency_contact_widget() {
  register_widget( 'AgencyContact' );
}
add_action( 'widgets_init', 'register_agency_contact_widget' );