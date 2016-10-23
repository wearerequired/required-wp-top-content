<?php
/**
 * PageViewsPostsListTableColumn class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content\Admin
 */

namespace Required\WP_Top_Content\Admin;
use WP_Query;

/**
 * Class used to add a pageviews / visits column to the posts list table.
 *
 * @since 2.0.0
 */
class PageViewsPostsListTableColumn {

	const COLUMN_NAME = 'rplustopcontent';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function __construct() {
	}

	/**
	 * Registers the column for all public posts types.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function register() {
		foreach ( get_post_types( [ 'public' => true ] ) as $post_type ) {
			add_filter( "manage_edit-{$post_type}_columns", [ $this, 'add_column' ] );
			add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'column_output' ], 10, 2 );
			add_filter( "manage_edit-{$post_type}_sortable_columns", [ $this, 'add_sortable_column' ] );
		}

		// Filter posts for custom column sorting.
		add_action( 'pre_get_posts', [ $this, 'filter_column_order_query' ] );
		add_filter( 'get_meta_sql', [ $this, 'filter_column_order_sql' ], 10, 6 );
	}

	/**
	 * Adds the column header for pageviews / visits.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param array $columns An array of column headers.
	 * @return array An array of column headers.
	 */
	public function add_column( $columns ) {
		// TODO: Hide if no token exists.
		$columns[ self::COLUMN_NAME ] = sprintf(
			__( 'Pageviews / Visits (last %d days)', 'required-wp-top-content' ),
			get_option( 'rplus_topcontent_options_sync_days' )
		);

		return $columns;
	}

	/**
	 * Adds the column to the list of sortable columns.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param array $sortable_columns An array of sortable columns.
	 * @return array An array of sortable columns.
	 */
	public function add_sortable_column( $sortable_columns ) {
		$sortable_columns[ self::COLUMN_NAME ] = self::COLUMN_NAME;

		return $sortable_columns;
	}

	/**
	 * Prints the output for the pageviews / visits column.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id     The current post ID.
	 */
	public function column_output( $column_name, $post_id ) {
		if ( self::COLUMN_NAME !== $column_name ) {
			return;
		}

		$pageviews = get_post_meta( $post_id, 'rplus_top_content_pageviews', true );
		$visits    = get_post_meta( $post_id, 'rplus_top_content_visits', true );

		if ( ! $pageviews ) {
			$pageviews = '-';
		}

		if ( ! $visits ) {
			$visits = '-';
		}

		printf(
			'%s / %s',
			is_numeric( $pageviews ) ? number_format_i18n( $pageviews ) : $pageviews,
			is_numeric( $visits ) ? number_format_i18n( $visits ) : $visits
		);
	}

	/**
	 * Sets `meta_key` and `orderby` when data is sorted by pageviews.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param \WP_Query $query The WP_Query instance.
	 */
	public function filter_column_order_query( $query ) {
		if ( ! $query->is_admin ) {
			return;
		}

		if ( self::COLUMN_NAME === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', 'rplus_top_content_pageviews' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Filters the SQL clauses for the column sorting to include posts
	 * without any ratings.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param array  $clauses           Array containing the query's JOIN and WHERE clauses.
	 * @param array  $queries           Array of meta queries.
	 * @param string $type              Type of meta.
	 * @param string $primary_table     Primary table.
	 * @param string $primary_id_column Primary column ID.
	 * @param object $context           The main query object.
	 * @return array Array containing the query's JOIN and WHERE clauses.
	 */
	public function filter_column_order_sql( $clauses, $queries, $type, $primary_table, $primary_id_column, $context ) {
		if ( ! $context instanceof WP_Query ) {
			return;
		}

		if ( 'rplus_top_content_pageviews' === $context->get( 'meta_key' ) && 'meta_value_num' === $context->get( 'orderby' ) ) {
			// Left join so empty values will be returned as well.
			$clauses['join']  = str_replace( 'INNER JOIN', 'LEFT JOIN', $clauses['join'] ) . $clauses['where'];
			$clauses['where'] = '';
		}

		return $clauses;
	}
}
