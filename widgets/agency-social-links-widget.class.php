<?php

use Proud\Core;

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
   * Determines if content empty, show widget, title ect?
   *
   * @see self::widget()
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function hasContent( $args, &$instance ) {
    global $pageInfo;
    $id = get_post_type() === 'agency' ? get_the_ID(): ( $pageInfo['parent_post'] ?? null );
    $instance = array_merge( $instance, Proud\Agency\agency_social_meta( $id ) );
    return !empty( $instance['social'] );
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function printWidget( $args, $instance ) {
    if ( empty( $instance['social'] ) ) {
      return;
    }
    ?>
    <ul class="list-inline">
      <?php foreach ( $instance['social'] as $service => $url ):
        $classes = Proud\Agency\social_icon_classes( $service );
        // Unsupported service, no icon to draw.
        if ( $classes === null ) {
          continue;
        }
        $schemes = Proud\Agency\social_allowed_schemes( $service );
        $scheme  = (string) wp_parse_url( $url, PHP_URL_SCHEME );
        if ( $scheme && !in_array( strtolower( $scheme ), $schemes, true ) ) {
          continue;
        }
        $label = ucfirst( $service );
      ?>
        <li>
          <a href="<?php echo esc_url( $url, $schemes ); ?>"
             title="<?php echo esc_attr( $label ); ?>"
             target="_blank"
             rel="noopener noreferrer">
            <i aria-hidden="true" class="<?php echo esc_attr( $classes ); ?> fa-2x"></i>
            <span class="sr-only"><?php echo esc_html( $label ); ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php
  }
}

// register Foo_Widget widget
function register_agency_social_widget() {
  register_widget( 'AgencySocial' );
}
add_action( 'widgets_init', 'register_agency_social_widget' );
