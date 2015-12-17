<?php
/*
Plugin Name: Proud Agency
Plugin URI: http://proudcity.com/
Description: Declares an Agency custom post type.
Version: 1.0
Author: ProudCity
Author URI: http://proudcity.com/
License: GPLv2
*/

namespace Proud\Agency;


// Load Extendible
// -----------------------

if ( ! class_exists( 'ProudPlugin' ) ) {
  require_once( plugin_dir_path(__FILE__) . 'proud-plugin.class.php' );
}

class Agency extends \ProudPlugin { {


  public function __construct() {
    parent::__construct( array(
      'textdomain'     => 'wp-proud-agency',
      'plugin_path'    => __FILE__,
    ) );

    $this->hook( 'init', 'create_agency' );
    $this->hook( 'admin_init', 'agency_admin' );
    $this->hook( 'plugins_loaded', 'agency_init_widgets' );
    $this->hook( 'save_post', 'add_agency_social_fields', 10, 2 );
    $this->hook( 'save_post', 'add_agency_contact_fields', 10, 2 );
    $this->hook( 'rest_api_init', 'agency_rest_support' );
    add_filter( 'template_include', array($this, 'agency_template') );
  }

  // Init on plugins loaded
  function agency_init_widgets() {
    require_once plugin_dir_path(__FILE__) . '/widgets/agency-contact-widget.class.php';
    require_once plugin_dir_path(__FILE__) . '/widgets/agency-hours-widget.class.php';
    require_once plugin_dir_path(__FILE__) . '/widgets/agency-social-links-widget.class.php';
  }

  public function agency_template( $template_path ) {
      if ( get_post_type() == 'agency' ) {
          if ( is_single() ) {
              // We use the default post template here since we're just going to override it with Page Builder
              /*if ( $theme_file = locate_template( array ( 'content-agency.php' ) ) ) {
                  $template_path = $theme_file;
              } else {
                  $template_path = plugin_dir_path( __FILE__ ) . '/single-agency.php';
              }*/
          }
          elseif ( is_archive() ) {
              if ( $theme_file = locate_template( array ( 'loop-agency.php' ) ) ) {
                  $template_path = $theme_file;
              } else {
                  $template_path = plugin_dir_path( __FILE__ ) . '/archive-agency.php';
              }
          }
      }
      return $template_path;
  }


  public function create_agency() {
      $labels = array(
          'singular_name'      => _x( 'Agency', 'post type singular name', 'wp-agency' ),
          'menu_name'          => _x( 'Agencies', 'admin menu', 'wp-agency' ),
          'name_admin_bar'     => _x( 'Agency', 'add new on admin bar', 'wp-agency' ),
          'add_new'            => _x( 'Add New', 'agency', 'wp-agency' ),
          'add_new_item'       => __( 'Add New Agency', 'wp-agency' ),
          'new_item'           => __( 'New Agency', 'wp-agency' ),
          'edit_item'          => __( 'Edit Agency', 'wp-agency' ),
          'view_item'          => __( 'View Agency', 'wp-agency' ),
          'all_items'          => __( 'All agencies', 'wp-agency' ),
          'search_items'       => __( 'Search agency', 'wp-agency' ),
          'parent_item_colon'  => __( 'Parent agency:', 'wp-agency' ),
          'not_found'          => __( 'No agencies found.', 'wp-agency' ),
          'not_found_in_trash' => __( 'No agencies found in Trash.', 'wp-agency' )
      );

      $args = array(
          'labels'             => $labels,
          'description'        => __( 'Description.', 'wp-agency' ),
          'public'             => true,
          'publicly_queryable' => true,
          'show_ui'            => true,
          'show_in_menu'       => true,
          'query_var'          => true,
          'rewrite'            => array( 'slug' => 'agencies' ),
          'capability_type'    => 'post',
          'has_archive'        => true,
          'hierarchical'       => false,
          'menu_position'      => null,
          'show_in_rest'       => true,
          'rest_base'          => 'agency',
          'rest_controller_class' => 'WP_REST_Posts_Controller',
          'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt')
      );

      register_post_type( 'agency', $args );
  }

  public function agency_admin() {
    add_meta_box( 'agency_social_meta_box',
      'Social Media Accounts',
      array($this, 'display_agency_social_meta_box'),
      'agency', 'normal', 'high'
    );
    add_meta_box( 'agency_contact_meta_box',
      'Contact information',
      array($this, 'display_agency_contact_meta_box'),
      'agency', 'normal', 'high'
    );
  }

  public function agency_rest_support() {
    register_api_field( 'agency',
          'meta',
          array(
              'get_callback'    => 'agency_rest_metadata',
              'update_callback' => null,
              'schema'          => null,
          )
      );
  }

  /**
   * Alter the REST endpoint.
   * Add metadata to the post response
   */
  public function agency_rest_metadata( $object, $field_name, $request ) {
      $return = array('social' => array());
      foreach ($this->agency_social_services() as $key => $label) {
        if ($value = get_post_meta( $object[ 'id' ], 'social_'.$key, true )) {
          $return['social'][$key] = $value;
        }
      }
      foreach ($this->agency_contact_fields() as $key => $label) {
        if ($value = get_post_meta( $object[ 'id' ], $key, true )) {
          $return[$key] = $value;
        }
      }
      return $return;
  }

  public function agency_social_services() {
    return array(
      'facebook' => 'http://facebook.com/pages/',
      'twitter' => 'http://twitter.com/',
      'instagram' => 'http://instagram.com/',
      'youtube' => 'http://youtube.com/',
      'rss' => 'Enter url to RSS news feed',
      'ical' => 'Enter url to iCal calendar feed',
    );
  }

  public function agency_contact_fields() {
    return array(
      'url' => 'Enter the full URL to an existing site',
      'name' => 'Contact name',
      'email' => 'Contact email',
      'phone' => 'Phone number',
      'address' => 'Physical address',
      'hours' => "Sunday: Closed\r\nMonday: 9:30am - 9:00pm\r\nTuesday: 9:00am - 5:00pm",
    );
  }


  public function display_agency_social_meta_box( $agency ) {
    foreach ($this->agency_social_services() as $service => $label) {
      $value = esc_html( get_post_meta( $agency->ID, 'social_'.$service, true ) );
      ?>
      <div class="field-group">
        <label><?php print ucfirst($service); ?></label>
        <input type="textfield" name="agency_social_<?php print $service; ?>" value="<?php print $value; ?>" placeholder="<?php print $label; ?>" />
      </div>
      <?php
    }
  }

  public function display_agency_contact_meta_box( $agency ) {
    foreach ($this->agency_contact_fields() as $key => $label) {
      $value = esc_html( get_post_meta( $agency->ID, $key, true ) );
      ?>
      <div class="field-group">
        <label><?php print ucfirst($key); ?></label>
        <?php if ($key == 'hours') { ?>
          <textarea rows="5" name="agency_<?php print $key; ?>" placeholder="<?php print $label; ?>"><?php print $value; ?></textarea><br/>
          Format: Monday: 9:30am - 9:00pm
        <?php } elseif ('address' == $key) { ?>
          <textarea rows="2" name="agency_<?php print $key; ?>" placeholder="<?php print $label; ?>"><?php print $value; ?></textarea><br/>
        <?php } else { ?>
          <input type="textfield" name="agency_<?php print $key; ?>" value="<?php print $value; ?>" placeholder="<?php print $label; ?>" />
        <?php } ?>
      </div>
      <?php
    }
  }

  /**
   * Saves social metadata fields 
   */
  public function add_agency_social_fields( $id, $agency ) {
    if ( $agency->post_type == 'agency' ) {
      foreach ($this->agency_social_services() as $service => $label) {
        if ( !empty( $_POST['agency_social_'.$service] ) ) {
          update_post_meta( $id, 'social_'.$service, $_POST['agency_social_'.$service] );
        }
      }
    }
  }

  /**
   * Saves contact metadata fields 
   */
  public function add_agency_contact_fields( $id, $agency ) {
    if ( $agency->post_type == 'agency' ) {
      foreach ($this->agency_contact_fields() as $field => $label) {
        //if ( !empty( $_POST['agency_'.$field] ) ) {  // @todo: check if it has been set already to allow clearing of value
          update_post_meta( $id, $field, $_POST['agency_'.$field] );
        //}
      }

    }
  }

  /**
   * Gets the url for the agency homepage (internal or external)
   */
  public function the_agency_permalink($post = 0) {
    $url = get_post_meta( get_the_ID(), 'url', true );
    if ( !empty($url) ) {
      echo esc_html( $url );
    }
    else {
      echo esc_url( apply_filters( 'the_permalink', get_permalink( $post ), $post ) );
    }
  }                   

  /**
   * Gets the url for the agency homepage (internal or external)
   */
  public function the_agency_social($post = 0) {
    $url = get_post_meta( get_the_ID(), 'url', true );
    if ( !empty($url) ) {
      echo esc_url( $url );
    }
    else {
      echo esc_url( apply_filters( 'the_permalink', get_permalink( $post ), $post ) );
    }
  }                   



} // class


new Agency;