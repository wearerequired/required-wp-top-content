<?php
/**
 * AdminPageInterface class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content\Admin
 */

namespace Required\WP_Top_Content\Admin;

/**
 * Interface for admin pages.
 *
 * @since 2.0.0
 */
interface AdminPageInterface {

	/**
	 * Adds a new menu for an admin page to the admin menu.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function add();
}
