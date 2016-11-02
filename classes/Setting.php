<?php
/**
 * Setting class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content;
use WP_Error;

/**
 * Class used to register a setting.
 *
 * @since 2.0.0
 */
class Setting {

	/**
	 * The settings group name.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $option_group;

	/**
	 * The name of an option to sanitize and save.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $option_name;

	/**
	 * The type of data associated with this setting.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $type = 'string';

	/**
	 * A description of the data attached to this setting.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * A callback function that sanitizes the option's value.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var callable
	 */
	public $sanitize_callback = null;

	/**
	 * Whether data associated with this setting should be included in the REST API.
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
	 * @param string $option_group The settings group name.
	 * @param string $option_name  The name of an option to sanitize and save.
	 */
	public function __construct( $option_group, $option_name ) {
		$this->option_group = $option_group;
		$this->option_name = $option_name;
	}

	/**
	 * Registers a new setting.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function register() {
		if ( ! $this->option_group || ! $this->option_name ) {
			return new WP_Error( 'missing_data' );
		}

		global $wp_version;

		if ( version_compare( $wp_version, '4.7-beta', '=>' ) ) {
			register_setting(
				$this->option_group,
				$this->option_name,
				$this->get_args()
			);
		} else {
			register_setting(
				$this->option_group,
				$this->option_name,
				$this->sanitize_callback
			);
		}
	}

	/**
	 * Retrieves the data used to describe the setting when registered.
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
			'sanitize_callback' => $this->sanitize_callback,
			'show_in_rest'      => $this->show_in_rest,
		];
	}
}
