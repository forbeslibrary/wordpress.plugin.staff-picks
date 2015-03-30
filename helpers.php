<?php
/**
 * Helper functions for the Staff Picks plugin.
 */

/**
 * Returns a simple HTML rendering of the staff_pick.
 */
function staff_picks_display($post) {
  $custom = get_post_custom($post->ID);
  $metadata = maybe_unserialize(
    $custom["staff_pick_metadata"][0]
  );
  
  ob_start();?>
  <article id="post-<?php $post->ID ?>" class="staff_picks post hentry">
  <div class="entry-content">
    <h2>
      <?php the_title(); ?>
      <?php if (!empty($metadata['author'])): ?>
        by <?php echo $metadata['author']; ?>
      <?php endif; ?>    <span class="staff_picks_format">
        [<?php the_terms( $post->ID, 'staff_pick_formats') ?>]
      </span>
    </h2>
    <a href="<?php echo $metadata['catalog_url']; ?>"
      class="wp-caption"
      style="clear: left;float: left;margin-bottom: 1em;margin-right: 1em">
      <?php
      echo get_the_post_thumbnail(
        $post->ID,
        array(100,100),
        array(
          'alt' => 'book-jacket',
          'class' => 'book-jacket'
        ))
        ?>
        <p class="wp-caption-text">view/request in library catalog</p>
    </a>
    <?php echo apply_filters('the_content', $post->post_content); ?>
  </div>
  <p class="staff_picks_byline"><?php the_terms( $post->ID, 'staff_pick_reviewers', 'Reviewed by ') ?></p>
  <p><?php the_terms( $post->ID, 'staff_pick_categories', 'Tagged: ') ?></p>
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
