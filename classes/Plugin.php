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

		// Meta.
		$top_content_exclude_meta = new TopContentExcludeMeta();
		$top_content_exclude_meta->register();

		if ( is_admin() ) {
			// Plugin action link for settings page.
			$plugin_action_links = new Admin\PluginActionLinks( PLUGIN_BASENAME );
			$plugin_action_links->add_link(
				'setting',
				'<a href="' . admin_url( ) . '">' . __( 'Settings', 'required-wp-top-content' ) . '</a>'
			);
			$plugin_action_links->register();

			// Settings page.
			$settings_page = new Admin\SettingsPage();
			add_action( 'admin_menu', [ $settings_page, 'add' ] );

			// Posts list table column.
			$pageviews_posts_list_table_column = new Admin\PageViewsPostsListTableColumn();
			$pageviews_posts_list_table_column->register();

			// Meta box.
			$top_content_exclude_meta_box = new Admin\TopContentExcludeMetaBox( $top_content_exclude_meta );
			add_action( 'add_meta_boxes', [ $top_content_exclude_meta_box, 'add' ], 10, 2 );
		}
	}
}
