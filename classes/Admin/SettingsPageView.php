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
	 * Data for the view.
	 *
	 * @var object
	 */
	protected $data;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param object $data Data for the view.
	 */
	public function __construct( $data = null ) {
		$this->data = $data;
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

			<?php
			if ( ! $this->data->option_ga_profile ) {
				$this->render_authorization_form();
			} else {
				$this->render_options_form();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Renders the authorization form.
	 *
	 * @since 2.0.0
	 * @access private
	 */
	private function render_authorization_form() {
		?>
		<div class="card google-api-auth">
			<div class="saving-indicator"><span class="spinner"></span><?php _e( 'Saving&hellip;', 'required-wp-top-content' ); ?></div>

			<h2><?php _e( 'Google API Authorization', 'required-wp-top-content' ); ?></h2>

			<div id="google-api-auth-notification-container"></div>

			<form
				id="auth-step-1"
				method="post"
				action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				<?php echo ( $this->data->show_step_1 ? '' : ' class="hidden"' ); ?>>
				<?php wp_nonce_field( 'save-auth-credentials' ); ?>
				<input type="hidden" name="action" value="required-save-auth-credentials" />

				<p>
					<input
						id="custom-api-credentials-default"
						name="api-credentials-type"
						type="radio"
						value="default"
						<?php disabled( ! $this->data->client_adapter->has_default_credentials() ); ?>
						<?php checked( 'default', $this->data->auth_type ); ?> />
					<label for="custom-api-credentials-default"><?php _e( 'Use <strong>default</strong> API credentials', 'required-wp-top-content' ); ?></label>
				</p>
				<p>
					<input
						id="custom-api-credentials-custom"
						name="api-credentials-type"
						type="radio"
						value="custom"
						<?php checked( 'custom', $this->data->auth_type ); ?> />
					<label for="custom-api-credentials-custom"><?php _e( 'Use <strong>custom</strong> API credentials', 'required-wp-top-content' ); ?></label>
				</p>

				<div class="custom-api-credentials<?php echo ( 'custom' === $this->data->auth_type ? '' : ' hidden' ); ?>">
					<p>
						<label for="google-client-api"><?php _e( 'Client ID:', 'required-wp-top-content' ); ?> </label><br />
						<input
							id="google-client-api"
							name="google-client-id"
							class="code long-text"
							type="text"
							value="<?php echo esc_attr( $this->data->client_id ); ?>" />
					</p>

					<p>
						<label for="google-client-api"><?php _e( 'Client Secret:', 'required-wp-top-content' ); ?></label><br />
						<input
							id="google-client-api"
							name="google-client-secret"
							class="code long-text"
							type="text"
							value="<?php echo esc_attr( $this->data->client_secret ); ?>" />
					</p>
				</div>

				<p class="actions">
					<button id="save-auth-data" type="button" class="button">
						<?php _e( 'Save and Continue to Authorization', 'required-wp-top-content' ); ?>
					</button>
				</p>
			</form>

			<form
				id="auth-step-2"
				method="post"
				action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				<?php echo ( $this->data->show_step_2 ? '' : ' class="hidden"' ); ?>>
				<?php wp_nonce_field( 'authorize' ); ?>
				<input type="hidden" name="action" value="required-authorize" />

				<p><?php _e( 'Please click on <em>Get Auth Code</em> and paste the code into the text field below.', 'required-wp-top-content' ); ?></p>

				<p>
					<label for="google-auth-code"><?php _e( 'Auth Code:', 'required-wp-top-content' ); ?></label><br />
					<input
						id="google-auth-code"
						name="google-auth-code"
						class="code long-text-with-button"
						type="text"
						value="" />

					<?php
					$auth_url = '';
					if ( $this->data->client_adapter->has_auth_secrets() ) {
						$auth_url = $this->data->client_adapter->get_client()->createAuthUrl();
					}
					?>
					<a id="get-auth-code" href="<?php echo esc_url( $auth_url ); ?>" target="_blank" class="button"><?php _e( 'Get Auth Code', 'required-wp-top-content' ); ?></a>
				</p>

				<p class="actions">
					<button id="authorize" type="button" class="button">
						<?php _e( 'Authorize', 'required-wp-top-content' ); ?>
					</button>
					<button id="return-to-step-1" type="button" class="button-link">
						<?php _e( 'Return to Credentials', 'required-wp-top-content' ); ?>
					</button>
				</p>
			</form>

			<form
				id="auth-step-3"
				method="post"
				action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				<?php echo ( $this->data->show_step_3 ? '' : ' class="hidden"' ); ?>>
				<?php wp_nonce_field( 'save-profile' ); ?>
				<input type="hidden" name="action" value="required-save-profile" />

				<p><?php _e( 'Please select the profile to use.', 'required-wp-top-content' ); ?></p>

				<p class="google-analytics-profiles">
					<label for="google-analytics-profile">Profiles:</label><br />
					<select id="google-analytics-profile" name="google-analytics-profile">
						<?php
						if ( $this->data->client_adapter->has_auth_token() ) {
							echo $this->data->client_adapter->get_profiles_as_html_options();
						}
						?>
					</select>
				</p>

				<p class="actions">
					<button id="save-profile" type="submit" class="button">
						<?php _e( 'Save Profile', 'required-wp-top-content' ); ?>
					</button>
					<button id="return-to-step-2" type="button" class="button-link">
						<?php _e( 'Return to Auth Code', 'required-wp-top-content' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the settings form.
	 *
	 * @since 2.0.0
	 * @access private
	 */
	private function render_options_form() {
		$deauthorize_url = wp_nonce_url( admin_url( 'admin-post.php?action=required-remove-authorization' ), 'remove-authorization' );
		/* @var $ga_profile \Google_Service_Analytics_Profile */
		$ga_profile = $this->data->ga_profile;
		?>
		<div class="card google-api-auth">
			<h2><?php _e( 'Google API Authorization', 'required-wp-top-content' ); ?></h2>

			<?php if ( $ga_profile ) : ?>
				<table class="auth-profile-data">
					<caption><?php _e( 'Current Profile Data', 'required-wp-top-content' ); ?></caption>
					<thead class="screen-reader-text">
						<tr>
							<th><?php _e( 'Field', 'required-wp-top-content' ); ?></th>
							<th><?php _e( 'Value', 'required-wp-top-content' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<?php _e( 'Name:', 'required-wp-top-content' ); ?>
							</td>
							<td>
								<?php echo esc_html( $ga_profile->getName() ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<?php _e( 'Web Property ID:', 'required-wp-top-content' ); ?>
							</td>
							<td>
								<?php echo esc_html( $ga_profile->getWebPropertyId() ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<?php _e( 'Website URL:', 'required-wp-top-content' ); ?>
							</td>
							<td>
								<a href="<?php echo esc_url( $ga_profile->getWebsiteUrl() ); ?>"><?php echo esc_html( $ga_profile->getWebsiteUrl() ); ?></a>
							</td>
						</tr>
						<tr>
							<td>
								<?php _e( 'Timezone:', 'required-wp-top-content' ); ?>
							</td>
							<td>
								<?php echo esc_html( $ga_profile->getTimezone() ); ?>
							</td>
						</tr>
					</tbody>
				</table>
			<?php endif; ?>

			<a href="<?php echo esc_url( $deauthorize_url ); ?>" class="button"><?php _e( 'Remove Authorization', 'required-wp-top-content' ); ?></a>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
			<?php
			settings_fields( 'required-wp-top-content-options' );

			do_settings_sections( 'rpluswptopcontent' );

			submit_button();
			?>
		</form>
		<?php
	}
}
