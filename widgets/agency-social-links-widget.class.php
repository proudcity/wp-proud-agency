<?php

use Proud\Core;
use Agency;

class AgencySocial extends Core\ProudWidget {

  function __construct() {
    parent::__construct(
      'agency_social', // Base ID
      __( 'Agency social media', 'wp-agency' ), // Name
      array( 'description' => __( "Display social media icons", 'wp-agency' ), ) // Args
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
      // @todo: use Agency->agency_social_services() to get this
      $agencies = array(
          'facebook' => 'http://facebook.com/pages/',
          'twitter' => 'http://twitter.com/',
          'instagram' => 'http://instagram.com/',
          'youtube' => 'http://youtube.com/',
          'rss' => 'Enter url to RSS news feed',
          'ical' => 'Enter url to iCal calendar feed',
      );
      foreach ($agencies as $service => $label) {
          $url = esc_html( get_post_meta( get_the_ID(), 'social_'.$service, true ) );
          if (!empty($url)) {
              ?><a href="<?php print $url; ?>" title="<?php print ucfirst($service); ?>" target="_blank" class="fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-<?php print $service; ?> fa-stack-1x fa-inverse"></i></a><?php
          }
      }
  }
}

// register Foo_Widget widget
function register_agency_social_widget() {
  register_widget( 'AgencySocial' );
}
add_action( 'widgets_init', 'register_agency_social_widget' );