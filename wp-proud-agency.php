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

// @todo: use CMB2: https://github.com/WebDevStudios/CMB2 or https://github.com/humanmade/Custom-Meta-Boxes

namespace Proud\Agency;

// Load Extendible
// -----------------------
if ( ! class_exists( 'ProudPlugin' ) ) {
  require_once( plugin_dir_path(__FILE__) . '../wp-proud-core/proud-plugin.class.php' );
}

class Agency extends \ProudPlugin {


  public function __construct() {
    parent::__construct( array(
      'textdomain'     => 'wp-proud-agency',
      'plugin_path'    => __FILE__,
    ) );

    $this->hook( 'init', 'create_agency' );
    $this->hook( 'admin_init', 'agency_admin' );
    $this->hook( 'plugins_loaded', 'agency_init_widgets' );
    $this->hook( 'save_post', 'add_agency_section_fields', 10, 2 );
    $this->hook( 'save_post', 'add_agency_social_fields', 10, 2 );
    $this->hook( 'save_post', 'add_agency_contact_fields', 10, 2 );
    $this->hook( 'rest_api_init', 'agency_rest_support' );
    $this->hook( 'before_delete_post', 'delete_agency_menu' );

    add_filter( 'template_include', array($this, 'agency_template') );
    add_filter( 'wp_insert_post_data' , array($this, 'add_agency_wr_code') , -10, 2 );

  }

  // Init on plugins loaded
  function agency_init_widgets() {
    require_once plugin_dir_path(__FILE__) . '/widgets/agency-contact-widget.class.php';
    require_once plugin_dir_path(__FILE__) . '/widgets/agency-hours-widget.class.php';
    require_once plugin_dir_path(__FILE__) . '/widgets/agency-social-links-widget.class.php';
    require_once plugin_dir_path(__FILE__) . '/widgets/agency-menu-widget.class.php';
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
          /*elseif ( is_archive() ) {
              if ( $theme_file = locate_template( array ( 'loop-agency.php' ) ) ) {
                  $template_path = $theme_file;
              } else {
                  $template_path = plugin_dir_path( __FILE__ ) . '/archive-agency.php';
              }
          }*/
      }
      return $template_path;
  }

  public function create_agency() {
      $labels = array(
          'name'               => _x( 'Agencies', 'post name', 'wp-agency' ),
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
          'has_archive'        => false,
          'hierarchical'       => false,
          'menu_position'      => null,
          'show_in_rest'       => true,
          'rest_base'          => 'agencies',
          'rest_controller_class' => 'WP_REST_Posts_Controller',
          'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt')
      );

      register_post_type( 'agency', $args );
  }

  public function agency_admin() {
    add_meta_box( 'agency_section_meta_box',
      'Agency type',
      array($this, 'display_agency_section_meta_box'),
      'agency', 'normal', 'high'
    );
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
    
    // @todo: see if we can move the editor below the fields (at least agency type?)
    // See: https://wordpress.org/support/topic/move-custom-meta-box-above-editor
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
      'name' => 'Contact name',
      'email' => 'Contact email',
      'phone' => '123-456-7890',
      'address' => 'Physical address',
      'hours' => "Sunday: Closed\r\nMonday: 9:30am - 9:00pm\r\nTuesday: 9:00am - 5:00pm",
    );
  }


  public function display_agency_social_meta_box( $agency ) {
    foreach ($this->agency_social_services() as $service => $label) {
      $value = esc_html( get_post_meta( $agency->ID, 'social_'.$service, true ) );
      ?>
      <div class="field-group">
        <label><?php print ucfirst($service); ?>:</label>
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
        <label><?php print ucfirst($key); ?>:</label>
        <?php if ($key == 'hours') { ?>
          <textarea rows="5" name="agency_<?php print $key; ?>" placeholder="<?php print $label; ?>"><?php print $value; ?></textarea><br/>
          <div class="description">Format: Monday: 9:30am - 9:00pm</div>
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
   * Displays the Agency Type metadata fieldset.
   */
  public function display_agency_section_meta_box( $agency ) {
    //if ( !empty($agency->post_title) && empty($agency->post_content) ) {
    //  $agency->post_content = $this->agency_wr_code( $data['post_title'], get_the_post_thumbnail_url($post_id) );
    //  update_post_meta( $agency->ID, '_wr_page_builder_content', $data['post_content'] );
    //  update_post_meta( $agency->ID, '_wr_page_active_tab', 1 );
    //  update_post_meta( $agency->ID, '_wr_deactivate_pb', 0 );
    //}
    //update_post_meta( $agency->ID, '_wr_page_active_tab', 1 );

    $menus = get_registered_nav_menus();

    $menus = get_terms( 'nav_menu', array( 'hide_empty' => false ) );
    global $menuArray;
    $menuArray = array(
      '' => 'No menu',
      'new' => 'Create new menu',
    );
    foreach ( $menus as $menu ) {
      $menuArray[$menu->slug] = $menu->name;
    }
    $type = get_post_meta( $agency->ID, 'agency_type', true );
    //$type = $type ? $type : 'page';
    $menu = get_post_meta( $agency->ID, 'post_menu', true );
    $menu = $menu ? $menu : 'new';

    $isNew = empty($agency->post_title) ? 1 : 0;
    ?>
      <label style="margin-top: .5em;">Agency type:</label>
      <div class="checkboxes">
        <div><label><input type="radio" name="agency_type" class="agency_type" value="page" <?php if('page' === $type) { echo 'checked="checked"'; } ?>/>Single page</label></div>
        <div><label><input type="radio" name="agency_type" class="agency_type" value="external" <?php if('external' === $type) { echo 'checked="checked"'; } ?>/>External link</label></div>
        <div><label><input type="radio" name="agency_type" class="agency_type" value="section" <?php if('section' === $type) { echo 'checked="checked"'; } ?>/>Section</label></div>
      </div>

      <div id="agency_url_wrapper" class="field-group">
        <label>Url:</label>
        <input type="text" name="agency_url" placeholder="Enter the full URL to an existing site" value="<?php echo esc_url( get_post_meta( $agency->ID, 'url', true ) ) ?>" />
      </div>

      <div id="post_menu_wrapper">
        <label>Menu: </label>
        <select name="post_menu">
          <?php foreach($menuArray as $key => $item) { ?>
            <option value="<?php echo $key ?>" <?php if ($key === $menu) { echo 'selected="selected"'; } ?>><?php echo $item ?></option>
          <?php } ?>
        </select>
      </div>

      <script>
        var isNewPost=<?php echo $isNew ?>;
        jQuery('#agency_section_meta_box').appendTo('#titlediv').css('margin-top', '1em');
        function changeType() {
          jQuery('#agency_url_wrapper, #post_menu_wrapper, #wr_editor_tabs, .wr-editor-tab-content').hide();
          //if (isNewPost) {
          //  window.setTimeout(function(){jQuery('#wr_editor_tabs a[href="#wr_editor_tab2"]').trigger('click');}, 1000);
          //}
          var type = jQuery('input.agency_type:checked').val();
          if (type == 'external') {
            jQuery('#agency_url_wrapper').show();
          }
          else if (type =='section') {
            jQuery('#post_menu_wrapper').show();
            activateWR();
          }
          else if (type =='page') {
            activateWR();
          }
        }
        function activateWR(){
          
          if (!isNewPost) {
            jQuery('#wr_editor_tabs, .wr-editor-tab-content').show();
          }
        }
        changeType();
        jQuery('.agency_type').bind('click', changeType);
      </script>
    <?php
  }

  /**
   * Saves social metadata fields and saves/creates the menu
   */
  public function add_agency_section_fields( $id, $agency ) {
    if ( $agency->post_type == 'agency' ) {
      $type = $_POST['agency_type'];
      update_post_meta( $id, 'agency_type', $type );
      if ('external' === $type) {
        update_post_meta( $id, 'url', esc_url($_POST['agency_url'] ));
      }
      else if ('section' === $type) {
        $menu = $_POST['post_menu'];
        if ('new' === $menu) {
          $menuId = wp_create_nav_menu($agency->post_title);
          $objMenu = get_term_by( 'id', $menuId, 'nav_menu');
          $menu = $objMenu->slug;
        }
        if (!is_array($menu)) {
          update_post_meta( $id, 'post_menu', $menu );
        }
      }
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
   * Adds the Woo Rockets code if none is set and this is a section.
   */
  public function add_agency_wr_code( $data , $postarr ) {
    if ('section' === $_POST['agency_type'] && empty($data['post_content'])) {
      $data['post_content'] = $this->agency_wr_code($data['post_title'], get_the_post_thumbnail_url($data['ID']));
      update_post_meta( $data['ID'], '_wr_page_builder_content', $data['post_content'] );
      update_post_meta( $data['ID'], '_wr_page_active_tab', 1 );
    }
    return $data;
  }

   /**
   * Adds the Woo Rockets code if none is set and this is a section.
   */
  public function delete_agency_menu( $post_id ) {
    $menu = get_post_meta( $post_id, 'post_menu' );
    wp_delete_nav_menu( $menu );
  }

  /**
   * Returns the default Woo Rockets code for agencies
   */
  private function agency_wr_code($title, $image) {
    return '[wr_row width="full" background="none" border_width_value_="0" border_style="solid" border_color="#000" div_padding_top="10" div_padding_left="10" div_padding_bottom="10" div_padding_right="10" ][wr_column span="span12" ][wr_jumbotronheader div_margin_top="0" div_margin_left="0" div_margin_bottom="25" div_margin_right="0" include_title="no" background="image" image="'.$image.'" img_repeat="none" background_size="normal" paralax="no" make_inverse="no" box_background="none" disabled_el="no" ]<h1>'.$title.'</h1>[/wr_jumbotronheader][/wr_column][/wr_row][wr_row width="boxed" background="none" solid_color_value="#FFFFFF" solid_color_color="#ffffff" gradient_color="0% #FFFFFF,100% #000000" gradient_direction="vertical" repeat="full" img_repeat="full" autoplay="yes" position="center center" paralax="no" border_width_value_="0" border_style="solid" border_color="#000" div_padding_top="10" div_padding_bottom="10" div_padding_right="10" div_padding_left="10" ][wr_column span="span4" ][wr_widget widget_id="AgencyMenu"]widget-agency_menu%5B%5D%5Btitle%5D=[/wr_widget][wr_widget widget_id="AgencyContact"]widget-agency_contact%5B%5D%5Btitle%5D=Contact[/wr_widget][wr_widget widget_id="AgencySocial"]widget-agency_social%5B%5D%5Btitle%5D=Connect[/wr_widget][wr_widget widget_id="AgencyHours"]widget-agency_hours%5B%5D%5Btitle%5D=Hours[/wr_widget][/wr_column][wr_column span="span8" ][wr_text text_margin_top="0" text_margin_bottom="0" enable_dropcap="no" appearing_animation="0" disabled_el="no" ][/wr_text][/wr_column][/wr_row]';
  }

} // class
$Agency = new Agency;


/**
 * Gets the url for the agency homepage (internal or external)
 */
function get_agency_permalink($post = 0) {
  $post = $post > 0 ? $post : get_the_ID();
  $url = get_post_meta( $post, 'url', true );
  if ( !empty($url) ) {
    return esc_html( $url );
  }
  else {
    return esc_url( apply_filters( 'the_permalink', get_permalink( $post ), $post ) );
  }
}