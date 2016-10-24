<?php
/**
 * TopContentExcludeMetaBoxView class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content\Admin
 */


namespace Required\WP_Top_Content\Admin;
use Required\WP_Top_Content\TopContentExcludeMeta;
use WP_Post;

/**
 * Class used to render the top content exclude meta box.
 *
 * @since 2.0.0
 */
class TopContentExcludeMetaBoxView implements MetaBoxViewInterface {

	/**
	 * The post instance.
	 *
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * The meta instance.
	 *
	 * @var Meta
	 */
	private $meta;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param \WP_Post              $post The post instance.
	 * @param TopContentExcludeMeta $meta The meta instance.
	 */
	public function __construct( WP_Post $post, TopContentExcludeMeta $meta ) {
		$this->post = $post;
		$this->meta = $meta;
	}

	/**
	 * Renders a meta box.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function render() {
		wp_nonce_field( $this->meta->meta_key . '-nonce' );

		$checkbox_value = get_post_meta( $this->post->ID, $this->meta->meta_key, $this->meta->single );
		?>
		<div>
			<label>
				<input type="hidden" name="rpluswptopcontent" value="no" />
				<input
					name="rpluswptopcontentexclude"
					type="checkbox"
					value="yes"
					id="rpluswptopcontentexclude"
					<?php checked( 'yes', $checkbox_value ); ?> />
				<?php _e( 'Exclude this post from top content lists', 'required-wp-top-content' ); ?>
			</label>
		</div>
		<?php
	}
}
