<?php
/**
 * TopContentAPI class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content;
use const Required\WP_Top_Content\PLUGIN_DIR as PLUGIN_DIR;
use WP_Query;

/**
 * Class used to provide an API to top content data.
 *
 * @since 2.0.0
 */
class TopContentAPI {

	/**
	 * Retrieves top visited posts/pages based on synced analytics data.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Moved to TopContentAPI.
	 * @access public
	 * @static
	 *
	 * @param array $post_types  Array of post types to get top contents from
	 * @param int   $count       Limit of contents to fetch
	 * @param array $query_args  Optional. Query parameters.
	 * @return array Array of posts.
	 */
	public static function get_top_content( $post_types, $count, $query_args ) {
		$args = apply_filters( 'rplus_wp_top_content_default_args', wp_parse_args( $query_args, [
			'post_type'      => $post_types,
			'nopaging'       => true,
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'orderby'        => 'meta_value_num',
			'meta_type'      => 'NUMERIC',
			'order'          => 'DESC',
			'meta_key'       => 'rplus_top_content_pageviews',
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => 'topcontent_exclude',
					'value'   => 'yes',
					'compare' => '!=',
				],
				[
					'key'     => 'topcontent_exclude',
					'compare' => 'NOT EXISTS',
				],
			],
		] ) );

		$query = new WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Retrieves Top Content item CSS classes.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Moved to TopContentAPI.
	 * @access public
	 * @static
	 *
	 * @param string|array $classes Array or space-separated list of classes
	 *                              to append to the defaults.
	 * @return string CSS classes.
	 */
	public static function item_classes( $classes ) {
		$defaults = apply_filters(
			'rplus_wp_top_content_default_classes',
			[
				'wp-top-content-item'
			]
		);

		if ( ! is_array( $classes ) ) {
			$classes = explode( ' ', $classes );
		}

		$classes = apply_filters(
			'rplus_wp_top_content_classes',
			array_merge( $defaults, $classes )
		);

		$classes = array_map( 'esc_attr', $classes );

		return join( ' ', $classes );
	}


	/**
	 * Renders the template and contents.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Moved to TopContentAPI.
	 * @access public
	 * @static
	 *
	 * @param array  $post_types Array of post types of the top contents.
	 * @param int    $count      The limit to display.
	 * @param string $template   The template to load for each element.
	 * @param array  $query_args Optional. Query arguments for wp_query.
	 */
	public static function render_top_content( $post_types, $count, $template, $query_args = [] ) {
		$top_content = self::get_top_content( $post_types, $count, $query_args );

		if ( $top_content ) {
			foreach ( $top_content as $tc ) {
				self::load_template( $template, $tc );
			}
		}
	}

	/**
	 * Loads a front end template.
	 *
	 * This function loads the specific template file from either your theme or child theme
	 * or falls back on the templates living in the /templates folder of the plugin.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Moved to TopContentAPI.
	 * @access private
	 * @static
	 *
	 * @param string  $template The template to be loaded (filename incl. extension).
	 * @param WP_Post $post     The WP_Post object to be used inside the template.
	 */
	private static function load_template( $template, $post ) {
		// Check if the template file exists in the theme folder.
		if ( $overridden_template = locate_template( $template ) ) {
			// Load the requested template file from the theme or child theme folder.
			$template_path = $overridden_template;
		} else {
			// Load the requested template file from the plugin folder
			$template_path = PLUGIN_DIR . '/templates/'  . $template;
		}

		include $template_path;
	}
}
