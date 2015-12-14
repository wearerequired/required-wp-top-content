<?php
/**
 * Google OAuth response page, saves the token, when exists.
 *
 * @package   required-wp-top-content
 * @author    Stefan Pasch <stefan@required.ch>
 * @license   GPL-2.0+
 * @link      http://required.ch
 * @copyright 2014 required gmbh
 */

$path = $_SERVER['DOCUMENT_ROOT'];

include_once $path . '/wp-config.php';
include_once $path . '/wp-load.php';

$code = '';
if ( isset( $_GET['code'] ) ) {

    $code = $_GET['code'];

}

wp_redirect( admin_url( 'options-general.php?page='.str_replace( '/admin', '', plugin_basename( plugin_dir_path( __DIR__ ) ) ) . '&google_oauth_response=' . urlencode( $code ) ) );
exit;
