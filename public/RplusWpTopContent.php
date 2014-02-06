<?php
/**
 * required WP Top Content
 *
 * @package   required-wp-top-content
 * @author    Stefan Pasch <stefan@required.ch>
 * @license   GPL-2.0+
 * @link      http://required.ch
 * @copyright 2014 required gmbh
 */

/**
 * @package required-wp-top-content
 * @author  Stefan Pasch <stefan@required.ch>
 */
class RplusWpTopContent {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * Unique identifier
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	protected $plugin_slug = 'rpluswptopcontent';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

    /**
     * Get post type name of custom post type
     *
     * @return string
     */
    public function get_post_type() {
        return $this->post_type;
    }

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 * @return    RplusWpTopContent    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {

	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {

	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

    /**
     * Get top visited posts/pages based on synced analytics data and return array of WP_Post Objects
     * or false when no top contents are available
     *
     * @param    array      $post_types     Array of post types to get top contents from
     * @param    int        $count          Limit of contents to fetch
     * @return   array|bool
     * @since    1.0.0
     */
    private function get_top_content( Array $post_types, $count ) {

        $args = apply_filters( 'rplus_wp_top_content_default_args', array(
            'post_type' => $post_types,
            'nopaging' => true,
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'orderby' => 'meta_value',
            'order' => 'DESC',
            'meta_key' => 'rplus_top_content_pageviews',
            'meta_query' => array(
                array(
                    'key' => 'rplus_top_content_pageviews',
                    'value' => 0,
                    'compare' => '>'
                )
            )
        ) );

        // query defined post types with synced analytics data.
        $the_query = new WP_Query( $args );

        $posts = empty( $the_query->found_posts ) ? false : $the_query->get_posts();

        wp_reset_query();

        return $posts;

    }

    /**
     * Load the frontend template
     *
     * This function loads the specific template file from either your theme or child theme
     * or falls back on the templates living in the /required-wp-top-content/templates folder.
     *
     * @param    string     $template   The template to be loaded (filename incl. extension)
     * @param    WP_Post    $post       The WP_Post object to be used inside the template
     * @since    1.0.0
     */
    private function load_template( $template, $post ) {

        // Check if the template file exists in the theme forlder
        if ( $overridden_template = locate_template( $template ) ) {
            // Load the requested template file from the theme or child theme folder
            $template_path = $overridden_template;

        } else {
            // Load the requested template file from the plugin folder
            $template_path = dirname( __FILE__ ) . '/templates/'  . $template;

        }

        include( $template_path );

    }

    /**
     * Renders the templates and contents
     *
     * @param    array      $post_types     Array of post types of the top contents
     * @param    int        $count          The limit to display
     * @param    string     $template       The template to load for each element
     * @since    1.0.0
     */
    public function render_top_content( Array $post_types, $count, $template ) {

        $top_content = $this->get_top_content( $post_types, $count );

        if ( $top_content ) {

            echo '<ul class="required-wp-top-content">';

            foreach ( $top_content as $tc ) {

                $this->load_template( $template, $tc );

            }

            echo '</ul>';

        }

    }

    /**
     * WP Top Content item classes
     *
     * Allows for ' ' seperated string and array as
     * data input.
     *
     * @param    mixed      $classes    Array of classes to append to the defaults
     * @return   string
     * @since    1.0.0
     */
    public function item_classes( $classes ) {

        $defaults = apply_filters(
            'rplus_wp_top_content_default_classes',
            array(
                'wp-top-content-item'
            )
        );

        if ( ! is_array( $classes ) )
            $classes = explode( ' ', $classes );

        $classes = apply_filters(
            'rplus_wp_top_content_classes',
            array_merge( $defaults, $classes )
        );

        $classes = array_map( 'esc_attr', $classes );

        return join( ' ', $classes );
    }

}
