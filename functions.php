<?php

if ( ! function_exists( 'rplus_wp_top_content' ) ) :

	/**
	 * Prints Top Content item CSS classes.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $post_types Optional. Array of post types of the top contents. Default 'post' and 'page'.
	 * @param int    $count      Optional. The limit to display. Default 5.
	 * @param string $template   Optional. The template to load for each element. Default 'rplus-wp-top-content.php'
	 * @param array  $query_args Optional. Query arguments for wp_query.
	 */
	function rplus_wp_top_content( $post_types = array( 'post', 'page' ), $count = 5, $template = 'rplus-wp-top-content.php', $query_args = [] ) {
		if ( ! class_exists( '\Required\WP_Top_Content\TopContentAPI' ) ) {
			return;
		}

		\Required\WP_Top_Content\TopContentAPI::render_top_content( $post_types, $count, $template, $query_args );
	}

endif;

if ( ! function_exists( 'rplus_wp_top_content_classes' ) ) :

	/**
	 * Prints Top Content item CSS classes.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $classes Array or space-separated list of classes
	 *                              to append to the defaults.
	 */
	function rplus_wp_top_content_classes( $classes ) {
		if ( ! class_exists( '\Required\WP_Top_Content\TopContentAPI' ) ) {
			return;
		}

		echo \Required\WP_Top_Content\TopContentAPI::item_classes( $classes );
	}

endif;
