<?php
/**
 * WidgetInterface class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content;

/**
 * Interface for widgets.
 *
 * @since 2.0.0
 */
interface WidgetInterface {

	/**
	 * Registers a new widget.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function register();
}
