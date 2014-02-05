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

        if ( isset( $_GET['google_oauth_response'] ) ) {

            self::google_authenticate( $_GET['google_oauth_response'] );

        }

    }

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 * @return    object    A single instance of this class.
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
	 * @since     1.0.0
	 * @return    null    Return early if no settings page is registered.
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
	 * @since     1.0.0
	 * @return    null    Return early if no settings page is registered.
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
			__( 'WP Top Content', 'required-wp-top-content' ),
			__( 'WP Top Content', 'required-wp-top-content' ),
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

        add_settings_section(
            'rplus_topcontent_options_ga',
            __( 'Google APIs', 'required-wp-top-content' ),
            function() {
                _e( 'Please fill in the data needed to access the Google APIs.', 'required-wp-top-content' );
            },
            $this->plugin_slug
        );

        add_settings_field(
            'rplus_topcontent_options_ga_client_id',
            __( 'Client ID', 'required-wp-top-content' ),
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
            __( 'Client Secret', 'required-wp-top-content' ),
            function() {
                ?>
                <input name="rplus_topcontent_options_ga_client_secret" class="regular-text" type="text" id="rplus_topcontent_options_ga_client_secret" value="<?php echo get_option( 'rplus_topcontent_options_ga_client_secret' ); ?>">
                <p class="description">
                    <?php printf(
                        __(
                            'If you don\'t already have a Client ID and/or Client Secret, go to the <a href="https://cloud.google.com/console" target="_blank">Google Developers Console</a>, '.
                            'create a new project or select a existing one, go to the <strong>APIs & auth > Credentials</strong> page and create a new Client ID. As <strong>Application type</strong>'.
                            'select <strong>Web application</strong>, fill in the correct domain name of your site (probably <strong>%s</strong>) and fill in the following as <strong>Authorized redirect URI</strong>: %s',
                            'required-wp-top-content'
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
            __( 'Google API Server Key', 'required-wp-top-content' ),
            function() {
                ?>
                <input name="rplus_topcontent_options_ga_devkey" class="regular-text" type="text" id="rplus_topcontent_options_ga_devkey" value="<?php echo get_option( 'rplus_topcontent_options_ga_devkey' ); ?>">
                <p class="description"><?php _e( 'You need to create a Server Key on the <a href="https://cloud.google.com/console" target="_blank">Google Developers Console</a>.<br>To do this, create a new project (or select a existing one), activate the Analytics API inside <strong>APIs & auth</strong>, now go to <strong>APIs & auth > Credentials > Create new key > Server Key</strong> and create the Server key.', 'required-wp-top-content' ); ?></p>
            <?php
            },
            $this->plugin_slug,
            'rplus_topcontent_options_ga'
        );

        add_settings_field(
            'rplus_topcontent_options_ga_propertyid',
            __( 'Analytics Property', 'required-wp-top-content' ),
            function() {

                $access_token = RplusWpTopContentAdmin::get_google_api_access_token();
                $client = RplusWpTopContentAdmin::get_google_api_client();
                if ( false === $access_token ) {

                    $authurl = $client->createAuthUrl();

                    echo '<p>';
                    printf( __( 'You have to authorize this plugin to access your Google Analytics data <a href="%s" class="button button-secondary">Ok, go to authorization page</a>', 'required-wp-top-content' ), $authurl );
                    echo '</p>';

                } else {

                    // set default value to zero
                    echo '<input type="hidden" name="rplus_topcontent_options_ga_propertyid" value="0">';

                    $client->setAccessToken( $access_token );
                    $analytics = new Google_Service_Analytics( $client );
                    $accounts = RplusWpTopContentAdmin::google_get_accounts( $analytics );

                    // var_dump( $analytics->management_profiles->listManagementProfiles( 32936972, '~all' ) );
                    // var_dump( $analytics->data_ga->get('ga:61435341', '2013-01-01', '2013-12-31', 'ga:visits,ga:pageviews' ) );
                    /*$data = $analytics->data_ga->get( 'ga:'.get_option('rplus_topcontent_options_ga_propertyid'), '2014-01-01', '2014-01-31', 'ga:visits,ga:pageviews', array(
                        'dimensions' => 'ga:pagePath',
                        'sort' => '-ga:visits,ga:pagePath'
                    ) );
                    var_dump( $data );*/

                    _e( '<p class="description">Please select the correct Analytics Profile for your WordPress installation. The Tracking code you\'ve used on this WordPress installation should match with the one of this list.</p>', 'required-wp-top-content' );

                    ?>
                    <table class="widefat">
                        <tr>
                            <td class="row-title"><?php _e( 'Account', 'required-wp-top-content' ); ?></td>
                            <td class="row-title"><?php _e( 'Profile', 'required-wp-top-content' ); ?></td>
                        </tr>
                    <?php
                    foreach ( $accounts as $a ) {
                        $profiles = RplusWpTopContentAdmin::google_get_profiles( $analytics, $a->id, '~all' );
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

    }

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', 'required-wp-top-content' ) . '</a>'
			),
			$links
		);

	}

    /**
     * Load google analytics libraries and return service object
     *
     * @return Google_Client
     */
    public static function get_google_api_client() {

        // check if all needed options are set
        $apikey = get_option( 'rplus_topcontent_options_ga_devkey' );
        $clientid = get_option( 'rplus_topcontent_options_ga_client_id' );
        $clientsecret = get_option( 'rplus_topcontent_options_ga_client_secret' );
        if ( empty( $apikey ) || empty( $clientid ) || empty( $clientsecret ) ) {
            return false;
        }

        // Google API Library path
        $lib_dir = plugin_dir_path( __DIR__ ) . 'includes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'google-api' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

        // update include path
        set_include_path( get_include_path() . PATH_SEPARATOR . $lib_dir );

        require_once $lib_dir . 'Google' . DIRECTORY_SEPARATOR . 'Client.php';
        require_once $lib_dir . 'Google' . DIRECTORY_SEPARATOR . 'Service' . DIRECTORY_SEPARATOR . 'Analytics.php';
        $client = new Google_Client();
        $client->setApplicationName( "WordPress Plugin - required-wp-top-content" );
        $client->setDeveloperKey( $apikey );
        $client->setClientId( $clientid );
        $client->setClientSecret( $clientsecret );
        $client->setScopes( 'https://www.googleapis.com/auth/analytics.readonly' );
        $client->setAccessType( 'offline' );
        $client->setRedirectUri( plugins_url( 'admin/includes/google_oauth_response.php', plugin_dir_path( __FILE__ ) ) );

        return $client;

    }

    /**
     * Get Google API access token
     *
     * @return bool|mixed|void
     */
    public static function get_google_api_access_token() {

        $access_token = get_option( 'rplus_topcontent_options_ga_access_token' );

        if ( ! $access_token || empty ( $access_token ) || $access_token == 'null' ) {

            return false;

        }

        try {

            $client = self::get_google_api_client();
            $client->setAccessToken( $access_token );

            if ( $client->isAccessTokenExpired() ) {

                $refresh = json_decode( $access_token );
                $client->refreshToken( $refresh->refresh_token );

            }

        } catch ( Exception $e ) {

            return false;

        }

        return $access_token;

    }

    /**
     * Google OAuth response, authenticate and fetch access token
     *
     * @param $code
     */
    private static function google_authenticate( $code ) {

        $client = self::get_google_api_client();
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
     * Get Google Analytics accounts (cached, when exists)
     *
     * @param Google_Service_Analytics $analytics
     * @return Google_Service_Analytics_Accounts|mixed
     */
    public static function google_get_accounts( Google_Service_Analytics $analytics ) {

        $tkey = 'rplus-wp-topcontent-ga-accounts-list';
        $cached = get_transient( $tkey );

        if ( $cached ) {

            return $cached;

        }

        $accounts = $analytics->management_accounts->listManagementAccounts();

        // fetch accounts every hour
        set_transient( $tkey, $accounts, 3600 );

        return $accounts;

    }

    /**
     * Get Profiles of defined account (chached, when exists)
     *
     * @param Google_Service_Analytics $analytics
     * @param $account_id
     * @param string $filter
     * @return Google_Service_Analytics_Profiles
     */
    public static function google_get_profiles( Google_Service_Analytics $analytics, $account_id, $filter = '~all' ) {

        $tkey = 'rplus-wp-topcontent-ga-profiles-'.$account_id.'-'.$filter;
        $cached = get_transient( $tkey );

        if ( $cached ) {
            return $cached;
        }

        $profiles = $analytics->management_profiles->listManagementProfiles( $account_id, $filter );

        // expires every hour
        set_transient( $tkey, $profiles, 3600 );

        return $profiles;

    }

}
