<?php

use Proud\Core;

class CustomContact extends Core\ProudWidget {

  function __construct() {
    parent::__construct(
      'custom_contact', // Base ID
      __( 'Custom contact block', 'wp-agency' ), // Name
      array( 'description' => __( "Display custom contact information in a sidebar", 'wp-agency' ), ) // Args
    );
  }


  /**
   * Define shortcode settings.
   *
   * @return  void
   */
  function initialize() {
    $this->settings += Proud\Agency\AgencyContact::get_fields(false);
    $this->settings['social_title'] = array(
      '#type' => 'html',
      '#html' => '<h3>Social Media Networks</h3>'
    );
    $this->settings += Proud\Agency\AgencySocial::get_fields(false);
  }

  // This is required by AgencySocial::set_fields()
  public function agency_social_services() {
    return Proud\Agency\agency_social_services();
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

    foreach ( Proud\Agency\agency_social_services() as $service => $label ) {
      $url = esc_html( $instance['social_'.$service] );
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
    include(plugin_dir_path( __FILE__ ) . 'templates/agency-contact.php');
  }

}

// register Foo_Widget widget
function register_custom_contact_widget() {
  register_widget( 'CustomContact' );
}
add_action( 'widgets_init', 'register_custom_contact_widget' );