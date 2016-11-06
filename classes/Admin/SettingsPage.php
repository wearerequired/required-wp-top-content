<?php
/**
 * SettingsPage class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content\Admin
 */

namespace Required\WP_Top_Content\Admin;
use Required\WP_Top_Content\Plugin;
use const Required\WP_Top_Content\PLUGIN_FILE;
use Required\WP_Top_Content\GoogleClientAdapter;
use stdClass;
use WP_Error;

/**
 * Class used to add a settings page.
 *
 * @since 2.0.0
 */
class SettingsPage implements AdminPageInterface {

	const MENU_SLUG = 'rpluswptopcontent';

	const CAPABILITY = 'manage_options';

	/**
	 * Page's hook_suffix.
	 *
	 * @since 2.0.0
	 * @access protected
	 *
	 * @var string
	 */
	protected $page_hook;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function __construct() {
	}

	/**
	 * Adds a new menu for an admin page to the admin menu.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function add() {
		$client_adapter = new GoogleClientAdapter();
		$client_has_auth_token = $client_adapter->has_auth_token();

		$data                    = new StdClass();
		$data->client_adapter    = $client_adapter;
		$data->client_id         = get_option( 'rplus_topcontent_options_ga_client_id', '' );
		$data->client_secret     = get_option( 'rplus_topcontent_options_ga_client_secret', '' );
		$data->option_ga_profile = get_option( 'rplus_topcontent_options_ga_profile', [] );
		$data->auth_type         = get_option( 'rplus_topcontent_options_ga_auth_type', 'default' );
		$data->show_step_1       = ! $data->client_id || ! $data->client_secret;
		$data->show_step_2       = ! $data->show_step_1 && ! $client_has_auth_token;
		$data->show_step_3       = ! $data->show_step_1 && ! $data->show_step_2;
		$data->ga_profile        = null;

		if ( $client_has_auth_token && $data->option_ga_profile ) {
			$data->ga_profile = $client_adapter->get_profile(
				$data->option_ga_profile['account-id'],
				$data->option_ga_profile['web-property-id'],
				$data->option_ga_profile['profile-id']
			);
		}

		$view = new SettingsPageView( $data );
		$this->page_hook = add_options_page(
			__( 'WP Top Content', 'required-wp-top-content' ),
			__( 'WP Top Content', 'required-wp-top-content' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			[ $view, 'render' ]
		);

		$this->register_setting_fields();

		add_action( 'admin_print_scripts-' . $this->page_hook, [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_print_styles-' . $this->page_hook, [ $this, 'enqueue_styles' ] );
		add_action( 'admin_footer-' . $this->page_hook, [ $this, 'print_js_templates' ] );
	}

	/**
	 * Enqueues scripts for this page.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'required-wp-top-content-google-auth',
			plugins_url( 'assets/js/google-auth.js', PLUGIN_FILE ),
			[ 'jquery', 'underscore', 'wp-util' ]
		);
	}

	/**
	 * Prints JavaScript templates used for this page.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function print_js_templates() {
		?>
		<script type="text/html" id="tmpl-required-wp-top-content-notifications">
			<ul>
				<# _.each( data.notifications, function( notification ) { #>
					<li class="notice notice-{{ notification.type || 'info' }}{{ data.altNotice ? ' notice-alt' : '' }}{{ data.isDismissible ? ' is-dismissible' : '' }}">
						<p>{{{ notification.message || notification.code }}}</p>
					</li>
				<# } ); #>
			</ul>
		</script>
		<?php
	}

	/**
	 * Enqueues styles for this page.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'required-wp-top-content-admin', plugins_url( 'assets/css/admin.css', PLUGIN_FILE ) );
	}

	/**
	 * Handles Ajax save actions for auth data.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function ajax_save_auth_data() {
		check_ajax_referer( 'save-auth-credentials' );

		$errors = new WP_Error();

		if ( empty( $_REQUEST['api-credentials-type'] ) ) {
			$errors->add( 'missing_api-credentials-type', __( 'The authentication type is missing.', 'required-wp-top-content' ) );
		} else {
			update_option( 'rplus_topcontent_options_ga_auth_type', $_REQUEST['api-credentials-type'] );
		}

		if ( $errors->get_error_codes() ) {
			wp_send_json_error( $errors );
		}

		$type = get_option( 'rplus_topcontent_options_ga_auth_type' );

		if ( 'custom' === $type && empty( $_REQUEST['google-client-id'] ) ) {
			$errors->add( 'missing_google-client-id', __( 'The client ID is missing.', 'required-wp-top-content' ) );
		} else {
			update_option( 'rplus_topcontent_options_ga_client_id', $_REQUEST['google-client-id'] );
		}

		if ( 'custom' === $type && empty( $_REQUEST['google-client-secret'] ) ) {
			$errors->add( 'missing_google-client-secret', __( 'The client secret is missing.', 'required-wp-top-content' ) );
		} else {
			update_option( 'rplus_topcontent_options_ga_client_secret', $_REQUEST['google-client-secret'] );
		}

		if ( $errors->get_error_codes() ) {
			wp_send_json_error( $errors );
		}

		$client_adapter = new GoogleClientAdapter();
		$client = $client_adapter->get_client();

		wp_send_json_success( [ 'authUrl' => esc_url_raw( $client->createAuthUrl() ) ] );
	}

	/**
	 * Handles Ajax authorization actions.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function ajax_google_authorize() {
		check_ajax_referer( 'authorize' );

		$errors = new WP_Error();
		if ( empty( $_REQUEST['google-auth-code'] ) ) {
			$errors->add( 'missing_google-auth-code', __( 'The authentication code is missing.', 'required-wp-top-content' ) );
		} else {
			update_option( 'rplus_topcontent_options_ga_access_code', $_REQUEST['google-auth-code'] );
		}

		if ( $errors->get_error_codes() ) {
			wp_send_json_error( $errors );
		}

		$client_adapter = new GoogleClientAdapter();
		$client = $client_adapter->get_client();
		try {
			$access_code = get_option( 'rplus_topcontent_options_ga_access_code' );
			$access_token = $client->fetchAccessTokenWithAuthCode( $access_code );
		} catch ( \Exception $exception ) {
			$errors->add( $exception->getCode(), $exception->getMessage() );
		}

		if ( $errors->get_error_codes() ) {
			wp_send_json_error( $errors );
		}

		$result = update_option( 'rplus_topcontent_options_ga_access_token', $access_token );
		if ( ! $result ) {
			$errors->add( 'update_failed', __( 'The settings couldn&#8217;t be saved. Please try again.', 'required-wp-top-content' ) );
			wp_send_json_error( $errors );
		}

		$html  = '<select id="google-analytics-profile" name="google-analytics-profile">';
		$html .= $client_adapter->get_profiles_as_html_options();
		$html .= '</select>';

		wp_send_json_success( $html );
	}

	/**
	 * Stores the Google profile.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function save_profile() {
		$referer = wp_get_referer();
		if ( ! $referer ) {
			exit;
		}

		$referer = add_query_arg( 'settings-updated', '1', $referer );

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'save-profile' ) ) {
			add_settings_error( self::MENU_SLUG, 'nonce_invalid', __( 'Error while saving settings. Please try again.', 'required-wp-top-content' ) );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( $referer );
			exit;
		}

		if ( empty( $_REQUEST['google-analytics-profile'] ) ) {
			add_settings_error( self::MENU_SLUG, 'missing_profile', __( 'No profile ID was defined. Please try again.', 'required-wp-top-content' ) );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( $referer );
			exit;
		}

		$profile = wp_unslash( $_REQUEST['google-analytics-profile'] );
		$data = explode( ':', $profile );

		if ( 3 !== count( $data ) ) {
			add_settings_error( self::MENU_SLUG, 'invalid_profile', __( 'The profile date was invalid. Please try again.', 'required-wp-top-content' ) );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( $referer );
			exit;
		}

		$profile = [
			'account-id'      => $data[0],
			'web-property-id' => $data[1],
			'profile-id'      => $data[2],
		];

		update_option( 'rplus_topcontent_options_ga_profile', $profile );

		wp_safe_redirect( add_query_arg( 'updated', '1', $referer ) );
	}

	/**
	 * Removes the Google API authorization.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function remove_authorization() {
		$referer = wp_get_referer();
		if ( ! $referer ) {
			exit;
		}

		$referer = add_query_arg( 'settings-updated', '1', $referer );

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'remove-authorization' ) ) {
			add_settings_error( self::MENU_SLUG, 'nonce_invalid', __( 'Error while removing authorization. Please try again.', 'required-wp-top-content' ) );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( $referer );
			exit;
		}

		$client_adapter = new GoogleClientAdapter();
		if ( $client_adapter->has_auth_token() ) {
			$google_client = $client_adapter->get_client();
			$google_client->revokeToken();
		}

		delete_option( 'rplus_topcontent_options_ga_auth_type' );
		delete_option( 'rplus_topcontent_options_ga_client_id' );
		delete_option( 'rplus_topcontent_options_ga_client_secret' );
		delete_option( 'rplus_topcontent_options_ga_propertyid' );
		delete_option( 'rplus_topcontent_options_ga_profile' );
		delete_option( 'rplus_topcontent_options_ga_access_token' );
		delete_option( 'rplus_topcontent_options_ga_access_code' );

		add_settings_error(
			self::MENU_SLUG,
			'authorization_removed',
			__( 'Authorization successfully removed.', 'required-wp-top-content' ),
			'updated'
		);
		set_transient( 'settings_errors', get_settings_errors(), 30 );

		wp_safe_redirect( $referer );
		exit;
	}

	/**
	 * Performs a manual Google Analytics data sync.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function do_manual_sync() {
		$referer = wp_get_referer();
		if ( ! $referer ) {
			exit;
		}

		$referer = add_query_arg( 'settings-updated', '1', $referer );

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'do-sync' ) ) {
			add_settings_error( self::MENU_SLUG, 'nonce_invalid', __( 'Error while performing manual sync. Please try again.', 'required-wp-top-content' ) );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( $referer );
			exit;
		}

		Plugin::sync_ga_data();

		add_settings_error(
			self::MENU_SLUG,
			'authorization_removed',
			__( 'Data successfully synced.', 'required-wp-top-content' ),
			'updated'
		);
		set_transient( 'settings_errors', get_settings_errors(), 30 );

		wp_safe_redirect( $referer );
		exit;
	}

	/**
	 * Retrieves page's hook_suffix.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return string Page's hook_suffix.
	 */
	public function get_page_hook() {
		return $this->page_hook;
	}

	/**
	 * Registers settings sections and fields.
	 *
	 * @since 2.0.0
	 * @access private
	 */
	protected function register_setting_fields() {
		add_settings_section(
			'rplus_topcontent_options_sync',
			__( 'Synchronisation', 'required-wp-top-content' ),
			'__return_null',
			self::MENU_SLUG
		);

		add_settings_field(
			'rplus_topcontent_options_sync_days',
			__( 'Time Range', 'required-wp-top-content' ),
			function() {
				?>
				<input
					name="rplus_topcontent_options_sync_days"
					type="text"
					id="rplus_topcontent_options_sync_days"
					value="<?php echo esc_attr( get_option( 'rplus_topcontent_options_sync_days', '' ) ); ?>"
					placeholder="30" />
				<p class="description">
					<?php _e( 'Sync Google Analytics data of the last X days. Default is 30 days.', 'required-wp-top-content' ); ?>
				</p>
				<?php
			},
			self::MENU_SLUG,
			'rplus_topcontent_options_sync'
		);

		add_settings_field(
			'rplus_topcontent_options_sync_test',
			__( 'Manual Synchronisation', 'required-wp-top-content' ),
			function() {
				printf(
					__( '<a href="%s" class="button button-secondary">Start synchronisation now</a>', 'required-wp-top-content' ),
					wp_nonce_url( admin_url( 'admin-post.php?action=required-do-sync' ), 'do-sync' )
				);
			},
			self::MENU_SLUG,
			'rplus_topcontent_options_sync'
		);
	}
}
