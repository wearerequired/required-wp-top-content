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

	/**
	 * Saves the value of the meta box.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param int     $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_meta( $post_id, WP_Post $post ) {
		if ( ! in_array( $post->post_type, $this->post_types, true ) ) {
			return;
		}

		$nonce_key = $this->meta->meta_key . '-nonce';
		if ( ! isset( $_POST[ $nonce_key ] ) || ! wp_verify_nonce( $_POST[ $nonce_key ], 'save-' . $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$value = '';
		if ( isset( $_POST[ $this->meta->meta_key ] ) ) {
			$value = $_POST[ $this->meta->meta_key ];
		}

		update_post_meta( $post_id, $this->meta->meta_key, $value );
	}
}
