<?php
/**
 * Shortcodes for the Forbes staff_picks plugin.
 */

/**
 * A shortcode for listing staff_picks.
 *
 * @wp-hook add_shortcode staff_pick_list
 */
function staff_picks_list_shortcode_handler( $atts, $content = null ) {
  if (is_search()) { return ''; }
  $the_query = staff_picks_query($atts);

  ob_start();
  if ( $the_query->have_posts() ) {
    while ( $the_query->have_posts() ) {
      $the_query->the_post();
      echo staff_picks_display(get_post());
    }
    next_posts_link();
    echo 'here';
    posts_nav_link();
  } else {
    echo 'no staff picks found';
  }
  wp_reset_postdata();

  return ob_get_clean();
}
