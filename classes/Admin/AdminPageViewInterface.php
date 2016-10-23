<?php
/**
 * AdminPageViewInterface class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content\Admin
 */

namespace Required\WP_Top_Content\Admin;

/**
 * Interface for admin page views.
 *
 * @since 2.0.0
 */
interface AdminPageViewInterface {

	/**
	 * Renders an admin page.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function render();
}
