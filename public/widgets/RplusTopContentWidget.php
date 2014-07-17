<?php
/**
 * required WP Top Content Widget
 *
 * @package   required-wp-top-content
 * @author    Stefan Pasch <stefan@required.ch>
 * @license   GPL-2.0+
 * @link      http://required.ch
 * @copyright 2014 required gmbh
 */

/**
 * @package required-wp-top-content
 * @author  Stefan Pasch <stefan@required.ch>
 */
class RplusTopContentWidget extends WP_Widget {

    /**
     * Specifies the classname and description, instantiates the widget,
     * loads localization files
     */
    public function __construct() {

        parent::__construct(
            'required-top-content-widget',
            __( 'r+ Top Content', 'rpluswptopcontent' ),
            array(
                'classname'  => 'RplusTopContentWidget',
                'description' => __( 'Display a list of top contents based on Google Analytics data.', 'rpluswptopcontent' )
            )
        );

    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     * @return void
     */
    public function form( $instance ) {

        $instance = wp_parse_args( (array) $instance, array(
            'title' => '',
            'count' => '',
            'posttypes' => '',
			'categories' => array()
        ) );

        $this->_form_add_input('title', __('Title', 'rpluswptopcontent'), $instance['title']);
        $this->_form_add_input('count', __('Show Top x Contents', 'rpluswptopcontent'), $instance['count'], 'small');

        ?>
        <p>
            <?php _e( 'Show contents of this types', 'rpluswptopcontent' ); ?><br>
            <?php
            foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
                $post_type_labels = get_post_type_labels( get_post_type_object( $post_type ) );
                ?>
                <label for="<?php echo $this->get_field_id('posttypes').$post_type; ?>">
                    <input name="<?php echo $this->get_field_name('posttypes'); ?>[]" type="checkbox" id="<?php echo $this->get_field_id('posttypes').$post_type; ?>" value="<?php echo $post_type; ?>" <?php echo ( is_array( $instance['posttypes'] ) && in_array($post_type, $instance['posttypes']) ) ? 'checked="checked"' : ''; ?>>
                    <?php echo $post_type_labels->name; ?>
                </label><br>
            <?php
            }
            ?>
        </p>
		<?php
		$categories = get_terms( 'category' );
		if ( count( $categories ) ) :
		?>
		<p>
			<?php _e( 'When showing Posts, you can filter by category (multiple possible).', 'rpluswptopcontent' ); ?>
			<select class="widefat" name="<?php echo $this->get_field_name('categories'); ?>[]" multiple>
				<?php foreach ( $categories as $c ) : ?>
					<option value="<?php echo $c->term_id; ?>" <?php if ( in_array( $c->term_id, $instance['categories'] ) ) : ?>selected="selected"<?php endif; ?>><?php echo $c->name . ' (' . $c->slug . ')'; ?></option>
				<?php endforeach; ?>
			</select>
		</p>
    <?php endif;

    }

    /**
     * Helper to create a form input field
     *
     * @param $field
     * @param $label
     * @param $val
     * @param string $type
     */
    private function _form_add_input($field, $label, $val, $type = 'widefat') {

        ?>
        <p>
            <label for="<?php echo $this->get_field_id($field); ?>">
                <?php echo $label . ':'; ?>
            </label>

            <?php if ( 'widefat' == $type ) : ?>

                <input class="widefat" id="<?php echo $this->get_field_id($field); ?>" name="<?php echo $this->get_field_name($field); ?>" type="text" value="<?php echo esc_attr($val); ?>" />

            <?php else : ?>

                <input size="3" id="<?php echo $this->get_field_id($field); ?>" name="<?php echo $this->get_field_name($field); ?>" type="text" value="<?php echo esc_attr($val); ?>" />

            <?php endif; ?>

        </p>
    <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance) {

        $instance = $old_instance;

        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['count'] = ( ! empty( $new_instance['count'] ) && is_numeric( $new_instance['count'] ) ) ? $new_instance['count'] : '';

        $instance['posttypes'] = ( is_array( $new_instance['posttypes'] ) ) ? $new_instance['posttypes'] : array();
		$instance['categories'] = ( is_array( $new_instance['categories'] ) ) ? $new_instance['categories'] : array();

        return $instance;
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {

        // set some default values
        $instance = wp_parse_args( (array) $instance, array(
            'count' => '5',
            'posttypes' => 'post'
        ) );

        $title = apply_filters( 'widget_title', $instance['title'] );

		$query = array();
		if ( isset( $instance['categories'] ) && count( $instance['categories'] ) ) {

			$query = array(
				'tax_query' => array(
					array(
						'taxonomy' => 'category',
						'field' => 'id',
						'terms' => $instance['categories']
					)
				)
			);

		}

        echo $args['before_widget'];

        if ( ! empty( $title ) )
            echo $args['before_title'] . $title . $args['after_title'];

        echo apply_filters( 'rplus_wp_top_content_widget_list_start', '<ul class="rplus-top-content">' );

        rplus_wp_top_content( $instance['posttypes'], $instance['count'], 'rplus-wp-top-content-widget.php', $query );

        echo apply_filters( 'rplus_wp_top_content_widget_list_end', '</ul>' );

        echo $args['after_widget'];

    }

} // end class

add_action( 'widgets_init', create_function( '', 'register_widget("RplusTopContentWidget");' ) );