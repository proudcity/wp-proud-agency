<?php

/**
Plugin Name: Proud Agency
Plugin URI: http://proudcity.com/
Description: Declares an Agency custom post type.
Version: 2026.10.26.1425
Author: ProudCity
Author URI: http://proudcity.com/
License: Affero GPL v3
 **/
// @todo: use CMB2: https://github.com/WebDevStudios/CMB2 or https://github.com/humanmade/Custom-Meta-Boxes

namespace Proud\Agency;

// Load Extendible
// -----------------------
if (! class_exists('ProudPlugin')) {
    require_once(plugin_dir_path(__FILE__) . '../wp-proud-core/proud-plugin.class.php');
}

// We need the pagebuilder file for the default pagebuilder layout
// @todo: Make this WORK!!
// @todo: dont make this required, gracefully degrade
//require_once( plugin_dir_path(__FILE__) . '../wp-proud-core/modules/so-pagebuilder/proud-so-pagebuilder.php' );

class Agency extends \ProudPlugin
{
    public static $key = 'agency_edit';

    public function __construct()
    {
        parent::__construct(array(
            'textdomain'     => 'wp-proud-agency',
            'plugin_path'    => __FILE__,
        ));
        $this->hook('init', 'create_agency');
        $this->hook('admin_enqueue_scripts', 'agency_assets');
        $this->hook('wp_enqueue_scripts', 'enqueue_frontend_assets');
        $this->hook('plugins_loaded', 'agency_init_widgets');
        $this->hook('rest_api_init', 'agency_rest_support');
        $this->hook('before_delete_post', 'delete_agency_menu');
    }

    //add assets
    public function agency_assets()
    {
        $path = plugins_url('assets/', __FILE__);
        wp_enqueue_script('proud-agency/js', $path . 'js/proud-agency.js', ['proud', 'jquery'], null, true);
    }

    //add assets
    public function enqueue_frontend_assets()
    {
        $path = plugins_url('assets/', __FILE__);
        wp_enqueue_style('proud-agency/css', $path . 'css/proud-agency-styles.css', '', '1.0');
    }

    // Init on plugins loaded
    public function agency_init_widgets()
    {
        if (class_exists('ProudMetaBox')) {
            require_once plugin_dir_path(__FILE__) . '/widgets/agency-contact-widget.class.php';
            require_once plugin_dir_path(__FILE__) . '/widgets/custom-contact-widget.class.php';
            require_once plugin_dir_path(__FILE__) . '/widgets/agency-hours-widget.class.php';
            require_once plugin_dir_path(__FILE__) . '/widgets/agency-social-links-widget.class.php';
            require_once plugin_dir_path(__FILE__) . '/widgets/agency-menu-widget.class.php';
        }
    }

    public function create_agency()
    {
        $labels = array(
            'name'               => _x('Agencies', 'post name', 'wp-agency'),
            'singular_name'      => _x('Agency', 'post type singular name', 'wp-agency'),
            'menu_name'          => _x('Agencies', 'admin menu', 'wp-agency'),
            'name_admin_bar'     => _x('Agency', 'add new on admin bar', 'wp-agency'),
            'add_new'            => _x('Add New', 'agency', 'wp-agency'),
            'add_new_item'       => __('Add New Agency', 'wp-agency'),
            'new_item'           => __('New Agency', 'wp-agency'),
            'edit_item'          => __('Edit Agency', 'wp-agency'),
            'view_item'          => __('View Agency', 'wp-agency'),
            'all_items'          => __('All agencies', 'wp-agency'),
            'search_items'       => __('Search agency', 'wp-agency'),
            'parent_item_colon'  => __('Parent agency:', 'wp-agency'),
            'not_found'          => __('No agencies found.', 'wp-agency'),
            'not_found_in_trash' => __('No agencies found in Trash.', 'wp-agency')
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Description.', 'wp-agency'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => _x('agencies', 'slug', 'wp-agency')),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'show_in_rest'       => true,
            'rest_base'          => 'agencies',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt')
        );

        register_post_type('agency', $args);
    }

    public function agency_rest_support()
    {
        register_rest_field(
            'agency',
            'meta',
            array(
                'get_callback'    => array($this, 'agency_rest_metadata'),
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }

    /**
     * Delete menu when agency is deleted.
     */
    public function delete_agency_menu($post_id)
    {
        $menu = get_post_meta($post_id, 'post_menu');
        wp_delete_nav_menu($menu);
    }

    /**
     * Alter the REST endpoint.
     * Add metadata to the post response
     */
    public function agency_rest_metadata($object, $field_name, $request)
    {
        $Contact = new AgencyContact();
        $return = $Contact->get_options($object['id']);
        $Social = new AgencySocial();
        $return['social'] = $Social->get_options($object['id']);
        return $return;
    }
} // class
$Agency = new Agency();

// Agency meta box
if (class_exists('ProudMetaBox')) {
    class AgencySection extends \ProudMetaBox
    {
        public $options = [  // Meta options, key => default
            'agency_type' => 'page',
            'url' => '',
            'post_menu' => 'new',
            'agency_icon' => '',
            'list_exclude' => ''
        ];

        public function __construct()
        {
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
        public function set_fields($displaying)
        {

            // Already set
            if ($displaying) {

                // Build menu options
                $menus = get_registered_nav_menus();
                $menus = get_terms('nav_menu', array('hide_empty' => false));
                global $menuArray;
                $menuArray = array(
                    '' => 'No menu',
                    'new' => 'Create new menu',
                );
                foreach ($menus as $menu) {
                    $menuArray[$menu->slug] = $menu->name;
                }
                $this->fields['post_menu']['#options'] = $menuArray;
                return;
            }

            $this->fields = [];

            $this->fields['agency_type'] = [
                '#type' => 'radios',
                '#title' => __('Type'),
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
                '#title' => __('Exclude from ' . _x('Agency', 'post type singular name', 'wp-agency') . ' Lists'),
                '#description' => __('Checking this box will cause this ' . _x('Agency', 'post type singular name', 'wp-agency') . ' to be hidden on the Government page'),
                '#return_value' => '1',
            ];
        }

        /**
         * Displays the Agency Type metadata fieldset.
         */
        public function settings_content($post)
        {
            // Call parent
            parent::settings_content($post);
            // Add js settings
            global $proudcore;
            $settings = $this->get_field_names(['agency_type']);
            $settings['isNewPost'] = empty($post->post_title);
            $settings['agency_panels'] = [
                'section' => agency_pagebuilder_code('section'),
                'page' => agency_pagebuilder_code('page') // @TODO change to page + figure out how to update on click
            ];
            $proudcore->addJsSettings([
                'proud_agency' => $settings
            ]);
        }

        /**
         * Saves form values
         * OVERRIDEN from parent for additional processing
         */
        public function save_meta($post_id, $post, $update)
        {
            $values = $this->validate_values($post);
            if (!empty($values['agency_type'])) {
                $type = $values['agency_type'];
                update_post_meta($post_id, 'agency_type', $type);
                if ('external' === $type) {
                    $url = $values['url'];
                    if (empty($url)) {
                        delete_post_meta($post_id, 'url');
                    } else {
                        update_post_meta($post_id, 'url', esc_url($url));
                    }
                } elseif ('section' === $type) {
                    $menu = $values['post_menu'];
                    if ('new' === $menu) {
                        $menuId = wp_create_nav_menu($post->post_title);
                        $objMenu = get_term_by('id', $menuId, 'nav_menu');
                        $menu = $objMenu->slug;
                    }
                    if (!is_array($menu)) {
                        update_post_meta($post_id, 'post_menu', $menu);
                    }
                }

                update_post_meta($post_id, 'agency_icon', $values['agency_icon']);
                update_post_meta($post_id, 'list_exclude', !empty($values['list_exclude']) ? 1 : 0);
            }
        }
    }

    if (is_admin()) {
        new AgencySection();
    }
}

// Agency contact meta box
if (class_exists('ProudMetaBox')) {
    class AgencyContact extends \ProudMetaBox
    {
        public $options = [  // Meta options, key => default
            'name' => '',
            'name_title' => '',
            'name_link' => '',
            'email' => '',
            'phone' => '',
            'fax' => '',
            'sms' => '',
            'address' => '',
            'hours' => '',
        ];

        public function __construct()
        {
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
        public function set_fields($displaying)
        {

            // Already set, no loading necessary
            if ($displaying) {
                return;
            }

            $this->fields = self::get_fields();
        }

        public static function get_fields()
        {
            $fields = [];

            $fields['name'] = [
                '#type' => 'text',
                '#title' => __('Contact name'),
            ];

            $fields['name_title'] = [
                '#type' => 'text',
                '#title' => __('Contact name title'),
                '#description' => __('This will appear directly below the Contact name.'),
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
                '#title' => __('Contact name link'),
                '#description' => __('If you enter a URL in this box, the Contact name above will turn into a link.'),
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
                '#title' => __('Contact email or form'),
            ];

            $fields['phone'] = [
                '#type' => 'text',
                '#title' => __('Contact phone'),
            ];

            $fields['fax'] = [
                '#type' => 'text',
                '#title' => __('Contact FAX'),
            ];

            $fields['sms'] = [
                '#type' => 'text',
                '#title' => __('Contact SMS Number'),
                '#description' => __('This will open in the Text Message app on mobile devices.'),
            ];

            $fields['address'] = [
                '#type' => 'textarea',
                '#title' => __('Contact address'),
            ];

            $fields['hours'] = [
                '#type' => 'textarea',
                '#title' => __('Contact hours'),
                '#description' => __('Example:<Br/>Sunday: Closed<Br/>Monday: 9:30am - 9:00pm<Br/>Tuesday: 9:00am - 5:00pm'),
            ];

            return $fields;
        }


        /**
         * Allowed markup in the contact free-text fields.
         *
         * These fields are linkified and printed as raw HTML, so their content
         * has to be filtered. esc_html() is the wrong tool: editors legitimately
         * use <br> here — San Rafael's Police department stores
         * "Emergencies: 9-1-1   <br>  Main: (415) 485-3304" — and esc_html()
         * would render that tag as visible text. This is the same allowlist
         * wp-proud-core settled on for widget titles in #2916.
         *
         * @return array
         */
        private static function allowed_contact_html()
        {
            return ['br' => []];
        }

        /**
         * Linkifies phone numbers in a contact free-text field.
         *
         * WARNING: the return value is HTML and is NOT attribute-safe. wp_kses()
         * does not encode quotes, so this may only ever be printed as element
         * content, which is what agency-contact.php and topic-contact.php do.
         *
         * @param string $s      Raw meta value.
         * @param string $prefix Link scheme, 'tel' or 'sms'.
         * @return string
         */
        public static function phone_tel_links($s, $prefix = 'tel')
        {
            // Filter first, then linkify: the anchors added below are ours and
            // must not be stripped, and the text around them is editor input.
            $s = wp_kses((string) $s, self::allowed_contact_html());

            // Commas separate entries and become line breaks. Done BEFORE
            // linkifying, so a comma inside a value cannot end up rewritten
            // into markup inside a generated href.
            $s = str_replace(',', '<br/>', $s);

            // Every capture here is a digit group, so the generated href needs
            // no further escaping. $prefix is ours ('tel' or 'sms') but is
            // escaped anyway so a future caller cannot inject through it.
            $prefix = esc_attr($prefix);

            return preg_replace(
                '/\(?([0-9]{3})(\-| |\) ?)([0-9]{3})(\-| |\)?)([0-9]{4})/',
                '<a href="' . $prefix . ':($1) $3-$5" title="Call this number">($1) $3-$5</a>',
                $s
            );
        }

        /**
         * Linkifies emails and contact-page URLs in a contact free-text field.
         *
         * WARNING: the return value is HTML and is NOT attribute-safe. See
         * phone_tel_links().
         *
         * @param string $s Raw meta value.
         * @return string
         */
        public static function email_mailto_links($s)
        {
            $s = wp_kses((string) $s, self::allowed_contact_html());
            $s = str_replace(',', '<br/>', $s);

            // One pass over both patterns. Running them as two sequential
            // preg_replace() calls meant the email pass scanned the URL pass's
            // own output: the URL host pattern does not consume "@", so any
            // contact URL containing one (https://city.gov/x?to=a@b.com) got
            // re-matched and the anchor came out mangled.
            //
            // Both branches emit only esc_url()'d hrefs and esc_html()'d text.
            return preg_replace_callback(
                '/(https?:\/\/[^\s<>"\']+)|([^\s<>"\'=@]+@[^\s<>"\'=@]+\.[^\s<>"\'=@]+)/i',
                function ($m) {
                    // URL branch. Most departments store a contact-page URL in
                    // this field rather than an address.
                    if (!empty($m[1])) {
                        $url = esc_url($m[1]);
                        return '' === $url ? esc_html($m[1]) : '<a href="' . $url . '">Contact us</a>';
                    }

                    // Email branch. sanitize_email() strips the RFC quoted-string
                    // characters that let a payload through
                    // filter_var(FILTER_VALIDATE_EMAIL); is_email() then rejects
                    // whatever is left if it is no longer an address.
                    $email = sanitize_email($m[2]);
                    if (empty($email) || !is_email($email)) {
                        return esc_html($m[2]);
                    }
                    return '<a href="' . esc_url('mailto:' . $email) . '" title="Send email">' . esc_html($email) . '</a>';
                },
                $s
            );
        }
    }

    if (is_admin()) {
        new AgencyContact();
    }
}

// Agency social metabox
if (class_exists('ProudMetaBox')) {
    class AgencySocial extends \ProudMetaBox
    {
        public function __construct()
        {
            parent::__construct(
                'agency_social', // key
                'Social Media Accounts', // title
                'agency', // screen
                'normal',  // position
                'high' // priority
            );

            // Build options
            foreach (agency_social_services() as $service => $label) {
                $this->options['social_' . $service]  = '';
            }
        }

        /**
         * Called on form creation
         * @param $displaying : false if just building form, true if about to display
         * Use displaying:true to do any difficult loading that should only occur when
         * the form actually will display
         */
        public function set_fields($displaying)
        {
            // Already set, no loading necessary
            if ($displaying) {
                return;
            }

            $this->fields = self::get_fields();
        }

        public static function get_fields()
        {

            $fields = [];

            foreach (agency_social_services() as $service => $label) {
                $fields['social_' . $service] = [
                    '#type' => 'text',
                    '#title' => __(ucfirst($service)),
                    '#name' => 'social_' . $service,
                ];
            }
            return $fields;
        }
    } // class
    if (is_admin()) {
        new AgencySocial();
    }
}


/**
 * Gets the url for the agency homepage (internal or external)
 */
function get_agency_permalink($post = 0)
{
    $post = $post > 0 ? $post : get_the_ID();
    $url = get_post_meta($post, 'url', true);

    if (get_post_meta($post, 'agency_type', true) === 'external' && !empty($url)) {
        return esc_html($url);
    } else {
        return esc_url(apply_filters('the_permalink', get_permalink($post), $post));
    }
}

/**
 * Returns the list of social fields (also sued in agency-social-links-widget.php)
 */
function agency_social_services()
{
    return array(
        'facebook' => 'http://facebook.com/pages/',
        'twitter' => 'http://twitter.com/',
        'x' => 'https://x.com',
        'instagram' => 'http://instagram.com/',
        'youtube' => 'http://youtube.com/',
        'rss' => 'Enter url to RSS news feed',
        'ical' => 'Enter url to iCal calendar feed',
        'nextdoor' => 'Enter NextDoor URL',
        'tiktok' => 'Enter TikTok account URL',
        'snapchat' => 'Enter Snapchat URL',
    );
}

/**
 * Maps a social service key to its Font Awesome 6 classes.
 *
 * Doubles as the allowlist for rendering: an unsupported service returns null,
 * so callers can skip it rather than emitting a guessed icon class. Kept here
 * rather than inline in the templates because it had drifted between
 * agency-contact.php and agency-social-links-widget.class.php.
 *
 * @param string $service Service key from agency_social_services().
 * @return string|null Font Awesome classes, or null if unsupported.
 */
function social_icon_classes($service)
{
    // Icon name where it differs from the service key.
    $icon_map = [
        'facebook'  => 'facebook',
        'twitter'   => 'twitter',
        'x'         => 'x-twitter',
        'instagram' => 'instagram',
        'youtube'   => 'youtube',
        'rss'       => 'rss',
        'ical'      => 'calendar',   // solid icon
        'nextdoor'  => 'door-open',  // solid icon
        'tiktok'    => 'tiktok',
        'snapchat'  => 'snapchat',
    ];

    if (!is_string($service) || !isset($icon_map[$service])) {
        return null;
    }

    // Not brands in FA6.
    $solid_icons = ['rss' => true, 'ical' => true, 'nextdoor' => true];
    // Brands with no square-* variant we want to use.
    $no_square = ['instagram' => true, 'x' => true, 'ical' => true, 'tiktok' => true, 'nextdoor' => true];

    $style = isset($solid_icons[$service]) ? 'fa-solid' : 'fa-brands';
    $icon  = isset($no_square[$service]) ? $icon_map[$service] : 'square-' . $icon_map[$service];

    return 'fa ' . $style . ' fa-' . $icon;
}

/**
 * Schemes a given social service is allowed to link out with.
 *
 * @param string $service Service key.
 * @return string[]
 */
function social_allowed_schemes($service)
{
    return $service === 'ical' ? ['http', 'https', 'webcal'] : ['http', 'https'];
}

/**
 * Pulls an agency's social accounts out of post meta.
 *
 * Returns BOTH shapes on purpose: the flat social_<service> keys the shared
 * contact template scans for, and the nested 'social' array older callers and
 * the emptiness checks still read. Writing only one of the two is what broke
 * the contact widget's social row in 9b60fdf.
 *
 * @param int $id Agency post ID.
 * @return array
 */
function agency_social_meta($id)
{
    $out = ['social' => []];

    foreach (agency_social_services() as $service => $label) {
        $url = trim((string) get_post_meta($id, 'social_' . $service, true));
        if ($url === '') {
            continue;
        }
        $out['social'][$service] = $url;
        $out['social_' . $service] = $url;
    }

    return $out;
}

/**
 * Returns agency pagebuilder defaults
 */
function agency_pagebuilder_code($type)
{
    if ($type === 'section') {
        $code = array(
            'name' => __('Agency home page', 'proud'),
            'description' => __('Agency header and sidebar with contact info', 'proud'),    // Optional
            'widgets' =>
            array(
                0 =>
                array(
                    'text' => '<h1>[title]</h1>',
                    'headertype' => 'header',
                    'background' => 'image',
                    'pattern' => '',
                    'repeat' => 'full',
                    'image' => '[featured-image]',
                    'make_inverse' => 'make_inverse',
                    'panels_info' =>
                    array(
                        'class' => 'JumbotronHeader',
                        'grid' => 0,
                        'cell' => 0,
                        'id' => 0,
                    ),
                ),
                1 =>
                array(
                    'title' => '',
                    'panels_info' =>
                    array(
                        'class' => 'AgencyMenu',
                        'raw' => false,
                        'grid' => 1,
                        'cell' => 0,
                        'id' => 1,
                    ),
                ),
                2 =>
                array(
                    'title' => 'Connect',
                    'panels_info' =>
                    array(
                        'class' => 'AgencySocial',
                        'raw' => false,
                        'grid' => 1,
                        'cell' => 0,
                        'id' => 2,
                    ),
                ),
                3 =>
                array(
                    'title' => 'Contact',
                    'panels_info' =>
                    array(
                        'class' => 'AgencyContact',
                        'raw' => false,
                        'grid' => 1,
                        'cell' => 0,
                        'id' => 3,
                    ),
                ),
                4 =>
                array(
                    'title' => 'Hours',
                    'panels_info' =>
                    array(
                        'class' => 'AgencyHours',
                        'raw' => false,
                        'grid' => 1,
                        'cell' => 0,
                        'id' => 4,
                    ),
                ),
                5 =>
                array(
                    'title' => '',
                    'text' => '',
                    'text_selected_editor' => 'tinymce',
                    'autop' => true,
                    '_sow_form_id' => '56ab38067a600',
                    'panels_info' =>
                    array(
                        'class' => 'SiteOrigin_Widget_Editor_Widget',
                        'grid' => 1,
                        'cell' => 1,
                        'id' => 5,
                        'style' =>
                        array(
                            'background_image_attachment' => false,
                            'background_display' => 'tile',
                        ),
                    ),
                ),
            ),
            'grids' =>
            array(
                0 =>
                array(
                    'cells' => 1,
                    'style' =>
                    array(
                        'row_stretch' => 'full',
                        'background_display' => 'tile',
                    ),
                ),
                1 =>
                array(
                    'cells' => 2,
                    'style' =>
                    array(),
                ),
            ),
            'grid_cells' =>
            array(
                0 =>
                array(
                    'grid' => 0,
                    'weight' => 1,
                ),
                1 =>
                array(
                    'grid' => 1,
                    'weight' => 0.33345145287029998,
                ),
                2 =>
                array(
                    'grid' => 1,
                    'weight' => 0.66654854712970002,
                ),
            ),
        );
    } else {
        $code = array(
            'name' => __('Agency home page', 'proud'),
            'description' => __('Agency header and sidebar with contact info', 'proud'),    // Optional
            'widgets' =>
            array(
                0 =>
                array(
                    'text' => '<h1>[title]</h1>',
                    'headertype' => 'header',
                    'background' => 'image',
                    'pattern' => '',
                    'repeat' => 'full',
                    'image' => '[featured-image]',
                    'make_inverse' => 'make_inverse',
                    'panels_info' =>
                    array(
                        'class' => 'JumbotronHeader',
                        'grid' => 0,
                        'cell' => 0,
                        'id' => 0,
                    ),
                ),
                1 =>
                array(
                    'title' => 'Connect',
                    'panels_info' =>
                    array(
                        'class' => 'AgencySocial',
                        'raw' => false,
                        'grid' => 1,
                        'cell' => 0,
                        'id' => 2,
                    ),
                ),
                2 =>
                array(
                    'title' => 'Contact',
                    'panels_info' =>
                    array(
                        'class' => 'AgencyContact',
                        'raw' => false,
                        'grid' => 1,
                        'cell' => 0,
                        'id' => 3,
                    ),
                ),
                3 =>
                array(
                    'title' => 'Hours',
                    'panels_info' =>
                    array(
                        'class' => 'AgencyHours',
                        'raw' => false,
                        'grid' => 1,
                        'cell' => 0,
                        'id' => 4,
                    ),
                ),
                4 =>
                array(
                    'title' => '',
                    'text' => '',
                    'text_selected_editor' => 'tinymce',
                    'autop' => true,
                    '_sow_form_id' => '56ab38067a600',
                    'panels_info' =>
                    array(
                        'class' => 'SiteOrigin_Widget_Editor_Widget',
                        'grid' => 1,
                        'cell' => 1,
                        'id' => 5,
                        'style' =>
                        array(
                            'background_image_attachment' => false,
                            'background_display' => 'tile',
                        ),
                    ),
                ),
            ),
            'grids' =>
            array(
                0 =>
                array(
                    'cells' => 1,
                    'style' =>
                    array(
                        'row_stretch' => 'full',
                        'background_display' => 'tile',
                    ),
                ),
                1 =>
                array(
                    'cells' => 2,
                    'style' =>
                    array(),
                ),
            ),
            'grid_cells' =>
            array(
                0 =>
                array(
                    'grid' => 0,
                    'weight' => 1,
                ),
                1 =>
                array(
                    'grid' => 1,
                    'weight' => 0.33345145287029998,
                ),
                2 =>
                array(
                    'grid' => 1,
                    'weight' => 0.66654854712970002,
                ),
            ),
        );
    }
    return json_encode($code);
}
