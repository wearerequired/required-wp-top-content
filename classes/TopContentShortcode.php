<?php
/**
 * TopContentShortcode class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content;

/**
 * Class used to implement the Top Content shortcode.
 *
 * @since 2.0.0
 */
class TopContentShortcode implements ShortcodeInterface {

	/**
	 * Tag of the shortcode.
	 *
	 * @var string
	 */
	protected $tag;

	/**
	 * Constructor.
	 *
	 * @param string $tag Tag of the shortcode.
	 */
	public function __construct( $tag ) {
		$this->tag = $tag;
	}

	/**
	 * Returns the shortcode tag.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return string Tag of the shortcode
	 */
	public function __toString() {
		return $this->tag;
	}

	/**
	 * Callback of the shortcode.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param array  $attr {
	 *     Attributes of the shortcode.
	 *
	 *     @type int    $count     Number of items to show.
	 *     @type string $posttypes Post types to show.
	 *     @type string $template  Template file.
	 * }
	 * @param string $content Shortcode content.
	 * @return string HTML content to display the shortcode.
	 */
	public function callback( $attr, $content = null ) {
		$atts = shortcode_atts( [
			'count'     => 5,
			'posttypes' => 'post,page',
			'template'  => 'rplus-wp-top-content.php'
		], $attr, $this->tag );


		// Sanitize post types attribute.
		$post_types = explode( ',', $atts['posttypes'] );
		if ( ! is_array( $post_types ) || empty( $post_types ) ) {
			$post_types = [ 'post', 'page' ];
		}

		// Sanitize count attribute.
		$count = (int) $atts['count'];
		if ( ! $count ) {
			$count = 5;
		}

		ob_start();

		TopContentAPI::render_top_content( $post_types, $count, $atts['template'] );

		return ob_get_clean();
	}
}
