<?php
/**
 * ShortcodeInterface class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content;

/**
 * Interface for shortcodes.
 *
 * @since 2.0.0
 */
interface ShortcodeInterface {

	/**
	 * Returns the shortcode tag.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return string Tag of the shortcode
	 */
	public function __toString();

	/**
	 * Callback of a shortcode.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param array  $attr    Attributes of the shortcode.
	 * @param string $content Shortcode content.
	 */
	public function callback( $attr, $content = null );
}
