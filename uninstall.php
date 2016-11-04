<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   required-wp-top-content
 * @author    Stefan Pasch <stefan@required.ch>
 * @license   GPL-2.0+
 * @link      http://required.ch
 * @copyright 2014 required gmbh
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove existing pageviews for all posts.
delete_post_meta_by_key( 'rplus_top_content_pageviews' );
delete_post_meta_by_key( 'rplus_top_content_visits' );
delete_post_meta_by_key( 'topcontent_exclude' );

// Remove options.
delete_option( 'rplus_topcontent_options_ga_auth_type' );
delete_option( 'rplus_topcontent_options_ga_access_token' );
delete_option( 'rplus_topcontent_options_sync_days' );
delete_option( 'rplus_topcontent_options_ga_propertyid' );
delete_option( 'rplus_topcontent_options_ga_client_id' );
delete_option( 'rplus_topcontent_options_ga_client_secret' );
delete_option( 'rplus_topcontent_options_ga_access_code' );
