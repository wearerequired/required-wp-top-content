<?php
/**
 * SettingsPageView class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content\Admin
 */

namespace Required\WP_Top_Content\Admin;

/**
 * Class used to render a settings page.
 *
 * @since 2.0.0
 */
class SettingsPageView implements AdminPageViewInterface {

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function __construct() {
	}

	/**
	 * Renders the settings admin page.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function render() {
		?>
		<div class="wrap">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

			<form method="post" action="<?php esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( 'rpluswptopcontent-options' );

				do_settings_sections( 'rpluswptopcontent' );

				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
