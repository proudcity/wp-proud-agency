<?php
/*
Plugin Name: Proud Agency
Plugin URI: http://proudcity.com/
Description: Declares an Agency custom post type.
Version: 1.0
Author: ProudCity
Author URI: http://proudcity.com/
License: Affero GPL v3
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

  static $key = 'agency_edit';

  public function __construct() {
    parent::__construct( array(
      'textdomain'     => 'wp-proud-agency',
      'plugin_path'    => __FILE__,
    ) );
    $this->hook( 'init', 'create_agency' );
    $this->hook( 'admin_enqueue_scripts', 'agency_assets' );
    $this->hook( 'plugins_loaded', 'agency_init_widgets' );
    $this->hook( 'rest_api_init', 'agency_rest_support' );
    $this->hook( 'before_delete_post', 'delete_agency_menu' );
  }

  //add assets
  function agency_assets() {
    $path = plugins_url('assets/',__FILE__);
    wp_enqueue_script('proud-agency/js', $path . 'js/proud-agency.js', ['proud','jquery'], null, true);
  }

  // Init on plugins loaded
  function agency_init_widgets() {
    require_once plugin_dir_path(__FILE__) . '/widgets/agency-contact-widget.class.php';
    require_once plugin_dir_path(__FILE__) . '/widgets/custom-contact-widget.class.php';
    require_once plugin_dir_path(__FILE__) . '/widgets/agency-hours-widget.class.php';
    require_once plugin_dir_path(__FILE__) . '/widgets/agency-social-links-widget.class.php';
    require_once plugin_dir_path(__FILE__) . '/widgets/agency-menu-widget.class.php';
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

  public function agency_rest_support() {
    register_rest_field( 'agency',
          'meta',
          array(
              'get_callback'    => 'agency_rest_metadata',
              'update_callback' => null,
              'schema'          => null,
          )
      );
  }

  /**
   * Delete menu when agency is deleted.
   */
  public function delete_agency_menu( $post_id ) {
    $menu = get_post_meta( $post_id, 'post_menu' );
    wp_delete_nav_menu( $menu );
  }

  /**
   * Alter the REST endpoint.
   * Add metadata to the post response
   */
  public function agency_rest_metadata( $object, $field_name, $request ) {
    $Contact = new AgencyContact;
    $return = $Contact->get_options( $object[ 'id' ] );
    $Social = new AgencySocial;
    $return['social'] = $Social->get_options( $object[ 'id' ] );
    return $return;
  }

} // class
$Agency = new Agency;

// Agency meta box
class AgencySection extends \ProudMetaBox {

  public $options = [  // Meta options, key => default                             
    'agency_type' => 'page',
    'url' => '',
    'post_menu' => 'new',
    'agency_icon' => '',
    'list_exclude' => ''
  ];

  public function __construct() {
    parent::__construct( 
      'agency_section', // key
      'Agency type', // title
      'agency', // screen
      'normal',  // position
      'high' // priority
    );
  }

  /**
   * Called on form creation
   * @param $displaying : false if just building form, true if about to display
   * Use displaying:true to do any difficult loading that should only occur when
   * the form actually will display
   */
  public function set_fields( $displaying ) {

    // Already set
    if( $displaying ) {

      // Build menu options
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
      $this->fields['post_menu']['#options'] = $menuArray;
      return;
    }

    $this->fields = [];  

    $this->fields['agency_type'] = [
      '#type' => 'radios',
      '#title' => __('Type'),
      //'#description' => __('The type of search to fallback on when users don\'t find what they\'re looking for in the autosuggest search and make a full site search.', 'proud-settings'),
      '#options' => array(
        'page' => __('Single page', 'proud'),
        'external' => __('External link', 'proud'),
        'section' => __('Section', 'proud'),
      ),
    ];

    $this->fields['url'] = [
      '#type' => 'text',
      '#title' => __('URL'),
      '#description' => __('Enter the full URL to an existing site'),
      '#states' => [
        'visible' => [
          'agency_type' => [
            'operator' => '==',
            'value' => ['external'],
            'glue' => '||'
          ],
        ],
      ],
    ];

    $this->fields['post_menu'] = [
      '#type' => 'select',
      '#title' => __('Menu'),
      //'#description' => __('Enter the full url to the payment page'),
      '#options' => [],
      '#states' => [
        'visible' => [
          'agency_type' => [
            'operator' => '==',
            'value' => ['section'],
            'glue' => '||'
          ],
        ],
      ],
    ];

    $this->fields['agency_icon'] = [
      '#type' => 'fa-icon',
      '#title' => __('Icon'),
      '#description' => __('If you are using the Icon Button list style, select an icon'),
    ];

    $this->fields['list_exclude'] = [
      '#type' => 'checkbox',
      '#title' => __('Exclude from '. _x( 'Agency', 'post type singular name', 'wp-agency' ) .' Lists'),
      '#description' => __('Checking this box will cause this '. _x( 'Agency', 'post type singular name', 'wp-agency' ) .' to be hidden on the Government page'),
      '#return_value' => '1',
    ];
  }

  /**
   * Displays the Agency Type metadata fieldset.
   */
  public function settings_content( $post ) {
    // Call parent
    parent::settings_content( $post );
    // Add js settings
    global $proudcore;
    $settings = $this->get_field_names( ['agency_type'] ); 
    $settings['isNewPost'] = empty( $post->post_title );
    $settings['agency_panels'] = [
      'section' => agency_pagebuilder_code('section'),
      'page' => agency_pagebuilder_code('page') // @TODO change to page + figure out how to update on click 
    ];
    $proudcore->addJsSettings( [
      'proud_agency' => $settings
    ] );
  }

  /** 
   * Saves form values
   * OVERRIDEN from parent for additional processing
   */
  public function save_meta( $post_id, $post, $update ) {
    $values = $this->validate_values( $post );
    if ( !empty( $values['agency_type'] ) ) {
      $type = $values['agency_type'];
      update_post_meta( $post_id, 'agency_type', $type );
      if ('external' === $type) {
        $url = $values['url'];
        if ( empty($url) ) {
          delete_post_meta( $post_id, 'url');
        }
        else {
          update_post_meta( $post_id, 'url', esc_url( $url ));
        }
      }
      else if ('section' === $type) {
        $menu = $values['post_menu'];
        if ('new' === $menu) {
          $menuId = wp_create_nav_menu( $post->post_title );
          $objMenu = get_term_by( 'id', $menuId, 'nav_menu');
          $menu = $objMenu->slug;
        }
        if (!is_array($menu)) {
          update_post_meta( $post_id, 'post_menu', $menu );
        }
      }

      update_post_meta( $post_id, 'agency_icon', $values['agency_icon'] );
      update_post_meta( $post_id, 'list_exclude', !empty( $values['list_exclude'] ) ? 1 : 0);
    }
  }
}
if( is_admin() )
  new AgencySection;

// Agency contact meta box
class AgencyContact extends \ProudMetaBox {

  public $options = [  // Meta options, key => default                             
    'name' => '',
    'name_title' => '',
    'name_link' => '',
    'email' => '',
    'phone' => '',
    'fax' => '',
    'address' => '',
    'hours' => '',
  ];

  public function __construct() {
    parent::__construct( 
      'agency_contact', // key
      'Contact information', // title
      'agency', // screen
      'normal',  // position
      'high' // priority
    );
  }

  /**
   * Called on form creation
   * @param $displaying : false if just building form, true if about to display
   * Use displaying:true to do any difficult loading that should only occur when
   * the form actually will display
   */
  public function set_fields( $displaying ) {

    // Already set, no loading necessary
    if( $displaying ) {
      return;
    }
    
    $this->fields = self::get_fields();
  }

  public static function get_fields() {
    $fields = [];

    $fields['name'] = [
      '#type' => 'text',
      '#title' => __( 'Contact name' ),
    ];

    $fields['name_title'] = [
      '#type' => 'text',
      '#title' => __( 'Contact name title' ),
      '#description' => __( 'This will appear directly below the Contact name.' ),
      '#states' => [
        'visible' => [
          'name' => [
            'operator' => '!=',
            'value' => [''],
            'glue' => '||'
          ],
        ],
      ],
    ];

    $fields['name_link'] = [
      '#type' => 'text',
      '#title' => __( 'Contact name link' ),
      '#description' => __( 'If you enter a URL in this box, the Contact name above will turn into a link.' ),
      '#states' => [
        'visible' => [
          'name' => [
            'operator' => '!=',
            'value' => [''],
            'glue' => '||'
          ],
        ],
      ],
    ];

    $fields['email'] = [
      '#type' => 'text',
      '#title' => __( 'Contact email or form' ),
    ];

    $fields['phone'] = [
      '#type' => 'text',
      '#title' => __( 'Contact phone' ),
    ];

    $fields['fax'] = [
      '#type' => 'text',
      '#title' => __( 'Contact FAX' ),
    ];

    $fields['address'] = [
      '#type' => 'textarea',
      '#title' => __( 'Contact address' ),
    ];

    $fields['hours'] = [
      '#type' => 'textarea',
      '#title' => __( 'Contact hours' ),
      '#description' => __( 'Example:<Br/>Sunday: Closed<Br/>Monday: 9:30am - 9:00pm<Br/>Tuesday: 9:00am - 5:00pm' ),
    ];

    return $fields;
  }


  public static function phone_tel_links($s) {
    $s = preg_replace('/\(?([0-9]{3})(\-| |\) ?)([0-9]{3})(\-| |\)?)([0-9]{4})/', '<a href="tel:($1) $3-$5" title="Call this number">($1) $3-$5</a>', $s);
    return str_replace(',', '<br/>', $s);
  }

  public static function email_mailto_links($s) {
    $s = preg_replace('/(https?:\/\/([\d\w\.-]+\.[\w\.]{2,6})[^\s\]\[\<\>]*)/i', '<a href="$1">Contact us</a>', $s);
    $s = preg_replace('/(\S+@\S+\.\S+)/', '<a href="mailto:$1" title="Send email">$1</a>', $s);
    return str_replace(',', '<br/>', $s);
  }



}
if( is_admin() )
  new AgencyContact;

// Agency social metabox
class AgencySocial extends \ProudMetaBox {

  public function __construct() {
    parent::__construct( 
      'agency_social', // key
      'Social Media Accounts', // title
      'agency', // screen
      'normal',  // position
      'high' // priority
    );
   
    // Build options
    foreach ( agency_social_services() as $service => $label ) {
      $this->options[ 'social_' . $service ]  = '';
    }
  }

  /**
   * Called on form creation
   * @param $displaying : false if just building form, true if about to display
   * Use displaying:true to do any difficult loading that should only occur when
   * the form actually will display
   */
  public function set_fields( $displaying ) {
    // Already set, no loading necessary
    if( $displaying ) {
      return;
    }

    $this->fields = self::get_fields();
  }

  public static function get_fields() {

    $fields = [];

    foreach (agency_social_services() as $service => $label) {
      $fields['social_' . $service] = [
        '#type' => 'text',
        '#title' => __( ucfirst($service) ),
        '#name' => 'social_' . $service,
      ];
    }
    return $fields;
  }

} // class
if( is_admin() )
  new AgencySocial;


/**
 * Gets the url for the agency homepage (internal or external)
 */
function get_agency_permalink( $post = 0 ) {
  $post = $post > 0 ? $post : get_the_ID();
  $url = get_post_meta( $post, 'url', true );

  if ( get_post_meta( $post, 'agency_type', true ) === 'external' && !empty($url) ) {
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

/** 
 * Returns agency pagebuilder defaults
 */
function agency_pagebuilder_code($type) {
  if($type === 'section') {
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
          'make_inverse' => 'make_inverse',
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
          'title' => 'Connect',
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
  }
  else {
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
          'make_inverse' => 'make_inverse',
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
          'title' => 'Connect',
          'panels_info' => 
          array (
            'class' => 'AgencySocial',
            'raw' => false,
            'grid' => 1,
            'cell' => 0,
            'id' => 2,
          ),
        ),
        2 => 
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
        3 => 
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
        4 => 
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
  }
  return json_encode($code);
}
