<?php
/**
 * MetaBoxInterface class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content\Admin
 */

namespace Required\WP_Top_Content\Admin;
use WP_Post;

/**
 * Interface for meta boxes.
 *
 * @since 2.0.0
 */
interface MetaBoxInterface {

	/**
	 * Adds a new meta box.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string  $post_type Post type.
	 * @param WP_Post $post      Post object.
	 */
	public function add( $post_type, WP_Post $post );
}
