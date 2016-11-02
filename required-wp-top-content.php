<?php
/**
 * required+ WordPress Top Content
 *
 * A Plugin to get top contents of your posts and pages.
 * Will snychronize pageviews & visits with your Google Analytics account in a defined date range.
 *
 * @package   required-wp-top-content
 * @author    Stefan Pasch <stefan@required.ch>
 * @license   GPL-2.0+
 * @link      http://required.ch
 * @copyright 2014 required gmbh
 *
 * @wordpress-plugin
 * Plugin Name:       required+ WP Top Content
 * Plugin URI:        https://github.com/wearerequired/required-wp-top-content
 * Description:       Sync google anaytics data with your posts and pages to get top contents
 * Version:           1.0.0
 * Author:            required+
 * Author URI:        http://required.ch
 * Text Domain:       rpluswptopcontent
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/wearerequired/required-wp-top-content
 * GitHub Branch:     master
 */

namespace Required\WP_Top_Content;

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}

require __DIR__ . '/vendor/autoload.php';

define( __NAMESPACE__ . '\PLUGIN_FILE', __FILE__ );
define( __NAMESPACE__ . '\PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

register_activation_hook( __FILE__, [ Plugin::class, 'activated' ] );
register_deactivation_hook( __FILE__, [ Plugin::class, 'deactivated' ] );

Plugin::get_instance();

include __DIR__ . '/functions.php';
