<?php
/**
 * required+ WP Top Content
 *
 * This template file is used to generate the output for the WP Top Content Widget
 * and can be overwritten in your theme or child theme, if you need different
 * markup.
 *
 * To change the Widget Markup rendered on the frontend, copy this file in your
 * theme folder. The final folder structure would be something like this:
 *
 * /wp-content/themes/<your theme>/rplus-wp-top-content-widget.php
 *
 * The plugin will look in your theme or child theme first and will fallback to
 * the file in the plugins folder.
 *
 * @package   required-wp-top-content
 * @author    Stefan Pasch <stefan@required.ch>
 * @license   GPL-2.0+
 * @link      http://required.ch
 * @copyright 2014 required gmbh
 */
?>
<!-- START: templates/rplus-wp-top-content-widget -->
<li class="<?php rplus_wp_top_content_classes( array( 'post-' . $post->ID ) ); ?>">

    <a href="<?php echo get_permalink( $post->ID ); ?>">
        <?php echo get_the_title( $post->ID ); ?>
    </a>

</li>
<!-- END: templates/rplus-wp-top-content-widget -->
