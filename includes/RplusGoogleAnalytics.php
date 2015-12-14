<?php
/**
 * required+ WP Top Content
 *
 * @package   required-wp-top-content
 * @author    Stefan Pasch <stefan@required.ch>
 * @license   GPL-2.0+
 * @link      http://required.ch
 * @copyright 2014 required gmbh
 */

/**
 * Google Analytics functions
 *
 * @package   required-wp-top-content
 * @author    Stefan Pasch <stefan@required.ch>
 * @license   GPL-2.0+
 * @link      http://required.ch
 * @copyright 2014 required gmbh
 */
class RplusGoogleAnalytics {

    /**
     * Load google analytics libraries and return service object
     *
     * @return Google_Client
     */
    public static function get_google_api_client() {

        // check if all needed options are set
        $apikey = get_option( 'rplus_topcontent_options_ga_devkey' );
        $clientid = get_option( 'rplus_topcontent_options_ga_client_id' );
        $clientsecret = get_option( 'rplus_topcontent_options_ga_client_secret' );
        if ( empty( $apikey ) || empty( $clientid ) || empty( $clientsecret ) ) {
            return false;
        }

        // check if we're configured with composer
        $composer_autoloader = plugin_dir_path( __DIR__ ) . 'vendor' . DIRECTORY_SEPARATOR . 'google' . DIRECTORY_SEPARATOR . 'google-api-php-client' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Google' . DIRECTORY_SEPARATOR . 'autoload.php';
        if ( file_exists( $composer_autoloader ) ) {

            require_once $composer_autoloader;

        } else {

            // Google API Library path
            $lib_dir = plugin_dir_path( __DIR__ ) . 'includes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'google-api' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

            // update include path
            set_include_path( get_include_path() . PATH_SEPARATOR . $lib_dir );

            require_once $lib_dir . 'Google' . DIRECTORY_SEPARATOR . 'Client.php';
            require_once $lib_dir . 'Google' . DIRECTORY_SEPARATOR . 'Service' . DIRECTORY_SEPARATOR . 'Analytics.php';

        }

        $client = new Google_Client();
        $client->setApplicationName( "WordPress Plugin - required-wp-top-content" );
        $client->setDeveloperKey( $apikey );
        $client->setClientId( $clientid );
        $client->setClientSecret( $clientsecret );
        $client->setScopes( 'https://www.googleapis.com/auth/analytics.readonly' );
        $client->setAccessType( 'offline' );
        $client->setRedirectUri( esc_url( add_query_arg( 'page', RplusWpTopContent::get_instance()->get_plugin_slug(), admin_url( 'options-general.php' ) ) ) );

        return $client;

    }

    /**
     * Get Google API access token
     *
     * @return bool|mixed|void
     */
    public static function get_google_api_access_token() {

        $access_token = get_option( 'rplus_topcontent_options_ga_access_token' );

        if ( ! $access_token || empty ( $access_token ) || $access_token == 'null' ) {

            return false;

        }

        try {

            $client = self::get_google_api_client();
            if ( false === $client )
                return false;

            $client->setAccessToken( $access_token );

            if ( $client->isAccessTokenExpired() ) {

                $refresh = json_decode( $access_token );
                $client->refreshToken( $refresh->refresh_token );

            }

        } catch ( Exception $e ) {

            return false;

        }

        return $access_token;

    }

    /**
     * Get Google Analytics accounts (cached, when exists)
     *
     * @param Google_Service_Analytics $analytics
     * @return Google_Service_Analytics_Accounts|mixed
     */
    public static function google_get_accounts( Google_Service_Analytics $analytics ) {

        $tkey = 'rplus-wp-topcontent-ga-accounts-list';
        $cached = get_transient( $tkey );

        if ( $cached ) {

            return $cached;

        }

        $accounts = $analytics->management_accounts->listManagementAccounts();

        // fetch accounts every hour
        set_transient( $tkey, $accounts, 3600 );

        return $accounts;

    }

    /**
     * Get Profiles of defined account (chached, when exists)
     *
     * @param Google_Service_Analytics $analytics
     * @param $account_id
     * @param string $filter
     * @return Google_Service_Analytics_Profiles
     */
    public static function google_get_profiles( Google_Service_Analytics $analytics, $account_id, $filter = '~all' ) {

        $tkey = 'rplus-wp-topcontent-ga-profiles-'.$account_id.'-'.$filter;
        $cached = get_transient( $tkey );

        if ( $cached ) {
            return $cached;
        }

        $profiles = $analytics->management_profiles->listManagementProfiles( $account_id, $filter );

        // expires every hour
        set_transient( $tkey, $profiles, 3600 );

        return $profiles;

    }

    /**
     * Get the the latest visitor and pageview data of analytics and saves to local post's meta data
     */
    public static function google_sync_ga_data( $debugoutput = false ) {

        $access_token = self::get_google_api_access_token();

        if ( ! $access_token || empty( $access_token ) ) {

            /* debug */ if ( true === $debugoutput ) {
                _e( '<div class="error below-h2"><p>Synchronisation error, no or invalid token!</p></div>', 'rpluswptopcontent' );
            } /* /debug */
            return;

        }

        $client = self::get_google_api_client();
        $client->setAccessToken( $access_token );
        $analytics = new Google_Service_Analytics( $client );

        // set default, when option is not set
        $days = get_option( 'rplus_topcontent_options_sync_days' );
        if ( ! is_numeric( $days ) || empty( $days ) ) {
            $days = 30;
        }

        $from = date( 'Y-m-d', strtotime( "-$days day" ) );
        $to = date('Y-m-d');

        /* debug */ if ( true === $debugoutput ) {
            printf( __( '<div class="update below-h2"><p>Starting synchronisation, fetching data from <strong>%s</strong> to <strong>%s</strong></p></div>', 'rpluswptopcontent' ), $from, $to );
        } /* /debug */

        // remove existing pageviews for all contents
        delete_post_meta_by_key( 'rplus_top_content_pageviews' );
        delete_post_meta_by_key( 'rplus_top_content_visits' );

        $data = $analytics->data_ga->get( 'ga:'.get_option('rplus_topcontent_options_ga_propertyid'), $from, $to, 'ga:pageviews,ga:visits', array(
            'dimensions' => 'ga:pagePath',
            'sort' => '-ga:pageviews,ga:pagePath'
        ) );

        $pages = $data->getRows();

        /* debug */ if ( true === $debugoutput ) {
            printf( __( '<div class="update below-h2"><p>Found %d pages to sync.</p></div>', 'rpluswptopcontent' ), count( $pages ) );
        } /* /debug */

        if ( ! count( $pages ) ) {

            return;

        }

        /* debug */ if ( $debugoutput ) {
            echo '<table class="widefat"><tr><td class="row-title">'.__( 'URL', 'rpluswptopcontent' ).'</td><td class="row-title">'.__( 'Pageviews / Visits', 'rpluswptopcontent' ).'</td><td class="row-title">'.__( 'Related WordPress Post', 'rpluswptopcontent' ).'</td></tr>';
        } /* /debug */

		$updated_posts = array();

        foreach ( $pages as $page ) {

            $url = rtrim ( $page[0], '/' );
            $postid = url_to_postid( $url );

			// save for later updating all except this ones
			$updated_posts[] = $postid;

            /* debug */ if ( $debugoutput ) {
                $wp_post = __( 'No related post/page found', 'rpluswptopcontent' );
                if ( ! empty( $postid ) ) {
                    $wp_post = '<a href="'.site_url( $url ).'" target="_blank">'.get_the_title( $postid ).'</a>';
                }
                echo  '<tr>'
                    .'<td valign="top" style="vertical-align: top;">'.$page[0].'</td>'
                    .'<td valign="top" style="vertical-align: top; text-align: center;">'.$page[1].' / '.$page[2].'</td>'
                    .'<td valign="top" style="vertical-align: top;">'.$wp_post.'</td>'.
                    '</tr>';
            } /* /debug */

            if ( empty( $postid ) ) {

                // page url could not be related to a wordpress post
                continue;

            }

            // add pageviews/visits to post meta data
            update_post_meta( $postid, 'rplus_top_content_pageviews', $page[1] );
            update_post_meta( $postid, 'rplus_top_content_visits', $page[2] );

        }

		/**
		 * - Get all post ids that where not updated with google analytics data
		 * - remove the meta key/values
		 * - add meta keys with value 0 (needed cause of some wp_query which will order by that etc.)
		 */
		global $wpdb;
		$results = $wpdb->get_results( "SELECT ID FROM {$wpdb->posts} WHERE post_status IN ('publish', 'ueberpruefung') AND post_type = 'post' AND ID NOT IN (" . implode( ',', $updated_posts ) . ")" );
		$post_ids = array();
		foreach ( $results as $r ) {
			$post_ids[] = $r->ID;

			$wpdb->replace( $wpdb->postmeta, array(
				'post_id' => $r->ID,
				'meta_key' => 'rplus_top_content_pageviews',
				'meta_value' => '0'
			), array( '%d', '%s', '%d' ) );

			$wpdb->replace( $wpdb->postmeta, array(
				'post_id' => $r->ID,
				'meta_key' => 'rplus_top_content_visits',
				'meta_value' => '0'
			), array( '%d', '%s', '%d' ) );

		}

		/* debug */ if ( $debugoutput ) {
            echo '</table>';
        } /* /debug */

    }

    /**
     * Remove all options of this plugin, to start fresh
     */
    public static function google_reset_options() {


        delete_option( 'rplus_topcontent_options_ga_client_id' );
        delete_option( 'rplus_topcontent_options_ga_client_secret' );
        delete_option( 'rplus_topcontent_options_ga_devkey' );
        delete_option( 'rplus_topcontent_options_ga_propertyid' );
        delete_option( 'rplus_topcontent_options_sync_days' );
        delete_option( 'rplus_topcontent_options_ga_access_token' );
        delete_option( 'rplus_topcontent_options_ga_propertyid' );
        delete_option( 'rplus_topcontent_options_ga_access_code' );

    }

} 