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
		if ( is_admin() ) {
			$plugin_action_links = new Admin\PluginActionLinks( PLUGIN_BASENAME );
			$plugin_action_links->add_link(
				'setting',
				'<a href="' . admin_url( ) . '">' . __( 'Settings', 'required-wp-top-content' ) . '</a>'
			);
			$plugin_action_links->register();

			$settings_page = new Admin\SettingsPage();
			add_action( 'admin_menu', [ $settings_page, 'add' ] );

			$pageviews_posts_list_table_column = new Admin\PageViewsPostsListTableColumn();
			$pageviews_posts_list_table_column->register();
		}

		add_action( 'widgets_init', [ __NAMESPACE__ . '\TopContentWidget', 'register' ] );
	}
}
