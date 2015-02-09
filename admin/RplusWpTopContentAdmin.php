<?php
/**
 * required+ WP Top Content
 *
 * @package   required-wp-top-content
 * @author    Stefan Pasch <stefan@required.ch>
 * @license   GPL-2.0+
 * @link      http://required.ch
 * @copyright 2014 required gmbh
 */

/**
 * required WP Top Content
 * Administrative functions
 *
 * @package required-wp-top-content
 * @author  Stefan Pasch <stefan@required.ch>
 */
class RplusWpTopContentAdmin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin = RplusWpTopContent::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

        // Add the options for the options page
        add_action( 'admin_init', array( $this, 'add_plugin_admin_options' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

        // Work with OAuth response, when set.
        if ( isset( $_GET['google_oauth_response'] ) ) {

            self::google_authenticate( $_GET['google_oauth_response'] );

        }

        // add admin notice in case we don't have a valid api token
        $access_token = RplusGoogleAnalytics::get_google_api_access_token();
        if ( ! $access_token || empty( $access_token ) ) {
            add_filter( 'admin_notices', array( $this, 'admin_notice_no_token' ) );
        }

        $this->change_admin_columns();

		// Filter posts for custom column sorting
		add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

		add_filter('get_meta_sql', array( $this, 'change_columns_order_sql' ) );

    }

    /**
     * Add filters to selected post types to changes admin columns and content
     *
     * @since   1.0.0
     */
    private function change_admin_columns() {

        foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {

            // modify admin list columns
            add_filter( "manage_edit-{$post_type}_columns", array( $this, 'admin_edit_columns' ) );

            // fill custom columns
            add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'admin_manage_columns' ), 10, 2 );

	        add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'admin_sortable_columns' ) );

        }

    }

    /**
     * WP-Admin Columns displayed for selected post types
     *
     * @param   array    $columns    Array of default columns
     * @return  array    $columns    Modified array of columns
     * @since   1.0.0
     */
    public function admin_edit_columns( $columns ) {

        if ( false !== RplusGoogleAnalytics::get_google_api_access_token() ) {
            $columns['rplustopcontent'] = sprintf( __( 'Pageviews / Visits (last %d days)', 'rpluswptopcontent' ), get_option( 'rplus_topcontent_options_sync_days' ) );
        }

        return $columns;

    }

    /**
     * WP-Admin Columns content displayed for selected post types
     *
     * @param   string   $column     Name of the column defined in $this->admin_edit_columns();
     * @param   int      $post_id    WP_Post ID
     * @return  string               Content for the columns
     * @since   1.0.0
     */
    public function admin_manage_columns( $column, $post_id ) {

        switch ( $column ) {

            // Display rating infos
            case 'rplustopcontent':
                $pageviews = get_post_meta( $post_id, 'rplus_top_content_pageviews', true );
                $visits    = get_post_meta( $post_id, 'rplus_top_content_visits', true );

                echo ( ! empty( $pageviews ) ) ? $pageviews : '-';
                echo ' / ';
                echo ( ! empty( $visits ) ) ? $visits : '-';
                break;

            // Don't show anything by default
            default:
                break;
        }

    }

	/**
	 * Filter the sortable columns.
	 *
	 * @param array $columns The columns that can be filtered.
	 *
	 * @return array
	 */
	public function admin_sortable_columns( $columns ) {
		$columns['rplustopcontent'] = 'rplustopcontent';

		return $columns;
	}

	/**
	 * Modify the query for the custom sorting.
	 *
	 * @param WP_Query $query
	 */
	public function pre_get_posts( $query ) {
		if( ! is_admin() )
			return;

		$orderby = $query->get( 'orderby');

		if( 'rplustopcontent' === $orderby ) {
			$query->set('meta_key','rplus_top_content_pageviews');
			$query->set('orderby','meta_value_num');
		}
	}

	/**
	 * Filter the SQL clauses for the column sorting to include posts
	 * without any ratings.
	 *
	 * @param array $clauses The SQL clauses
	 *
	 * @return array
	 */
	public function change_columns_order_sql( $clauses ) {
		global $wp_query;

		if ( 'rplus_top_content_pageviews' === $wp_query->get( 'meta_key' ) && 'meta_value_num' === $wp_query->get( 'orderby' ) ) {
			// Left Join so empty values will be returned as well
			$clauses['join'] = str_replace( 'INNER JOIN', 'LEFT JOIN', $clauses['join'] ) . $clauses['where'];
			$clauses['where'] = '';
		}

		return $clauses;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return    object    A single instance of this class.
     * @since   1.0.0
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @return    null    Return early if no settings page is registered.
     * @since     1.0.0
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), RplusWpTopContent::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @return    null    Return early if no settings page is registered.
     * @since     1.0.0
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), RplusWpTopContent::VERSION );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'WP Top Content', 'rpluswptopcontent' ),
			__( 'WP Top Content', 'rpluswptopcontent' ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {

    	include_once( 'views/admin.php' );

	}

    /**
     * Add options for the options page
     *
     * @since   1.0.0
     */
    public function add_plugin_admin_options() {

        register_setting( $this->plugin_slug . '-options', 'rplus_topcontent_options_ga_client_id' );
        register_setting( $this->plugin_slug . '-options', 'rplus_topcontent_options_ga_client_secret' );
        register_setting( $this->plugin_slug . '-options', 'rplus_topcontent_options_ga_devkey' );
        register_setting( $this->plugin_slug . '-options', 'rplus_topcontent_options_ga_propertyid' );
        register_setting( $this->plugin_slug . '-options', 'rplus_topcontent_options_sync_days' );

        add_settings_section(
            'rplus_topcontent_options_ga',
            __( 'Google APIs', 'rpluswptopcontent' ),
            function() {
                _e( 'Please fill in the data needed to access the Google APIs.', 'rpluswptopcontent' );
            },
            $this->plugin_slug
        );

        add_settings_field(
            'rplus_topcontent_options_ga_client_id',
            __( 'Client ID', 'rpluswptopcontent' ),
            function() {
                ?>
                <input name="rplus_topcontent_options_ga_client_id" class="regular-text" type="text" id="rplus_topcontent_options_ga_client_id" value="<?php echo get_option( 'rplus_topcontent_options_ga_client_id' ); ?>">
                <?php
            },
            $this->plugin_slug,
            'rplus_topcontent_options_ga'
        );

        add_settings_field(
            'rplus_topcontent_options_ga_client_secret',
            __( 'Client Secret', 'rpluswptopcontent' ),
            function() {
                ?>
                <input name="rplus_topcontent_options_ga_client_secret" class="regular-text" type="text" id="rplus_topcontent_options_ga_client_secret" value="<?php echo get_option( 'rplus_topcontent_options_ga_client_secret' ); ?>">
                <p class="description">
                    <?php printf(
                        __(
                            'If you don\'t already have a Client ID and/or Client Secret, go to the <a href="https://cloud.google.com/console" target="_blank">Google Developers Console</a>, '.
                            'create a new project or select a existing one, go to the <strong>APIs & auth > Credentials</strong> page and create a new Client ID. As <strong>Application type</strong>'.
                            'select <strong>Web application</strong>, fill in the correct domain name of your site (probably <strong>%s</strong>) and fill in the following as <strong>Authorized redirect URI</strong>: %s',
                            'rpluswptopcontent'
                        ),
                        site_url(),
                        plugins_url( 'admin/includes/google_oauth_response.php', plugin_dir_path( __FILE__ ) )
                    ); ?>
                </p>
                <?php
            },
            $this->plugin_slug,
            'rplus_topcontent_options_ga'
        );

        add_settings_field(
            'rplus_topcontent_options_ga_devkey',
            __( 'Google API Server Key', 'rpluswptopcontent' ),
            function() {
                ?>
                <input name="rplus_topcontent_options_ga_devkey" class="regular-text" type="text" id="rplus_topcontent_options_ga_devkey" value="<?php echo get_option( 'rplus_topcontent_options_ga_devkey' ); ?>">
                <p class="description"><?php _e( 'You need to create a Server Key on the <a href="https://cloud.google.com/console" target="_blank">Google Developers Console</a>.<br>To do this, create a new project (or select a existing one), activate the Analytics API inside <strong>APIs & auth</strong>, now go to <strong>APIs & auth > Credentials > Create new key > Server Key</strong> and create the Server key.', 'rpluswptopcontent' ); ?></p>
            <?php
            },
            $this->plugin_slug,
            'rplus_topcontent_options_ga'
        );

        add_settings_field(
            'rplus_topcontent_options_ga_propertyid',
            __( 'Analytics Property', 'rpluswptopcontent' ),
            function() {

                $access_token = RplusGoogleAnalytics::get_google_api_access_token();
                $client = RplusGoogleAnalytics::get_google_api_client();
                if ( false === $access_token ) {

                    if ( false === $client ) {

                        echo '<div class="error below-h2"><p>';
                        _e( 'Please fill in all option fields to proceed.', 'rpluswptopcontent' );
                        echo '</p></div>';

                    } else {

                        $authurl = $client->createAuthUrl();

                        echo '<div class="error below-h2"><p>';
                        printf( __( 'You have to authorize this plugin to access your Google Analytics data. <a href="%s" class="">Ok, go to authorization page &raquo;</a>', 'rpluswptopcontent' ), $authurl );
                        echo '</p></div>';

                    }

                } else {

                    // set default value to zero
                    echo '<input type="hidden" name="rplus_topcontent_options_ga_propertyid" value="0">';

                    $client->setAccessToken( $access_token );
                    $analytics = new Google_Service_Analytics( $client );
                    $accounts = RplusGoogleAnalytics::google_get_accounts( $analytics );

                    _e( '<p class="description">Please select the correct Analytics Profile for your WordPress installation. The Tracking code you\'ve used on this WordPress installation should match with the one of this list.</p>', 'rpluswptopcontent' );

                    ?>
                    <table class="widefat">
                        <tr>
                            <td class="row-title"><?php _e( 'Account', 'rpluswptopcontent' ); ?></td>
                            <td class="row-title"><?php _e( 'Profile', 'rpluswptopcontent' ); ?></td>
                        </tr>
                    <?php
                    foreach ( $accounts as $a ) {
                        $profiles = RplusGoogleAnalytics::google_get_profiles( $analytics, $a->id, '~all' );
                        ?>
                        <tr>
                            <td valign="top" style="vertical-align: top;">
                                <?php echo $a->name; ?>
                            </td>
                            <td valign="top" style="vertical-align: top;">
                                <ul style="margin: 0; padding: 0;">
                            <?php foreach ( $profiles as $p ) : ?>
                                <li>
                                    <label for="rplus_topcontent_options_ga_propertyid_<?php echo $p->id; ?>">
                                        <input type="radio" id="rplus_topcontent_options_ga_propertyid_<?php echo $p->id; ?>" name="rplus_topcontent_options_ga_propertyid" value="<?php echo $p->id; ?>" <?php checked( get_option('rplus_topcontent_options_ga_propertyid'), $p->id ); ?>>
                                        <strong><?php echo $p->name; ?></strong> (<?php echo $p->webPropertyId; ?>, <?php echo $p->websiteUrl; ?>)
                                    </label>
                                </li>
                            <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    <?php
                    }
                    echo '</table>';
                }
            },
            $this->plugin_slug,
            'rplus_topcontent_options_ga'
        );

        add_settings_section(
            'rplus_topcontent_options_sync',
            __( 'Synchronisation', 'rpluswptopcontent' ),
            function() {

            },
            $this->plugin_slug
        );

        add_settings_field(
            'rplus_topcontent_options_sync_days',
            __( 'Time range', 'rpluswptopcontent' ),
            function() {
                ?>
                <input name="rplus_topcontent_options_sync_days" class="" type="text" id="rplus_topcontent_options_sync_days" value="<?php echo get_option( 'rplus_topcontent_options_sync_days' ); ?>" placeholder="30">
                <p class="description"><?php _e( 'Sync google analytics data of the last X days. Default is 30 days.', 'rpluswptopcontent' ); ?></p>
            <?php
            },
            $this->plugin_slug,
            'rplus_topcontent_options_sync'
        );

        /*
         * Displays a button, or starts synchronisation with debug output
         */
        add_settings_field(
            'rplus_topcontent_options_sync_test',
            __( 'Do synchronisation', 'rpluswptopcontent' ),
            function() {
                $test = false;
                if ( isset( $_GET['rplusdosync'] ) && $_GET['rplusdosync'] == 'now' ) {

                    RplusGoogleAnalytics::google_sync_ga_data( true );

                } else {

                    printf( __( '<a href="%s" class="button button-secondary">Start synchronisation now</a>', 'rpluswptopcontent' ), admin_url( 'options-general.php?page=' . RplusWpTopContent::get_instance()->get_plugin_slug() . '&rplusdosync=now') );

                }
            },
            $this->plugin_slug,
            'rplus_topcontent_options_sync'
        );

        /*
         * Displays a button, to delete all saved data. For debugging reasons.
         */
        add_settings_field(
            'rplus_topcontent_options_reset',
            __( 'Remove all settings', 'rpluswptopcontent' ),
            function() {

                if ( isset( $_GET['rplusdoreset'] ) && $_GET['rplusdoreset'] == 'now' ) {

                    RplusGoogleAnalytics::google_reset_options();
                    _e( 'All settings removed, reload this page.', 'rpluswptopcontent' );

                } else {

                    printf( __( '<a href="%s" class="button button-secondary">Remove Settings</a>', 'rpluswptopcontent' ), admin_url( 'options-general.php?page=' . RplusWpTopContent::get_instance()->get_plugin_slug() . '&rplusdoreset=now') );

                }
            },
            $this->plugin_slug,
            'rplus_topcontent_options_sync'
        );

    }

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', 'rpluswptopcontent' ) . '</a>'
			),
			$links
		);

	}

    /**
     * Google OAuth response, authenticate and fetch access token
     *
     * @param   $code
     * @since   1.0.0
     */
    private static function google_authenticate( $code ) {

        $client = RplusGoogleAnalytics::get_google_api_client();
        if ( $client ) {

            $client->authenticate( $code );
            $access_token = $client->getAccessToken();

            update_option( 'rplus_topcontent_options_ga_access_token', $access_token );
            update_option( 'rplus_topcontent_options_ga_access_code', $code );

        }

        wp_redirect( admin_url( 'options-general.php?page=' . RplusWpTopContent::get_instance()->get_plugin_slug() ) );
        exit();

    }

    /**
     * Display admin infos when no token exists
     * 
     * @since   1.0.0
     */
    public function admin_notice_no_token() {
        ?>
        <div class="error">
            <p><?php printf( __( 'required+ WordPress Top Content Plugin is not configured properly. Google Analytics API Token is missing. Please go to <a href="%s">settings page</a> to authorize this Plugin.', 'rpluswptopcontent' ), admin_url( 'options-general.php?page=' . $this->plugin_slug ) ); ?></p>
        </div>
        <?php
    }

}
