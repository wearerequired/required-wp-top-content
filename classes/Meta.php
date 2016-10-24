<?php
/**
 * Meta class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content;
use WP_Error;

/**
 * Class used to register meta.
 *
 * @since 2.0.0
 */
class Meta {

	/**
	 * Type of object this meta is registered to.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $object_type;

	/**
	 * Meta key to register.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $meta_key;

	/**
	 * The type of data associated with this meta key.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $type = 'string';

	/**
	 * A description of the data attached to this meta key.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * Whether the meta key has one value per object, or an array of values per object.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var bool
	 */
	public $single = false;


	/**
	 * A function or method to call when sanitizing `$meta_key` data.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $sanitize_callback = null;

	/**
	 * A function or method to call when performing edit_post_meta,
	 * add_post_meta, and delete_post_meta capability checks.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $auth_callback = null;

	/**
	 * Whether data associated with this meta key can be considered public.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var bool
	 */
	public $show_in_rest = false;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $object_type Type of object this meta is registered to.
	 * @param string $meta_key    Meta key to register.
	 */
	public function __construct( $object_type, $meta_key ) {
		$this->object_type = $object_type;
		$this->meta_key = $meta_key;
	}

	/**
	 * Registers a new meta key.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function register() {
		if ( ! $this->object_type || ! $this->meta_key ) {
			return new WP_Error( 'missing_data' );
		}

		register_meta(
			$this->object_type,
			$this->meta_key,
			$this->get_args()
		);
	}

	/**
	 * Retrieves the data used to describe the meta key when registered.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_args() {
		return [
			'type'              => $this->type,
			'description'       => $this->description,
			'single'            => $this->single,
			'sanitize_callback' => $this->sanitize_callback,
			'auth_callback'     => $this->auth_callback,
			'show_in_rest'      => $this->show_in_rest,
		];
	}
}
