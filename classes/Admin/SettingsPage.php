<?php
/**
 * SettingsPage class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content\Admin
 */

namespace Required\WP_Top_Content\Admin;

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
		$view = new SettingsPageView();

		$this->page_hook = add_options_page(
			__( 'WP Top Content', 'required-wp-top-content' ),
			__( 'WP Top Content', 'required-wp-top-content' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			[ $view, 'render' ]
		);
	}
}
