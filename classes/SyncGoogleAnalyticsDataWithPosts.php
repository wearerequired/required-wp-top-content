<?php
/**
 * SyncGoogleAnalyticsDataWithPosts class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content;

/**
 * Class used to sync Google Analytics data with posts.
 *
 * @since 2.0.0
 */
class SyncGoogleAnalyticsDataWithPosts {

	/**
	 * Data to sync.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Synced post IDs.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @var array
	 */
	private $synced_post_ids = [];

	/**
	 * Log of synced data.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @var array
	 */
	private $log = [];

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @param array $data The data to sync.
	 */
	public function __construct( $data ) {
		$this->data = $data;
	}

	/**
	 * Processes the data sync.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return bool True on success, false on failure.
	 */
	public function process() {
		if ( ! $this->data ) {
			return false;
		}

		foreach ( $this->data as $page ) {
			$url = rtrim ( $page[0], '/' );
			$post_id = url_to_postid( $url );

			if ( ! $post_id ) {
				$this->log[] = "No post ID found for $url.";
				continue;
			}

			update_post_meta( $post_id, 'rplus_top_content_pageviews', $page[1] );
			update_post_meta( $post_id, 'rplus_top_content_visits', $page[2] );

			$this->synced_post_ids[] = $post_id;
			$this->log[] = "Found '$post_id' for $url.";
		}

		return true;
	}

	/**
	 * Performs some clean up.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @todo This doesn't scale, is this still required?
	 * @todo Use WP_Query
	 * @todo Use meta API because of meta cache
	 */
	public function cleanup() {
		global $wpdb;

		/**
		 * - Get all post ids that where not updated with google analytics data
		 * - remove the meta key/values
		 * - add meta keys with value 0 (needed cause of some wp_query which will order by that etc.)
		 */
		$results = $wpdb->get_results(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status IN ('publish') AND post_type = 'post' AND ID NOT IN (" . implode( ',', $this->synced_post_ids ) . ")"
		);

		foreach ( $results as $post ) {
			$wpdb->replace(
				$wpdb->postmeta,
				[
					'post_id'    => $post->ID,
					'meta_key'   => 'rplus_top_content_pageviews',
					'meta_value' => 0,
				],
				[
					'%d',
					'%s',
					'%d',
				]
			);

			$wpdb->replace(
				$wpdb->postmeta,
				[
					'post_id'    => $post->ID,
					'meta_key'   => 'rplus_top_content_visits',
					'meta_value' => 0
				],
				[
					'%d',
					'%s',
					'%d',
				]
			);
		}
	}

	/**
	 * Retrieves log entries of the last sync.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return array Log entries.
	 */
	public function get_log() {
		return $this->log;
	}
}
