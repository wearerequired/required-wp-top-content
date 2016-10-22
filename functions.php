<?php


if ( ! function_exists( 'rplus_wp_top_content' ) ) :

	function rplus_wp_top_content( Array $post_types = array( 'post', 'page' ), $count = 5, $template = 'rplus-wp-top-content.php', $query_args = array() ) {

		if (  ! class_exists( 'RplusWpTopContent' ) )
			wp_die( __( 'Oops, it looks like RplusWpTopContent doesn\'t exist!', 'rpluswptopcontent' ) );

		$wp_top_content = RplusWpTopContent::get_instance();

		$wp_top_content->render_top_content( $post_types, $count, $template, $query_args );

	}

endif;

if ( ! function_exists( 'rplus_wp_top_content_classes' ) ) :

	function rplus_wp_top_content_classes( $classes ) {

		if (  ! class_exists( 'RplusWpTopContent' ) )
			wp_die( __( 'Oops, it looks like RplusWpTopContent doesn\'t exist!', 'rpluswptopcontent' ) );

		$wp_top_content = RplusWpTopContent::get_instance();

		echo $wp_top_content->item_classes( $classes );
	}

endif; // ( ! function_exists( 'rplus_wp_team_list_classes' ) )
