<?php
/**
 * GoogleClientAdapter class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content;
use Exception;
use Google_Client;
use Google_Service_Analytics;

/**
 * Class used to adapt the Google's API client.
 *
 * @since 2.0.0
 */
class GoogleClientAdapter {

	const AUTH_CLIENT_ID = '543844416224-dns07iord4p0cncd617h44ggjtuvb0lt.apps.googleusercontent.com';
	const AUTH_CLIENT_SECRET = 'SvBZVG12TVt4bxDnZb8hmDf4';

	/**
	 * Google's client API.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @var \Google_Client
	 */
	private $client;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function __construct() {
		$this->client = new Google_Client();
		$this->client->setApplicationName( 'WordPress Plugin - required-wp-top-content' );
		$this->client->setRedirectUri( 'urn:ietf:wg:oauth:2.0:oob' );
		$this->client->setScopes( 'https://www.googleapis.com/auth/analytics.readonly' );
		$this->client->setAccessType( 'offline' );

		$this->set_access_token();

		$type = get_option( 'rplus_topcontent_options_ga_auth_type' );
		if ( 'custom' === $type ) {
			$this->use_user_credentials();
		} else {
			$this->use_default_credentials();
		}

		$this->service = new Google_Service_Analytics( $this->client );
	}


	/**
	 * Returns the Google_Client instance.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return \Google_Client
	 */
	public function get_client() {
		return $this->client;
	}

	/**
	 * Sets client ID and secret based on database values.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function use_user_credentials() {
		$this->client->setClientId( get_option( 'rplus_topcontent_options_ga_client_id', '' ) );
		$this->client->setClientSecret( get_option( 'rplus_topcontent_options_ga_client_secret', '' ) );
	}

	/**
	 * Sets client ID and secret based on class constants.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function use_default_credentials() {
		$this->client->setClientId( self::AUTH_CLIENT_ID );
		$this->client->setClientSecret( self::AUTH_CLIENT_SECRET );
	}

	/**
	 * Retrieves a list of Google Analytics accounts.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return \Google_Service_Analytics_Accounts List of Google Analytics accounts.
	 */
	public function get_accounts() {
		return $this->service->management_accounts->listManagementAccounts();
	}

	/**
	 * Retrieves a list of Google Analytics profiles.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $account_id      An account ID.
	 * @param string $web_property_id A web property ID or '~all' for all.
	 * @return \Google_Service_Analytics_Profiles List of Google Analytics profiles.
	 */
	public function get_profiles( $account_id, $web_property_id = '~all' ) {
		return $this->service->management_profiles->listManagementProfiles( $account_id, $web_property_id );
	}

	/**
	 * Generates HTML markup for a select form for all Google Analytics profiles.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return string HTML markup with '<optgroup` and `<option>`.
	 */
	public function get_profiles_as_html_options() {
		$cache_key = 'required-profiles-as-html-options';
		$cached = get_transient( 'required-profiles-as-html-options' );

		if ( $cached ) {
			return $cached;
		}

		$html = '';
		$accounts = $this->get_accounts();
		foreach ( $accounts as $account ) {
			/** @var $account \Google_Service_Analytics_Account */
			$html .= sprintf(
				'<optgroup label="%s">',
				esc_attr( $account->getName() )
			);

			$profiles = $this->get_profiles( $account->id );
			foreach ( $profiles as $profile ) {
				/** @var $profile \Google_Service_Analytics_Profile */
				$html .= sprintf(
					'<option value="%s">%s</option>',
					esc_attr( $profile->getId() ),
					esc_html( sprintf(
						'%s (%s, %s)',
						$profile->getName(),
						$profile->getWebPropertyId(),
						$profile->getWebsiteUrl()
					) )
				);
			}

			$html .= '</optgroup>';
		}

		set_transient( $cache_key, $html, HOUR_IN_SECONDS );

		return $html;
	}

	/**
	 * Sets the access token and refreshes the toke if it's expired.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return bool True on success, false otherwise.
	 */
	public function set_access_token() {
		$access_token = get_option( 'rplus_topcontent_options_ga_access_token' );
		if ( ! $access_token ) {
			return false;
		}

		try {
			$this->client->setAccessToken( $access_token );

			if ( $this->client->isAccessTokenExpired() ) {
				$new_access_token = $this->client->refreshToken( $access_token['refresh_token'] );
				update_option( 'rplus_topcontent_options_ga_access_token', $new_access_token );
			}
		} catch ( Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether the client has a valid auth token.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return bool True on success, false otherwise.
	 */
	public function has_auth_token() {
		return ! empty( $this->client->getAccessToken() );
	}

	/**
	 * Whether the client has valid secrets.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return bool True on success, false otherwise.
	 */
	public function has_auth_secrets() {
		return ! empty( $this->client->getClientId() ) && ! empty( $this->client->getClientSecret() );
	}
}
