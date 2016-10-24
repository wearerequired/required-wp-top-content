<?php
/**
 * Meta class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content;

/**
 * Class used to register the top content exclude meta.
 *
 * @since 2.0.0
 */
class TopContentExcludeMeta extends Meta {

	/**
	 * Type of object this meta is registered to.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $object_type = 'post';

	/**
	 * Meta key to register.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $meta_key = 'topcontent_exclude';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 */
	public function __construct() {
		$this->sanitize_callback = [ $this, 'sanitize_callback' ];
	}

	/**
	 * Sanitizes the exclude post meta value.
	 *
	 * @param string $value
	 * @return string
	 */
	public function sanitize_callback( $value ) {
		if ( in_array( $value, array( 'yes', 'no' ), true ) ) {
			return $value;
		}

		// The submitted value is not valid, set default option to 'no'.
		return 'no';
	}
}
