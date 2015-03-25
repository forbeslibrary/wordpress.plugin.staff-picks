<?php
/**
 * Helper functions for the Staff Picks plugin.
 */

/**
 * Returns the generated content.
 */
function staff_picks_generated_content($post) {
  $custom = get_post_custom($post->ID);
  $metadata = maybe_unserialize(
    $custom["staff_pick_metadata"][0]
  );

  ob_start();
  ?>
  <a href="<?php echo $metadata['catalog_url']; ?>"
    style="clear: left;float: left;margin-bottom: 1em;margin-right: 1em">
    <?php
    echo get_the_post_thumbnail(
      $post->ID,
      'medium',
      array(
        'alt' => 'book-jacket',
        'class' => 'book-jacket'
      ))
      ?>
  </a>
  <p><strong>
    <a href="<?php echo $metadata['catalog_url']; ?>">
      <?php echo get_the_title(); ?>
    </a>
    <?php if (!empty($metadata['author'])): ?>
      by <?php echo $metadata['author']; ?>
    <?php endif; ?>
  </strong></p>
  <?php
  return ob_get_clean();
}

/**
 * Returns a simple HTML rendering of the staff_pick.
 */
function staff_picks_display($post) {
  ob_start();?>
  <article id="post-<?php the_ID(); ?>" class="staff_picks post hentry">
  <h2><?php the_title(); ?></h2>
  <div class="entry-content">
    <?php echo staff_picks_generated_content($post); ?>
    <?php echo apply_filters('the_content', $post->post_content); ?>
  </div>
  <?php if (is_user_logged_in()): ?>
    <footer class="entry-utility"><span class="edit-link"><?php edit_post_link('Edit Staff Pick'); ?></span></footer>
  <?php endif; ?>
  </article><?php
  return ob_get_clean();
}

/**
 * Returns a wp_query object for the passed shortcode attributes.
 */
function staff_picks_query($atts) {
  extract( shortcode_atts( array(
    'staff_pick_audience' => null,
  ), $atts ) );

  $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

  $query_args = array(
    'post_type' => 'staff_picks',
    'orderby' => 'title',
    'order' => 'ASC',
    'paged' => $paged,
    );

  if ($staff_pick_audience) {
    $query_args['tax_query'] = array( array('taxonomy' => 'staff-pick-category', 'field'=>'slug', 'include_children'=>FALSE, 'terms' => $staff_pick_category) );
  }

  $the_query = new WP_Query( $query_args );

  return $the_query;
}
