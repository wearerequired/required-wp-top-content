<?php
/**
 * TopContentExcludeMetaBox class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content\Admin
 */

namespace Required\WP_Top_Content\Admin;
use Required\WP_Top_Content\TopContentExcludeMeta;
use WP_Post;

/**
 * Class used to register the top content exclude meta box.
 *
 * @since 2.0.0
 */
class TopContentExcludeMetaBox implements MetaBoxInterface {

	/**
	 * List of post types the meta box is for.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var array
	 */
	public $post_types = [
		'post',
	];

	/**
	 * The context within the screen where the boxes should display.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $context = 'side';

	/**
	 * The priority within the context where the boxes should show.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $priority = 'default';

	/**
	 * Data that should be set as the $args property of the box array
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var array
	 */
	public $callback_args = null;

	/**
	 * The meta instance.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @var Meta
	 */
	private $meta;

	/**
	 * TopContentExcludeMetaBox constructor.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param TopContentExcludeMeta $meta The meta instance.
	 */
	public function __construct( TopContentExcludeMeta $meta ) {
		$this->meta = $meta;
	}

	/**
	 * Adds a new meta box.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string  $post_type Post type.
	 * @param \WP_Post $post      Post object.
	 */
	public function add( $post_type, WP_Post $post ) {
		if ( ! in_array( $post_type, $this->post_types, true ) ) {
			return;
		}

		$view = new TopContentExcludeMetaBoxView( $post, $this->meta );

		add_meta_box(
			'required-wp-top-content_' . $this->meta->meta_key,
			__( 'Top Content', 'rpluswptopcontent' ),
			[ $view, 'render' ],
			$this->post_types,
			$this->context,
			$this->priority,
			$this->callback_args
		);
	}
}
