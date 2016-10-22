<?php
/**
 * Plugin class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content\Admin
 */

namespace Required\WP_Top_Content\Admin;


/**
 * Class used to add custom action links displayed in the Plugins list table.
 *
 * @since 2.0.0
 */
class PluginActionLinks {

	/**
	 * Holds the path to the plugin file.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @var array
	 */
	private $plugin_file;

	/**
	 * Holds the custom action links.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @var array
	 */
	private $action_links = [];

	/**
	 * Cnstructor.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Adds an action link to the list of custom action links.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param strin $key   The key of the action link.
	 * @param string $link The HTML link of the action link.
	 * @return PluginActionLinks This instance.
	 */
	public function add_link( $key, $link ) {
		$this->action_links[ $key ] = $link;
		return $this;
	}

	/**
	 * Registers the filter callback.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function register() {
		add_filter( 'plugin_action_links_' . $this->plugin_file, [ $this, 'add_action_links' ], 10, 1 );
	}

	/**
	 * Adds the list of action links.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param array $actions An array of plugin action links.
	 * @return array An array of plugin action links.
	 */
	public function add_action_links( $actions ) {
		if ( ! $this->action_links ) {
			return $actions;
		}

		foreach ( $this->action_links as $action_key => $action_link ) {
			$actions[ $action_key ] = $action_link;
		}

		return $actions;
	}
}
