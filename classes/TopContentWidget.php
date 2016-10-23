<?php
/**
 * TopContentWidget class
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */

namespace Required\WP_Top_Content;
use WP_Widget;

/**
 * Class used  to implement the Top Content widget.
 *
 * @since 2.0.0
 *
 * @package Required\WP_Top_Content
 */
class TopContentWidget extends WP_Widget implements WidgetInterface {

	const ID = 'required-top-content-widget';

	/**
	 * Registers the widget.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function register() {
		register_widget( __CLASS__ );
	}

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function __construct() {
		parent::__construct(
			self::ID,
			__( 'r+ Top Content', 'required-wp-top-content' ),
			[
				'classname'  => 'RplusTopContentWidget',
				'description' => __( 'Display a list of top contents based on Google Analytics data.', 'required-wp-top-content' )
			]
		);
	}

	/**
	 * Outputs the settings update form.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Current settings.
	 * @return void
	 */
	public function form( $instance ) {
		// Default values.
		$instance = wp_parse_args( $instance, [
			'title'      => '',
			'count'      => 5,
			'posttypes'  => [ 'post' ],
			'categories' => [],
		] );

		// Back-compat.
		if ( ! is_array( $instance['posttypes'] ) ) {
			$instance['posttypes'] = [ $instance['posttypes'] ];
		}

		?>
		<p>
			<?php
			$field_id = $this->get_field_id( 'title' );
			$field_name = $this->get_field_name( 'title' );
			?>
			<label for="<?php echo esc_attr( $field_id ); ?>">
				<?php _e( 'Title:', 'required-wp-top-content' ); ?>
			</label>
			<input
				class="widefat"
				id="<?php echo esc_attr( $field_id ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>"
				type="text"
				value="<?php echo esc_attr( sanitize_text_field( $instance['title'] ) ); ?>" />
		</p>

		<p>
			<?php
			$field_id = $this->get_field_id( 'count' );
			$field_name = $this->get_field_name( 'count' );
			?>
			<label for="<?php echo esc_attr( $field_id ); ?>">
				<?php _e( 'Number of posts to show:', 'required-wp-top-content' ); ?>
			</label>
			<input
				class="tiny-text"
				id="<?php echo esc_attr( $field_id ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>"
				type="number"
				min="1"
				step="1"
				value="<?php echo esc_attr( $instance['count'] ); ?>"
				size="3" />
		</p>

		<p>
			<?php _e( 'Show contents of this types:', 'required-wp-top-content' ); ?><br>
			<?php
			$field_id = $this->get_field_id( 'posttypes' );
			$field_name = $this->get_field_name( 'posttypes' );
			foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $post_type ) {
				$post_type_labels = get_post_type_labels( $post_type );
				?>
				<label for="<?php echo esc_attr( $field_id . $post_type->name ); ?>">
					<input
						id="<?php echo esc_attr( $field_id . $post_type->name ); ?>"
						name="<?php echo esc_attr( $field_name ); ?>[]"
						type="checkbox"
						value="<?php echo esc_attr( $post_type->name ); ?>"
						<?php checked( in_array( $post_type->name, $instance['posttypes'], true ) ); ?> />
					<?php echo esc_html( $post_type_labels->name ); ?>
				</label><br>
				<?php
			}
			?>
		</p>

		<?php
		$categories = get_terms( 'category' );
		if ( count( $categories ) ) {
			?>
			<p>
				<?php
				$field_id = $this->get_field_id( 'categories' );
				$field_name = $this->get_field_name( 'categories' );
				?>
				<label for="<?php echo esc_attr( $field_id ); ?>">
					<?php _e( 'When showing Posts, you can filter by category (multiple possible):', 'required-wp-top-content' ); ?>
				</label>
				<select
					class="widefat"
					id="<?php echo esc_attr( $field_id ); ?>"
					name="<?php echo esc_attr( $field_name ); ?>[]"
					multiple>
					<?php foreach ( $categories as $category ) : ?>
						<option
							value="<?php echo esc_attr( $category->term_id ); ?>"
							<?php selected( in_array( $category->term_id, $instance['categories'], true ) ); ?>>
							<?php echo esc_html( $category->name . ' (' . $category->slug . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<?php
		}
	}

	/**
	 * Sanitizes and updates an instance of this widget.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance New settings for this instance as input by the user.
	 * @param array $old_instance Old settings for this instance.
	 * @return array Sanitized settings to save.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['count'] = absint( $new_instance['count'] );

		if ( 0 === $instance['count'] ) {
			$instance['count'] = 5;
		}

		if ( is_array( $new_instance['posttypes'] ) ) {
			$instance['posttypes'] = array_filter( $new_instance['posttypes'], 'post_type_exists' );
		} else {
			$instance['posttypes'] = [ 'post' ];
		}

		if ( is_array( $new_instance['categories'] ) ) {
			$instance['categories'] = array_map( 'intval', $new_instance['categories'] );
			$instance['categories'] = array_filter( $instance['categories'], 'category_exists' );
		} else {
			$instance['categories'] = [];
		}

		return $instance;
	}

	/**
	 * Prints the widget content.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for this instance of this widget.
	 */
	public function widget( $args, $instance ) {
		// Default values.
		$instance = wp_parse_args( $instance, [
			'title'      => '',
			'count'      => 5,
			'posttypes'  => [ 'post' ],
			'categories' => [],
		] );

		$title = apply_filters( 'widget_title', $instance['title'] );

		$query = [];
		if ( $instance['categories'] ) {
			$query = [
				'tax_query' => [
					[
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => $instance['categories']
					]
				]
			];
		}

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo apply_filters( 'rplus_wp_top_content_widget_list_start', '<ul class="rplus-top-content">' );

		rplus_wp_top_content( $instance['posttypes'], $instance['count'], 'rplus-wp-top-content-widget.php', $query );

		echo apply_filters( 'rplus_wp_top_content_widget_list_end', '</ul>' );

		echo $args['after_widget'];
	}
}
