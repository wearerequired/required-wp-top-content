<?php
/**
 * AdminNotice class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content\Admin;

/**
 * Class used to add an admin notice.
 *
 * @since 2.0.0
 */
class AdminNotice {

	/**
	 * Screen of the notice.
	 *
	 * Supported: all, admin, network, user
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $screen = 'admin';

	/**
	 * Type of the notice.
	 *
	 * Supported: info, success, warning, error
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $type = 'info';

	/**
	 * Whether the notice is dismissible.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var bool
	 */
	public $is_dismissible = true;

	/**
	 * Whether the alternative notice styling should be used.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var bool
	 */
	public $use_alt_style = false;

	/**
	 * HTML message of the notice.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $message = '';

	/**
	 * Condition on when to show the notice.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @var callable
	 */
	public $condition = '__return_true';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param object $data Data of the notice.
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			foreach ( get_object_vars( $data ) as $key => $value ) {
				if ( isset( $this->$key ) ) {
					$this->$key = $value;
				}
			}
		}
	}

	/**
	 * Registers the admin notice.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function register() {
		$hook = 'admin_notices';
		if ( in_array( $this->screen, [ 'all', 'network', 'user' ], true ) ) {
			$hook = $this->screen . $hook;
		}

		add_action( $hook, [ $this, 'render' ] );
	}

	/**
	 * Renders the admin notice.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function render() {
		if ( ! is_callable( $this->condition ) || ! call_user_func( $this->condition ) ) {
			return;
		}

		$classes = 'notice';
		if ( in_array( $this->type, [ 'info', 'success', 'warning', 'error' ], true ) ) {
			$classes .=  ' notice-' . $this->type;
		}

		if ( $this->use_alt_style ) {
			$classes .= ' notice-alt';
		}

		if ( $this->is_dismissible ) {
			$classes .= ' is-dismissible';
		}
		?>
		<div class="<?php echo esc_attr( $classes ); ?>">
			<p><?php echo $this->message; ?></p>
		</div>
		<?php
	}
}
