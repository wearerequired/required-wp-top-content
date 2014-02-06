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

        // TODO: update widget-name-id, classname and description
        // TODO: replace 'widget-name-locale' to be named more plugin specific. Other instances exist throughout the code, too.
        parent::__construct(
            'required-top-content-widget',
            __( 'r+ Top Content', 'required-wp-top-content' ),
            array(
                'classname'  => 'RplusTopContentWidget',
                'description' => __( 'Display a list of top contents based on Google Analytics data.', 'required-wp-top-content' )
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
            'posttypes' => ''
        ) );

        $this->_form_add_input('title', __('Title', 'required-wp-top-content'), $instance['title']);
        $this->_form_add_input('count', __('Show Top x Contents', 'required-wp-top-content'), $instance['count'], 'small');

        ?>
        <p>
            <?php _e( 'Show contents of this types', 'required-wp-top-content' ); ?><br>
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

        echo $args['before_widget'];

        if ( ! empty( $title ) )
            echo $args['before_title'] . $title . $args['after_title'];

        // query defined post types with synced analytics data.
        $the_query = new WP_Query( array(
            'post_type' => $instance['posttypes'],
            'nopaging' => true,
            'post_status' => 'publish',
            'posts_per_page' => $instance['count'],
            'orderby' => 'meta_value',
            'order' => 'DESC',
            'meta_key' => 'rplus_top_content_pageviews',
            'meta_query' => array(
                array(
                    'key' => 'rplus_top_content_pageviews',
                    'value' => 0,
                    'compare' => '>'
                )
            )
        ) );

        if ( $the_query->have_posts() ) {

            echo '<ul class="required-wp-top-content">';

            while ( $the_query->have_posts() ) { ?>
                <?php $the_query->the_post(); ?>
                <li>
                    <a href="<?php the_permalink(); ?>">
                        <?php the_title(); ?>
                    </a>
                </li>

            <?php }

            echo '</ul>';

        } else {

            echo '<p>'.__('No top contents.', 'required-wp-top-content').'</p>';

        }

        echo $args['after_widget'];

        wp_reset_query();
    }

} // end class

add_action( 'widgets_init', create_function( '', 'register_widget("RplusTopContentWidget");' ) );