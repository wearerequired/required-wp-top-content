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

		add_action( 'admin_init', [ $this, 'register_settings' ] );

		// Meta.
		$top_content_exclude_meta = new TopContentExcludeMeta();
		$top_content_exclude_meta->register();

		if ( is_admin() ) {
			// Settings page.
			$settings_page = new Admin\SettingsPage();
			add_action( 'admin_menu', [ $settings_page, 'add' ] );
			add_action( 'wp_ajax_required-save-auth-data', [ $settings_page, 'ajax_save_auth_data' ] );
			add_action( 'wp_ajax_required-google-authorize', [ $settings_page, 'ajax_google_authorize' ] );
			add_action( 'admin_post_save-profile', [ $settings_page, 'save_profile' ] );
			add_action( 'admin_post_required-remove-authorization', [ $settings_page, 'remove_authorization' ] );

			// Plugin action link for settings page.
			$plugin_action_links = new Admin\PluginActionLinks( PLUGIN_BASENAME );
			$plugin_action_links->add_admin_page_link(
				$settings_page,
				__( 'Settings', 'required-wp-top-content' )
			);
			$plugin_action_links->register();

			// Posts list table column.
			$pageviews_posts_list_table_column = new Admin\PageViewsPostsListTableColumn();
			$pageviews_posts_list_table_column->register();

			// Meta box.
			$top_content_exclude_meta_box = new Admin\TopContentExcludeMetaBox( $top_content_exclude_meta );
			add_action( 'add_meta_boxes', [ $top_content_exclude_meta_box, 'add' ], 10, 2 );
			add_action( 'save_post', [ $top_content_exclude_meta_box, 'save_meta' ], 10, 2 );
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
			if ( is_array( $value ) ) {
				return $value;
			}

			// The submitted value is not valid, set default option to an empty array.
			return [];
		};
		$options_ga_access_token->register();

		$options_ga_access_code = new Setting( $options_group, 'rplus_topcontent_options_ga_access_code' );
		$options_ga_access_code->sanitize_callback = 'sanitize_text_field';
		$options_ga_access_code->register();

		$options_ga_propertyid = new Setting( $options_group, 'rplus_topcontent_options_ga_propertyid' );
		$options_ga_propertyid->sanitize_callback = 'sanitize_text_field';
		$options_ga_propertyid->register();

		// Unused
		//$options_ga_devkey = new Setting( $options_group, 'rplus_topcontent_options_ga_devkey' );
		//$options_ga_devkey->register();

		$options_group = 'required-wp-top-content-options';

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

		$ga_propertyid = get_option( 'rplus_topcontent_options_ga_propertyid', 0 );
		if ( ! $ga_propertyid ) {
			return;
		}

		$days = get_option( 'rplus_topcontent_options_sync_days', 30 );
		$from = date( 'Y-m-d', strtotime( "-$days day" ) );
		$to = date( 'Y-m-d' );
		$data = $client_adapter->get_page_views( $ga_propertyid, $from, $to );

		$syncer = new SyncGoogleAnalyticsDataWithPosts( $data );
		$syncer->process();
		$syncer->cleanup();
	}
}
