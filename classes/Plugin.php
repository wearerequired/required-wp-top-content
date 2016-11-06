<?php
/**
 * Plugin class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content;

/**
 * Manages hook registrations.
 *
 * @since 2.0.0
 */
class Plugin {

	const CRON_HOOK = 'rplus_top_content_cron_hook';

	/**
	 * Holds the singleton instance.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @var Plugin
	 */
	private static $instance;

	/**
	 * Returns always the same instance of this plugin.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( ! ( self::$instance instanceof Plugin ) ) {
			self::$instance = new Plugin();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access private
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initializes the plugin.
	 *
	 * @since 2.0.0
	 * @access private
	 */
	private function init() {
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

	/**
	 * Initializes the plugin.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function plugins_loaded() {
		// Widgets.
		add_action( 'widgets_init', [ __NAMESPACE__ . '\TopContentWidget', 'register' ] );

		// Options and sanitize callbacks.
		add_action( 'admin_init', [ $this, 'register_settings' ] );

		// Cron job for data sync.
		add_action( self::CRON_HOOK, [ self::class, 'sync_ga_data' ] );

		// Shortcode.
		$top_content_shortcode = new TopContentShortcode( 'rplus-topcontent' );
		add_shortcode( (string) $top_content_shortcode, [ $top_content_shortcode, 'callback' ] );

		// Meta.
		$top_content_exclude_meta = new TopContentExcludeMeta();
		$top_content_exclude_meta->register();

		if ( is_admin() ) {
			// Settings page.
			$settings_page = new Admin\SettingsPage();
			add_action( 'admin_menu', [ $settings_page, 'add' ] );
			add_action( 'wp_ajax_required-save-auth-data', [ $settings_page, 'ajax_save_auth_data' ] );
			add_action( 'wp_ajax_required-google-authorize', [ $settings_page, 'ajax_google_authorize' ] );
			add_action( 'admin_post_required-save-profile', [ $settings_page, 'save_profile' ] );
			add_action( 'admin_post_required-remove-authorization', [ $settings_page, 'remove_authorization' ] );
			add_action( 'admin_post_required-do-sync', [ $settings_page, 'do_manual_sync' ] );

			// Plugin action link for settings page.
			$plugin_action_links = new Admin\PluginActionLinks( PLUGIN_BASENAME );
			$plugin_action_links->add_admin_page_link(
				$settings_page,
				__( 'Settings', 'required-wp-top-content' )
			);
			$plugin_action_links->register();

			$client_adapter = new GoogleClientAdapter();
			if ( $client_adapter->has_auth_token() ) {
				// Posts list table column.
				$pageviews_posts_list_table_column = new Admin\PageViewsPostsListTableColumn();
				$pageviews_posts_list_table_column->register();

				// Meta box.
				$top_content_exclude_meta_box = new Admin\TopContentExcludeMetaBox( $top_content_exclude_meta );
				add_action( 'add_meta_boxes', [ $top_content_exclude_meta_box, 'add' ], 10, 2 );
				add_action( 'save_post', [ $top_content_exclude_meta_box, 'save_meta' ], 10, 2 );
			} else {
				$admin_notice = new Admin\AdminNotice();
				$admin_notice->message = sprintf(
					__( 'required+ WordPress Top Content Plugin is not properly configured. Please go to the <a href="%s">settings page</a> to authorize this Plugin.','rpluswptopcontent' ),
					admin_url( 'options-general.php?page=' . $settings_page::MENU_SLUG )
				);
				$admin_notice->condition = function() {
					$screen = get_current_screen();
					return 'plugins' === $screen->id;
				};
				$admin_notice->register();
			}
		}
	}

	/**
	 * Registers options with their sanitize callbacks.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function register_settings() {
		$options_group = 'required-wp-top-content-options-ga';

		$options_ga_client_type = new Setting( $options_group, 'rplus_topcontent_options_ga_auth_type' );
		$options_ga_client_type->sanitize_callback = function( $value ) {
			if ( in_array( $value, [ 'default', 'custom' ], true ) ) {
				return $value;
			}

			// The submitted value is not valid, set default option to 'default'.
			return 'default';
		};
		$options_ga_client_type->register();

		$options_ga_client_id = new Setting( $options_group, 'rplus_topcontent_options_ga_client_id' );
		$options_ga_client_id->sanitize_callback = 'sanitize_text_field';
		$options_ga_client_id->register();

		$options_ga_client_secret = new Setting( $options_group, 'rplus_topcontent_options_ga_client_secret' );
		$options_ga_client_secret->sanitize_callback = 'sanitize_text_field';
		$options_ga_client_secret->register();

		$options_ga_access_token = new Setting( $options_group, 'rplus_topcontent_options_ga_access_token' );
		$options_ga_access_token->sanitize_callback = function( $value ) {
			if ( ! is_array( $value ) ) {
				return [];
			}

			$new_value = array_map( 'sanitize_text_field', $value );
			$new_value = array_filter( $new_value );

			if (
				count( $new_value ) !== 5 ||
				empty( $new_value['access_token'] ) ||
				empty( $new_value['token_type'] ) ||
				empty( $new_value['expires_in'] ) ||
				empty( $new_value['refresh_token'] ) ||
				empty( $new_value['created'] )
			) {
				return [];
			}

			$new_value['expires_in'] = (int) $new_value['expires_in'];
			$new_value['created']    = (int) $new_value['created'];

			return $new_value;
		};
		$options_ga_access_token->register();

		$options_ga_access_code = new Setting( $options_group, 'rplus_topcontent_options_ga_access_code' );
		$options_ga_access_code->sanitize_callback = 'sanitize_text_field';
		$options_ga_access_code->register();

		$options_ga_propertyid = new Setting( $options_group, 'rplus_topcontent_options_ga_propertyid' );
		$options_ga_propertyid->sanitize_callback = 'sanitize_text_field';
		$options_ga_propertyid->register();

		$options_ga_profile = new Setting( $options_group, 'rplus_topcontent_options_ga_profile' );
		$options_ga_profile->sanitize_callback = function( $value ) {
			if ( ! is_array( $value ) ) {
				return [];
			}

			$new_value = array_map( 'sanitize_text_field', $value );
			$new_value = array_filter( $new_value );

			if (
				count( $new_value ) !== 3 ||
				empty( $new_value['account-id'] ) ||
				empty( $new_value['web-property-id'] ) ||
				empty( $new_value['profile-id'] )
			) {
				return [];
			}

			return $new_value;
		};
		$options_ga_profile->register();

		$options_sync_lastrun = new Setting( $options_group, 'rplus_topcontent_options_sync_lastrun' );
		$options_sync_lastrun->sanitize_callback = 'sanitize_text_field';
		$options_sync_lastrun->register();

		$options_group = 'required-wp-top-content-options-public';

		$options_sync_days = new Setting( $options_group, 'rplus_topcontent_options_sync_days' );
		$options_sync_days->sanitize_callback = function( $value ) {
			$value = (int) $value;

			if ( 0 === $value ) {
				return 30;
			}

			return $value;
		};
		$options_sync_days->register();
	}

	/**
	 * Syncs Google Analytics data with posts.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function sync_ga_data() {
		$client_adapter = new GoogleClientAdapter();

		if ( ! $client_adapter->has_auth_token() ) {
			return;
		}

		$ga_profile = get_option( 'rplus_topcontent_options_ga_profile', [] );
		if ( empty( $ga_profile['profile-id'] ) ) {
			return;
		}

		$days = get_option( 'rplus_topcontent_options_sync_days', 30 );
		$from = date( 'Y-m-d', strtotime( "-$days day" ) );
		$to = date( 'Y-m-d' );
		$data = $client_adapter->get_page_views( $ga_profile['profile-id'], $from, $to );

		$syncer = new SyncGoogleAnalyticsDataWithPosts( $data );
		$syncer->process();
		$syncer->cleanup();

		update_option( 'rplus_topcontent_options_sync_lastrun', date( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Called when plugin has been activated.
	 *
	 * Schedules a periodic event for data sync.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function activated() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Called when plugin has been deactivated.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function deactivated() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}
}
