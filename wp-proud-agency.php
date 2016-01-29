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

// We need the pagebuilder file for the default pagebuilder layout
// @todo: Make this WORK!!
// @todo: dont make this required, gracefully degrade
//require_once( plugin_dir_path(__FILE__) . '../wp-proud-core/modules/so-pagebuilder/proud-so-pagebuilder.php' );

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
    // @todo fix this???
    // add_action( 'save_post', 'my_project_updated_send_email' );
    add_filter( 'template_include', array($this, 'agency_template') );
    //add_filter( 'wp_insert_post_data' , array($this, 'add_agency_pagebulder_code') , -10, 2 );
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
          'rewrite'            => array( 'slug' => _x( 'agencies', 'slug', 'wp-agency' ) ),
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
        var agency_panels = '<?php echo $this->agency_pagebuilder_code(); ?>';
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
            activatePagebuilder();
          }
          else if (type =='page') {
            activatePagebuilder();
          }
        }
        function activatePagebuilder(){
          console.log(agency_panels);
          jQuery('input[name="panels_data"]').val(agency_panels);
          jQuery('#content-panels').trigger('click');
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
        $field = 'social_'.$service;
        $old = get_post_meta( $id, $field, true );
        $new = $_POST['agency_social_' . $service];
        if( !is_null( $old ) ){
          if ( is_null( $new ) ){
            delete_post_meta( $id, $field );
          } else {
            update_post_meta( $id, $field, $new, $old );
          }
        } elseif ( !is_null( $new ) ){
          add_post_meta( $id, $field, $new, true );
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
          $old = get_post_meta( $id, $field, true );
          $new = $_POST['agency_' . $field] ;
          if( !is_null( $old ) ){
            if ( is_null( $new ) ){
              delete_post_meta( $id, $field );
            } else {
              update_post_meta( $id, $field, $new, $old );
            }
          } elseif ( !is_null( $new ) ){
            add_post_meta( $id, $field, $new, true );
          }
      }
    }
  }

  /**
   * Adds the Woo Rockets code if none is set and this is a section.
   */
  /*public function add_agency_pagebulder_code( $data , $postarr ) {
    // Add default styles + content ?
    if ( ('section' === $postarr['agency_type'] || 'page' === $postarr['agency_type'])&& !empty($postarr['ID']) && empty($data['post_content'])) {
      // @todo: get this from proud-so-pagebuilder.php (proud-core)
      $code = $this->agency_pagebuilder_code();
      update_post_meta( $postarr['ID'], 'panels_data', $code );
      print_r($code);
      print_r($postarr['ID']);
    }
    return $data;
  }*/

   /**
   * Adds the Woo Rockets code if none is set and this is a section.
   */
  public function delete_agency_menu( $post_id ) {
    $menu = get_post_meta( $post_id, 'post_menu' );
    wp_delete_nav_menu( $menu );
  }

  // @todo: get this from proud-so-pagebuilder.php (proud-core)
  private function agency_pagebuilder_code() {
    $code = array(
      'name' => __('Agency home page', 'proud'),    
      'description' => __('Agency header and sidebar with contact info', 'proud'),    // Optional
      'widgets' => 
      array (
        0 => 
        array (
          'text' => '<h1>[title]</h1>',
          'headertype' => 'header',
          'background' => 'image',
          'pattern' => '',
          'repeat' => 'full',
          'image' => '[featured-image]',
          'make_inverse' => 'yes',
          'panels_info' => 
          array (
            'class' => 'JumbotronHeader',
            'grid' => 0,
            'cell' => 0,
            'id' => 0,
          ),
        ),
        1 => 
        array (
          'title' => '',
          'panels_info' => 
          array (
            'class' => 'AgencyMenu',
            'raw' => false,
            'grid' => 1,
            'cell' => 0,
            'id' => 1,
          ),
        ),
        2 => 
        array (
          'title' => '',
          'panels_info' => 
          array (
            'class' => 'AgencySocial',
            'raw' => false,
            'grid' => 1,
            'cell' => 0,
            'id' => 2,
          ),
        ),
        3 => 
        array (
          'title' => 'Contact',
          'panels_info' => 
          array (
            'class' => 'AgencyContact',
            'raw' => false,
            'grid' => 1,
            'cell' => 0,
            'id' => 3,
          ),
        ),
        4 => 
        array (
          'title' => 'Hours',
          'panels_info' => 
          array (
            'class' => 'AgencyHours',
            'raw' => false,
            'grid' => 1,
            'cell' => 0,
            'id' => 4,
          ),
        ),
        5 => 
          array (
            'title' => '',
            'text' => '',
            'text_selected_editor' => 'tinymce',
            'autop' => true,
            '_sow_form_id' => '56ab38067a600',
            'panels_info' => 
            array (
              'class' => 'SiteOrigin_Widget_Editor_Widget',
              'grid' => 1,
              'cell' => 1,
              'id' => 5,
              'style' => 
              array (
                'background_image_attachment' => false,
                'background_display' => 'tile',
              ),
            ),
          ),
      ),
      'grids' => 
      array (
        0 => 
        array (
          'cells' => 1,
          'style' => 
          array (
            'row_stretch' => 'full',
            'background_display' => 'tile',
          ),
        ),
        1 => 
        array (
          'cells' => 2,
          'style' => 
          array (
          ),
        ),
      ),
      'grid_cells' => 
      array (
        0 => 
        array (
          'grid' => 0,
          'weight' => 1,
        ),
        1 => 
        array (
          'grid' => 1,
          'weight' => 0.33345145287029998,
        ),
        2 => 
        array (
          'grid' => 1,
          'weight' => 0.66654854712970002,
        ),
      ),
    );
    
    return json_encode($code);
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

/**
 * Returns the list of social fields (also sued in agency-social-links-widget.php)
 */
function agency_social_services() {
  return array(
    'facebook' => 'http://facebook.com/pages/',
    'twitter' => 'http://twitter.com/',
    'instagram' => 'http://instagram.com/',
    'youtube' => 'http://youtube.com/',
    'rss' => 'Enter url to RSS news feed',
    'ical' => 'Enter url to iCal calendar feed',
  );
}